<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $site_name;
    public ?string $logo_path;
    public string $nama;
    public ?string $favicon_path;
    public string $tema_warna;
    public ?string $alamat;
    public ?string $kabupaten;
    public ?string $provinsi;
    public ?string $kode_pos;
    public ?string $telepon;
    public ?string $email;
    public ?string $nama_pimpinan;
    public ?string $hp_pimpinan;
    public ?int $kasir_id;
    public ?string $npwp;
    public float $saldo;
    public bool $is_active;
    public ?string $keterangan;

    public static function group(): string
    {
        return 'general';
    }

    public static function defaultSettings(): array
    {
        return [
            'site_name' => 'Success App',
            'nama' => 'Success App',
            'logo_path' => null,
            'favicon_path' => null,
            'tema_warna' => 'amber',
            'alamat' => null,
            'kabupaten' => null,
            'provinsi' => null,
            'kode_pos' => null,
            'telepon' => null,
            'email' => null,
            'nama_pimpinan' => null,
            'no_hp' => null,
            'kasir_id' => null,
            'npwp' => null,
            'saldo' => 0,
            'is_active' => true,
            'keterangan' => null,
        ];
    }
}
