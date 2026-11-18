# tespit/kurallar.py
# Şüpheli giriş kuralları. Her kural veritabanı bağlantısını alır ve
# aynı biçimde bir alarm listesi döndürür. Her alarm bir sözlüktür:
# {"kural": ..., "kart_id": ..., "zaman": ..., "detay": ...}

import ipaddress  # Python'un hazır kütüphanesi: IP adreslerini inceler

from tespit.istihbarat import ip_bilgisi

KOPYA_ESIK_SANIYE = 60  # Bu sürede iki farklı kapı = kopyalanmış kart şüphesi
MESAI_BASLANGIC = 6     # 06:00'dan önce mesai dışı
MESAI_BITIS = 23        # 23:00 ve sonrası mesai dışı


def mesai_disi(baglanti):
    # strftime('%H', zaman): "2026-11-17 02:14:00" metninden saati ("02") alır
    # CAST(... AS INTEGER): "02" metnini 2 sayısına çevirir
    satirlar = baglanti.execute("""
        SELECT kart_id, kapi, zaman FROM okutmalar
        WHERE CAST(strftime('%H', zaman) AS INTEGER) < ?
           OR CAST(strftime('%H', zaman) AS INTEGER) >= ?
        ORDER BY zaman
    """, (MESAI_BASLANGIC, MESAI_BITIS)).fetchall()
    # baglanti.execute(): imlec açmadan doğrudan sorgu çalıştırmanın kısa yolu

    alarmlar = []
    for s in satirlar:
        alarmlar.append({
            "kural": "Mesai dışı giriş",
            "kart_id": s["kart_id"],
            "zaman": s["zaman"],
            "detay": s["kapi"],
        })
    return alarmlar


def reddedilen(baglanti):
    satirlar = baglanti.execute("""
        SELECT kart_id, kapi, zaman FROM okutmalar
        WHERE basarili = 0
        ORDER BY zaman
    """).fetchall()

    alarmlar = []
    for s in satirlar:
        alarmlar.append({
            "kural": "Reddedilen geçiş",
            "kart_id": s["kart_id"],
            "zaman": s["zaman"],
            "detay": s["kapi"],
        })
    return alarmlar


def kopyalanmis_kart(baglanti):
    # Ders 14'teki self join: tabloyu kendisiyle karşılaştırıyoruz.
    # ROUND: julianday küsuratlı sonuç verebilir (59.9999 gibi), yuvarlıyoruz.
    satirlar = baglanti.execute("""
        SELECT a.kart_id, a.kapi AS kapi1, a.zaman AS zaman1, b.kapi AS kapi2,
               CAST(ROUND((julianday(b.zaman) - julianday(a.zaman)) * 86400) AS INTEGER) AS fark
        FROM okutmalar AS a
        JOIN okutmalar AS b
             ON a.kart_id = b.kart_id
            AND a.id != b.id
            AND a.kapi != b.kapi
        WHERE ROUND((julianday(b.zaman) - julianday(a.zaman)) * 86400) BETWEEN 0 AND ?
        ORDER BY a.zaman
    """, (KOPYA_ESIK_SANIYE,)).fetchall()

    alarmlar = []
    for s in satirlar:
        alarmlar.append({
            "kural": "Kopyalanmış kart şüphesi",
            "kart_id": s["kart_id"],
            "zaman": s["zaman1"],
            "detay": f"{s['kapi1']} → {s['fark']} sn sonra {s['kapi2']}",
        })
    return alarmlar


def yurt_disi_ip(baglanti):
    alarmlar = []

    # DISTINCT: aynı IP'yi bir kez getir; her IP için API'ye bir kez soralım
    ipler = baglanti.execute("SELECT DISTINCT ip FROM okutmalar").fetchall()

    for s in ipler:
        ip = s["ip"]

        try:
            # is_private: 192.168.x.x gibi iç ağ adresi mi?
            if ipaddress.ip_address(ip).is_private:
                continue  # İç ağ adresi; dışarıya sormaya gerek yok
        except ValueError:
            continue  # IP biçimi bozuk; bu IP'yi atla

        bilgi = ip_bilgisi(ip)
        if bilgi is None or bilgi["countryCode"] == "TR":
            continue  # Bilgi alınamadı ya da Türkiye kaynaklı

        # Bu IP'den gelen tüm okutmaları alarm olarak ekle
        okutmalar = baglanti.execute(
            "SELECT kart_id, kapi, zaman FROM okutmalar WHERE ip = ? ORDER BY zaman",
            (ip,),
        ).fetchall()
        for o in okutmalar:
            alarmlar.append({
                "kural": "Yurt dışı IP",
                "kart_id": o["kart_id"],
                "zaman": o["zaman"],
                "detay": f"{o['kapi']} ({bilgi['country']} kaynaklı IP: {ip})",
            })

    return alarmlar


def tum_kurallari_calistir(baglanti):
    # Bütün kuralları sırayla çalıştırıp alarmları tek listede toplar.
    # extend: bir listenin tüm elemanlarını diğer listenin sonuna ekler
    # (append tek eleman ekler, extend bir listenin hepsini)
    alarmlar = []
    alarmlar.extend(mesai_disi(baglanti))
    alarmlar.extend(reddedilen(baglanti))
    alarmlar.extend(kopyalanmis_kart(baglanti))
    alarmlar.extend(yurt_disi_ip(baglanti))
    return alarmlar
