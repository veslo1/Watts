<?php

namespace Yab\Watts\Console;

use Exception;
use Illuminate\Console\AppNamespaceDetectorTrait;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Yab\Watts\Generators\CrudGenerator;

class Crud extends Command
{
    use AppNamespaceDetectorTrait;

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
        $splitTable = [];

        $table = ucfirst(str_singular($this->argument('table')));

        $this->validateSchema();

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
            '_app_namespace_'            => $this->getAppNamespace(),
            '_namespace_services_'       => $this->getAppNamespace().'Services',
            '_namespace_repository_'     => $this->getAppNamespace().'Repositories\_table_',
            '_namespace_model_'          => $this->getAppNamespace().'Repositories\_table_',
            '_namespace_controller_'     => $this->getAppNamespace().'Http\Controllers',
            '_namespace_api_controller_' => $this->getAppNamespace().'Http\Controllers\Api',
            '_namespace_request_'        => $this->getAppNamespace().'Http\Requests',
            '_table_name_'               => str_plural(strtolower($table)),
            '_lower_case_'               => strtolower($table),
            '_lower_casePlural_'         => str_plural(strtolower($table)),
            '_camel_case_'               => ucfirst(camel_case($table)),
            '_camel_casePlural_'         => str_plural(camel_case($table)),
            '_ucCamel_casePlural_'       => ucfirst(str_plural(camel_case($table))),
            'tests_generated'            => 'integration,service,repository',
        ];

        if (stristr($table, '_')) {
            $splitTable = explode('_', $table);
            $table = $splitTable[1];
            $section = $splitTable[0];
            $config = $this->configASectionedCRUD($config, $section, $table, $splitTable);
        } else {
            $config = $this->setConfig($config, $section, $table);
        }

        $this->createCRUD($config, $section, $table, $splitTable);

        $this->info('You may wish to add this as your testing database');
        $this->comment("'testing' => [ 'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '' ],");
        $this->info('CRUD for '.$table.' is done.'."\n");
    }

    /**
     * Create a CRUD
     *
     * @param  array $config
     * @param  string $section
     * @param  string $table
     * @param  array $splitTable
     * @return void
     */
    public function createCRUD($config, $section, $table, $splitTable)
    {
        $crudGenerator = new CrudGenerator();

        $config['schema'] = $this->option('schema', null);
        $config['relationships'] = $this->option('relationships', null);

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

        if ($this->option('migration')) {
            $this->createMigration($config, $section, $table, $splitTable);
            if ($this->option('schema')) {
                $this->prepareSchema($config, $section, $table, $splitTable);
            }
        } else {
            $this->info("\nYou will want to create a migration in order to get the $table tests to work correctly.\n");
        }
    }

    /**
     * Set the config of the CRUD
     *
     * @param  array $config
     * @param  string $section
     * @param  string $table
     * @param  array $splitTable
     * @return array
     */
    public function configASectionedCRUD($config, $section, $table, $splitTable)
    {
        $sectionalConfig = [
            '_sectionPrefix_'            => strtolower($section).'.',
            '_sectionTablePrefix_'       => strtolower($section).'_',
            '_sectionRoutePrefix_'       => strtolower($section).'/',
            '_sectionNamespace_'         => ucfirst($section).'\\',
            '_path_repository_'          => 'app/Repositories/'.ucfirst($section).'/'.ucfirst($table),
            '_path_model_'               => 'app/Repositories/'.ucfirst($section).'/'.ucfirst($table),
            '_path_api_controller_'      => 'app/Http/Controllers/Api/'.ucfirst($section).'/',
            '_path_api_routes_'          => 'app/Http/api-routes.php',
            'routes_prefix'              => "\n\n\$app->group(['namespace' => '".ucfirst($section)."', 'prefix' => '".strtolower($section)."', 'middleware' => ['web']], function () uses (\$app) { \n",
            'routes_suffix'              => "\n});",
            '_app_namespace_'            => $this->getAppNamespace(),
            '_namespace_services_'       => $this->getAppNamespace().'Services\\'.ucfirst($section),
            '_namespace_repository_'     => $this->getAppNamespace().'Repositories\\'.ucfirst($section).'\\'.ucfirst($table),
            '_namespace_model_'          => $this->getAppNamespace().'Repositories\\'.ucfirst($section).'\\'.ucfirst($table),
            '_namespace_api_controller_' => $this->getAppNamespace().'Http\Controllers\Api\\'.ucfirst($section),
            '_namespace_request_'        => $this->getAppNamespace().'Http\Requests\\'.ucfirst($section),
            '_namespace_request_'        => $this->getAppNamespace().'Http\Requests\\'.ucfirst($section),
            '_lower_case_'               => strtolower($splitTable[1]),
            '_lower_casePlural_'         => str_plural(strtolower($splitTable[1])),
            '_camel_case_'               => ucfirst(camel_case($splitTable[1])),
            '_camel_casePlural_'         => str_plural(camel_case($splitTable[1])),
            '_ucCamel_casePlural_'       => ucfirst(str_plural(camel_case($splitTable[1]))),
            '_table_name_'               => str_plural(strtolower(implode('_', $splitTable))),
        ];

        $config = array_merge($config, $sectionalConfig);
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

        return $config;
    }

    /**
     * Create the migrations
     *
     * @param  array $config
     * @param  string $section
     * @param  string $table
     * @param  array $splitTable
     * @return void
     */
    public function createMigration($config, $section, $table, $splitTable)
    {
        $this->line('Building migration...');
        try {
            if ($section) {
                $migrationName = 'create_'.str_plural(strtolower(implode('_', $splitTable))).'_table';
                $tableName = str_plural(strtolower(implode('_', $splitTable)));
            } else {
                $migrationName = 'create_'.str_plural(strtolower($table)).'_table';
                $tableName = str_plural(strtolower($table));
            }

            Artisan::call('make:migration', [
                'name'     => $migrationName,
                '--table'  => $tableName,
                '--create' => true,
            ]);
        } catch (Exception $e) {
            throw new Exception('Could not process the migration', 1);
        }
    }

    /**
     * Prepare the Schema
     *
     * @param  array $config
     * @param  string $section
     * @param  string $table
     * @param  array $splitTable
     * @return void
     */
    public function prepareSchema($config, $section, $table, $splitTable)
    {
        $filesystem = new Filesystem();

        $migrationFiles = $filesystem->allFiles(base_path('database/migrations'));

        if ($section) {
            $migrationName = 'create_'.str_plural(strtolower(implode('_', $splitTable))).'_table';
        } else {
            $migrationName = 'create_'.str_plural(strtolower($table)).'_table';
        }

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

    /**
     * Validate the Schema
     *
     * @return void
     */
    public function validateSchema()
    {
        if ($this->option('schema')) {
            foreach (explode(',', $this->option('schema')) as $column) {
                $columnDefinition = explode(':', $column);
                if (!in_array($columnDefinition[1], $this->columnTypes)) {
                    throw new Exception($columnDefinition[1].' is not in the array of valid column types: '.implode(', ', $this->columnTypes), 1);
                }
            }
        }
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
