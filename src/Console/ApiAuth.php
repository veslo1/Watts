<?php

namespace Yab\Watts\Console;

use Illuminate\Console\Command;
use Yab\Watts\Generators\FileMakerTrait;

class ApiAuth extends Command
{
    use FileMakerTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'watts:auth';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Watts will prepare your micro-service for API access with JWT.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (!file_exists(getcwd().'/.env')) {
            $this->line("\n\nPlease create an .env file.\n");
        } else {
            // Add JWT service provider
            $bootstrapContents = file_get_contents(getcwd().'/bootstrap/app.php');
            $bootstrapContents = str_replace('$app->register(App\Providers\EventServiceProvider::class);', '$app->register(App\Providers\EventServiceProvider::class);'."\n".'$app->register(Tymon\JWTAuth\Providers\JWTAuthServiceProvider::class);', $bootstrapContents);
            file_put_contents(getcwd().'/bootstrap/app.php', $bootstrapContents);

            // Add the routes
            $bootstrapContents = file_get_contents(getcwd().'/bootstrap/app.php');
            $bootstrapContents = str_replace("require __DIR__.'/../app/Http/routes.php';", "require __DIR__.'/../app/Http/routes.php';\n    require __DIR__.'/../app/Http/api-routes.php';", $bootstrapContents);
            file_put_contents(getcwd().'/bootstrap/app.php', $bootstrapContents);

            // Add the Facades
            $bootstrapContents = file_get_contents(getcwd().'/bootstrap/app.php');
            $bootstrapContents = str_replace("\$app->withFacades();", "\$app->withFacades();\n\$app->configure('jwt');\n\$app->configure('auth');\nclass_alias('Tymon\JWTAuth\Facades\JWTAuth', 'JWTAuth');\nclass_alias('Tymon\JWTAuth\Facades\JWTFactory', 'JWTFactory');\n\$app->alias('cache', 'Illuminate\Cache\CacheManager');\n\$app->alias('auth', 'Illuminate\Auth\AuthManager');", $bootstrapContents);
            file_put_contents(getcwd().'/bootstrap/app.php', $bootstrapContents);

            // Add the Facades
            $bootstrapContents = file_get_contents(getcwd().'/bootstrap/app.php');
            $bootstrapContents = str_replace("App\Http\Middleware\ExampleMiddleware::class\n// ]);", "App\Http\Middleware\ExampleMiddleware::class\n// ]);\n\n\$app->routeMiddleware([\n\t'jwt.auth'    => Tymon\JWTAuth\Middleware\GetUserFromToken::class,\n\t'jwt.refresh' => Tymon\JWTAuth\Middleware\RefreshToken::class,\n]);", $bootstrapContents);
            file_put_contents(getcwd().'/bootstrap/app.php', $bootstrapContents);

            // Append the Kernel
            $kernelContents = file_get_contents(getcwd().'/app/Console/Kernel.php');
            $kernelContents = str_replace("protected \$commands = [\n        //\n    ];", "protected \$commands = [\n            \BasicIT\LumenVendorPublish\VendorPublishCommand::class,\n\t];", $kernelContents);
            file_put_contents(getcwd().'/app/Console/Kernel.php', $kernelContents);

            // Update the User
            $userContents = file_get_contents(getcwd().'/app/User.php');
            $userContents = str_replace("'name', 'email'", "'name', 'email', 'password'", $userContents);
            file_put_contents(getcwd().'/app/User.php', $userContents);

            $this->copyPreparedFiles(__DIR__.'/../Api/Helpers', getcwd().'/app');
            $this->copyPreparedFiles(__DIR__.'/../Api/Users/Migrations', getcwd().'/database/migrations');
            $this->copyPreparedFiles(__DIR__.'/../Api/Users/Controllers', getcwd().'/app/Http/Controllers/Api');
            $this->copyPreparedFiles(__DIR__.'/../Api/Users/Auth', getcwd().'/config');
            $this->copyPreparedFiles(__DIR__.'/../Api/Users/Routes', getcwd().'/app/Http');

            $this->info("\nYour micro-service is now prepared with the JWT Auth setup. Please run the following:\n");

            $this->comment("composer require tymon/jwt-auth basicit/lumen-vendor-publish illuminate/routing");

            $this->info("\nYour micro-service is now prepared with the JWT Auth setup.\n");
            $this->line('Please add the following to your composer file under "autoload":'."\n");
            $this->comment('"files": [ "app/helpers.php" ]');
            $this->info("\nThen run:\n");
            $this->comment('composer dump');
            $this->info("\nThen you need to publish!\n");
            $this->comment('php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\JWTAuthServiceProvider"');
            $this->info("\nNext up, generate your token:\n");
            $this->comment('php artisan jwt:generate');
            $this->info("\nNow build your APIs!\n");

            $this->line("\n\n\nNow if you don't have a user table. You can create one by running the migration. Create a database and run:\n");
            $this->comment('php artisan watts:prepare');
            $this->info('Then set the database in your .env file and run:');
            $this->comment('php artisan migrate');
        }
    }
}
