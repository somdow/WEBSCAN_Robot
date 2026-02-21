{{-- Global toast notification container --}}
<div
	x-data
	class="pointer-events-none fixed bottom-0 right-0 z-50 flex flex-col items-end gap-3 px-4 py-5"
	aria-live="polite"
>
	<template x-for="toast in $store.toasts.items" :key="toast.id">
		<div
			x-show="toast.visible"
			x-transition:enter="transition duration-300 ease-out"
			x-transition:enter-start="translate-y-4 opacity-0"
			x-transition:enter-end="translate-y-0 opacity-100"
			x-transition:leave="transition duration-200 ease-in"
			x-transition:leave-start="translate-y-0 opacity-100"
			x-transition:leave-end="translate-y-4 opacity-0"
			class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-lg border p-4 shadow-lg"
			:class="{
				'border-emerald-600/30 bg-emerald-950 text-emerald-100': toast.type === 'success',
				'border-red-600/30 bg-red-950 text-red-100': toast.type === 'error',
				'border-amber-600/30 bg-amber-950 text-amber-100': toast.type === 'warning',
				'border-blue-600/30 bg-blue-950 text-blue-100': toast.type === 'info',
			}"
		>
			{{-- Icon --}}
			<div class="shrink-0 pt-0.5">
				{{-- Success --}}
				<template x-if="toast.type === 'success'">
					<svg class="h-5 w-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
					</svg>
				</template>
				{{-- Error --}}
				<template x-if="toast.type === 'error'">
					<svg class="h-5 w-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
					</svg>
				</template>
				{{-- Warning --}}
				<template x-if="toast.type === 'warning'">
					<svg class="h-5 w-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
					</svg>
				</template>
				{{-- Info --}}
				<template x-if="toast.type === 'info'">
					<svg class="h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
					</svg>
				</template>
			</div>

			{{-- Message --}}
			<p class="flex-1 text-sm font-medium" x-text="toast.message"></p>

			{{-- Dismiss --}}
			<button
				@click="$store.toasts.dismiss(toast.id)"
				class="shrink-0 rounded-md p-0.5 opacity-60 transition hover:opacity-100"
			>
				<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
				</svg>
			</button>
		</div>
	</template>
</div>

{{-- Hydrate session flash messages into the Alpine store --}}
@php
	$toastFlashTypes = array(
		"success" => 4000,
		"error" => 6000,
		"warning" => 5000,
		"info" => 4000,
	);

	$statusMessages = array(
		"profile-updated" => "Profile updated successfully.",
		"password-updated" => "Password updated successfully.",
		"verification-link-sent" => "A new verification link has been sent to your email.",
		"ai-settings-updated" => "AI settings updated successfully.",
	);
@endphp

@foreach($toastFlashTypes as $toastType => $toastDuration)
	@if(session($toastType))
		<div x-data x-init="$store.toasts.add(@js(session($toastType)), '{{ $toastType }}', {{ $toastDuration }})" class="hidden"></div>
	@endif
@endforeach

@if(session("status"))
	<div x-data x-init="$store.toasts.add(@js($statusMessages[session('status')] ?? session('status')), 'success')" class="hidden"></div>
@endif
