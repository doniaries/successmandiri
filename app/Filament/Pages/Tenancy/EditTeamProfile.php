<?php

namespace App\Filament\Pages\Tenancy;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\EditTenantProfile;

class EditTeamProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return 'Team profile';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('slug')
                    ->hidden()
                    ->unique(ignoreRecord: true),
                TextInput::make('saldo')
                    ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 0)
                    ->prefix('Rp.'),
                TextInput::make('alamat'),
                TextInput::make('telepon'),
                TextInput::make('email')
                    ->email(),
                TextInput::make('pimpinan')
                    ->label('Nama Pimpinan'),
                TextInput::make('npwp')
                    ->label('Nomor Pokok Wajib Pajak (NPWP)')
                    ->numeric(),
            ]);
    }
}
