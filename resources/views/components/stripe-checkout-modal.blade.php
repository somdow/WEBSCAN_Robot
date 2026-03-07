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
				<svg class="h-8 w-auto" viewBox="0 0 120 60" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" aria-label="Stripe">
					<path fill="#6772E5" d="M101.547 30.94c0-5.885-2.85-10.53-8.3-10.53-5.47 0-8.782 4.644-8.782 10.483 0 6.92 3.908 10.414 9.517 10.414 2.736 0 4.805-.62 6.368-1.494v-4.598c-1.563.782-3.356 1.264-5.632 1.264-2.23 0-4.207-.782-4.46-3.494h11.24c0-.3.046-1.494.046-2.046zM90.2 28.757c0-2.598 1.586-3.678 3.035-3.678 1.402 0 2.897 1.08 2.897 3.678zm-14.597-8.345c-2.253 0-3.7 1.057-4.506 1.793l-.3-1.425H65.73v26.805l5.747-1.218.023-6.506c.828.598 2.046 1.448 4.07 1.448 4.115 0 7.862-3.3 7.862-10.598-.023-6.667-3.816-10.3-7.84-10.3zm-1.38 15.84c-1.356 0-2.16-.483-2.713-1.08l-.023-8.53c.598-.667 1.425-1.126 2.736-1.126 2.092 0 3.54 2.345 3.54 5.356 0 3.08-1.425 5.38-3.54 5.38zm-16.4-17.196l5.77-1.24V13.15l-5.77 1.218zm0 1.747h5.77v20.115h-5.77zm-6.185 1.7l-.368-1.7h-4.966V40.92h5.747V27.286c1.356-1.77 3.655-1.448 4.368-1.195v-5.287c-.736-.276-3.425-.782-4.782 1.7zm-11.494-6.7L34.535 17l-.023 18.414c0 3.402 2.552 5.908 5.954 5.908 1.885 0 3.264-.345 4.023-.76v-4.667c-.736.3-4.368 1.356-4.368-2.046V25.7h4.368v-4.897h-4.37zm-15.54 10.828c0-.897.736-1.24 1.954-1.24a12.85 12.85 0 0 1 5.7 1.47V21.47c-1.908-.76-3.793-1.057-5.7-1.057-4.667 0-7.77 2.437-7.77 6.506 0 6.345 8.736 5.333 8.736 8.07 0 1.057-.92 1.402-2.207 1.402-1.908 0-4.345-.782-6.276-1.84v5.47c2.138.92 4.3 1.3 6.276 1.3 4.782 0 8.07-2.368 8.07-6.483-.023-6.85-8.782-5.632-8.782-8.207z"/>
				</svg>
				<span class="text-sm text-gray-400">|</span>
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
