<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Keycloak\KeycloakExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Socialite'a "keycloak" sürücüsünü tanıt.
        // Socialite kendi içinde Google, GitHub gibi sürücülerle gelir; Keycloak
        // topluluk paketinden geliyor ve bu satırla Socialite'a ekleniyor.
        Event::listen(SocialiteWasCalled::class, [KeycloakExtendSocialite::class, 'handle']);
    }
}
