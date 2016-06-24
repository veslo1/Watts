<?php

namespace Yab\Watts\Console;

use Illuminate\Console\Command;
use Yab\Watts\Generators\FileMakerTrait;

class Prepare extends Command
{
    use FileMakerTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'watts:prepare';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Watts will prepare your micro-service for CRUD integration with testing.';

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
            if (!is_dir(getcwd().'/config')) {
                mkdir(getcwd().'/config', 0777, true);
            }

            $this->copyPreparedFiles(__DIR__.'/../Prepare', getcwd().'/config');

            $this->info("\n\nYour micro-service is now prepared with the database config for testing.");
            $this->line("\nPlease add the following line to the php group in: phpunit.xml");
            $this->comment("\n<env name=\"DB_CONNECTION\" value=\"testing\"/>");
            $this->info("\nNow build your minions!\n");
        }
    }
}
