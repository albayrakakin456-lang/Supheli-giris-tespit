<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alarm extends Model
{
    // Tabloyu Python oluşturdu ve adı "alarmlar".
    // Laravel model adından "alarms" tahmin eder, o yüzden elle belirtiyoruz.
    protected $table = 'alarmlar';

    // Python'un tablosunda created_at / updated_at sütunları yok.
    // false demezsek Laravel kayıt okurken sorun çıkarmaz ama yazarken hata verir.
    public $timestamps = false;
}
