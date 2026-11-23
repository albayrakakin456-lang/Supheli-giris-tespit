<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class GirisController extends Controller
{
    // 1) Kullanıcıyı Keycloak'ın giriş sayfasına gönderir
    public function yonlendir()
    {
        return Socialite::driver('keycloak')->redirect();
    }

    // 2) Keycloak girişten sonra kullanıcıyı buraya geri yollar (?code=...&state=...)
    public function geriDon(Request $request)
    {
        try {
            // Socialite arka planda "code"u client secret ile token'a çevirir
            // ve state değerini kontrol eder (CSRF koruması)
            $kullanici = Socialite::driver('keycloak')->user();
        } catch (\Throwable $e) {
            abort(401, 'Keycloak ile giriş başarısız oldu.');
        }

        // Oturum kimliğini yenile: girişten önceki oturum kimliğini bilen biri
        // girişten sonra o oturumu ele geçiremesin (session fixation koruması)
        $request->session()->regenerate();

        $request->session()->put('kullanici', [
            'kullanici_adi' => $kullanici->getNickname(),
            'ad' => $kullanici->getName(),
            'roller' => $this->rolleriOku($kullanici->token),
        ]);
        // Çıkış yaparken Keycloak'a "bu oturumu kapat" demek için lazım
        $request->session()->put('id_token', $kullanici->accessTokenResponseBody['id_token'] ?? null);

        // Giriş öncesi gitmek istediği sayfa varsa oraya, yoksa alarmlara
        return redirect()->intended('/alarmlar');
    }

    // 3) Hem Laravel oturumunu hem Keycloak oturumunu kapatır
    public function cikis(Request $request)
    {
        $idToken = $request->session()->get('id_token');

        // Laravel tarafı: oturumu tamamen sil ve CSRF token'ını yenile
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Keycloak tarafı: sadece Laravel'den çıkarsak Keycloak hâlâ girişli kalır,
        // "Giriş"e basınca şifre sormadan tekrar içeri alır. O yüzden Keycloak'tan da çık.
        $adres = config('services.keycloak.base_url')
            . '/realms/' . config('services.keycloak.realms')
            . '/protocol/openid-connect/logout?'
            . http_build_query([
                'client_id' => config('services.keycloak.client_id'),
                'id_token_hint' => $idToken,
                'post_logout_redirect_uri' => url('/') . '/',
            ]);

        return redirect()->away($adres);
    }

    // Access token bir JWT: "başlık.içerik.imza" şeklinde, noktayla ayrılmış üç parça.
    // Roller ortadaki "içerik" parçasında, realm_access.roles altında.
    private function rolleriOku(string $token): array
    {
        $parcalar = explode('.', $token);
        // JWT "base64url" kullanır: - ve _ karakterlerini + ve / yapıp çözüyoruz
        $icerik = json_decode(base64_decode(strtr($parcalar[1] ?? '', '-_', '+/')), true);

        // İmzayı burada kontrol etmiyoruz, çünkü bu token'ı tarayıcıdan değil,
        // Laravel'in kendisi client secret ile Keycloak'tan doğrudan aldı.
        // API'de token dışarıdan (kullanıcıdan) geleceği için orada imza kontrolü ŞART.
        return $icerik['realm_access']['roles'] ?? [];
    }
}
