<?php

namespace App\Traits;

use App\Models\{LaporanKeuangan, Perusahaan};
use Illuminate\Support\Facades\{DB, Log};

trait LaporanKeuanganTrait
{
    /**
     * Hitung total transaksi
     */
    protected function hitungTotal(): float
    {
        return $this->tonase * $this->harga_satuan;
    }

    /**
     * Hitung total pemasukan dari transaksi
     */
    protected function hitungTotalPemasukan(): float
    {
        return $this->upah_bongkar + $this->biaya_lain + $this->pembayaran_hutang;
    }

    /**
     * Hitung sisa pembayaran
     */
    protected function hitungSisaBayar(): float
    {
        $total = $this->hitungTotal();
        $totalPemasukan = $this->hitungTotalPemasukan();
        return max(0, $total - $totalPemasukan);
    }

    /**
     * Hitung sisa hutang
     */
    protected function hitungSisaHutang(): float
    {
        return max(0, $this->hutang_awal - $this->pembayaran_hutang);
    }

    /**
     * Handle pembayaran hutang
     */
    protected function handlePembayaranHutang(float $jumlahBayar): void
    {
        if (!$this->penjual) {
            throw new \Exception('Data penjual tidak ditemukan');
        }

        // Validasi pembayaran tidak melebihi hutang
        if ($jumlahBayar > $this->hutang_awal) {
            throw new \Exception(
                "Pembayaran hutang Rp " . $this->formatCurrency($jumlahBayar) .
                    " melebihi hutang Rp " . $this->formatCurrency($this->hutang_awal)
            );
        }

        DB::beginTransaction();
        try {
            // Update hutang penjual
            $this->penjual->decrement('hutang', $jumlahBayar);

            // Catat riwayat pembayaran hutang
            $this->penjual->riwayatHutang()->create([
                'tanggal' => $this->tanggal,
                'nominal' => $jumlahBayar,
                'tipe' => 'pembayaran',
                'keterangan' => "Pembayaran hutang via {$this->nomor}"
            ]);

            // Update data transaksi
            $this->pembayaran_hutang = $jumlahBayar;
            $this->sisa_hutang_penjual = $this->hitungSisaHutang();
            $this->sisa_bayar = $this->hitungSisaBayar();

            DB::commit();

            $this->logTransactionActivity('pembayaran_hutang', [
                'nomor_referensi' => $this->nomor,
                'nominal' => $jumlahBayar,
                'sisa_hutang' => $this->sisa_hutang_penjual
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Validasi saldo untuk transaksi tunai
     */
    protected function validateSaldoTunai(float $nominal): void
    {
        if ($this->cara_bayar === 'Tunai') {
            $saldo = $this->getSaldoPerusahaan();
            if ($nominal > $saldo) {
                throw new \Exception(
                    "Saldo tidak mencukupi untuk pembayaran tunai.\n" .
                        "Saldo: Rp " . $this->formatCurrency($saldo) . "\n" .
                        "Dibutuhkan: Rp " . $this->formatCurrency($nominal)
                );
            }
        }
    }

    /**
     * Update saldo perusahaan
     */
    protected function updateSaldoPerusahaan(): void
    {
        $team = $this->team;
        if (!$team) {
            throw new \Exception('Data Perusahaan tidak ditemukan');
        }

        DB::beginTransaction();
        try {
            // 1. Handle pemasukan tunai
            $totalPemasukan = 0;
            if ($this->upah_bongkar > 0) {
                $totalPemasukan += $this->upah_bongkar;
            }
            if ($this->biaya_lain > 0) {
                $totalPemasukan += $this->biaya_lain;
            }
            if ($this->pembayaran_hutang > 0) {
                $totalPemasukan += $this->pembayaran_hutang;
            }

            // Update saldo sekali untuk total pemasukan
            if ($totalPemasukan > 0) {
                $this->updateSaldo('increment', $totalPemasukan);
                Log::info('Saldo bertambah:', [
                    'nominal' => $totalPemasukan,
                    'keterangan' => "Pemasukan dari {$this->nomor}"
                ]);
            }

            // 2. Handle pengeluaran tunai
            if ($this->cara_bayar === 'Tunai' && $this->sisa_bayar > 0) {
                $this->validateSaldoTunai($this->sisa_bayar);
                $this->updateSaldo('decrement', $this->sisa_bayar);
                Log::info('Saldo berkurang:', [
                    'nominal' => $this->sisa_bayar,
                    'keterangan' => "Pembayaran tunai {$this->nomor}"
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update saldo
     */
    private function updateSaldo(string $operation, float $amount): void
    {
        $perusahaan = Perusahaan::first();
        if ($operation === 'increment') {
            $perusahaan->increment('saldo', $amount);
        } else {
            $perusahaan->decrement('saldo', $amount);
        }
    }

    /**
     * Format currency
     */
    protected function formatCurrency(float $nominal): string
    {
        return number_format($nominal, 0, ',', '.');
    }

    /**
     * Check transaksi tunai
     */
    protected function isTransaksiTunai(): bool
    {
        return $this->cara_bayar === 'Tunai';
    }

    /**
     * Log activity
     */
    protected function logTransactionActivity(string $action, array $data): void
    {
        Log::info("Transaction {$action}:", array_merge(
            ['nomor' => $this->nomor ?? '-'],
            $data
        ));
    }
}
