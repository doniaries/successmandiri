<?php

namespace App\Filament\Traits;

use Filament\Notifications\Notification;

trait HasDynamicNotification
{
    protected function getModelLabel(): string
    {
        return $this->getResource()::getModelLabel();
    }

    protected function getRecordName(): string
    {
        // Cek field yang biasa digunakan sebagai nama/label
        $nameFields = ['nama', 'name', 'title', 'nomor', 'kode'];

        foreach ($nameFields as $field) {
            if (isset($this->record->$field)) {
                return $this->record->$field;
            }
        }

        // Fallback ke ID jika tidak ada field nama
        return "#{$this->record->id}";
    }

    protected function getCreatedNotification(): ?Notification
    {
        $modelLabel = $this->getModelLabel();
        $recordName = $this->getRecordName();

        return Notification::make()
            ->success()
            ->title("Data {$modelLabel} berhasil ditambahkan")
            ->body("{$modelLabel} {$recordName} telah ditambahkan ke database.")
            ->duration(5000);
    }

    protected function getSavedNotification(): ?Notification
    {
        $modelLabel = $this->getModelLabel();
        $recordName = $this->getRecordName();

        return Notification::make()
            ->success()
            ->title("Data {$modelLabel} diperbarui")
            ->body("{$modelLabel} {$recordName} berhasil diperbarui.")
            ->duration(5000);
    }

    protected function getDeletedNotification(): ?Notification
    {
        $modelLabel = $this->getModelLabel();
        $recordName = $this->getRecordName();

        return Notification::make()
            ->success()
            ->color('danger')
            ->title("Data {$modelLabel} dihapus")
            ->body("{$modelLabel} {$recordName} telah dihapus.")
            ->duration(5000);
    }

    protected function getRestoredNotification(): ?Notification
    {
        $modelLabel = $this->getModelLabel();
        $recordName = $this->getRecordName();

        return Notification::make()
            ->success()
            ->title("Data {$modelLabel} dipulihkan")
            ->body("{$modelLabel} {$recordName} telah dipulihkan.")
            ->duration(5000);
    }

    protected function getForceDeletedNotification(): ?Notification
    {
        $modelLabel = $this->getModelLabel();
        $recordName = $this->getRecordName();

        return Notification::make()
            ->success()
            ->title("Data {$modelLabel} dihapus permanen")
            ->body("{$modelLabel} {$recordName} telah dihapus secara permanen.")
            ->duration(5000);
    }

    // Helper untuk header actions
    protected function getHeaderActionsNotifications(): array
    {
        $modelLabel = $this->getModelLabel();
        $recordName = $this->getRecordName();

        return [
            'delete' => Notification::make()
                ->success()
                ->title("Data {$modelLabel} dihapus")
                ->body("{$modelLabel} {$recordName} telah dihapus.")
                ->duration(5000),

            'restore' => Notification::make()
                ->success()
                ->title("Data {$modelLabel} dipulihkan")
                ->body("{$modelLabel} {$recordName} telah dipulihkan.")
                ->duration(5000),

            'forceDelete' => Notification::make()
                ->success()
                ->title("Data {$modelLabel} dihapus permanen")
                ->body("{$modelLabel} {$recordName} telah dihapus secara permanen.")
                ->duration(5000),
        ];
    }
}