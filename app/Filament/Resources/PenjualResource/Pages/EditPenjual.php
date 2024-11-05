<?php

namespace App\Filament\Resources\PenjualResource\Pages;

use App\Filament\Resources\PenjualResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPenjual extends EditRecord
{
    protected static string $resource = PenjualResource::class;


    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->nama;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Telepon' => $record->telepon,
            'Hutang' => money($record->hutang, 'IDR'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
