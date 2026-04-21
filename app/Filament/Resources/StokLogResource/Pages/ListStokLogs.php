<?php

namespace App\Filament\Resources\StokLogResource\Pages;

use App\Filament\Resources\StokLogResource;
use Filament\Resources\Pages\ListRecords;

class ListStokLogs extends ListRecords
{
    protected static string $resource = StokLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
