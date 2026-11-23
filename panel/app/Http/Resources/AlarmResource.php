<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlarmResource extends JsonResource
{
    // API'nin dışarıya hangi alanları, hangi adla vereceğini belirler.
    // Tablo yapısı değişse bile API'nin şekli sabit kalır.
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kural' => $this->kural,
            'kart_id' => $this->kart_id,
            'zaman' => $this->zaman,
            'detay' => $this->detay,
        ];
    }
}
