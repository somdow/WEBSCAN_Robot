<!DOCTYPE html>
<html lang="{{ str_replace("_", "-", app()->getLocale()) }}">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="csrf-token" content="{{ csrf_token() }}">

		<title>{{ config("app.name", "HELLO WEB_SCANS") }}</title>

		@vite(["resources/css/app.css", "resources/js/app.js"])
	</head>
	<body class="font-sans antialiased" x-data="{ sidebarOpen: false }">
		<div class="min-h-screen bg-background">

			@auth
				{{-- Mobile sidebar overlay --}}
				<div
					x-show="sidebarOpen"
					x-transition:enter="transition-opacity ease-linear duration-200"
					x-transition:enter-start="opacity-0"
					x-transition:enter-end="opacity-100"
					x-transition:leave="transition-opacity ease-linear duration-200"
					x-transition:leave-start="opacity-100"
					x-transition:leave-end="opacity-0"
					class="fixed inset-0 z-40 bg-black/30 lg:hidden"
					@click="sidebarOpen = false"
					style="display: none;"
				></div>
			@endauth

			{{-- Sidebar (authenticated only) --}}
			@auth
				@include("layouts.sidebar")
			@endauth

			{{-- Main content area --}}
			<div class="{{ auth()->check() ? 'lg:pl-60' : '' }}">
				{{-- Mobile top bar --}}
				<div class="sticky top-0 z-30 flex h-14 items-center gap-4 border-b border-border bg-surface px-4 lg:hidden">
					@auth
						<button @click="sidebarOpen = true" class="-ml-1 p-1.5 text-text-secondary hover:text-text-primary">
							<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
							</svg>
						</button>
					@endauth
					<span class="flex-1 text-sm font-semibold text-text-primary">{{ \App\Models\Setting::getValue("site_name", config("app.name", "HELLO WEB_SCANS")) }}</span>
					@auth
						@include("layouts.user-menu")
					@endauth
				</div>

				{{-- Desktop user avatar (top-right) --}}
				@auth
					<div class="fixed right-6 top-4 z-30 hidden lg:block">
						@include("layouts.user-menu")
					</div>
				@endauth

				{{-- Page header --}}
				@isset($header)
					<header class="mx-auto max-w-7xl px-6 py-5 lg:px-8">
						{{ $header }}
					</header>
				@endisset

				{{-- Page content --}}
				<main class="relative mx-auto max-w-7xl px-6 py-6 lg:px-8">
					{{ $slot }}
				</main>

				<footer class="mx-auto max-w-7xl px-6 py-10 lg:px-8 text-center">
					<p class="text-sm font-extrabold text-text-primary">{{ \App\Models\Setting::getValue("site_name", config("app.name", "HELLO WEB_SCANS")) }}</p>
					<p class="mt-1 text-xs text-text-secondary">{{ \App\Models\Setting::getValue("site_tagline", "") }}</p>
					<p class="mt-2 text-xs text-gray-400">&copy; {{ date("Y") }} {{ \App\Models\Setting::getValue("site_name", config("app.name", "HELLO WEB_SCANS")) }}. All rights reserved.</p>
				</footer>
			</div>
		</div>
		<x-toast-container />
	</body>
</html>
