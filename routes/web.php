<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // return view('welcome');
    return response()->json([
        'name' => 'API - Desconectando para Conectar',
        'status' => 'online',
        'version' => '1.0'
    ]);
});
