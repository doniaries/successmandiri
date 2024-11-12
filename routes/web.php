<?php

use Illuminate\Support\Facades\Route;
use App\Filament\Pages\Settings\ManageSettings;

// Route::get('/', function () {
//     return view('welcome');
// });

//langsung ke halaman login
Route::get('/', function () {
    return redirect('/admin');
});


// Route::middleware([
//     'auth:sanctum',
//     config('jetstream.auth_session'),
//     'verified'
// ])->group(function () {
//     Route::get('/settings', ManageSettings::class)->name('filament.pages.settings');
// });