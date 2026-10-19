<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    public function boot(): void
    {
        // APP_URL is the canonical URL of the deployment. When it is an
        // https:// URL (e.g. https://ecoweb-production.up.railway.app on
        // Railway), every URL Laravel generates - including form actions and
        // redirects - is forced to https:// as well, so browsers never see an
        // insecure http:// submission target. Local development uses
        // APP_URL=http://localhost, so nothing is forced there and http://
        // keeps working as before.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
