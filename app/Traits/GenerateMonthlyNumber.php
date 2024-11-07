<?php

namespace App\Traits;

trait GenerateMonthlyNumber
{
    public static function generateMonthlyNumber()
    {
        $today = now();
        $day = $today->format('d');
        $month = $today->format('m');
        $year = $today->format('Y');

        // Ambil nomor terakhir untuk hari ini
        $lastNumber = static::whereDate('tanggal', $today->toDateString())
            ->withTrashed() // Termasuk data yang sudah dihapus
            ->max('nomor');

        if (!$lastNumber) {
            // Jika belum ada nomor untuk hari ini
            $newNumber = 1;
        } else {
            // Ekstrak nomor dari format DO-YYYYMMDD-XXXX
            preg_match('/DO-\d{8}-(\d+)/', $lastNumber, $matches);
            $newNumber = isset($matches[1]) ? ((int)$matches[1] + 1) : 1;
        }

        // Format: DO-YYYYMMDD-XXXX
        return sprintf(
            'DO-%s%s%s-%s',
            $year,
            $month,
            $day,
            str_pad($newNumber, 4, '0', STR_PAD_LEFT)
        );
    }
}
