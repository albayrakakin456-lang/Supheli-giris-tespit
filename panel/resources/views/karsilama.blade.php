<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Şüpheli Giriş Paneli</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 600px; margin: 4rem auto; padding: 0 1rem; color: #222; text-align: center; }
        a.dugme { display: inline-block; margin-top: 1rem; padding: .6rem 1.4rem; background: #1f6feb; color: #fff; border-radius: 6px; text-decoration: none; }
    </style>
</head>
<body>
    <h1>Şüpheli Giriş Paneli</h1>

    {{-- session('kullanici'): giriş yapılmışsa dolu, yapılmamışsa null --}}
    @if (session('kullanici'))
        <p>Hoş geldin, {{ session('kullanici.kullanici_adi') }}.</p>
        <a class="dugme" href="/alarmlar">Alarmlara git</a>
    @else
        <p>Devam etmek için giriş yap.</p>
        <a class="dugme" href="/giris">Keycloak ile giriş yap</a>
    @endif
</body>
</html>
