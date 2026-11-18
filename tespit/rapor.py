# tespit/rapor.py
# Kurallardan gelen alarmları okunur bir rapora çevirir.


def kartlara_gore_grupla(alarmlar):
    # Alarm listesini kart_id'ye göre gruplar.
    # Giriş:  [{"kart_id": "D4", ...}, {"kart_id": "A1", ...}, {"kart_id": "D4", ...}]
    # Çıkış:  {"D4": [alarm, alarm], "A1": [alarm]}
    # (Laravel'deki collect($alarmlar)->groupBy('kart_id') ile aynı iş)
    gruplar = {}
    for a in alarmlar:
        kart = a["kart_id"]
        if kart not in gruplar:
            gruplar[kart] = []  # Bu kartı ilk kez görüyoruz: boş liste aç
        gruplar[kart].append(a)  # Alarmı kartın listesine ekle
    return gruplar


def metin_raporu(alarmlar):
    # Alarmlardan okunur bir metin raporu üretir (ekrana basmaz, metni döndürür).
    gruplar = kartlara_gore_grupla(alarmlar)

    # sorted(...): listeyi sıralanmış yeni liste olarak verir.
    # key=: "neye göre sırala" demek. Burada kartın alarm sayısına göre.
    # lambda: tek satırlık isimsiz küçük fonksiyon (PHP'deki fn($x) => ...)
    # reverse=True: büyükten küçüğe, yani en çok alarmı olan kart en üstte.
    sirali = sorted(gruplar.items(), key=lambda cift: len(cift[1]), reverse=True)

    satirlar = []  # Metni satır satır listede biriktirip sonda birleştireceğiz
    satirlar.append(f"ŞÜPHELİ GİRİŞ RAPORU: {len(alarmlar)} alarm, {len(gruplar)} kart")
    satirlar.append("=" * 50)  # "=" karakterini 50 kez tekrarlar

    for kart, liste in sirali:
        satirlar.append("")  # Boş satır, kartlar arası ferahlık
        satirlar.append(f"KART {kart} — {len(liste)} alarm")
        for a in liste:
            satirlar.append(f"  - [{a['kural']}] {a['zaman']} - {a['detay']}")

    # "\n".join(liste): listedeki metinleri aralarına satır sonu koyarak birleştirir
    # (PHP'deki implode("\n", $satirlar) ile aynı)
    return "\n".join(satirlar)


def rapor_yaz(metin, dosya_yolu):
    # Metni dosyaya yazar.
    # "w": yazma modu (dosya varsa üstüne yazar, yoksa oluşturur)
    # encoding="utf-8": ş, ğ, İ gibi Türkçe harfler bozulmasın diye şart.
    # Windows varsayılan olarak başka bir kodlama kullanabilir.
    # with: iş bitince dosyayı otomatik kapatır (close() yazmaya gerek kalmaz)
    with open(dosya_yolu, "w", encoding="utf-8") as dosya:
        dosya.write(metin)
