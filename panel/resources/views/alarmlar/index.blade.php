<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Şüpheli Girişler</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 900px; margin: 2rem auto; padding: 0 1rem; color: #222; }
        h1 { margin-bottom: .25rem; }
        .ozet { color: #666; margin-bottom: 2rem; }
        .kart { border: 1px solid #ddd; border-radius: 8px; margin-bottom: 1.5rem; overflow: hidden; }
        .kart h2 { margin: 0; padding: .75rem 1rem; background: #f5f5f5; font-size: 1.1rem; }
        .kart.yuksek h2 { background: #fde8e8; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: .5rem 1rem; border-top: 1px solid #eee; font-size: .9rem; }
        .kural { font-weight: 600; white-space: nowrap; }
        .ust { display: flex; justify-content: space-between; align-items: center; font-size: .9rem; color: #555; }
        .ust button { cursor: pointer; }
    </style>
</head>
<body>
    {{-- Giriş yapan kullanıcı, rolü ve çıkış düğmesi --}}
    <div class="ust">
        <span>{{ session('kullanici.kullanici_adi') }} ({{ implode(', ', array_intersect(session('kullanici.roller', []), ['guvenlik_gorevlisi', 'yonetici'])) }})</span>
        {{-- Çıkış bir form: POST + @csrf (başka siteler seni zorla çıkış yaptıramasın) --}}
        <form method="POST" action="/cikis">
            @csrf
            <button type="submit">Çıkış yap</button>
        </form>
    </div>

    <h1>Şüpheli Girişler</h1>
    <p class="ozet">{{ $toplam }} alarm, {{ $gruplar->count() }} kart</p>

    {{-- @forelse: liste boşsa @empty bloğunu gösterir (PHP'deki foreach + if boş kontrolü) --}}
    @forelse ($gruplar as $kart => $liste)
        {{-- 5 ve üzeri alarmı olan kartı kırmızımsı göster --}}
        <div class="kart {{ $liste->count() >= 5 ? 'yuksek' : '' }}">
            <h2>Kart {{ $kart }} — {{ $liste->count() }} alarm</h2>
            <table>
                @foreach ($liste as $alarm)
                    <tr>
                        <td class="kural">{{ $alarm->kural }}</td>
                        <td>{{ $alarm->zaman }}</td>
                        <td>{{ $alarm->detay }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    @empty
        <p>Şüpheli giriş bulunamadı.</p>
    @endforelse
</body>
</html>
