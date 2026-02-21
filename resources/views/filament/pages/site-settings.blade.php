<x-filament-panels::page>
	<form wire:submit="save">
		{{ $this->form }}

		<div style="margin-top: 2rem;">
			<x-filament::button type="submit" size="lg">
				Save Settings
			</x-filament::button>
		</div>
	</form>
</x-filament-panels::page>
