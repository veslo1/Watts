<?php

namespace Yab\Watts\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Yab\Watts\Generators\FileMakerTrait;

class GenerateApiKey extends Command
{
    use FileMakerTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'watts:api';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Watts will generate an API access key for this micro-service';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (! file_exists(getcwd().'/.env')) {
            $this->line("\n\nPlease create an .env file.\n");
        } else {
            $env = file_get_contents(getcwd().'/.env');
            $key = substr(md5(rand(111111, 999999)), 0, 32);
            $env .= "API_KEY=".$key;
            file_put_contents(getcwd().'/.env', $env);

            $this->info("\n\nYour micro-service is now set with the following API key: $key\n");
            $this->line("This is a more optimal approach for using micro-services as anonymous consumers.");
            $this->line("In order to set authorization by API key you can add this to the - app/Providers/AuthServiceProvider.php file:");

            $this->comment("Auth::viaRequest('api', function (\$request) {");
            $this->comment("\tif (\$request->input('api_key')) {");
            $this->comment("\t\treturn \$request->input('api_key') === env('API_KEY');");
            $this->comment("\t}");
            $this->comment("});");

            $this->info("Now build your minions!");
        }
    }
}
