<?php

namespace App\Filament\Resources\TransaksiDoResource\Pages;

use App\Filament\Resources\TransaksiDoResource;
use App\Models\{Penjual, TransaksiDo};
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EditTransaksiDo extends EditRecord
{
    protected static string $resource = TransaksiDoResource::class;

    /**
     * Method yang dijalankan setelah form terisi data record
     * Untuk mengatur nilai awal form ketika edit
     */
    protected function afterFill(): void
    {
        try {
            $record = $this->record;

            // Set nilai-nilai awal form dari data record
            $this->data['hutang'] = $record->hutang;
            $this->data['total'] = $record->tonase * $record->harga_satuan;
            $this->data['bayar_hutang'] = $record->bayar_hutang;
            $this->data['sisa_hutang'] = $record->hutang - $record->bayar_hutang;
            $this->data['sisa_bayar'] = $record->total - $record->upah_bongkar - $record->biaya_lain - $record->bayar_hutang;

            Log::info('TransaksiDO afterFill Success', [
                'id' => $record->id,
                'nomor' => $record->nomor,
                'data' => $this->data
            ]);
        } catch (\Exception $e) {
            Log::error('TransaksiDO afterFill Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Notification::make()
                ->title('Error saat mengisi form')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Method untuk memformat data sebelum disimpan
     * Memastikan semua perhitungan dan format angka benar
     *
     * @param array $data
     * @return array
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        try {
            // Format fungsi untuk konversi string currency ke integer
            $formatNumber = function ($value) {
                if (empty($value)) return 0;
                if (is_numeric($value)) return (int)$value;
                return (int)str_replace(['Rp', '.', ',', ' '], '', $value);
            };

            // Format semua field numeric
            $numericFields = [
                'tonase',
                'harga_satuan',
                'upah_bongkar',
                'biaya_lain',
                'hutang',
                'bayar_hutang'
            ];

            foreach ($numericFields as $field) {
                $data[$field] = $formatNumber($data[$field]);
            }

            // Perhitungan nilai
            $data['total'] = $data['tonase'] * $data['harga_satuan'];
            $data['sisa_hutang'] = max(0, $data['hutang'] - $data['bayar_hutang']);
            $data['sisa_bayar'] = max(0, $data['total'] - $data['upah_bongkar'] - $data['biaya_lain'] - $data['bayar_hutang']);

            Log::info('TransaksiDO mutateFormDataBeforeSave', [
                'formatted_data' => $data
            ]);

            return $data;
        } catch (\Exception $e) {
            Log::error('TransaksiDO mutateFormDataBeforeSave Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Method yang dijalankan setelah data berhasil disimpan
     * Menampilkan notifikasi perubahan data dan menghandle update hutang penjual
     */
    protected function afterSave(): void
    {
        try {
            DB::beginTransaction();

            $record = $this->record;
            $changes = $record->getDirty();
            $old = $record->getOriginal();

            // Siapkan pesan perubahan
            $messages = [];

            // Cek perubahan nilai-nilai penting
            $checkFields = [
                'tonase' => 'Tonase',
                'harga_satuan' => 'Harga Satuan',
                'total' => 'Total',
                'upah_bongkar' => 'Upah Bongkar',
                'biaya_lain' => 'Biaya Lain',
                'bayar_hutang' => 'Bayar Hutang',
                'sisa_bayar' => 'Sisa Bayar',
                'cara_bayar' => 'Cara Bayar',
                'status_bayar' => 'Status Bayar'
            ];

            foreach ($checkFields as $field => $label) {
                if (isset($changes[$field])) {
                    // Format angka untuk field numerik
                    if (in_array($field, ['tonase', 'harga_satuan', 'total', 'upah_bongkar', 'biaya_lain', 'bayar_hutang', 'sisa_bayar'])) {
                        $messages[] = "{$label}:\nDari: Rp " . number_format($old[$field] ?? 0, 0, ',', '.') .
                            "\nMenjadi: Rp " . number_format($record->$field, 0, ',', '.');
                    } else {
                        $messages[] = "{$label}:\nDari: {$old[$field]}\nMenjadi: {$record->$field}";
                    }
                }
            }

            // Jika ada perubahan, tampilkan notifikasi perubahan
            if (count($messages) > 0) {
                Notification::make()
                    ->title('Data Transaksi DO berhasil diupdate')
                    ->body(implode("\n\n", $messages))
                    ->success()
                    ->send();
            }

            // Proses update hutang penjual jika ada perubahan bayar_hutang
            if (isset($changes['bayar_hutang'])) {
                $penjual = Penjual::find($record->penjual_id);

                if ($penjual) {
                    $originalBayarHutang = (float) $old['bayar_hutang'] ?? 0;
                    $penjual->hutang += $originalBayarHutang;
                    $sisaHutang = max(0, $penjual->hutang - $record->bayar_hutang);

                    // Update hutang penjual
                    $penjual->update(['hutang' => $sisaHutang]);

                    // Update sisa hutang di transaksi
                    $record->update(['sisa_hutang' => $sisaHutang]);

                    Notification::make()
                        ->title('Hutang Penjual berhasil diupdate')
                        ->body(
                            "Hutang awal: Rp " . number_format($penjual->hutang + $originalBayarHutang, 0, ',', '.') . "\n" .
                                "Pembayaran: Rp " . number_format($record->bayar_hutang, 0, ',', '.') . "\n" .
                                "Sisa hutang: Rp " . number_format($sisaHutang, 0, ',', '.')
                        )
                        ->success()
                        ->persistent()
                        ->send();

                    Log::info('TransaksiDO Hutang Updated', [
                        'penjual_id' => $penjual->id,
                        'hutang_awal' => $penjual->hutang + $originalBayarHutang,
                        'bayar_hutang' => $record->bayar_hutang,
                        'sisa_hutang' => $sisaHutang
                    ]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('TransaksiDO afterSave Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Notification::make()
                ->title('Error saat menyimpan perubahan')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->persistent()
                ->send();
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
            Actions\DeleteAction::make()
                ->before(function (TransaksiDo $record) {
                    // Kembalikan hutang penjual jika ada pembayaran hutang
                    if ($record->bayar_hutang > 0) {
                        $penjual = Penjual::find($record->penjual_id);
                        if ($penjual) {
                            $penjual->increment('hutang', $record->bayar_hutang);
                        }
                    }
                }),
        ];
    }
}
