<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Team;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\RegisterTenant;

class RegisterTeam extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Register team';
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

    protected function handleRegistration(array $data): Team
    {
        $team = Team::create($data);

        $team->members()->attach(auth()->user());

        return $team;
    }
}
