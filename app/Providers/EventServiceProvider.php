<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Okta\OktaExtendSocialite;
use Laravel\Socialite\Facades\Socialite;
use SocialiteProviders\Okta\Provider as OktaProvider;

class EventServiceProvider extends ServiceProvider
{

    protected $listen = [
        SocialiteWasCalled::class => [
            OktaExtendSocialite::class . '@handle',
        ],
    ];
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Socialite::extend('okta', function ($app) {
            $config = $app['config']['services.okta'];

            return Socialite::buildProvider(OktaProvider::class, $config);
        });
    }
}

