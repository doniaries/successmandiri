<?php

namespace App\Filament\Pages\Settings;

use App\Settings\GeneralSettings;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Pages\SettingsPage;
use Filament\Forms\Form;

class ManageSettings extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'System';
    protected static ?string $title = 'Pengaturan';
    protected static ?string $navigationLabel = 'Pengaturan';
    protected static ?int $navigationSort = 1;
    protected static ?string $slug = 'settings';

    protected static string $settings = GeneralSettings::class;

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Aplikasi')
                ->description('Pengaturan umum aplikasi')
                ->schema([
                    TextInput::make('site_name')
                        ->label('Nama Aplikasi')
                        ->required(),
                    TextInput::make('nama_perusahaan')
                        ->label('Nama Perusahaan')
                        ->required(),
                    TextInput::make('kode_perusahaan')
                        ->label('Kode Perusahaan')
                        ->required(),
                    FileUpload::make('logo_path')
                        ->label('Logo')
                        ->image()
                        ->directory('app-assets'),
                    FileUpload::make('favicon_path')
                        ->label('Favicon')
                        ->image()
                        ->directory('app-assets'),
                    Select::make('tema_warna')
                        ->label('Tema Warna')
                        ->options([
                            'slate' => 'Slate',
                            'gray' => 'Gray',
                            'zinc' => 'Zinc',
                            'neutral' => 'Neutral',
                            'stone' => 'Stone',
                            'red' => 'Red',
                            'orange' => 'Orange',
                            'amber' => 'Amber',
                            'yellow' => 'Yellow',
                            'lime' => 'Lime',
                            'green' => 'Green',
                            'emerald' => 'Emerald',
                            'teal' => 'Teal',
                            'cyan' => 'Cyan',
                            'sky' => 'Sky',
                            'blue' => 'Blue',
                            'indigo' => 'Indigo',
                            'violet' => 'Violet',
                            'purple' => 'Purple',
                            'fuchsia' => 'Fuchsia',
                            'pink' => 'Pink',
                            'rose' => 'Rose',
                        ])
                        ->default('amber'),
                ])->columns(2),

            Section::make('Informasi Perusahaan')
                ->schema([
                    Textarea::make('alamat')
                        ->label('Alamat'),
                    TextInput::make('kabupaten')
                        ->label('Kabupaten'),
                    TextInput::make('provinsi')
                        ->label('Provinsi'),
                    TextInput::make('kode_pos')
                        ->label('Kode Pos'),
                    TextInput::make('telepon')
                        ->label('Telepon')
                        ->tel(),
                    TextInput::make('email')
                        ->label('Email')
                        ->email(),
                ])->columns(2),

            Section::make('Pimpinan & Legal')
                ->schema([
                    TextInput::make('nama_pimpinan')
                        ->label('Nama Pimpinan'),
                    TextInput::make('no_hp')
                        ->label('HP Pimpinan')
                        ->tel(),
                    Select::make('kasir_id')
                        ->label('Kasir')
                        ->relationship('users', 'name'),
                    TextInput::make('npwp')
                        ->label('NPWP'),
                ])->columns(2),

            Section::make('Status')
                ->schema([
                    TextInput::make('saldo')
                        ->label('Saldo')
                        ->disabled()
                        ->numeric()
                        ->prefix('Rp'),
                    Toggle::make('is_active')
                        ->label('Status Aktif'),
                    Textarea::make('keterangan')
                        ->label('Keterangan'),
                ])->columns(2),
        ]);
    }
}
