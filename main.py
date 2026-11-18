# main.py
# Programın giriş noktası. Proje klasörünün içindeyken çalıştırılır.

from tespit.okuyucu import csv_oku
from tespit import veritabani  # Modülün tamamını al, veritabani.baglan() diye kullan

CSV_YOLU = "veri/okutmalar.csv"
DB_YOLU = "veri/girisler.db"


def main():
    okutmalar, hatali = csv_oku(CSV_YOLU)  # Dönen iki değeri iki değişkene ayır

    baglanti = veritabani.baglan(DB_YOLU)
    veritabani.tablo_olustur(baglanti)
    veritabani.kayitlari_yaz(baglanti, okutmalar)
    baglanti.close()

    print(f"{len(okutmalar)} okutma veritabanına yazıldı, {hatali} satır hatalıydı.")


# Bu dosya doğrudan çalıştırıldığında main() çalışır.
# Başka bir dosya bunu import ederse çalışmaz.
if __name__ == "__main__":
    main()
