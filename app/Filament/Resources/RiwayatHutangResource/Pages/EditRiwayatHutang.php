<?php

namespace App\Filament\Resources\RiwayatHutangResource\Pages;

use App\Filament\Resources\RiwayatHutangResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRiwayatHutang extends EditRecord
{
    protected static string $resource = RiwayatHutangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
