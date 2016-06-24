<?php

namespace Yab\Watts\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Yab\Watts\Generators\CrudGenerator;

class Crud extends Command
{
    /**
     * Column Types.
     *
     * @var array
     */
    protected $columnTypes = [
        'bigIncrements',
        'increments',
        'bigInteger',
        'binary',
        'boolean',
        'char',
        'date',
        'dateTime',
        'decimal',
        'double',
        'enum',
        'float',
        'integer',
        'ipAddress',
        'json',
        'jsonb',
        'longText',
        'macAddress',
        'mediumInteger',
        'mediumText',
        'morphs',
        'smallInteger',
        'string',
        'string',
        'text',
        'time',
        'tinyInteger',
        'timestamp',
        'uuid',
    ];

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'watts:crud {table}
        {--migration}
        {--serviceOnly}
        {--schema= : Basic schema support ie: id,increments,name:string,parent_id:integer}
        {--relationships= : Define the relationship ie: hasOne|App\Comment|comment,hasOne|App\Rating|rating or relation|class|column (without the _id)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a basic API CRUD for a table with option for migration';

    /**
     * Generate a CRUD stack.
     *
     * @return mixed
     */
    public function handle()
    {
        $section = false;
        $crudGenerator = new CrudGenerator();
        $filesystem = new Filesystem();
        $splitTable = [];

        $table = ucfirst(str_singular($this->argument('table')));

        if (stristr($table, '_')) {
            $splitTable = explode('_', $table);
            $table = $splitTable[1];
            $section = $splitTable[0];
        }

        if ($this->option('schema')) {
            foreach (explode(',', $this->option('schema')) as $column) {
                $columnDefinition = explode(':', $column);
                if (!in_array($columnDefinition[1], $this->columnTypes)) {
                    throw new Exception("$columnDefinition[1] is not in the array of valid column types: ".implode(', ', $this->columnTypes), 1);
                }
            }
        }

        $config = [
            'schema'                     => null,
            'relationships'              => null,
            '_sectionPrefix_'            => '',
            '_sectionTablePrefix_'       => '',
            '_sectionRoutePrefix_'       => '',
            '_sectionNamespace_'         => '',
            '_path_service_'             => 'app/Services',
            '_path_repository_'          => 'app/Repositories/_table_',
            '_path_model_'               => 'app/Repositories/_table_',
            '_path_api_controller_'      => 'app/Http/Controllers/Api',
            '_path_tests_'               => 'tests',
            '_path_routes_'              => 'app/Http/routes.php',
            '_path_api_routes_'          => 'app/Http/api-routes.php',
            'routes_prefix'              => '',
            'routes_suffix'              => '',
            '_app_namespace_'            => 'App\\',
            '_namespace_services_'       => 'App\Services',
            '_namespace_repository_'     => 'App\Repositories\_table_',
            '_namespace_model_'          => 'App\Repositories\_table_',
            '_namespace_controller_'     => 'App\Http\Controllers',
            '_namespace_api_controller_' => 'App\Http\Controllers\Api',
            '_namespace_request_'        => 'App\Http\Requests',
            '_table_name_'               => str_plural(strtolower($table)),
            '_lower_case_'               => strtolower($table),
            '_lower_casePlural_'         => str_plural(strtolower($table)),
            '_camel_case_'               => ucfirst(camel_case($table)),
            '_camel_casePlural_'         => str_plural(camel_case($table)),
            '_ucCamel_casePlural_'       => ucfirst(str_plural(camel_case($table))),
            'tests_generated'            => 'integration,service,repository',
        ];

        $config = $this->setConfig($config, $section, $table);

        if ($section) {
            $config = [
                'schema'                     => null,
                'relationships'              => null,
                '_sectionPrefix_'            => strtolower($section).'.',
                '_sectionTablePrefix_'       => strtolower($section).'_',
                '_sectionRoutePrefix_'       => strtolower($section).'/',
                '_sectionNamespace_'         => ucfirst($section).'\\',
                '_path_service_'             => 'app/Services',
                '_path_repository_'          => 'app/Repositories/'.ucfirst($section).'/'.ucfirst($table),
                '_path_model_'               => 'app/Repositories/'.ucfirst($section).'/'.ucfirst($table),
                '_path_api_controller_'      => 'app/Http/Controllers/Api/'.ucfirst($section).'/',
                '_path_tests_'               => 'tests',
                '_path_api_routes_'          => 'app/Http/api-routes.php',
                'routes_prefix'              => "\n\n\$app->group(['namespace' => '".ucfirst($section)."', 'prefix' => '".strtolower($section)."', 'middleware' => ['web']], function () uses (\$app) { \n",
                'routes_suffix'              => "\n});",
                '_app_namespace_'            => 'App\\',
                '_namespace_services_'       => 'App\Services\\'.ucfirst($section),
                '_namespace_repository_'     => 'App\Repositories\\'.ucfirst($section).'\\'.ucfirst($table),
                '_namespace_model_'          => 'App\Repositories\\'.ucfirst($section).'\\'.ucfirst($table),
                '_namespace_api_controller_' => 'App\Http\Controllers\Api\\'.ucfirst($section),
                '_namespace_request_'        => 'App\Http\Requests\\'.ucfirst($section),
                '_table_name_'               => str_plural(strtolower(implode('_', $splitTable))),
                '_lower_case_'               => strtolower($table),
                '_lower_casePlural_'         => str_plural(strtolower($table)),
                '_camel_case_'               => ucfirst(camel_case($table)),
                '_camel_casePlural_'         => str_plural(camel_case($table)),
                '_ucCamel_casePlural_'       => ucfirst(str_plural(camel_case($table))),
                'tests_generated'            => 'integration,service,repository',
            ];

            $config = $this->setConfig($config, $section, $table);

            $pathsToMake = [
                '_path_repository_',
                '_path_model_',
                '_path_controller_',
                '_path_api_controller_',
                '_path_views_',
                '_path_request_'
            ];

            foreach ($config as $key => $value) {
                if (in_array($key, $pathsToMake) && ! file_exists($value)) {
                    mkdir($value, 0777, true);
                }
            }
        }

        if ($this->option('schema')) {
            $config['schema'] = $this->option('schema');
        }

        if ($this->option('relationships')) {
            $config['relationships'] = $this->option('relationships');
        }

        if (!isset($config['template_source'])) {
            $config['template_source'] = __DIR__.'/../Templates';
        }

        try {
            $this->line('Building service...');
            $crudGenerator->createService($config);

            $this->line('Building repository...');
            $crudGenerator->createRepository($config);

            if ($this->option('serviceOnly')) {
                $config['tests_generated'] = 'service,repository';
            }

            $this->line('Building tests...');
            $crudGenerator->createTests($config);

            $this->line('Building factory...');
            $crudGenerator->createFactory($config);

            if (!$this->option('serviceOnly')) {
                $this->line('Building api...');
                $this->comment("\nAdd the following to your bootstrap/app.php: \n");
                $this->info("require __DIR__.'/../app/Http/api-routes.php'; \n");
                $crudGenerator->createApi($config);
            }
        } catch (Exception $e) {
            throw new Exception('Unable to generate your CRUD: '.$e->getMessage(), 1);
        }

        try {
            if ($this->option('migration')) {
                $this->line('Building migration...');
                if ($section) {
                    $migrationName = 'create_'.str_plural(strtolower(implode('_', $splitTable))).'_table';
                    Artisan::call('make:migration', [
                        'name'     => $migrationName,
                        '--table'  => str_plural(strtolower(implode('_', $splitTable))),
                        '--create' => true,
                    ]);
                } else {
                    $migrationName = 'create_'.str_plural(strtolower($table)).'_table';
                    Artisan::call('make:migration', [
                        'name'     => $migrationName,
                        '--table'  => str_plural(strtolower($table)),
                        '--create' => true,
                    ]);
                }

                if ($this->option('schema')) {
                    $migrationFiles = $filesystem->allFiles(base_path('database/migrations'));
                    foreach ($migrationFiles as $file) {
                        if (stristr($file->getBasename(), $migrationName)) {
                            $migrationData = file_get_contents($file->getPathname());
                            $parsedTable = '';

                            foreach (explode(',', $this->option('schema')) as $key => $column) {
                                $columnDefinition = explode(':', $column);
                                if ($key === 0) {
                                    $parsedTable .= "\$table->$columnDefinition[1]('$columnDefinition[0]');\n";
                                } else {
                                    $parsedTable .= "\t\t\t\$table->$columnDefinition[1]('$columnDefinition[0]');\n";
                                }
                            }

                            $migrationData = str_replace("\$table->increments('id');", $parsedTable, $migrationData);
                            file_put_contents($file->getPathname(), $migrationData);
                        }
                    }
                }
            } else {
                $this->info("\nYou will want to create a migration in order to get the $table tests to work correctly.\n");
            }
        } catch (Exception $e) {
            throw new Exception('Could not process the migration but your CRUD was generated', 1);
        }

        $this->info('You may wish to add this as your testing database');
        $this->comment("'testing' => [ 'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '' ],");
        $this->info('CRUD for '.$table.' is done.'."\n");
    }

    /**
     * Set the config.
     *
     * @param array  $config
     * @param string $section
     * @param string $table
     *
     * @return array
     */
    public function setConfig($config, $section, $table)
    {
        if (!is_null($section)) {
            foreach ($config as $key => $value) {
                $config[$key] = str_replace('_table_', ucfirst($table), str_replace('_section_', ucfirst($section), str_replace('_sectionLowerCase_', strtolower($section), $value)));
            }
        } else {
            foreach ($config as $key => $value) {
                $config[$key] = str_replace('_table_', ucfirst($table), $value);
            }
        }

        return $config;
    }
}
