<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class KeycloakRol
{
    // Kullanım: ->middleware('rol:guvenlik_gorevlisi,yonetici')
    // Virgülle verilen rollerden EN AZ BİRİ kullanıcıda varsa geçişe izin verir.
    public function handle(Request $request, Closure $next, string ...$izinliRoller): Response
    {
        $kullanici = $request->session()->get('kullanici');

        // Giriş yapılmamış: Keycloak'a gönder.
        // guest(): gitmek istediği adresi hatırlar, girişten sonra oraya döner.
        if (! $kullanici) {
            return redirect()->guest('/giris');
        }

        // Giriş yapılmış ama rolü yetmiyor: 403 (Yasak)
        // array_intersect: iki listenin ortak elemanları; boşsa ortak rol yok demektir
        if (! array_intersect($izinliRoller, $kullanici['roller'])) {
            abort(403, 'Bu sayfayı görme yetkin yok.');
        }

        return $next($request);
    }
}
