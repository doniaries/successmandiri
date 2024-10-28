<?php

namespace App\Filament\Resources\PabrikResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\PabrikResource;
use App\Filament\Traits\HasDynamicNotification;

class EditPabrik extends EditRecord
{

    use HasDynamicNotification;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    protected static string $resource = PabrikResource::class;





    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->after(function () {
                    // Redirect setelah delete
                    return redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }

    // Optional: Mutate data sebelum disimpan
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }
}