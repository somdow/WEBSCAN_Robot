{{-- Stripe Embedded Checkout modal — renders Stripe's checkout form in an iframe overlay.
     Triggered via Alpine event: $dispatch('open-stripe-checkout', { planId, billingCycle, couponCode })
     Requires STRIPE_KEY in .env for the publishable key. --}}

@auth
<div
	x-data="stripeCheckoutModal()"
	x-on:open-stripe-checkout.window="openCheckout($event.detail)"
	x-show="visible"
	x-cloak
	class="fixed inset-0 z-50 flex items-center justify-center"
>
	{{-- Backdrop --}}
	<div
		x-show="visible"
		x-transition:enter="transition-opacity duration-200"
		x-transition:enter-start="opacity-0"
		x-transition:enter-end="opacity-100"
		x-transition:leave="transition-opacity duration-150"
		x-transition:leave-start="opacity-100"
		x-transition:leave-end="opacity-0"
		class="absolute inset-0 bg-black/50"
		@click="closeCheckout()"
	></div>

	{{-- Modal panel --}}
	<div
		x-show="visible"
		x-transition:enter="transition duration-200 ease-out"
		x-transition:enter-start="opacity-0 scale-95"
		x-transition:enter-end="opacity-100 scale-100"
		x-transition:leave="transition duration-150 ease-in"
		x-transition:leave-start="opacity-100 scale-100"
		x-transition:leave-end="opacity-0 scale-95"
		class="relative w-full max-w-lg rounded-xl bg-white shadow-2xl"
		@click.stop
	>
		{{-- Header --}}
		<div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
			<div class="flex items-center gap-3">
				<svg class="h-8 w-auto" viewBox="0 0 60 25" xmlns="http://www.w3.org/2000/svg" aria-label="Powered by Stripe">
					<path fill="#635BFF" d="M59.64 14.28h-8.06c.19 1.93 1.6 2.55 3.2 2.55 1.64 0 2.96-.37 4.05-.95v3.32a12.3 12.3 0 0 1-4.56.85c-4.14 0-6.6-2.48-6.6-6.94 0-3.83 2.14-7.01 6.01-7.01 3.72 0 5.96 2.85 5.96 6.84v1.34zm-3.86-2.57c0-1.3-.64-2.8-2.14-2.8-1.44 0-2.2 1.43-2.3 2.8h4.44zm-11.8-5.39h3.98v12.06h-3.98v-1.23c-.93 1.01-2.28 1.6-3.82 1.6-3.37 0-6.1-2.93-6.1-6.73s2.73-6.73 6.1-6.73c1.54 0 2.89.6 3.82 1.6V6.32zm-3.03 3.4c-1.7 0-2.86 1.36-2.86 3.3s1.16 3.3 2.86 3.3c1.67 0 2.86-1.36 2.86-3.3s-1.2-3.3-2.86-3.3zM27.68.18v18.2h3.98V.18h-3.98zm-5.85 18.38c-1.54 0-2.89-.6-3.82-1.6v1.42h-3.98V.18h3.98v7.57c.93-1.01 2.28-1.6 3.82-1.6 3.37 0 6.1 2.93 6.1 6.73s-2.73 6.73-6.1 6.73v-.05zm-.82-9.99c-1.67 0-2.86 1.36-2.86 3.3s1.2 3.3 2.86 3.3c1.7 0 2.86-1.36 2.86-3.3s-1.16-3.3-2.86-3.3zM8.44 1.88c1.38 0 2.5 1.13 2.5 2.5s-1.12 2.5-2.5 2.5a2.5 2.5 0 0 1-2.5-2.5c0-1.38 1.12-2.5 2.5-2.5zM6.46 18.38V6.32h3.98v12.06H6.46zM3.3 6.32H0v12.06h3.3c0-.01 0-12.06 0-12.06zM1.65 1.41C.74 1.41 0 2.15 0 3.06s.74 1.65 1.65 1.65S3.3 3.97 3.3 3.06 2.56 1.41 1.65 1.41z"/>
				</svg>
				<h3 class="text-lg font-semibold text-text-primary">Secure Checkout</h3>
			</div>
			<button @click="closeCheckout()" class="cursor-pointer rounded-md p-1 text-gray-400 transition hover:text-gray-600">
				<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
				</svg>
			</button>
		</div>

		{{-- Loading state --}}
		<div x-show="loading" class="flex items-center justify-center py-20">
			<svg class="h-8 w-8 animate-spin text-accent" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
				<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
				<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
			</svg>
		</div>

		{{-- Error state --}}
		<div x-show="errorMessage" class="px-6 py-12 text-center">
			<div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
				<svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
				</svg>
			</div>
			<p class="mt-3 text-sm text-red-600" x-text="errorMessage"></p>
			<button @click="closeCheckout()" class="mt-4 cursor-pointer rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-text-primary transition hover:bg-gray-200">
				Close
			</button>
		</div>

		{{-- Stripe Embedded Checkout container --}}
		<div x-show="!loading && !errorMessage" id="stripe-checkout-container" class="overflow-hidden rounded-b-xl"></div>
	</div>
</div>

<script>
	document.addEventListener("alpine:init", () => {
		Alpine.data("stripeCheckoutModal", () => ({
			visible: false,
			loading: false,
			errorMessage: null,
			stripeInstance: null,
			checkoutInstance: null,

			async openCheckout(detail) {
				this.visible = true;
				this.loading = true;
				this.errorMessage = null;

				try {
					const response = await fetch("{{ route('billing.checkout') }}", {
						method: "POST",
						headers: {
							"Content-Type": "application/json",
							"X-CSRF-TOKEN": document.querySelector("meta[name='csrf-token']").content,
							"Accept": "application/json",
						},
						body: JSON.stringify({
							plan_id: detail.planId,
							billing_cycle: detail.billingCycle,
							coupon_code: detail.couponCode || null,
						}),
					});

					const data = await response.json();

					if (!response.ok || data.error) {
						this.errorMessage = data.error || "Something went wrong. Please try again.";
						this.loading = false;
						return;
					}

					if (!this.stripeInstance) {
						this.stripeInstance = Stripe("{{ config('cashier.key') }}");
					}

					this.checkoutInstance = await this.stripeInstance.initEmbeddedCheckout({
						clientSecret: data.clientSecret,
					});

					this.loading = false;

					this.$nextTick(() => {
						this.checkoutInstance.mount("#stripe-checkout-container");
					});
				} catch (fetchError) {
					this.errorMessage = "Unable to connect. Please check your internet connection and try again.";
					this.loading = false;
				}
			},

			closeCheckout() {
				if (this.checkoutInstance) {
					this.checkoutInstance.destroy();
					this.checkoutInstance = null;
				}
				this.visible = false;
				this.loading = false;
				this.errorMessage = null;
			},
		}));
	});
</script>
@endauth
