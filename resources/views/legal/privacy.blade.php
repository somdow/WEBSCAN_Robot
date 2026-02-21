<x-app-layout>
	<x-slot name="header">
		<h1 class="text-[2.5rem] font-bold leading-tight tracking-tight text-text-primary">Privacy Policy</h1>
		<p class="mt-1 text-sm text-text-secondary">Last updated: {{ now()->format("F j, Y") }}</p>
	</x-slot>

	<div class="mx-auto max-w-3xl">
		<div class="rounded-xl border border-border bg-surface p-8 shadow-card prose-container">

			<h2 class="text-lg font-semibold text-text-primary">1. Information We Collect</h2>
			<p class="mt-2 text-sm leading-relaxed text-text-secondary">
				We collect information you provide directly: your name, email address, and password when you register. When you use the Service, we collect the URLs you scan, scan results, and AI-generated recommendations. We also collect billing information processed securely through Stripe.
			</p>

			<h2 class="mt-8 text-lg font-semibold text-text-primary">2. How We Use Your Information</h2>
			<p class="mt-2 text-sm leading-relaxed text-text-secondary">
				We use your information to provide and improve the Service, process payments, send transactional emails (welcome messages, scan completion notifications), and communicate important updates. We do not sell your personal information to third parties.
			</p>

			<h2 class="mt-8 text-lg font-semibold text-text-primary">3. Data Storage &amp; Security</h2>
			<p class="mt-2 text-sm leading-relaxed text-text-secondary">
				Your data is stored on secure servers with encryption at rest and in transit. We implement industry-standard security measures including password hashing, CSRF protection, and parameterized database queries. However, no method of transmission over the internet is 100% secure.
			</p>

			<h2 class="mt-8 text-lg font-semibold text-text-primary">4. Third-Party Services</h2>
			<p class="mt-2 text-sm leading-relaxed text-text-secondary">
				We use the following third-party services that may process your data:
			</p>
			<ul class="mt-2 ml-6 list-disc space-y-1 text-sm text-text-secondary">
				<li><strong>Stripe</strong> &mdash; payment processing (billing details, subscription management)</li>
				<li><strong>AI Providers</strong> &mdash; OpenAI, Anthropic, or Google (scan data sent for AI analysis, only when you trigger AI features)</li>
			</ul>

			<h2 class="mt-8 text-lg font-semibold text-text-primary">5. Cookies &amp; Tracking</h2>
			<p class="mt-2 text-sm leading-relaxed text-text-secondary">
				We use essential cookies for session management and authentication. We do not use third-party advertising trackers. Analytics, if implemented, will be disclosed here.
			</p>

			<h2 class="mt-8 text-lg font-semibold text-text-primary">6. Data Retention</h2>
			<p class="mt-2 text-sm leading-relaxed text-text-secondary">
				We retain your account data for as long as your account is active. When you deactivate your account, your personal information and scan history are preserved so you can reactivate at any time. Deactivated accounts retain all data indefinitely unless you request permanent deletion.
			</p>

			<h2 class="mt-8 text-lg font-semibold text-text-primary">7. Account Deactivation &amp; Deletion</h2>
			<p class="mt-2 text-sm leading-relaxed text-text-secondary">
				You can deactivate your account at any time from your profile settings. Deactivation cancels any active subscription and prevents access to the Service, but preserves your data for future reactivation.
			</p>
			<p class="mt-2 text-sm leading-relaxed text-text-secondary">
				To request permanent deletion of your personal data, contact us at <a href="mailto:{{ $supportEmail }}" class="font-medium text-accent hover:text-accent-hover">{{ $supportEmail }}</a>. Upon receiving a verified deletion request, we will anonymize your personal information within 30 days. Non-personal data such as aggregated scan statistics may be retained.
			</p>

			<h2 class="mt-8 text-lg font-semibold text-text-primary">8. Your Rights</h2>
			<p class="mt-2 text-sm leading-relaxed text-text-secondary">
				You have the right to access, correct, or delete your personal data. You can update your profile information or deactivate your account through the Service. For data access or deletion requests, contact us at <a href="mailto:{{ $supportEmail }}" class="font-medium text-accent hover:text-accent-hover">{{ $supportEmail }}</a>.
			</p>

			<h2 class="mt-8 text-lg font-semibold text-text-primary">9. Children's Privacy</h2>
			<p class="mt-2 text-sm leading-relaxed text-text-secondary">
				The Service is not intended for users under 16 years of age. We do not knowingly collect personal information from children.
			</p>

			<h2 class="mt-8 text-lg font-semibold text-text-primary">10. Changes to This Policy</h2>
			<p class="mt-2 text-sm leading-relaxed text-text-secondary">
				We may update this Privacy Policy from time to time. We will notify you of material changes via email or in-app notification. The "Last updated" date at the top reflects the most recent revision.
			</p>

			<h2 class="mt-8 text-lg font-semibold text-text-primary">11. Contact</h2>
			<p class="mt-2 text-sm leading-relaxed text-text-secondary">
				For privacy-related questions or requests, contact us at <a href="mailto:{{ $supportEmail }}" class="font-medium text-accent hover:text-accent-hover">{{ $supportEmail }}</a>.
			</p>

		</div>

		<x-footer-links />
	</div>
</x-app-layout>
