<?php

namespace App\Filament\Resources\TransaksiDoResource\Pages;

use App\Filament\Resources\TransaksiDoResource;
use App\Models\Penjual;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditTransaksiDo extends EditRecord
{
    protected static string $resource = TransaksiDoResource::class;

    /**
     * Method yang dijalankan setelah form terisi data record
     * Untuk mengatur nilai awal form ketika edit
     */
    protected function afterFill(): void
    {
        $record = $this->record;

        // Set nilai-nilai awal form dari data record
        $this->data['hutang'] = $record->hutang;
        $this->data['total'] = $record->tonase * $record->harga_satuan;
        $this->data['bayar_hutang'] = $record->bayar_hutang;
        $this->data['sisa_hutang'] = $record->hutang - $record->bayar_hutang;
        $this->data['sisa_bayar'] = $record->total - $record->upah_bongkar - $record->biaya_lain - $record->bayar_hutang;
    }

    /**
     * Method untuk memformat data sebelum disimpan
     * Memastikan semua perhitungan dan format angka benar
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Format fungsi untuk konversi string currency ke integer
        $formatNumber = fn($value) => (int)str_replace(['Rp', '.', ','], '', $value ?? 0);

        // Format semua field numeric
        $data['tonase'] = $formatNumber($data['tonase']);
        $data['harga_satuan'] = $formatNumber($data['harga_satuan']);
        $data['total'] = $data['tonase'] * $data['harga_satuan'];
        $data['upah_bongkar'] = $formatNumber($data['upah_bongkar']);
        $data['biaya_lain'] = $formatNumber($data['biaya_lain']);
        $data['hutang'] = $formatNumber($data['hutang']);
        $data['bayar_hutang'] = $formatNumber($data['bayar_hutang']);

        // Hitung sisa hutang dan sisa bayar
        $data['sisa_hutang'] = $data['hutang'] - $data['bayar_hutang'];
        $data['sisa_bayar'] = $data['total'] - $data['upah_bongkar'] - $data['biaya_lain'] - $data['bayar_hutang'];

        return $data;
    }

    /**
     * Method yang dijalankan setelah data berhasil disimpan
     * Menghandle update hutang penjual
     */
    protected function afterSave(): void
    {
        $record = $this->record;

        // Proses hanya jika ada pembayaran hutang
        if ($record->bayar_hutang > 0) {
            $penjual = Penjual::find($record->penjual_id);
            if ($penjual) {
                // Ambil nilai pembayaran sebelum diupdate
                $originalBayarHutang = (float) $record->getOriginal('bayar_hutang') ?? 0;

                // Kembalikan dulu hutang penjual ke nilai sebelum pembayaran
                $penjual->hutang += $originalBayarHutang;

                // Hitung sisa hutang dengan pembayaran baru
                $sisaHutang = $penjual->hutang - $record->bayar_hutang;

                // Update hutang penjual dan sisa hutang di transaksi
                $penjual->update(['hutang' => $sisaHutang]);
                $record->update(['sisa_hutang' => $sisaHutang]);

                // Tampilkan notifikasi sukses
                Notification::make()
                    ->title('Hutang penjual berhasil diupdate')
                    ->body(
                        "Hutang awal: Rp " . number_format($penjual->hutang + $originalBayarHutang, 0, ',', '.') . "\n" .
                            "Dibayar: Rp " . number_format($record->bayar_hutang, 0, ',', '.') . "\n" .
                            "Sisa hutang: Rp " . number_format($sisaHutang, 0, ',', '.')
                    )
                    ->success()
                    ->send();
            }
        }
    }

    /**
     * Redirect setelah simpan/update
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Tombol aksi di header
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
