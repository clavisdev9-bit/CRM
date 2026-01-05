<?php

use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });
// Route::get('/{any}', function () {
//     return view('app'); // Vue handle semua routing
// })->where('any', '.*');

Route::get('/{any}', function () {
    return view('app'); // Vue handle routing
})->where('any', '^(?!api).*$');
