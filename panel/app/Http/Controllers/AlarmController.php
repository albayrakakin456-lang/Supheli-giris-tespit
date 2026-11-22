<?php

namespace App\Http\Controllers;

use App\Models\Alarm;

class AlarmController extends Controller
{
    public function index()
    {
        // Alarmları karta göre gruplayıp, en çok alarmı olan kart en üstte olsun
        // (Python'daki kartlara_gore_grupla + sorted işinin karşılığı).
        $gruplar = Alarm::orderBy('zaman')->get()
            ->groupBy('kart_id')
            ->sortByDesc(fn ($liste) => $liste->count());

        return view('alarmlar.index', [
            'gruplar' => $gruplar,
            'toplam' => $gruplar->flatten()->count(),
        ]);
    }
}
