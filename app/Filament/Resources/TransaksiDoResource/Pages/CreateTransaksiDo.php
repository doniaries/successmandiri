<?php

namespace App\Filament\Resources\TransaksiDoResource\Pages;

use App\Filament\Resources\TransaksiDoResource;
use App\Models\{Penjual};
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class CreateTransaksiDo extends CreateRecord
{
    protected static string $resource = TransaksiDoResource::class;

    // Validasi data sebelum disimpan
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        try {
            // Format angka dari input
            $formatNumber = fn($value) => is_numeric($value) ?
                (int)$value : (int)str_replace(['Rp', '.', ',', ' '], '', $value);

            // Format semua field numeric
            $numericFields = [
                'tonase',
                'harga_satuan',
                'total',
                'upah_bongkar',
                'biaya_lain',
                'hutang_awal',
                'pembayaran_hutang',
                'sisa_hutang_penjual',
                'sisa_bayar'
            ];

            foreach ($numericFields as $field) {
                if (isset($data[$field])) {
                    $data[$field] = $formatNumber($data[$field]);
                }
            }

            // Validasi hutang dan pembayaran
            if ($data['penjual_id']) {
                $penjual = Penjual::find($data['penjual_id']);
                if ($penjual) {
                    // Pastikan hutang_awal sesuai dengan hutang penjual saat ini
                    $data['hutang_awal'] = $penjual->hutang;

                    // Validasi pembayaran hutang
                    if (($data['pembayaran_hutang'] ?? 0) > $penjual->hutang) {
                        throw new \Exception(
                            "Pembayaran hutang melebihi hutang yang ada\n" .
                                "Hutang saat ini: Rp " . number_format($penjual->hutang, 0, ',', '.') . "\n" .
                                "Pembayaran: Rp " . number_format($data['pembayaran_hutang'], 0, ',', '.')
                        );
                    }
                }
            }

            // Hitung ulang total dan sisa
            $data['total'] = $data['tonase'] * $data['harga_satuan'];
            $data['sisa_hutang_penjual'] = max(0, $data['hutang_awal'] - ($data['pembayaran_hutang'] ?? 0));
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

    // Handle after record is created
    protected function afterCreate(): void
    {
        $record = $this->record;

        try {
            DB::beginTransaction();

            if ($record->pembayaran_hutang > 0) {
                $penjual = $record->penjual;
                if (!$penjual) {
                    throw new \Exception('Data penjual tidak ditemukan');
                }

                // // Catat riwayat hutang
                // RiwayatHutang::create([
                //     'tipe_entitas' => 'penjual',
                //     'entitas_id' => $penjual->id,
                //     'nominal' => $record->pembayaran_hutang,
                //     'jenis' => 'pengurangan',
                //     'hutang_sebelum' => $record->hutang_awal,
                //     'hutang_sesudah' => $record->sisa_hutang_penjual,
                //     'keterangan' => "Pembayaran hutang melalui DO #{$record->nomor}",
                //     'transaksi_do_id' => $record->id
                // ]);

                // Tampilkan notifikasi detail
                Notification::make()
                    ->title('Pembayaran Hutang Berhasil')
                    ->body(
                        "DO #{$record->nomor}\n" .
                            "Hutang awal: Rp " . number_format($record->hutang_awal, 0, ',', '.') . "\n" .
                            "Pembayaran: Rp " . number_format($record->pembayaran_hutang, 0, ',', '.') . "\n" .
                            "Sisa hutang: Rp " . number_format($record->sisa_hutang_penjual, 0, ',', '.')
                    )
                    ->success()
                    ->persistent()
                    ->send();
            }

            // Notifikasi transaksi berhasil
            Notification::make()
                ->title('Transaksi DO Berhasil')
                ->body(
                    "DO #{$record->nomor}\n" .
                        "Total: Rp " . number_format($record->total, 0, ',', '.') . "\n" .
                        "Sisa bayar: Rp " . number_format($record->sisa_bayar, 0, ',', '.')
                )
                ->success()
                ->send();

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

    // Redirect after successful creation
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
