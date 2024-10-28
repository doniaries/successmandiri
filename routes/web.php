<?php

use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

//langsung ke halaman login
Route::get('/', function () {
    return redirect('/admin');
});