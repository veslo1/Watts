<?php

namespace Yab\Watts\Generators;

use Exception;
use Illuminate\Filesystem\Filesystem;

/**
 * Generate the CRUD
 */
class CrudGenerator
{
    protected $filesystem;

    public function __construct()
    {
        $this->filesystem = new Filesystem;
    }

    /**
     * Create the repository
     * @param  array $config
     * @return bool
     */
    public function createRepository($config)
    {
        if (! is_dir($config['_path_repository_'])) mkdir($config['_path_repository_'], 0777, true);
        if (! is_dir($config['_path_model_'])) mkdir($config['_path_model_'], 0777, true);

        $repo = file_get_contents($config['template_source'].'/Repository/Repository.txt');
        $model = file_get_contents($config['template_source'].'/Repository/Model.txt');

        if (! empty($config['schema'])) {
            $model = str_replace('// _camel_case_ table data', $this->prepareTableDefinition($config['schema']), $model);
        }

        if (! empty($config['relationships'])) {
            $relationships = [];

            foreach (explode(',', $config['relationships']) as $relationshipExpression) {
                $relationships[] = explode('|', $relationshipExpression);
            }

            $model = str_replace('// _camel_case_ relationships', $this->prepareModelRelationships($relationships), $model);
        }

        foreach ($config as $key => $value) {
            $repo = str_replace($key, $value, $repo);
            $model = str_replace($key, $value, $model);
        }

        $repository = file_put_contents($config['_path_repository_'].'/'.$config['_camel_case_'].'Repository.php', $repo);
        $model = file_put_contents($config['_path_model_'].'/'.$config['_camel_case_'].'.php', $model);

        return ($repository && $model);
    }

    /**
     * Create the service
     * @param  array $config
     * @return bool
     */
    public function createService($config)
    {
        if (! is_dir($config['_path_service_'])) mkdir($config['_path_service_'], 0777, true);

        $request = file_get_contents($config['template_source'].'/Service.txt');

        foreach ($config as $key => $value) {
            $request = str_replace($key, $value, $request);
        }

        $request = file_put_contents($config['_path_service_'].'/'.$config['_camel_case_'].'Service.php', $request);

        return $request;
    }

    /**
     * Append to the factory
     * @param  array $config
     * @return bool
     */
    public function createFactory($config)
    {
        $factory = file_get_contents($config['template_source'].'/Factory.txt');

        if (! empty($config['schema'])) {
            $factory = str_replace('// _camel_case_ table data', $this->prepareTableExample($config['schema']), $factory);
        }

        $factoryMaster = base_path('database/factories/ModelFactory.php');

        foreach ($config as $key => $value) {
            $factory = str_replace($key, $value, $factory);
        }

        return file_put_contents($factoryMaster, $factory, FILE_APPEND);
    }

    /**
     * Create the tests
     * @param  array $config
     * @return bool
     */
    public function createTests($config)
    {
        $testMakerResults = [];
        foreach (explode(',', $config['tests_generated']) as $testType) {
            $test = file_get_contents($config['template_source'].'/Tests/'.ucfirst($testType).'Test.txt');

            if (! empty($config['schema'])) {
                $test = str_replace('// _camel_case_ table data', $this->prepareTableExample($config['schema']), $test);
            }

            foreach ($config as $key => $value) {
                $test = str_replace($key, $value, $test);
            }

            if (! file_put_contents($config['_path_tests_'].'/'.$config['_camel_case_'].''.ucfirst($testType).'Test.php', $test)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create the Api
     * @param  array $config
     * @return bool
     */
    public function createApi($config, $appendRoutes = true)
    {
        if ($appendRoutes) {
            $routesMaster = 'app/Http/api-routes.php';
        } else {
            $routesMaster = $config['_path_api_routes_'];
        }

        if (! file_exists($routesMaster)) {
            file_put_contents($routesMaster, "<?php\n\n");
        }

        if (! is_dir($config['_path_api_controller_'])) {
            mkdir($config['_path_api_controller_'], 0777, true);
        }

        $routes = file_get_contents($config['template_source'].'/ApiRoutes.txt');

        foreach ($config as $key => $value) {
            $routes = str_replace($key, $value, $routes);
        }

        file_put_contents($routesMaster, $routes, FILE_APPEND);

        $request = file_get_contents($config['template_source'].'/ApiController.txt');

        foreach ($config as $key => $value) {
            $request = str_replace($key, $value, $request);
        }

        $request = file_put_contents($config['_path_api_controller_'].'/'.$config['_camel_case_'].'Controller.php', $request);

        return $request;
    }

    /**
     * Prepare a string of the table
     * @param  string $table
     * @return string
     */
    public function prepareTableDefinition($table)
    {
        $tableDefintion = '';

        foreach (explode(',', $table) as $column) {
            $columnDefinition = explode(':', $column);
            $tableDefintion .= "\t\t'$columnDefinition[0]',\n";
        }

        return $tableDefintion;
    }

    /**
     * Prepare a table array example
     * @param  string $table
     * @return string
     */
    public function prepareTableExample($table)
    {
        $tableExample = '';

        foreach (explode(',', $table) as $key => $column) {
            $columnDefinition = explode(':', $column);
            $example = $this->createExampleByType($columnDefinition[1]);
              if ($key === 0) {
                    $tableExample .= "'$columnDefinition[0]' => '$example',\n";
                } else {
                    $tableExample .= "\t\t'$columnDefinition[0]' => '$example',\n";
                }
        }

        return $tableExample;
    }

    /**
     * Prepare a models relationships
     *
     * @param  array $relationships
     * @return string
     */
    public function prepareModelRelationships($relationships)
    {
        $relationshipMethods = '';

        foreach ($relationships as $relation) {
            if (! isset($relation[2])) {
                $relation[2] = strtolower(end(explode('\\', $relation[1])));
            }

            $method = str_singular($relation[2]);

            if (stristr($relation[0], 'many')) {
                $method = str_plural($relation[2]);
            }

            $relationshipMethods .= "\n\tpublic function ".$method."() {";
            $relationshipMethods .= "\n\t\treturn \$this->$relation[0]($relation[1]::class);";
            $relationshipMethods .= "\n\t}";
        }

        return $relationshipMethods;
    }

    /**
     * Create an example by type for table definitions
     * @param  string  $type
     * @return mixed
     */
    public function createExampleByType($type)
    {
        switch ($type) {
            case 'bigIncrements':           return 1;
            case 'increments':              return 1;
            case 'string':                  return 'laravel';
            case 'boolean':                 return 1;
            case 'binary':                  return 'Its a bird, its a plane, no its Superman!';
            case 'char':                    return 'a';
            case 'ipAddress':               return '192.168.1.1';
            case 'macAddress':              return 'X1:X2:X3:X4:X5:X6';
            case 'json':                    return json_encode(['json' => 'test']);
            case 'text':                    return 'I am Batman';
            case 'longText':                return 'I am Batman';
            case 'mediumText':              return 'I am Batman';
            case 'dateTime':                return date('Y-m-d h:i:s');
            case 'date':                    return date('Y-m-d');
            case 'time':                    return date('h:i:s');
            case 'timestamp':               return time();
            case 'float':                   return 1.1;
            case 'decimal':                 return 1.1;
            case 'double':                  return 1.1;
            case 'integer':                 return 1;
            case 'bigInteger':              return 1;
            case 'mediumInteger':           return 1;
            case 'smallInteger':            return 1;
            case 'tinyInteger':             return 1;

            default:                        return 1;
        }
    }

}
