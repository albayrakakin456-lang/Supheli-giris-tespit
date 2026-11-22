<?php

use App\Http\Controllers\AlarmController;
use Illuminate\Support\Facades\Route;

// Ana sayfa şüpheli giriş listesine yönlendirsin
Route::redirect('/', '/alarmlar');

Route::get('/alarmlar', [AlarmController::class, 'index']);
