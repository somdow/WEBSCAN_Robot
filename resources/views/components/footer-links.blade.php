<footer class="mt-12 border-t border-border py-6 text-center text-sm text-text-secondary">
	<div class="flex flex-wrap items-center justify-center gap-x-6 gap-y-2">
		<a href="{{ route("legal.terms") }}" class="hover:text-text-primary transition">Terms of Service</a>
		<a href="{{ route("legal.privacy") }}" class="hover:text-text-primary transition">Privacy Policy</a>
		<a href="{{ route("legal.acceptable-use") }}" class="hover:text-text-primary transition">Acceptable Use</a>
	</div>
	<p class="mt-3 text-xs text-text-tertiary">&copy; {{ date("Y") }} {{ config("app.name", "HELLO WEB_SCANS") }}. All rights reserved.</p>
</footer>
