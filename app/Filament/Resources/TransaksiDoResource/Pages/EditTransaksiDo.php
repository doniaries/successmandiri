<?php

namespace App\Filament\Resources\TransaksiDoResource\Pages;

use App\Filament\Resources\TransaksiDoResource;
use Filament\Actions;
use App\Models\Penjual;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditTransaksiDo extends EditRecord
{
    protected static string $resource = TransaksiDoResource::class;

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

    protected function afterSave(): void
    {
        $record = $this->record;

        if ($record->bayar_hutang > 0) {
            $penjual = Penjual::find($record->penjual_id);
            if ($penjual) {
                // Ambil nilai asli dari database
                $originalBayarHutang = (float) $record->getOriginal('bayar_hutang') ?? 0;
                $hutangAwal = (float) $record->hutang;  // Hutang yang tersimpan di transaksi

                \Log::info('Debug Perhitungan Hutang Edit', [
                    'hutang_awal' => $hutangAwal,
                    'original_bayar' => $originalBayarHutang,
                    'bayar_baru' => $record->bayar_hutang
                ]);

                // Kembalikan hutang penjual ke nilai sebelum pembayaran
                $penjual->hutang += $originalBayarHutang;

                // Hitung sisa hutang dengan pembayaran baru
                $sisaHutang = $penjual->hutang - (float) $record->bayar_hutang;

                // Update hutang penjual
                $penjual->update([
                    'hutang' => $sisaHutang
                ]);

                // Update sisa hutang di transaksi
                $record->update([
                    'sisa_hutang' => $sisaHutang
                ]);

                Notification::make()
                    ->title('Hutang penjual berhasil diupdate')
                    ->body(
                        "Hutang awal: Rp " . number_format($hutangAwal, 0, ',', '.') . "\n" .
                            "Dibayar: Rp " . number_format($record->bayar_hutang, 0, ',', '.') . "\n" .
                            "Sisa hutang: Rp " . number_format($sisaHutang, 0, ',', '.')
                    )
                    ->success()
                    ->send();
            }
        }
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
    protected function afterFill(): void
    {
        $record = $this->record;

        // Set nilai awal
        $this->data['hutang'] = $record->hutang;
        $this->data['bayar_hutang'] = $record->bayar_hutang;
        $this->data['sisa_hutang'] = (int)($record->hutang - $record->bayar_hutang);
        $this->data['sisa_bayar'] = (int)($record->total - $record->upah_bongkar - $record->bayar_hutang);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Pastikan nilai numerik diformat dengan benar
        $data['total'] = (int)str_replace(['Rp', '.', ','], '', $data['total'] ?? 0);
        $data['upah_bongkar'] = (int)str_replace(['Rp', '.', ','], '', $data['upah_bongkar'] ?? 0);
        $data['hutang'] = (int)str_replace(['Rp', '.', ','], '', $data['hutang'] ?? 0);
        $data['bayar_hutang'] = (int)str_replace(['Rp', '.', ','], '', $data['bayar_hutang'] ?? 0);
        $data['sisa_hutang'] = (int)($data['hutang'] - $data['bayar_hutang']);
        $data['sisa_bayar'] = (int)($data['total'] - $data['upah_bongkar'] - $data['bayar_hutang']);

        return $data;
    }
}
