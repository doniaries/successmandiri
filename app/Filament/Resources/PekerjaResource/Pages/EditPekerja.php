<?php

namespace App\Filament\Resources\PekerjaResource\Pages;

use App\Filament\Resources\PekerjaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditPekerja extends EditRecord
{
    protected static string $resource = PekerjaResource::class;

    // Redirect ke index setelah edit
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // Dynamic notification for update
    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Data pekerja diperbarui')
            ->body("Pekerja {$this->record->nama} berhasil diperbarui.")
            ->duration(5000);
    }

    // Dynamic notification for delete
    protected function getDeletedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Data pekerja dihapus')
            ->body("Pekerja {$this->record->nama} telah dihapus.")
            ->duration(5000);
    }

    // Dynamic notification for restore
    protected function getRestoredNotification(): ?Notification
    {
        return Notification::make()
            ->warning()
            ->title('Data pekerja dipulihkan')
            ->body("Pekerja {$this->record->nama} telah dipulihkan.")
            ->duration(5000);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->danger()
                        ->title('Data pekerja dihapus')
                        ->body("Pekerja {$this->record->nama} telah dihapus.")
                        ->duration(5000)
                ),

            Actions\RestoreAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Data pekerja dipulihkan')
                        ->body("Pekerja {$this->record->nama} telah dipulihkan.")
                        ->duration(5000)
                ),

            Actions\ForceDeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Data pekerja dihapus permanen')
                        ->body("Pekerja {$this->record->nama} telah dihapus secara permanen.")
                        ->duration(5000)
                ),
        ];
    }

    // Mengisi updated_by
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }
}