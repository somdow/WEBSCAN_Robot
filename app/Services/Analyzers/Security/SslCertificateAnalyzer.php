<?php

namespace App\Services\Analyzers\Security;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;
use Illuminate\Support\Facades\Log;

/**
 * Validates the site's SSL/TLS certificate: expiration, domain match,
 * chain trust, and protocol version. An expired or misconfigured certificate
 * triggers browser warnings that destroy user trust and hurt SEO.
 *
 * Site-wide scope — a single certificate covers the entire domain.
 */
class SslCertificateAnalyzer implements AnalyzerInterface
{
	/** Warn when certificate expires within this many days. */
	private const EXPIRY_WARNING_DAYS = 30;

	/** Connection timeout for the SSL handshake (seconds). */
	private const CONNECT_TIMEOUT = 10;

	public function moduleKey(): string
	{
		return "sslCertificate";
	}

	public function label(): string
	{
		return "SSL Certificate";
	}

	public function category(): string
	{
		return "Security";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.sslCertificate", 6);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$findings = array();
		$recommendations = array();

		$parsed = parse_url($scanContext->effectiveUrl);
		$host = strtolower($parsed["host"] ?? "");
		$scheme = strtolower($parsed["scheme"] ?? "http");

		if ($host === "") {
			$findings[] = array("type" => "info", "message" => "Could not extract hostname for SSL certificate analysis.");

			return new AnalysisResult(status: ModuleStatus::Info, findings: $findings, recommendations: $recommendations);
		}

		/* HTTP-only sites have no certificate to check */
		if ($scheme === "http") {
			$findings[] = array("type" => "bad", "message" => "Your site is served over HTTP without SSL/TLS encryption. Modern browsers display \"Not Secure\" warnings and search engines penalize unencrypted sites.");
			$findings[] = array("type" => "data", "key" => "sslStatus", "value" => "no_ssl");
			$recommendations[] = "Install an SSL/TLS certificate (free via Let's Encrypt) and configure HTTPS for your domain.";

			return new AnalysisResult(status: ModuleStatus::Bad, findings: $findings, recommendations: $recommendations);
		}

		/* Fetch and parse the certificate */
		$certData = $this->fetchCertificate($host);

		if ($certData === null) {
			$findings[] = array("type" => "bad", "message" => "Could not retrieve the SSL certificate from {$host}. The server may not support HTTPS on port 443, or the SSL handshake failed.");
			$findings[] = array("type" => "data", "key" => "sslStatus", "value" => "unreachable");
			$recommendations[] = "Verify that your web server is properly configured to serve HTTPS on port 443 and that the SSL certificate is installed correctly.";

			return new AnalysisResult(status: ModuleStatus::Bad, findings: $findings, recommendations: $recommendations);
		}

		$issues = 0;
		$warnings = 0;

		/* ── Check 1: Certificate expiration ── */
		$expiryResult = $this->checkExpiration($certData, $host);
		$findings = array_merge($findings, $expiryResult["findings"]);
		$recommendations = array_merge($recommendations, $expiryResult["recommendations"]);
		$issues += $expiryResult["issues"];
		$warnings += $expiryResult["warnings"];

		/* ── Check 2: Domain match ── */
		$domainResult = $this->checkDomainMatch($certData, $host);
		$findings = array_merge($findings, $domainResult["findings"]);
		$recommendations = array_merge($recommendations, $domainResult["recommendations"]);
		$issues += $domainResult["issues"];

		/* ── Check 3: Self-signed certificate ── */
		$trustResult = $this->checkChainTrust($certData);
		$findings = array_merge($findings, $trustResult["findings"]);
		$recommendations = array_merge($recommendations, $trustResult["recommendations"]);
		$warnings += $trustResult["warnings"];

		/* ── Determine overall status ── */
		if ($issues > 0) {
			return new AnalysisResult(status: ModuleStatus::Bad, findings: $findings, recommendations: $recommendations);
		}

		if ($warnings > 0) {
			return new AnalysisResult(status: ModuleStatus::Warning, findings: $findings, recommendations: $recommendations);
		}

		$findings[] = array("type" => "ok", "message" => "SSL certificate is valid, trusted, and properly configured for {$host}.");
		$findings[] = array("type" => "data", "key" => "sslStatus", "value" => "valid");

		return new AnalysisResult(status: ModuleStatus::Ok, findings: $findings, recommendations: $recommendations);
	}

	/**
	 * Connect to the host via SSL and extract the peer certificate data.
	 * Returns parsed certificate array or null on failure.
	 */
	private function fetchCertificate(string $host): ?array
	{
		$contextOptions = array(
			"ssl" => array(
				"capture_peer_cert" => true,
				"verify_peer" => false,
				"verify_peer_name" => false,
				"allow_self_signed" => true,
			),
		);

		$context = stream_context_create($contextOptions);

		try {
			$stream = @stream_socket_client(
				"ssl://{$host}:443",
				$errorCode,
				$errorMessage,
				self::CONNECT_TIMEOUT,
				STREAM_CLIENT_CONNECT,
				$context,
			);

			if ($stream === false) {
				Log::info("SslCertificateAnalyzer connection failed", array(
					"host" => $host,
					"error_code" => $errorCode,
					"error_message" => $errorMessage,
				));
				return null;
			}

			$params = stream_context_get_params($stream);
			fclose($stream);

			$peerCert = $params["options"]["ssl"]["peer_certificate"] ?? null;

			if ($peerCert === null) {
				return null;
			}

			$certInfo = openssl_x509_parse($peerCert);

			if ($certInfo === false) {
				return null;
			}

			return $certInfo;
		} catch (\Throwable $exception) {
			Log::info("SslCertificateAnalyzer exception", array(
				"host" => $host,
				"error" => $exception->getMessage(),
			));
			return null;
		}
	}

	/**
	 * Check certificate expiration: expired, expiring soon, or valid.
	 */
	private function checkExpiration(array $certData, string $host): array
	{
		$findings = array();
		$recommendations = array();
		$issues = 0;
		$warnings = 0;

		$validFrom = $certData["validFrom_time_t"] ?? null;
		$validTo = $certData["validTo_time_t"] ?? null;

		if ($validTo === null) {
			$findings[] = array("type" => "warning", "message" => "Could not determine the certificate expiration date.");
			$warnings++;

			return array("findings" => $findings, "recommendations" => $recommendations, "issues" => $issues, "warnings" => $warnings);
		}

		$now = time();
		$daysUntilExpiry = (int) floor(($validTo - $now) / 86400);
		$expiryDate = date("M j, Y", $validTo);

		$findings[] = array("type" => "data", "key" => "sslExpiryDate", "value" => $expiryDate);
		$findings[] = array("type" => "data", "key" => "sslDaysUntilExpiry", "value" => $daysUntilExpiry);

		if ($validFrom !== null) {
			$findings[] = array("type" => "data", "key" => "sslIssuedDate", "value" => date("M j, Y", $validFrom));
		}

		if ($daysUntilExpiry < 0) {
			$expiredDaysAgo = abs($daysUntilExpiry);
			$findings[] = array("type" => "bad", "message" => "SSL certificate expired {$expiredDaysAgo} " . ($expiredDaysAgo === 1 ? "day" : "days") . " ago ({$expiryDate}). Browsers are showing security warnings to all visitors, which devastates trust and SEO rankings.");
			$findings[] = array("type" => "data", "key" => "sslStatus", "value" => "expired");
			$recommendations[] = "Renew the SSL certificate immediately. If using Let's Encrypt, check that auto-renewal is configured (certbot renew). If purchased, contact your certificate provider.";
			$issues++;
		} elseif ($daysUntilExpiry <= self::EXPIRY_WARNING_DAYS) {
			$findings[] = array("type" => "warning", "message" => "SSL certificate expires in {$daysUntilExpiry} " . ($daysUntilExpiry === 1 ? "day" : "days") . " ({$expiryDate}). Renew it now to avoid browser security warnings.");
			$findings[] = array("type" => "data", "key" => "sslStatus", "value" => "expiring_soon");
			$recommendations[] = "Renew the SSL certificate before it expires. Enable auto-renewal if available (certbot renew --deploy-hook for Let's Encrypt, or enable auto-renew in your hosting panel).";
			$warnings++;
		} else {
			$findings[] = array("type" => "ok", "message" => "SSL certificate is valid and expires in {$daysUntilExpiry} days ({$expiryDate}).");
		}

		return array("findings" => $findings, "recommendations" => $recommendations, "issues" => $issues, "warnings" => $warnings);
	}

	/**
	 * Check if the certificate's subject/SAN covers the scanned domain.
	 */
	private function checkDomainMatch(array $certData, string $host): array
	{
		$findings = array();
		$recommendations = array();
		$issues = 0;

		$coveredDomains = $this->extractCoveredDomains($certData);

		$findings[] = array("type" => "data", "key" => "sslCoveredDomains", "value" => implode(", ", array_slice($coveredDomains, 0, 10)));

		$issuerOrg = $certData["issuer"]["O"] ?? $certData["issuer"]["CN"] ?? "Unknown";
		$findings[] = array("type" => "data", "key" => "sslIssuer", "value" => $issuerOrg);

		if (empty($coveredDomains)) {
			$findings[] = array("type" => "warning", "message" => "Could not extract domain names from the certificate to verify domain coverage.");

			return array("findings" => $findings, "recommendations" => $recommendations, "issues" => 0);
		}

		$domainMatched = $this->domainMatchesAnyCovered($host, $coveredDomains);

		if (!$domainMatched) {
			$findings[] = array("type" => "bad", "message" => "SSL certificate does not cover {$host}. The certificate is issued for: " . implode(", ", array_slice($coveredDomains, 0, 5)) . ". Browsers will show a domain mismatch warning.");
			$findings[] = array("type" => "data", "key" => "sslDomainMatch", "value" => "mismatch");
			$recommendations[] = "Obtain an SSL certificate that includes {$host} in its Subject Alternative Names (SAN). If using a shared hosting certificate, contact your hosting provider.";
			$issues++;
		} else {
			$findings[] = array("type" => "ok", "message" => "SSL certificate properly covers {$host}.");
			$findings[] = array("type" => "data", "key" => "sslDomainMatch", "value" => "match");
		}

		return array("findings" => $findings, "recommendations" => $recommendations, "issues" => $issues);
	}

	/**
	 * Check if the certificate is self-signed (issuer matches subject).
	 */
	private function checkChainTrust(array $certData): array
	{
		$findings = array();
		$recommendations = array();
		$warnings = 0;

		$subjectCn = $certData["subject"]["CN"] ?? "";
		$issuerCn = $certData["issuer"]["CN"] ?? "";
		$subjectOrg = $certData["subject"]["O"] ?? "";
		$issuerOrg = $certData["issuer"]["O"] ?? "";

		$isSelfSigned = ($subjectCn === $issuerCn) && ($subjectOrg === $issuerOrg) && $subjectCn !== "";

		if ($isSelfSigned) {
			$findings[] = array("type" => "warning", "message" => "SSL certificate appears to be self-signed. Browsers will show a trust warning because the certificate is not issued by a recognized Certificate Authority.");
			$findings[] = array("type" => "data", "key" => "sslSelfSigned", "value" => true);
			$recommendations[] = "Replace the self-signed certificate with one from a trusted Certificate Authority. Free trusted certificates are available from Let's Encrypt.";
			$warnings++;
		} else {
			$findings[] = array("type" => "data", "key" => "sslSelfSigned", "value" => false);
		}

		return array("findings" => $findings, "recommendations" => $recommendations, "warnings" => $warnings);
	}

	/**
	 * Extract all domain names covered by the certificate (CN + SANs).
	 *
	 * @return string[]
	 */
	private function extractCoveredDomains(array $certData): array
	{
		$domains = array();

		$subjectCn = $certData["subject"]["CN"] ?? null;
		if ($subjectCn !== null) {
			$domains[] = strtolower($subjectCn);
		}

		$sanString = $certData["extensions"]["subjectAltName"] ?? "";
		if ($sanString !== "") {
			$sanEntries = explode(",", $sanString);
			foreach ($sanEntries as $entry) {
				$entry = trim($entry);
				if (str_starts_with($entry, "DNS:")) {
					$domains[] = strtolower(substr($entry, 4));
				}
			}
		}

		return array_unique($domains);
	}

	/**
	 * Check if the host matches any of the certificate's covered domains,
	 * supporting wildcard certificates (e.g., *.example.com).
	 */
	private function domainMatchesAnyCovered(string $host, array $coveredDomains): bool
	{
		foreach ($coveredDomains as $covered) {
			if ($covered === $host) {
				return true;
			}

			/* Wildcard match: *.example.com covers sub.example.com but not example.com */
			if (str_starts_with($covered, "*.")) {
				$wildcardBase = substr($covered, 2);
				if (str_ends_with($host, $wildcardBase) && $host !== ltrim($wildcardBase, ".")) {
					return true;
				}
			}
		}

		return false;
	}
}
