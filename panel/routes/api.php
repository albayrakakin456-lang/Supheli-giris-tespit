<?php

use App\Http\Controllers\Api\AlarmApiController;
use App\Http\Controllers\Api\OkutmaApiController;
use Illuminate\Support\Facades\Route;

// Bu dosyadaki tüm rotalar otomatik olarak /api ön eki alır.
// Her istek "Authorization: Bearer <token>" başlığı ister; token Keycloak'tan alınır
// ve KeycloakToken middleware'i tarafından imzası doğrulanır.

// Alarmlar: güvenlik görevlisi ve yönetici görebilir (paneldeki yetkiyle aynı)
Route::get('/alarmlar', [AlarmApiController::class, 'index'])        // GET /api/alarmlar?kart_id=D4
    ->middleware('api.rol:guvenlik_gorevlisi,yonetici');

// Okutmalar: ham kayıtlar (IP adresleri dahil) daha hassas; sadece yönetici
Route::middleware('api.rol:yonetici')->group(function () {
    Route::get('/okutmalar', [OkutmaApiController::class, 'index']);          // GET /api/okutmalar?kart_id=D4
    Route::get('/okutmalar/{okutma}', [OkutmaApiController::class, 'show']);  // GET /api/okutmalar/5
});
