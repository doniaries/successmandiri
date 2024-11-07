<?php

namespace App\Filament\Resources\KeuanganResource\Pages;

use App\Filament\Resources\KeuanganResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKeuangan extends CreateRecord
{
    protected static string $resource = KeuanganResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
