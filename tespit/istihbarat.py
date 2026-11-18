# tespit/istihbarat.py
# IP adresleri hakkında dış kaynaktan (ip-api.com) bilgi toplar.

import requests  # HTTP isteği atmak için (Laravel'deki Http::get gibi)


def ip_bilgisi(ip):
    # IP'nin ülke bilgisini sözlük olarak döndürür, bulamazsa None döner.
    try:
        cevap = requests.get(f"http://ip-api.com/json/{ip}", timeout=5)
        veri = cevap.json()  # JSON cevabı sözlüğe çevir
    except requests.RequestException:
        return None  # İnternet yok ya da servis cevap vermedi

    if veri.get("status") != "success":
        return None  # Geçersiz IP ya da servis sonuç bulamadı
    # .get("status"): anahtar yoksa hata vermek yerine None döner

    return veri  # İçinde "country" (ülke adı) ve "countryCode" (TR, US gibi) var
