# main.py
# Programın giriş noktası. Proje klasörünün içindeyken çalıştırılır.

from tespit.okuyucu import csv_oku
from tespit import veritabani
from tespit.kurallar import tum_kurallari_calistir  # Yeni: kurallar

CSV_YOLU = "veri/okutmalar.csv"
DB_YOLU = "veri/girisler.db"


def main():
    okutmalar, hatali = csv_oku(CSV_YOLU)

    baglanti = veritabani.baglan(DB_YOLU)
    veritabani.tablo_olustur(baglanti)
    veritabani.kayitlari_yaz(baglanti, okutmalar)
    print(f"{len(okutmalar)} okutma veritabanına yazıldı, {hatali} satır hatalıydı.\n")

    alarmlar = tum_kurallari_calistir(baglanti)  # Bütün kuralları çalıştır
    baglanti.close()

    print(f"{len(alarmlar)} alarm bulundu:")
    for a in alarmlar:
        print(f"[{a['kural']}] {a['kart_id']} - {a['zaman']} - {a['detay']}")


if __name__ == "__main__":
    main()
