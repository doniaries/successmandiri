<?php

namespace App\Filament\Resources\TransaksiDoResource\Pages;

use App\Filament\Resources\TransaksiDoResource;
use App\Filament\Resources\TransaksiDoResource\Widgets\TransaksiDoStatWidget;
use App\Filament\Widgets\TransaksiDOWidget;
use App\Filament\Widgets\TransaksiWidget;
use App\Models\Operasional; // Tambahkan ini
use Illuminate\Support\Facades\DB; // Tambahkan ini
use Filament\Actions;  // Ubah import ini
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListTransaksiDos extends ListRecords
{
    protected static string $resource = TransaksiDoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),  // Ubah ini
            Action::make('syncOperasional')
                ->label('Sync ke Operasional')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('Proses ini akan mensinkronkan semua data Transaksi DO yang belum tercatat ke tabel Operasional.')
                ->action(function () {
                    DB::beginTransaction();
                    try {
                        $count = 0;
                        // Ambil semua transaksi DO yang belum tercatat di operasional
                        $transaksiDos = $this->getModel()::whereDoesntHave('operasional')->get();

                        foreach ($transaksiDos as $transaksiDo) {
                            // 1. Jika cara bayar transfer
                            if ($transaksiDo->cara_bayar === 'Transfer') {
                                Operasional::create([
                                    'tanggal' => $transaksiDo->tanggal,
                                    'operasional' => 'pemasukan',
                                    'kategori_id' => 7, // Lain-lain
                                    'tipe_nama' => 'penjual',
                                    'penjual_id' => $transaksiDo->penjual_id,
                                    'nominal' => $transaksiDo->sisa_bayar,
                                    'keterangan' => "Transfer masuk DO #{$transaksiDo->nomor}",
                                ]);

                                Operasional::create([
                                    'tanggal' => $transaksiDo->tanggal,
                                    'operasional' => 'pengeluaran',
                                    'kategori_id' => 2, // Uang Jalan
                                    'tipe_nama' => 'penjual',
                                    'penjual_id' => $transaksiDo->penjual_id,
                                    'nominal' => $transaksiDo->total,
                                    'keterangan' => "Transaksi DO #{$transaksiDo->nomor}",
                                ]);
                                $count += 2;
                            } else {
                                Operasional::create([
                                    'tanggal' => $transaksiDo->tanggal,
                                    'operasional' => 'pengeluaran',
                                    'kategori_id' => 2, // Uang Jalan
                                    'tipe_nama' => 'penjual',
                                    'penjual_id' => $transaksiDo->penjual_id,
                                    'nominal' => $transaksiDo->sisa_bayar,
                                    'keterangan' => "Pembayaran DO #{$transaksiDo->nomor} via {$transaksiDo->cara_bayar}",
                                ]);
                                $count++;
                            }

                            // 2. Jika ada pembayaran hutang
                            if ($transaksiDo->bayar_hutang > 0) {
                                Operasional::create([
                                    'tanggal' => $transaksiDo->tanggal,
                                    'operasional' => 'pemasukan',
                                    'kategori_id' => 6, // Bayar Hutang
                                    'tipe_nama' => 'penjual',
                                    'penjual_id' => $transaksiDo->penjual_id,
                                    'nominal' => $transaksiDo->bayar_hutang,
                                    'keterangan' => "Pembayaran Hutang DO #{$transaksiDo->nomor}",
                                ]);
                                $count++;
                            }
                        }

                        DB::commit();

                        Notification::make()
                            ->title('Sinkronisasi Berhasil')
                            ->success()
                            ->body("{$count} data operasional berhasil dibuat.")
                            ->send();
                    } catch (\Exception $e) {
                        DB::rollback();
                        Notification::make()
                            ->title('Sinkronisasi Gagal')
                            ->danger()
                            ->body('Error: ' . $e->getMessage())
                            ->send();
                    }
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TransaksiDoStatWidget::class,

        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            //
        ];
    }
}
