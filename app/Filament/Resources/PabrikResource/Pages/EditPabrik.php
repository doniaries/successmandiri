<?php

namespace App\Filament\Resources\PabrikResource\Pages;

use App\Filament\Resources\PabrikResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

namespace App\Filament\Resources\PabrikResource\Pages;

use App\Filament\Resources\PabrikResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditPabrik extends EditRecord
{
    protected static string $resource = PabrikResource::class;

    // Redirect ke halaman index setelah edit
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // Kustomisasi notifikasi sukses
    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Pabrik diperbarui')
            ->body("Data pabrik {$this->record->nama} berhasil diperbarui.")
            ->duration(5000); // Durasi tampil 5 detik
    }



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