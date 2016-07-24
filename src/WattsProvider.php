<?php

namespace Yab\Watts;

use Illuminate\Support\ServiceProvider;

class WattsProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        /*
        |--------------------------------------------------------------------------
        | Providers
        |--------------------------------------------------------------------------
        */

        $this->app->register(\Yab\Crypto\CryptoProvider::class);
        $this->app->register(\Yab\CrudMaker\CrudMakerProvider::class);

        /*
        |--------------------------------------------------------------------------
        | Register the Commands
        |--------------------------------------------------------------------------
        */

        $this->commands([
            \Yab\Watts\Console\Prepare::class,
            \Yab\Watts\Console\GenerateApiKey::class,
        ]);
    }
}
