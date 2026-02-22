@php
	$currentOrganization = Auth::user()?->currentOrganization();
	$hasOverride = $currentOrganization?->hasActiveOverride() ?? false;
	$overridePlanName = $hasOverride ? ($currentOrganization->plan?->name ?? "Override") : null;
@endphp

<div x-data="{ open: false }" class="relative">
	<button @click="open = !open" class="flex items-center gap-2 rounded-lg p-1.5 transition duration-75 hover:bg-gray-100">
		@if($hasOverride)
			<span class="inline-flex animate-pulse items-center rounded-full border border-amber-300 bg-amber-100 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-amber-800">
				{{ $overridePlanName }} Override
			</span>
		@endif
		<div class="flex h-8 w-8 items-center justify-center rounded-full bg-orange-600 text-xs font-semibold text-white">
			{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
		</div>
	</button>

	<div
		x-show="open"
		@click.outside="open = false"
		x-transition:enter="transition ease-out duration-100"
		x-transition:enter-start="opacity-0 scale-95"
		x-transition:enter-end="opacity-100 scale-100"
		x-transition:leave="transition ease-in duration-75"
		x-transition:leave-start="opacity-100 scale-100"
		x-transition:leave-end="opacity-0 scale-95"
		class="absolute right-0 top-full mt-1 w-48 rounded-lg border border-gray-200 bg-white py-1 shadow-lg"
		style="display: none;"
	>
		<div class="border-b border-gray-100 px-4 py-2">
			<div class="truncate text-sm font-medium text-gray-700">{{ Auth::user()->name }}</div>
			<div class="truncate text-xs text-gray-500">{{ Auth::user()->email }}</div>
		</div>
		<a href="{{ route("profile.edit") }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
			Profile
		</a>
		<form method="POST" action="{{ route("logout") }}">
			@csrf
			<button type="submit" class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100">
				Log Out
			</button>
		</form>
	</div>
</div>
