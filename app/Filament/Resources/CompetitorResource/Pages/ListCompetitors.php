<?php

namespace App\Filament\Resources\CompetitorResource\Pages;

use App\Filament\Resources\CompetitorResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListCompetitors extends ListRecords
{
	protected static string $resource = CompetitorResource::class;

	protected Width | string | null $maxContentWidth = Width::Full;
}
