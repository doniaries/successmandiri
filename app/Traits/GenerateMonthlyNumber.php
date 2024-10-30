<?php

namespace App\Traits;

trait GenerateMonthlyNumber
{
    public static function generateMonthlyNumber()
    {
        $today = now();
        $month = $today->format('m');
        $year = $today->format('Y');

        // Ambil nomor terakhir untuk bulan ini
        $lastNumber = static::whereYear('tanggal', $year)
            ->whereMonth('tanggal', $month)
            ->withTrashed() // Termasuk data yang sudah dihapus
            ->max('nomor');

        if (!$lastNumber) {
            // Jika belum ada nomor untuk bulan ini
            $newNumber = 1;
        } else {
            // Ekstrak nomor dari format DO-YYYYMM-XXXX
            preg_match('/DO-\d{6}-(\d+)/', $lastNumber, $matches);
            $newNumber = isset($matches[1]) ? ((int)$matches[1] + 1) : 1;
        }

        // Format: DO-YYYYMM-XXXX
        return sprintf(
            'DO-%s%s-%s',
            $year,
            $month,
            str_pad($newNumber, 4, '0', STR_PAD_LEFT)
        );
    }
}
