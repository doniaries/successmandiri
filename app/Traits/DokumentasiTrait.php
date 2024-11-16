<?php

namespace App\Traits;

trait DokumentasiTrait
{
    /**
     * Generate nomor transaksi
     */
    protected function generateNomorTransaksi(string $prefix, string $format = 'Ymd'): string
    {
        $date = date($format);
        $count = static::whereDate('created_at', today())->count() + 1;
        return sprintf("%s-%s-%04d", $prefix, $date, $count);
    }

    /**
     * Generate kode referensi
     */
    protected function generateKodeReferensi(string $prefix, int $id): string
    {
        return sprintf("%s-%04d", $prefix, $id);
    }

    /**
     * Format tanggal Indonesia
     */
    protected function formatTanggalIndo($date): string
    {
        return $date->format('d/m/Y H:i');
    }

    /**
     * Generate text dokumentasi
     */
    protected function generateDokumentasi(array $data): string
    {
        $template = "%s\n%s\n\nDetail Transaksi:\n%s\n\nKeterangan: %s";

        return sprintf(
            $template,
            $data['nomor'] ?? '-',
            $this->formatTanggalIndo($data['tanggal']),
            $this->generateDetailTransaksi($data),
            $data['keterangan'] ?? '-'
        );
    }

    /**
     * Generate detail transaksi untuk dokumentasi
     */
    private function generateDetailTransaksi(array $data): string
    {
        $details = [];

        if (!empty($data['jenis_transaksi'])) {
            $details[] = "Jenis: {$data['jenis_transaksi']}";
        }

        if (!empty($data['kategori'])) {
            $details[] = "Kategori: {$data['kategori']}";
        }

        if (!empty($data['nominal'])) {
            $details[] = "Nominal: " . $this->formatCurrency($data['nominal']);
        }

        if (!empty($data['cara_pembayaran'])) {
            $details[] = "Cara Bayar: {$data['cara_pembayaran']}";
        }

        return implode("\n", $details);
    }
}
