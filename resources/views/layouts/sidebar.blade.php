<aside
	:class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
	class="fixed inset-y-0 left-0 z-50 flex w-60 flex-col transition-transform duration-200 lg:translate-x-0"
>
	{{-- Logo --}}
	<div class="flex h-16 items-center gap-3 px-6">
		<div class="flex h-8 w-8 items-center justify-center rounded-lg bg-accent text-sm font-bold text-white">
			W
		</div>
		<span class="text-sm font-semibold text-gray-900">{{ \App\Models\Setting::getValue("site_name", config("app.name", "HELLO WEB_SCANS")) }}</span>
	</div>

	{{-- Organization Switcher (only if user belongs to multiple orgs) --}}
	@php
		$activeOrganization = auth()->user()->currentOrganization();
		$userOrganizations = auth()->user()->relationLoaded("organizations")
			? auth()->user()->getRelation("organizations")
			: auth()->user()->organizations()->get();
	@endphp
	@if($userOrganizations->count() > 1)
		<div class="px-4 pb-2" x-data="{ orgOpen: false }">
			<button
				@click="orgOpen = !orgOpen"
				class="flex w-full items-center justify-between rounded-lg border border-border bg-white px-3 py-2 text-left transition hover:bg-gray-50"
			>
				<div class="flex items-center gap-2 min-w-0">
					<div class="flex h-6 w-6 shrink-0 items-center justify-center rounded bg-accent/10 text-xs font-bold text-accent">
						{{ strtoupper(substr($activeOrganization->name ?? "", 0, 1)) }}
					</div>
					<span class="truncate text-sm font-medium text-text-primary">{{ $activeOrganization->name ?? "Select org" }}</span>
				</div>
				<svg class="h-4 w-4 shrink-0 text-gray-400 transition" :class="orgOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
				</svg>
			</button>

			<div x-show="orgOpen" @click.away="orgOpen = false" x-transition class="mt-1 rounded-lg border border-border bg-white py-1 shadow-lg" style="display: none;">
				@foreach($userOrganizations as $org)
					@if($org->id === $activeOrganization->id)
						<div class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-accent">
							<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
							</svg>
							<span class="truncate">{{ $org->name }}</span>
						</div>
					@else
						<form method="POST" action="{{ route('organizations.switch', $org) }}">
							@csrf
							<button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-text-secondary transition hover:bg-gray-50 hover:text-text-primary">
								<span class="h-4 w-4"></span>
								<span class="truncate">{{ $org->name }}</span>
							</button>
						</form>
					@endif
				@endforeach
			</div>
		</div>
	@endif

	{{-- Navigation --}}
	<nav class="flex flex-1 flex-col gap-y-7 overflow-y-auto px-4 py-6">
		<div class="flex flex-col gap-y-1">
			<x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="home">
				Home
			</x-sidebar-link>
		</div>

		<div class="flex flex-col gap-y-1">
			<div class="px-2 pb-1">
				<span class="text-sm font-medium leading-6 text-gray-500">Projects</span>
			</div>
			<x-sidebar-link :href="route('projects.index')" :active="request()->routeIs('projects.*')" icon="folder">
				All Projects
			</x-sidebar-link>
			<x-sidebar-link :href="route('scans.index')" :active="request()->routeIs('scans.*')" icon="chart">
				Scans
			</x-sidebar-link>
		</div>

		<div class="flex flex-col gap-y-1">
			<div class="px-2 pb-1">
				<span class="text-sm font-medium leading-6 text-gray-500">Team</span>
			</div>
			<x-sidebar-link :href="route('team.index')" :active="request()->routeIs('team.*')" icon="team">
				Team
			</x-sidebar-link>
		</div>

		<div class="flex flex-col gap-y-1">
			<div class="px-2 pb-1">
				<span class="text-sm font-medium leading-6 text-gray-500">Account</span>
			</div>
			<x-sidebar-link :href="route('billing.index')" :active="request()->routeIs('billing.*')" icon="creditcard">
				Billing (disabled)
			</x-sidebar-link>
			<x-sidebar-link :href="route('branding.edit')" :active="request()->routeIs('branding.*')" icon="paintbrush">
				Branding
			</x-sidebar-link>
			<x-sidebar-link :href="route('profile.edit')" :active="request()->routeIs('profile.edit')" icon="settings">
				Settings
			</x-sidebar-link>
		</div>
	</nav>

</aside>
