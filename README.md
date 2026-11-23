# Şüpheli Giriş Tespit

Kart okutma kayıtlarından şüpheli girişleri tespit eden, sonuçları bir web
panelinde ve REST API üzerinden sunan küçük bir güvenlik sistemi.

- **Python** CSV'deki okutmaları okur, dört kuralla şüpheli girişleri bulur ve
  sonuçları SQLite veritabanına yazar.
- **Laravel** aynı veritabanını okuyup güvenlik görevlisine bir panel ve başka
  sistemlere bir REST API sunar.
- **Keycloak** (Docker'da) kimlik doğrulamayı ve rol bazlı yetkiyi yönetir.

Python öğrenirken geliştirdiğim bir proje. Sırada kendi sistemim üzerinde
güvenlik testi var (aşağıda "Yol haritası").

## Mimari

```
okutmalar.csv → Python (4 kural) → SQLite (girisler.db) ← Laravel ─┬─ Panel  (tarayıcı, oturum)
                                                                    └─ REST API (Bearer token)
                                                         Keycloak ──┘  giriş + roller
```

- Python ve Laravel doğrudan konuşmaz; ortak SQLite dosyası üzerinden haberleşir.
  Python yazar, Laravel okur.
- Laravel kendi tablolarını bu veritabanına eklemez (oturum ve önbellek dosyada
  tutulur), tabloları Python yönetir.
- Laravel kullanıcı şifresi saklamaz; kullanıcılar ve roller Keycloak'tadır.

## Tespit kuralları

| Kural | Mantık |
|---|---|
| Mesai dışı giriş | 06:00'dan önce ya da 23:00 ve sonrasındaki okutmalar |
| Reddedilen geçiş | Başarısız (reddedilmiş) okutmalar |
| Kopyalanmış kart şüphesi | Aynı kartın 60 saniye içinde iki farklı kapıda okutulması (SQL self join) |
| Yurt dışı IP | Okutmanın geldiği IP'nin ülkesi Türkiye değilse (ip-api.com ile sorgulanır, iç ağ IP'leri atlanır) |

## Roller ve yetkiler

| | `guvenlik_gorevlisi` | `yonetici` |
|---|---|---|
| Panel: `/alarmlar` | ✅ | ✅ |
| API: `GET /api/alarmlar` | ✅ | ✅ |
| API: `GET /api/okutmalar` (IP adresleri içerir) | ❌ 403 | ✅ |

Giriş yapılmamışsa ya da token geçersizse **401**, giriş yapılmış ama rol
yetmiyorsa **403** döner.

## Kurulum

Gerekenler: Python 3, PHP 8.2+ (`pdo_sqlite` eklentisi), Composer, Docker.
Komutlar Windows içindir (`py`); diğer sistemlerde `python3` kullanın.

### 1. Python: tespit motoru

```
py -m pip install -r requirements.txt
py main.py
```

`py main.py` CSV'yi okur, kayıtları ve alarmları `veri/girisler.db` dosyasına
yazar, raporu ekrana basar ve `veri/rapor.txt` dosyasına kaydeder.

Yurt dışı IP kuralı internet bağlantısı ister; bağlantı yoksa bu kural atlanır,
diğer kurallar çalışmaya devam eder.

### 2. Keycloak (Docker)

Proje ana klasöründe bir `.env` dosyası oluşturun (git'e girmez):

```
KEYCLOAK_ADMIN_SIFRE=guclu-bir-sifre
```

```
docker compose up -d
```

http://localhost:8080 adresinden `admin` kullanıcısıyla giriş yapıp şunları oluşturun:

1. **Realm:** `supheli-giris`
2. **Realm roles:** `guvenlik_gorevlisi`, `yonetici`
3. **Users:** en az bir kullanıcı; Credentials sekmesinden şifre (Temporary: Off),
   Role mapping sekmesinden rol
4. **Client:** `panel`
   - Client authentication: On
   - Standard flow: On (panel girişi), Direct access grants: On (API'yi komut satırından denemek için)
   - Valid redirect URIs: `http://127.0.0.1:8000/auth/callback`
   - Valid post logout redirect URIs: `http://127.0.0.1:8000/*`
   - Web origins: `http://127.0.0.1:8000`
   - Credentials sekmesindeki **Client Secret**'ı bir sonraki adımda kullanın

Keycloak geliştirme modunda (`start-dev`) çalışır: HTTP ve gömülü veritabanı
kullanır, sadece yerel geliştirme içindir.

### 3. Laravel: panel ve API

```
cd panel
composer install
copy .env.example .env
php artisan key:generate
```

`panel/.env` dosyasında şunları ayarlayın:

```
DB_CONNECTION=sqlite
DB_DATABASE=<proje klasörünün tam yolu>/veri/girisler.db

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync

KEYCLOAK_BASE_URL=http://localhost:8080
KEYCLOAK_REALM=supheli-giris
KEYCLOAK_CLIENT_ID=panel
KEYCLOAK_CLIENT_SECRET=<Keycloak'tan kopyalanan secret>
KEYCLOAK_REDIRECT_URI=http://127.0.0.1:8000/auth/callback
```

`php artisan migrate` **çalıştırmayın**: tabloları Python oluşturur.

```
php artisan serve
```

Panel: http://127.0.0.1:8000 (`localhost` değil; Keycloak'taki adreslerle aynı
olmalı, aksi hâlde oturum çerezleri kaybolur).

## REST API

Her istek `Authorization: Bearer <access_token>` başlığı ister.

| Uç nokta | Açıklama |
|---|---|
| `GET /api/alarmlar` | Bütün alarmlar. `?kart_id=D4` ile filtrelenebilir. |
| `GET /api/okutmalar` | Okutmalar, sayfa başına 20. `?kart_id=` ve `?page=` desteklenir. |
| `GET /api/okutmalar/{id}` | Tek okutma; yoksa 404. |

Token almak (geliştirme ortamında):

```
curl -X POST http://localhost:8080/realms/supheli-giris/protocol/openid-connect/token \
  -d grant_type=password -d client_id=panel -d client_secret=<secret> \
  -d username=<kullanici> -d password=<sifre>
```

Dönen `access_token` ile:

```
curl -H "Authorization: Bearer <access_token>" http://127.0.0.1:8000/api/alarmlar
```

API token'ı şu sırayla doğrular: Keycloak imzası (realm'in açık anahtarlarıyla),
süre (`exp`), üreten realm (`iss`), hangi client için üretildiği (`azp`) ve rol.

## Örnek rapor çıktısı (Python)

```
ŞÜPHELİ GİRİŞ RAPORU: 13 alarm, 4 kart
==================================================

KART D4 — 10 alarm
  - [Mesai dışı giriş] 2026-11-17 02:14:00 - Sunucu Odası
  - [Reddedilen geçiş] 2026-11-17 02:14:00 - Sunucu Odası
  - [Kopyalanmış kart şüphesi] 2026-11-17 02:14:30 - Sunucu Odası → 30 sn sonra Ana Giriş
  - [Yurt dışı IP] 2026-11-17 02:14:00 - Sunucu Odası (United States kaynaklı IP: 8.8.8.8)
  ...

KART A1 — 1 alarm
  - [Kopyalanmış kart şüphesi] 2026-11-17 09:00:10 - Ana Giriş → 30 sn sonra Lab
```

Kartlar alarm sayısına göre sıralanır, en şüpheli kart en üstte çıkar. Panel de
aynı sıralamayı kullanır.

## Güvenlik önlemleri

- **SQL injection:** Python'da değerler her zaman `?` ile, Laravel'de Query
  Builder parametre bağlamasıyla verilir; SQL hiçbir yerde metin birleştirerek
  kurulmaz.
- **XSS:** Blade'de çıktılar `{{ }}` ile otomatik HTML-escape edilir.
- **Giriş akışı:** OAuth2 Authorization Code Flow. Şifre sadece Keycloak'a
  gider; `state` parametresi kontrol edilir; girişten sonra oturum kimliği
  yenilenir (session fixation).
- **Çıkış:** POST + CSRF token; hem Laravel hem Keycloak oturumu kapatılır.
- **API:** JWT imzası, süresi, realm'i, client'ı ve rolü doğrulanır.
- **En az yetki:** IP adresi içeren ham okutmalar sadece `yonetici` rolüne açık.
- **Sırlar:** Keycloak admin şifresi ve client secret `.env` dosyalarında; git'e girmez.

## Bilinen sınırlamalar

- **Ortak SQLite:** Yoğun eşzamanlı yazmaya uygun değil; büyürse MySQL ya da
  PostgreSQL'e geçilmeli.
- **Kalıcı olmayan ID'ler:** Python her çalıştırmada tabloları boşaltıp yeniden
  yazdığı için kayıt ID'leri değişir; `/api/okutmalar/{id}` adresleri kalıcı değildir.
- **İnternet bağımlılığı:** Bağlantı yokken yurt dışı IP kuralı sessizce atlanır.
- **Otomatik test yok:** Şimdilik testler elle yapıldı.
- **Geliştirme ortamı:** Keycloak `start-dev` ile HTTP üzerinde çalışır.

## Proje yapısı

```
main.py                     Python giriş noktası
tespit/
  okuyucu.py                CSV okuma ve doğrulama
  veritabani.py             SQLite bağlantısı, okutma ve alarm yazma
  kurallar.py               Dört şüpheli giriş kuralı
  istihbarat.py             IP'nin ülke bilgisini ip-api.com'dan alır
  rapor.py                  Gruplama, metin raporu, dosyaya yazma
veri/
  okutmalar.csv             Örnek okutma kayıtları
docker-compose.yml          Keycloak (Docker)
panel/                      Laravel uygulaması
  app/Models/               Alarm, Okutma (Python'un tablolarını okur)
  app/Http/Controllers/     AlarmController (panel), GirisController (Keycloak girişi),
                            Api/ (REST API)
  app/Http/Middleware/      KeycloakRol (panel rol kontrolü),
                            KeycloakToken (API token doğrulama)
  app/Http/Resources/       API'nin JSON biçimi
  routes/web.php            Panel rotaları
  routes/api.php            API rotaları
```

## Yol haritası

- [x] Python tespit motoru
- [x] Laravel panel: güvenlik görevlisinin şüpheli girişleri gördüğü sayfa
- [x] REST API: alarm ve okutma kayıtları
- [x] Keycloak ile giriş ve rol bazlı yetki (panel ve API)
- [ ] Kendi panelime ve API'me white box güvenlik testi (SQL injection,
      yetkisiz erişim, IDOR) ve kısa bir güvenlik raporu
