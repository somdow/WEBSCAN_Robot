<x-app-layout>
	<x-slot name="header">
		<h1 class="text-[2.5rem] font-bold leading-tight tracking-tight text-text-primary">Acceptable Use Policy</h1>
		<p class="mt-1 text-sm text-text-secondary">Last updated: {{ now()->format("F j, Y") }}</p>
	</x-slot>

	<div class="mx-auto max-w-3xl">
		<div class="rounded-xl border border-border bg-surface p-8 shadow-card prose-container">

			<h2 class="text-lg font-semibold text-text-primary">1. Purpose</h2>
			<p class="mt-2 text-sm leading-relaxed text-text-secondary">
				This Acceptable Use Policy outlines the rules and guidelines for using {{ $siteName }}. By using the Service, you agree to comply with this policy in addition to our <a href="{{ route("legal.terms") }}" class="font-medium text-accent hover:text-accent-hover">Terms of Service</a>.
			</p>

			<h2 class="mt-8 text-lg font-semibold text-text-primary">2. Authorized Scanning</h2>
			<p class="mt-2 text-sm leading-relaxed text-text-secondary">
				You may only scan websites that you own, operate, or have explicit written authorization to analyze. Unauthorized scanning of third-party websites is strictly prohibited and may result in immediate account termination.
			</p>

			<h2 class="mt-8 text-lg font-semibold text-text-primary">3. Prohibited Activities</h2>
			<p class="mt-2 text-sm leading-relaxed text-text-secondary">
				The following activities are prohibited when using the Service:
			</p>
			<ul class="mt-2 ml-6 list-disc space-y-1 text-sm text-text-secondary">
				<li>Scanning websites without the owner's permission</li>
				<li>Attempting to overwhelm, crash, or degrade target website servers</li>
				<li>Using the Service to identify vulnerabilities for malicious purposes</li>
				<li>Circumventing plan limits through multiple accounts or automation</li>
				<li>Reselling or redistributing the Service without authorization</li>
				<li>Uploading malicious content or attempting to compromise the platform</li>
				<li>Using the Service in violation of any applicable laws or regulations</li>
			</ul>

			<h2 class="mt-8 text-lg font-semibold text-text-primary">4. Rate Limits &amp; Fair Use</h2>
			<p class="mt-2 text-sm leading-relaxed text-text-secondary">
				Each subscription plan includes defined limits for projects and scans per month. These limits exist to ensure fair access for all users and to prevent abuse. Attempting to circumvent these limits is a violation of this policy.
			</p>

			<h2 class="mt-8 text-lg font-semibold text-text-primary">5. AI Feature Usage</h2>
			<p class="mt-2 text-sm leading-relaxed text-text-secondary">
				AI-powered features use third-party providers (OpenAI, Anthropic, Google). You agree not to use AI features to generate harmful, misleading, or illegal content. AI recommendations are suggestions only and should be reviewed before implementation.
			</p>

			<h2 class="mt-8 text-lg font-semibold text-text-primary">6. Reporting Violations</h2>
			<p class="mt-2 text-sm leading-relaxed text-text-secondary">
				If you become aware of any violations of this policy, please report them to <a href="mailto:{{ $supportEmail }}" class="font-medium text-accent hover:text-accent-hover">{{ $supportEmail }}</a>. We take all reports seriously and will investigate promptly.
			</p>

			<h2 class="mt-8 text-lg font-semibold text-text-primary">7. Enforcement</h2>
			<p class="mt-2 text-sm leading-relaxed text-text-secondary">
				Violations of this policy may result in warnings, temporary suspension, or permanent termination of your account, at our sole discretion. Serious violations may be reported to law enforcement authorities.
			</p>

		</div>

		<x-footer-links />
	</div>
</x-app-layout>
