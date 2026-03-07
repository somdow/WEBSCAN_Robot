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
			<h3 class="text-lg font-semibold text-text-primary">Complete Your Subscription</h3>
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
