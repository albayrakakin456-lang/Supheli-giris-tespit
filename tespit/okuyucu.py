# tespit/okuyucu.py
# CSV dosyasından okutma kayıtlarını okur ve doğrular.

import csv
from datetime import datetime

# Beklenen tarih biçimi. Büyük harfle yazılan değişkenler "sabit" anlamına gelir.
ZAMAN_BICIMI = "%Y-%m-%d %H:%M:%S"


def csv_oku(dosya_yolu):
    # İki şey döndürür: düzgün kayıtların listesi ve hatalı satır sayısı
    okutmalar = []
    hatali = 0

    with open(dosya_yolu, encoding="utf-8") as dosya:
        for satir in csv.DictReader(dosya):
            try:
                zaman = satir["zaman"].strip()
                # Tarih geçerli mi? Değilse ValueError fırlatır ve except'e düşer
                datetime.strptime(zaman, ZAMAN_BICIMI)

                okutma = {
                    "kart_id": satir["kart_id"].strip(),
                    "kapi": satir["kapi"].strip(),
                    "zaman": zaman,
                    "basarili": satir["durum"].strip() == "BASARILI",
                    "ip": satir["ip"].strip(),
                }
            except (ValueError, KeyError):
                # ValueError: bozuk tarih; KeyError: eksik sütun
                hatali += 1
                continue  # Bu satırı atla, sonrakine geç

            okutmalar.append(okutma)

    return okutmalar, hatali  # İki değeri birlikte döndürür (aslında bir tuple)
