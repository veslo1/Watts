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
        | Register the Commands
        |--------------------------------------------------------------------------
        */

        $this->commands([
            \Yab\Watts\Console\ApiAuth::class,
            \Yab\Watts\Console\Prepare::class,
            \Yab\Watts\Console\GenerateApiKey::class,
            \Yab\Watts\Console\TableCrud::class,
            \Yab\Watts\Console\Crud::class,
        ]);
    }
}
