<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AlarmResource;
use App\Models\Alarm;
use Illuminate\Http\Request;

class AlarmApiController extends Controller
{
    public function index(Request $request)
    {
        $sorgu = Alarm::orderBy('zaman');

        // ?kart_id=D4 verilmişse sadece o kartın alarmları.
        // where() değeri parametre olarak bağlar (Python'daki ? ile aynı güvenlik).
        if ($request->filled('kart_id')) {
            $sorgu->where('kart_id', $request->query('kart_id'));
        }

        // collection(): listeyi tek tek AlarmResource'tan geçirip {"data": [...]} döndürür
        return AlarmResource::collection($sorgu->get());
    }
}
