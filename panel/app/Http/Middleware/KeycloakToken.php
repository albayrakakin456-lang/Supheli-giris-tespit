<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class KeycloakToken
{
    // API isteklerini korur.
    // Kullanım: ->middleware('api.rol:yonetici') ya da 'api.rol:guvenlik_gorevlisi,yonetici'
    //
    // İstemci her istekte şu başlığı göndermeli:
    //   Authorization: Bearer <Keycloak'tan aldığı access token>
    public function handle(Request $request, Closure $next, string ...$izinliRoller): Response
    {
        // bearerToken(): "Authorization: Bearer xxx" başlığından xxx kısmını alır
        $token = $request->bearerToken();
        if (! $token) {
            return $this->hata(401, 'Token yok. Authorization: Bearer <token> başlığı gerekli.');
        }

        try {
            // Saatler arasında birkaç saniyelik fark olabilir; 30 saniye tolerans
            JWT::$leeway = 30;

            // decode() üç şeyi birden kontrol eder:
            //   1) İmza: token gerçekten Keycloak'ın özel anahtarıyla mı imzalanmış?
            //      (Biri token'ın içindeki rolü elle değiştirirse imza bozulur)
            //   2) exp: süresi dolmuş mu?
            //   3) nbf/iat: henüz geçerli olmayan bir token mı?
            $icerik = JWT::decode($token, $this->anahtarlar());
        } catch (\Throwable $e) {
            return $this->hata(401, 'Geçersiz token: ' . $e->getMessage());
        }

        // 4) Kim üretti? Başka bir realm'in (ya da başka bir Keycloak'ın) token'ı kabul edilmez
        $beklenenIssuer = config('services.keycloak.base_url') . '/realms/' . config('services.keycloak.realms');
        if (($icerik->iss ?? null) !== $beklenenIssuer) {
            return $this->hata(401, 'Token bu realm tarafından üretilmemiş.');
        }

        // 5) Hangi client için üretildi? (azp = "authorized party")
        // Aynı realm'deki başka bir uygulamanın token'ı bizim API'mize girmesin
        if (($icerik->azp ?? null) !== config('services.keycloak.client_id')) {
            return $this->hata(401, 'Token bu uygulama için üretilmemiş.');
        }

        // 6) Rol kontrolü: imza doğrulandığı için bu rollere artık güvenebiliriz
        $roller = $icerik->realm_access->roles ?? [];
        if (! array_intersect($izinliRoller, $roller)) {
            return $this->hata(403, 'Bu kaynağa erişim yetkin yok.');
        }

        return $next($request);
    }

    // Keycloak'ın açık (public) anahtarlarını getirir.
    // Keycloak token'ı ÖZEL anahtarla imzalar; biz AÇIK anahtarla doğrularız.
    // Açık anahtar gizli değildir, herkes /certs adresinden alabilir.
    private function anahtarlar(): array
    {
        // Her istekte Keycloak'a sormamak için 1 saat önbellekte tut
        $jwks = Cache::remember('keycloak_jwks', 3600, function () {
            $adres = config('services.keycloak.base_url')
                . '/realms/' . config('services.keycloak.realms')
                . '/protocol/openid-connect/certs';

            return Http::timeout(5)->get($adres)->throw()->json();
        });

        // Keycloak iki tür anahtar yayınlar: imza ("sig") ve şifreleme ("enc").
        // Token doğrulamada sadece imza anahtarları kullanılır.
        $jwks['keys'] = array_values(array_filter(
            $jwks['keys'] ?? [],
            fn ($anahtar) => ($anahtar['use'] ?? 'sig') === 'sig'
        ));

        return JWK::parseKeySet($jwks);
    }

    // API hataları HTML değil JSON dönsün
    private function hata(int $kod, string $mesaj): Response
    {
        return response()->json(['hata' => $mesaj], $kod);
    }
}
