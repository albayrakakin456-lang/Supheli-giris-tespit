<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Okutma extends Model
{
    // Tabloyu Python oluşturdu, adı "okutmalar" (Laravel "okutmas" tahmin ederdi)
    protected $table = 'okutmalar';

    // Python'un tablosunda created_at / updated_at yok
    public $timestamps = false;

    // basarili sütunu SQLite'ta 0/1 olarak duruyor; true/false'a çevir
    protected $casts = [
        'basarili' => 'boolean',
    ];
}
