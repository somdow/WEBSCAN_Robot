<div class="space-y-4">
	@if($record->old_values)
		<div>
			<h3 class="text-sm font-medium text-gray-500">Old Values</h3>
			<pre class="mt-1 rounded-lg bg-gray-50 p-3 text-sm">{{ json_encode($record->old_values, JSON_PRETTY_PRINT) }}</pre>
		</div>
	@endif

	@if($record->new_values)
		<div>
			<h3 class="text-sm font-medium text-gray-500">New Values</h3>
			<pre class="mt-1 rounded-lg bg-gray-50 p-3 text-sm">{{ json_encode($record->new_values, JSON_PRETTY_PRINT) }}</pre>
		</div>
	@endif

	@if(!$record->old_values && !$record->new_values)
		<p class="text-sm text-gray-500">No value changes recorded for this entry.</p>
	@endif
</div>
