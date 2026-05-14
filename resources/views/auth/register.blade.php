<x-guest-layout>
	@if(!empty($pendingScanUrl))
		<div class="mb-4 rounded-md border border-accent-border bg-accent-light px-4 py-3 text-sm text-text-primary">
			We will scan <strong>{{ parse_url($pendingScanUrl, PHP_URL_HOST) ?: $pendingScanUrl }}</strong> right after you sign up.
		</div>
	@endif

	<form method="POST" action="{{ route("register") }}">
		@csrf

		@if(!empty($pendingScanUrl))
			<input type="hidden" name="pending_scan_url" value="{{ $pendingScanUrl }}">
		@endif

		<!-- Name -->
		<div>
			<x-input-label for="name" :value="__('Name')" />
			<x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
			<x-input-error :messages="$errors->get('name')" class="mt-2" />
		</div>

		<!-- Email Address -->
		<div class="mt-4">
			<x-input-label for="email" :value="__('Email')" />
			<x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
			<x-input-error :messages="$errors->get('email')" class="mt-2" />
		</div>

		<!-- Password -->
		<div class="mt-4">
			<x-input-label for="password" :value="__('Password')" />

			<x-text-input id="password" class="block mt-1 w-full"
							type="password"
							name="password"
							required autocomplete="new-password" />

			<x-input-error :messages="$errors->get('password')" class="mt-2" />
		</div>

		<!-- Confirm Password -->
		<div class="mt-4">
			<x-input-label for="password_confirmation" :value="__('Confirm Password')" />

			<x-text-input id="password_confirmation" class="block mt-1 w-full"
							type="password"
							name="password_confirmation" required autocomplete="new-password" />

			<x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
		</div>

		<div class="flex items-center justify-end mt-4">
			<a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500" href="{{ route("login") }}">
				{{ __("Already registered?") }}
			</a>

			<x-primary-button class="ms-4">
				{{ __("Register") }}
			</x-primary-button>
		</div>
	</form>
</x-guest-layout>
