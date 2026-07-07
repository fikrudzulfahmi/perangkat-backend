<?php

use Illuminate\Support\Facades\Route;

Route::get('/{any}', function () {
    return view('welcome'); // Sesuaikan dengan nama file blade utama Anda (tanpa .blade.php)
})->where('any', '.*');
