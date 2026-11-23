<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OkutmaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kart_id' => $this->kart_id,
            'kapi' => $this->kapi,
            'zaman' => $this->zaman,
            'basarili' => $this->basarili,
            'ip' => $this->ip,
        ];
    }
}
