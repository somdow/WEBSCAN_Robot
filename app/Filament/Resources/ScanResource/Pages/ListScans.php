<?php

namespace App\Filament\Resources\ScanResource\Pages;

use App\Filament\Resources\ScanResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListScans extends ListRecords
{
	protected static string $resource = ScanResource::class;

	protected Width | string | null $maxContentWidth = Width::Full;
}
