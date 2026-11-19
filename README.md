# Şüpheli Giriş Tespit

Kart okutma kayıtlarını (CSV) okuyup SQLite veritabanına yazan ve şüpheli
girişleri dört kuralla tespit eden bir Python aracı. Sonuç, kart bazında
gruplanmış bir metin raporu olarak ekrana basılır ve dosyaya kaydedilir.

Python öğrenirken geliştirdiğim bir proje. Sırada bir Laravel panel, REST API
ve kendi sistemim üzerinde güvenlik testi var (aşağıda "Yol haritası").

## Tespit kuralları

| Kural | Mantık |
|---|---|
| Mesai dışı giriş | 06:00'dan önce ya da 23:00 ve sonrasındaki okutmalar |
| Reddedilen geçiş | Başarısız (reddedilmiş) okutmalar |
| Kopyalanmış kart şüphesi | Aynı kartın 60 saniye içinde iki farklı kapıda okutulması (SQL self join) |
| Yurt dışı IP | Okutmanın geldiği IP'nin ülkesi Türkiye değilse (ip-api.com ile sorgulanır, iç ağ IP'leri atlanır) |

## Kurulum ve çalıştırma

Python 3 gerekir. Proje klasöründeyken:

```
py -m pip install -r requirements.txt
py main.py
```

`py main.py` sırasıyla şunları yapar:
1. `veri/okutmalar.csv` dosyasını okur ve doğrular (hatalı satırları sayar)
2. Kayıtları `veri/girisler.db` (SQLite) dosyasına yazar
3. Dört kuralı çalıştırır
4. Raporu ekrana basar ve `veri/rapor.txt` dosyasına yazar

Not: Yurt dışı IP kuralı internet bağlantısı ister. Bağlantı yoksa o kural
sessizce atlanır, diğer kurallar çalışmaya devam eder.

## Örnek çıktı

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

Kartlar alarm sayısına göre sıralanır, en şüpheli kart en üstte çıkar.

## Proje yapısı

```
main.py                  Giriş noktası
tespit/
  okuyucu.py             CSV okuma ve doğrulama
  veritabani.py          SQLite bağlantısı ve kayıt yazma
  kurallar.py            Dört şüpheli giriş kuralı
  istihbarat.py          IP'nin ülke bilgisini ip-api.com'dan alır
  rapor.py               Gruplama, metin raporu, dosyaya yazma
veri/
  okutmalar.csv          Örnek okutma kayıtları
```

SQL sorgularında değerler her zaman `?` ile verilir, SQL hiçbir yerde
f-string ile kurulmaz (SQL injection'a karşı).

## Yol haritası

- [x] Python tespit motoru
- [ ] Laravel panel: güvenlik görevlisinin şüpheli girişleri gördüğü sayfa
- [ ] REST API: kart ve okutma kayıtlarını dışarıya açan uç noktalar
- [ ] Keycloak ile giriş koruma ve rol bazlı yetki
- [ ] Kendi panelime ve API'me white box güvenlik testi (SQL injection,
      yetkisiz erişim, IDOR) ve kısa bir güvenlik raporu
