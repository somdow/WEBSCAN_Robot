<x-guest-layout>
	<div class="flex justify-center">
		<div class="flex h-10 w-10 items-center justify-center rounded-lg bg-accent text-sm font-bold text-white">
			{{ strtoupper(substr(config("app.name", "W"), 0, 1)) }}
		</div>
	</div>

	<h1 class="mt-4 text-center text-xl font-semibold text-text-primary">You're invited to join a team</h1>

	<p class="mt-3 text-center text-sm text-text-secondary">
		<strong>{{ $inviterName }}</strong> invited you to join
		<strong>{{ $organizationName }}</strong>.
	</p>

	<div class="mt-6 space-y-3">
		<a
			href="{{ route("login") }}"
			class="block w-full rounded-md bg-accent px-4 py-2.5 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-accent/90"
		>
			Log In to Accept
		</a>

		<a
			href="{{ route("register") }}"
			class="block w-full rounded-md border border-border bg-white px-4 py-2.5 text-center text-sm font-semibold text-text-primary shadow-sm transition hover:bg-gray-50"
		>
			Create an Account
		</a>
	</div>

	<p class="mt-5 text-center text-xs text-text-tertiary">
		This invitation expires on {{ $invitation->expires_at->format("F j, Y") }}.
	</p>
</x-guest-layout>
