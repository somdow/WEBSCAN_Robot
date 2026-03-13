<x-app-layout>
	<x-slot name="header">
		<h1 class="text-[2.5rem] font-bold leading-tight tracking-tight text-text-primary">Subscription Confirmed</h1>
		<x-breadcrumb :items="array(
			array('label' => 'Home', 'url' => route('dashboard')),
			array('label' => 'Billing', 'url' => route('billing.index')),
			array('label' => 'Confirmed'),
		)" />
	</x-slot>

	<style>
		@keyframes check-draw {
			0% { stroke-dashoffset: 48; }
			100% { stroke-dashoffset: 0; }
		}
		@keyframes circle-draw {
			0% { stroke-dashoffset: 166; }
			100% { stroke-dashoffset: 0; }
		}
		@keyframes ring-pulse {
			0% { transform: scale(1); opacity: 0.4; }
			100% { transform: scale(1.8); opacity: 0; }
		}
		@keyframes fade-up {
			0% { opacity: 0; transform: translateY(16px); }
			100% { opacity: 1; transform: translateY(0); }
		}
		@keyframes glow-pulse {
			0%, 100% { box-shadow: 0 0 20px rgba(79, 70, 229, 0.15); }
			50% { box-shadow: 0 0 40px rgba(79, 70, 229, 0.25); }
		}
		.animate-circle-draw {
			stroke-dasharray: 166;
			stroke-dashoffset: 166;
			animation: circle-draw 0.8s cubic-bezier(0.65, 0, 0.35, 1) 0.2s forwards;
		}
		.animate-check-draw {
			stroke-dasharray: 48;
			stroke-dashoffset: 48;
			animation: check-draw 0.4s cubic-bezier(0.65, 0, 0.35, 1) 0.8s forwards;
		}
		.animate-ring-pulse {
			animation: ring-pulse 1.2s cubic-bezier(0, 0, 0.2, 1) 1s forwards;
		}
		.animate-fade-up {
			opacity: 0;
			animation: fade-up 0.5s ease-out forwards;
		}
		.animate-glow {
			animation: glow-pulse 3s ease-in-out infinite;
		}
	</style>

	@php
		$isSyncSuccessful = $plan && $plan->slug !== "free";
		$planName = $plan->name ?? "your plan";
		$features = array();

		if ($isSyncSuccessful) {
			$features = array(
				array(
					"label" => $plan->max_projects . " " . ($plan->max_projects > 1 ? "Projects" : "Project"),
					"description" => "Create and monitor up to " . $plan->max_projects . " websites",
					"icon" => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />',
				),
				array(
					"label" => $plan->max_scans_per_month . " Scans/month",
					"description" => "Run comprehensive SEO audits every month",
					"icon" => '<path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />',
				),
				array(
					"label" => $plan->max_users . " Team " . ($plan->max_users > 1 ? "Members" : "Member"),
					"description" => "Collaborate with your team on projects",
					"icon" => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />',
				),
				array(
					"label" => ($plan->scan_history_days >= 36500 ? "Unlimited" : $plan->scan_history_days . "-day") . " History",
					"description" => "Track your SEO progress over time",
					"icon" => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />',
				),
			);
		}
	@endphp

	<div class="mx-auto max-w-xl">
		@if($isSyncSuccessful)
			{{-- Successful subscription --}}
			<div class="rounded-2xl border border-border bg-surface p-8 shadow-card animate-glow sm:p-10">
				{{-- Animated checkmark --}}
				<div class="relative mx-auto h-20 w-20">
					{{-- Pulse ring --}}
					<div class="absolute inset-0 rounded-full border-2 border-accent/40 animate-ring-pulse"></div>

					{{-- Circle + check SVG --}}
					<svg class="h-20 w-20" viewBox="0 0 56 56" fill="none">
						<circle
							cx="28" cy="28" r="26"
							stroke="#4F46E5"
							stroke-width="2.5"
							fill="none"
							class="animate-circle-draw"
						/>
						<path
							d="M17 28.5L24 35.5L39 20.5"
							stroke="#4F46E5"
							stroke-width="3"
							stroke-linecap="round"
							stroke-linejoin="round"
							fill="none"
							class="animate-check-draw"
						/>
					</svg>
				</div>

				{{-- Headline --}}
				<div class="mt-6 text-center animate-fade-up" style="animation-delay: 1s;">
					<h2 class="text-2xl font-bold tracking-tight text-text-primary">You're all set!</h2>
					<p class="mt-2 text-sm text-text-secondary">
						Your <span class="inline-flex items-center rounded-full bg-accent/10 px-3 py-0.5 text-sm font-semibold text-accent">{{ $planName }}</span> subscription is active.
					</p>
				</div>

				{{-- Feature unlock cards --}}
				<div class="mt-8 grid grid-cols-2 gap-3 animate-fade-up" style="animation-delay: 1.2s;">
					@foreach($features as $feature)
						<div class="group rounded-xl border border-border bg-background p-4 transition hover:border-accent/30 hover:shadow-sm">
							<div class="flex h-9 w-9 items-center justify-center rounded-lg bg-accent/10">
								<svg class="h-5 w-5 text-accent" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
									{!! $feature["icon"] !!}
								</svg>
							</div>
							<p class="mt-3 text-sm font-semibold text-text-primary">{{ $feature["label"] }}</p>
							<p class="mt-0.5 text-xs text-text-tertiary">{{ $feature["description"] }}</p>
						</div>
					@endforeach
				</div>

				{{-- CTAs --}}
				<div class="mt-8 flex flex-col gap-3 sm:flex-row animate-fade-up" style="animation-delay: 1.4s;">
					<a
						href="{{ route("projects.create") }}"
						class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-accent px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-accent-hover"
					>
						<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
						</svg>
						Create Your First Project
					</a>
					<a
						href="{{ route("dashboard") }}"
						class="inline-flex flex-1 items-center justify-center rounded-lg border border-border bg-surface px-5 py-3 text-sm font-medium text-text-primary transition hover:bg-gray-50"
					>
						Go to Dashboard
					</a>
				</div>

				{{-- Receipt note --}}
				<p class="mt-6 text-center text-xs text-text-tertiary animate-fade-up" style="animation-delay: 1.6s;">
					A receipt has been sent to your email. You can manage your subscription anytime from
					<a href="{{ route("billing.index") }}" class="font-medium text-accent hover:underline">Billing</a>.
				</p>
			</div>

		@else
			{{-- Plan sync may have failed — gentle fallback --}}
			<div class="rounded-2xl border border-amber-200 bg-amber-50 p-8 text-center shadow-card sm:p-10">
				<div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-amber-100">
					<svg class="h-7 w-7 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
					</svg>
				</div>

				<h2 class="mt-4 text-xl font-bold text-amber-900">Payment received!</h2>
				<p class="mt-2 text-sm text-amber-700">
					Your payment was successful, but your plan update is still processing. This usually takes a few seconds.
				</p>

				<div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-center">
					<a
						href="{{ route("billing.index") }}"
						class="inline-flex items-center justify-center gap-2 rounded-lg bg-amber-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-700"
					>
						<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" />
						</svg>
						Check Billing Status
					</a>
					<a
						href="{{ route("dashboard") }}"
						class="inline-flex items-center justify-center rounded-lg border border-amber-300 bg-white px-5 py-3 text-sm font-medium text-amber-800 transition hover:bg-amber-50"
					>
						Go to Dashboard
					</a>
				</div>

				<p class="mt-4 text-xs text-amber-600">
					If your plan doesn't update within a few minutes, please
					<a href="mailto:support@hellowebscans.com" class="font-medium underline">contact support</a>.
				</p>
			</div>
		@endif
	</div>
</x-app-layout>
