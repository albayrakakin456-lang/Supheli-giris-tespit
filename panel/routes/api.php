<?php

use App\Http\Controllers\Api\AlarmApiController;
use App\Http\Controllers\Api\OkutmaApiController;
use Illuminate\Support\Facades\Route;

// Bu dosyadaki tüm rotalar otomatik olarak /api ön eki alır.
// DİKKAT: Şu an kimlik doğrulama YOK, herkes erişebilir.
// Keycloak dersinde bu rotaları koruma altına alacağız.

Route::get('/alarmlar', [AlarmApiController::class, 'index']);        // GET /api/alarmlar?kart_id=D4
Route::get('/okutmalar', [OkutmaApiController::class, 'index']);      // GET /api/okutmalar?kart_id=D4
Route::get('/okutmalar/{okutma}', [OkutmaApiController::class, 'show']); // GET /api/okutmalar/5
