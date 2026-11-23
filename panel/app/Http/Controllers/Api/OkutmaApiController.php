<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OkutmaResource;
use App\Models\Okutma;
use Illuminate\Http\Request;

class OkutmaApiController extends Controller
{
    public function index(Request $request)
    {
        $sorgu = Okutma::orderBy('zaman');

        if ($request->filled('kart_id')) {
            $sorgu->where('kart_id', $request->query('kart_id'));
        }

        // paginate(20): sayfa başına 20 kayıt; ?page=2 ile sonraki sayfa.
        // Cevaba "links" ve "meta" (toplam, sayfa sayısı) otomatik eklenir.
        return OkutmaResource::collection($sorgu->paginate(20));
    }

    // Route model binding: {okutma} id'sine göre kaydı bulur, yoksa otomatik 404 döner
    public function show(Okutma $okutma)
    {
        return new OkutmaResource($okutma);
    }
}
