<?php

use App\Http\Controllers\JuegoController;
use Illuminate\Support\Facades\Route;

// Menú
Route::get('/', [JuegoController::class, 'portada'])->name('portada');
Route::get('/unirse', [JuegoController::class, 'formUnirse'])->name('unirse');
Route::post('/unirse', [JuegoController::class, 'unirse']);
Route::get('/perfil', [JuegoController::class, 'formPerfil'])->name('perfil');
Route::post('/perfil', [JuegoController::class, 'perfil']);
Route::post('/salir', [JuegoController::class, 'salir'])->name('salir');

// Jugar
Route::get('/jugar', [JuegoController::class, 'jugar'])->name('jugar');
Route::post('/atacar', [JuegoController::class, 'atacar'])->name('atacar');
Route::post('/mover', [JuegoController::class, 'mover'])->name('mover');
Route::post('/escudo', [JuegoController::class, 'escudo'])->name('escudo');
Route::post('/especial', [JuegoController::class, 'especial'])->name('especial');

// Pantalla grande
Route::get('/pantalla', [JuegoController::class, 'pantalla'])->name('pantalla');
Route::post('/reiniciar', [JuegoController::class, 'reiniciar'])->name('reiniciar');
Route::post('/nueva-partida', [JuegoController::class, 'nuevaPartida'])->name('nueva-partida');
