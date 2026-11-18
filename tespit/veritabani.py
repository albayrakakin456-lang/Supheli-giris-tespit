# tespit/veritabani.py
# Veritabanı bağlantısı, tablo kurulumu ve kayıt yazma işleri burada.

import sqlite3


def baglan(dosya_yolu):
    # Veritabanına bağlanır, satırlara sütun adıyla ulaşılacak şekilde ayarlar
    baglanti = sqlite3.connect(dosya_yolu)
    baglanti.row_factory = sqlite3.Row
    return baglanti


def tablo_olustur(baglanti):
    # okutmalar tablosunu yoksa oluşturur
    baglanti.execute("""
        CREATE TABLE IF NOT EXISTS okutmalar (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            kart_id TEXT NOT NULL,
            kapi TEXT NOT NULL,
            zaman TEXT NOT NULL,
            basarili INTEGER NOT NULL,
            ip TEXT NOT NULL
        )
    """)


def kayitlari_yaz(baglanti, okutmalar):
    # Tabloyu boşaltıp okutmaları baştan yazar
    baglanti.execute("DELETE FROM okutmalar")

    degerler = []
    for o in okutmalar:
        # Her okutmayı INSERT'in beklediği sırada bir tuple'a çevir
        degerler.append((o["kart_id"], o["kapi"], o["zaman"], o["basarili"], o["ip"]))

    # executemany: aynı INSERT'i listedeki her tuple için çalıştırır
    baglanti.executemany(
        "INSERT INTO okutmalar (kart_id, kapi, zaman, basarili, ip) VALUES (?, ?, ?, ?, ?)",
        degerler,
    )
    baglanti.commit()
