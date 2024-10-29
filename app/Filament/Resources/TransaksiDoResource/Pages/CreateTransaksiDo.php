<?php

namespace App\Filament\Resources\TransaksiDoResource\Pages;

use App\Filament\Resources\TransaksiDoResource;
use App\Models\Penjual;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateTransaksiDo extends CreateRecord
{
    protected static string $resource = TransaksiDoResource::class;

    // CreateTransaksiDo.php

    protected function afterCreate(): void
    {
        $transaksiDo = $this->record;

        if ($transaksiDo->bayar_hutang > 0) {
            $penjual = Penjual::find($transaksiDo->penjual_id);

            if ($penjual) {
                // Cast semua nilai ke float
                $hutangAwal = (float) $penjual->hutang;
                $bayarHutang = (float) $transaksiDo->bayar_hutang;

                // Hitung sisa hutang
                $sisaHutang = $hutangAwal - $bayarHutang;

                // Update penjual dengan sisa hutang
                $penjual->update([
                    'hutang' => $sisaHutang
                ]);

                // Update transaksi
                $transaksiDo->update([
                    'hutang' => $hutangAwal,
                    'sisa_hutang' => $sisaHutang
                ]);

                Notification::make()
                    ->title('Hutang Penjual Diperbarui')
                    ->body(
                        "Hutang awal: Rp " . number_format($hutangAwal, 0, ',', '.') . "\n" .
                            "Dibayar: Rp " . number_format($bayarHutang, 0, ',', '.') . "\n" .
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
}
