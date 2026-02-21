<section>
	<header>
		<h2 class="text-lg font-semibold text-text-primary">Invoices</h2>
		<p class="mt-1 text-sm text-text-secondary">Download receipts for your past payments.</p>
	</header>

	<div class="mt-6">
		@if(count($invoices) > 0)
			<div class="overflow-x-auto">
				<table class="w-full text-left text-sm">
					<thead>
						<tr class="border-b border-border">
							<th class="pb-3 pr-4 font-medium text-text-secondary">Date</th>
							<th class="pb-3 pr-4 font-medium text-text-secondary">Amount</th>
							<th class="pb-3 pr-4 font-medium text-text-secondary">Status</th>
							<th class="pb-3 font-medium text-text-secondary"></th>
						</tr>
					</thead>
					<tbody class="divide-y divide-border">
						@foreach($invoices as $invoice)
							<tr>
								<td class="py-3 pr-4 text-text-primary">{{ $invoice->date()->format("M j, Y") }}</td>
								<td class="py-3 pr-4 text-text-primary">{{ $invoice->total() }}</td>
								<td class="py-3 pr-4">
									@if($invoice->paid)
										<span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">Paid</span>
									@else
										<span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">Pending</span>
									@endif
								</td>
								<td class="py-3 text-right">
									<a href="{{ route("billing.invoice.download", $invoice->id) }}" class="text-sm font-medium text-accent hover:text-accent-hover">
										Download
									</a>
								</td>
							</tr>
						@endforeach
					</tbody>
				</table>
			</div>
		@else
			<p class="text-sm text-text-tertiary">No invoices yet.</p>
		@endif
	</div>
</section>
