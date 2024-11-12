<?php

namespace App\Filament\Resources\RiwayatHutangResource\Pages;

use App\Filament\Resources\RiwayatHutangResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRiwayatHutang extends ViewRecord
{
    protected static string $resource = RiwayatHutangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }
}
