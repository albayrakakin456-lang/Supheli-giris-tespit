<?php

use App\Http\Controllers\AlarmController;
use App\Http\Controllers\GirisController;
use Illuminate\Support\Facades\Route;

// Karşılama sayfası: herkes görebilir
Route::view('/', 'karsilama');

// Keycloak giriş/çıkış akışı
Route::get('/giris', [GirisController::class, 'yonlendir']);
Route::get('/auth/callback', [GirisController::class, 'geriDon']);  // Keycloak'taki "Valid redirect URI" ile aynı
Route::post('/cikis', [GirisController::class, 'cikis']);           // POST: başka bir site seni linkle çıkış yaptıramasın

// Sadece güvenlik görevlisi ve yönetici görebilir
Route::get('/alarmlar', [AlarmController::class, 'index'])
    ->middleware('rol:guvenlik_gorevlisi,yonetici');
