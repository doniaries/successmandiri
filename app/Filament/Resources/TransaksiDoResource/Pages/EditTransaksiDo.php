<?php

namespace App\Filament\Resources\TransaksiDoResource\Pages;

use App\Filament\Resources\TransaksiDoResource;
use App\Models\Penjual;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Filament\Actions;

class EditTransaksiDo extends EditRecord
{
    protected static string $resource = TransaksiDoResource::class;

    // Set initial form data
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Pastikan data hutang yang ditampilkan adalah hutang_awal saat transaksi
        $data['hutang_awal'] = $this->record->hutang_awal;

        // Set info hutang terkini dari penjual
        if ($this->record->penjual_id) {
            $data['info_hutang_terkini'] = $this->record->penjual->hutang ?? 0;
        }

        return $data;
    }

    // Validate data before save
    protected function mutateFormDataBeforeSave(array $data): array
    {
        try {
            // Format angka
            $formatNumber = fn($value) => is_numeric($value) ?
                (int)$value : (int)str_replace(['Rp', '.', ',', ' '], '', $value);

            // Format numeric fields
            $numericFields = [
                'tonase',
                'harga_satuan',
                'total',
                'upah_bongkar',
                'biaya_lain',
                'pembayaran_hutang',
                'sisa_hutang_penjual',
                'sisa_bayar'
            ];

            foreach ($numericFields as $field) {
                if (isset($data[$field])) {
                    $data[$field] = $formatNumber($data[$field]);
                }
            }

            // Validasi pembayaran hutang
            $hutangAwal = $this->record->hutang_awal;
            if (($data['pembayaran_hutang'] ?? 0) > $hutangAwal) {
                throw new \Exception(
                    "Pembayaran hutang melebihi hutang awal transaksi\n" .
                        "Hutang awal: Rp " . number_format($hutangAwal, 0, ',', '.') . "\n" .
                        "Pembayaran: Rp " . number_format($data['pembayaran_hutang'], 0, ',', '.')
                );
            }

            // Hitung ulang total dan sisa
            $data['total'] = $data['tonase'] * $data['harga_satuan'];
            $data['sisa_hutang_penjual'] = max(0, $hutangAwal - ($data['pembayaran_hutang'] ?? 0));
            $data['sisa_bayar'] = max(0, $data['total'] - ($data['upah_bongkar'] ?? 0) - ($data['biaya_lain'] ?? 0) - ($data['pembayaran_hutang'] ?? 0));

            return $data;
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Validasi Data')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();

            throw $e;
        }
    }

    // Handle after record is saved
    protected function afterSave(): void
    {
        $record = $this->record;
        $changes = $record->getDirty();

        try {
            DB::beginTransaction();

            // Jika ada perubahan pembayaran hutang
            if (isset($changes['pembayaran_hutang'])) {
                $oldPayment = $record->getOriginal('pembayaran_hutang');
                $newPayment = $record->pembayaran_hutang;

                if ($oldPayment != $newPayment) {
                    // Update hutang penjual
                    $penjual = $record->penjual;
                    if ($penjual) {
                        $selisihPembayaran = $newPayment - $oldPayment;
                        if ($selisihPembayaran > 0) {
                            $penjual->decrement('hutang', $selisihPembayaran);
                        } else {
                            $penjual->increment('hutang', abs($selisihPembayaran));
                        }
                    }

                    // Notifikasi perubahan hutang
                    Notification::make()
                        ->title('Pembayaran Hutang Diperbarui')
                        ->body(
                            "DO #{$record->nomor}\n" .
                                "Pembayaran sebelumnya: Rp " . number_format($oldPayment, 0, ',', '.') . "\n" .
                                "Pembayaran baru: Rp " . number_format($newPayment, 0, ',', '.') . "\n" .
                                "Sisa hutang: Rp " . number_format($record->sisa_hutang_penjual, 0, ',', '.')
                        )
                        ->success()
                        ->persistent()
                        ->send();
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            Notification::make()
                ->title('Error')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->persistent()
                ->send();

            throw $e;
        }
    }

    // Configure header actions
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function () {
                    // Validasi sebelum hapus
                    if ($this->record->pembayaran_hutang > 0) {
                        $penjual = $this->record->penjual;
                        if ($penjual) {
                            // Kembalikan hutang
                            $penjual->increment('hutang', $this->record->pembayaran_hutang);
                        }
                    }
                }),
        ];
    }

    // Redirect after save
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
