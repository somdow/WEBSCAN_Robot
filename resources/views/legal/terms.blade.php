<x-app-layout>
	<x-slot name="header">
		<h1 class="text-[2.5rem] font-bold leading-tight tracking-tight text-text-primary">Terms of Service</h1>
		<p class="mt-1 text-sm text-text-secondary">Last updated: {{ now()->format("F j, Y") }}</p>
	</x-slot>

	<div class="mx-auto max-w-3xl">
		<div class="rounded-xl border border-border bg-surface p-8 shadow-card prose-container">

			<h2 class="text-lg font-semibold text-text-primary">1. Acceptance of Terms</h2>
			<p class="mt-2 text-sm leading-relaxed text-text-secondary">
				By accessing or using {{ $siteName }} ("Service"), you agree to be bound by these Terms of Service. If you do not agree to these terms, do not use the Service.
			</p>

			<h2 class="mt-8 text-lg font-semibold text-text-primary">2. Description of Service</h2>
			<p class="mt-2 text-sm leading-relaxed text-text-secondary">
				{{ $siteName }} is a web-based search engine optimization analysis platform that scans websites and provides actionable recommendations. The Service includes automated SEO audits, AI-powered optimization suggestions, PDF report generation, and related tools.
			</p>

			<h2 class="mt-8 text-lg font-semibold text-text-primary">3. Account Registration</h2>
			<p class="mt-2 text-sm leading-relaxed text-text-secondary">
				To use the Service, you must create an account with a valid email address and password. You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account. You must notify us immediately of any unauthorized use.
			</p>

			<h2 class="mt-8 text-lg font-semibold text-text-primary">4. Subscription Plans &amp; Billing</h2>
			<p class="mt-2 text-sm leading-relaxed text-text-secondary">
				The Service offers free and paid subscription plans. Paid plans are billed monthly or annually through Stripe. You may upgrade, downgrade, or cancel your subscription at any time through your billing portal. Refunds are handled on a case-by-case basis.
			</p>

			<h2 class="mt-8 text-lg font-semibold text-text-primary">5. Permitted Use</h2>
			<p class="mt-2 text-sm leading-relaxed text-text-secondary">
				You may only scan websites that you own or have explicit authorization to analyze. You agree not to use the Service to scan websites without permission, attempt to overload target servers, or engage in any activity that violates applicable laws.
			</p>

			<h2 class="mt-8 text-lg font-semibold text-text-primary">6. Intellectual Property</h2>
			<p class="mt-2 text-sm leading-relaxed text-text-secondary">
				All content, features, and functionality of the Service are owned by {{ $siteName }} and are protected by copyright, trademark, and other intellectual property laws. You retain ownership of your data and scan results.
			</p>

			<h2 class="mt-8 text-lg font-semibold text-text-primary">7. Data &amp; Privacy</h2>
			<p class="mt-2 text-sm leading-relaxed text-text-secondary">
				Your use of the Service is also governed by our <a href="{{ route("legal.privacy") }}" class="font-medium text-accent hover:text-accent-hover">Privacy Policy</a>. We collect and process data as described therein.
			</p>

			<h2 class="mt-8 text-lg font-semibold text-text-primary">8. Limitation of Liability</h2>
			<p class="mt-2 text-sm leading-relaxed text-text-secondary">
				The Service is provided "as is" without warranties of any kind. We do not guarantee that SEO recommendations will improve your search rankings. In no event shall {{ $siteName }} be liable for any indirect, incidental, special, or consequential damages arising from your use of the Service.
			</p>

			<h2 class="mt-8 text-lg font-semibold text-text-primary">9. Termination</h2>
			<p class="mt-2 text-sm leading-relaxed text-text-secondary">
				We may suspend or deactivate your account at any time for violations of these Terms or the Acceptable Use Policy. You may deactivate your account at any time through your profile settings. To request permanent data deletion, contact us at <a href="mailto:{{ $supportEmail }}" class="font-medium text-accent hover:text-accent-hover">{{ $supportEmail }}</a>.
			</p>

			<h2 class="mt-8 text-lg font-semibold text-text-primary">10. Changes to Terms</h2>
			<p class="mt-2 text-sm leading-relaxed text-text-secondary">
				We reserve the right to modify these Terms at any time. Material changes will be communicated via email or in-app notification. Continued use of the Service after changes constitutes acceptance of the updated Terms.
			</p>

			<h2 class="mt-8 text-lg font-semibold text-text-primary">11. Contact</h2>
			<p class="mt-2 text-sm leading-relaxed text-text-secondary">
				For questions about these Terms, contact us at <a href="mailto:{{ $supportEmail }}" class="font-medium text-accent hover:text-accent-hover">{{ $supportEmail }}</a>.
			</p>

		</div>

		<x-footer-links />
	</div>
</x-app-layout>
