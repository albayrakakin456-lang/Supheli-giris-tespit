# main.py
# Programın giriş noktası. Proje klasörünün içindeyken çalıştırılır.

from tespit.okuyucu import csv_oku
from tespit import veritabani
from tespit.kurallar import tum_kurallari_calistir  # Yeni: kurallar
from tespit.rapor import metin_raporu, rapor_yaz  # Yeni: rapor

CSV_YOLU = "veri/okutmalar.csv"
DB_YOLU = "veri/girisler.db"
RAPOR_YOLU = "veri/rapor.txt"


def main():
    okutmalar, hatali = csv_oku(CSV_YOLU)

    baglanti = veritabani.baglan(DB_YOLU)
    veritabani.tablo_olustur(baglanti)
    veritabani.kayitlari_yaz(baglanti, okutmalar)
    print(f"{len(okutmalar)} okutma veritabanına yazıldı, {hatali} satır hatalıydı.\n")

    alarmlar = tum_kurallari_calistir(baglanti)  # Bütün kuralları çalıştır
    veritabani.alarmlari_yaz(baglanti, alarmlar)  # Yeni: alarmları veritabanına yaz (panel okuyacak)
    baglanti.close()

    rapor = metin_raporu(alarmlar)  # Alarmlardan metin raporu üret
    print(rapor)  # Ekrana bas
    rapor_yaz(rapor, RAPOR_YOLU)  # Aynı raporu dosyaya da yaz
    print(f"\nRapor kaydedildi: {RAPOR_YOLU}")


if __name__ == "__main__":
    main()
