<?php

use org\bovigo\vfs\vfsStream;
use Yab\Watts\Generators\CrudGenerator;

class CrudApiGeneratorTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->generator = new CrudGenerator();
        $this->config = [
            'schema'                     => null,
            'relationships'              => null,
            '_path_service_'             => vfsStream::url('Services'),
            '_path_repository_'          => vfsStream::url('Repositories/'.ucfirst('testTable')),
            '_path_model_'               => vfsStream::url('Repositories/'.ucfirst('testTable')),
            '_path_api_controller_'      => vfsStream::url('Http/Controllers/Api'),
            '_path_tests_'               => vfsStream::url('tests'),
            '_path_api_routes_'          => vfsStream::url('Http/api-routes.php'),
            'routes_prefix'              => '',
            'routes_suffix'              => '',
            '_namespace_services_'       => 'App\Services',
            '_namespace_repository_'     => 'App\Repositories\\'.ucfirst('testTable'),
            '_namespace_model_'          => 'App\Repositories\\'.ucfirst('testTable'),
            '_namespace_api_controller_' => 'App\Http\Controllers\Api',
            '_lower_case_'               => strtolower('testTable'),
            '_lower_casePlural_'         => str_plural(strtolower('testTable')),
            '_camel_case_'               => ucfirst(camel_case('testTable')),
            '_camel_casePlural_'         => str_plural(camel_case('testTable')),
            'template_source'            => __DIR__.'/../src/Templates',
            'tests_generated'            => 'integration,service,repository',
        ];
    }

    public function testApiGenerator()
    {
        $this->crud = vfsStream::setup('Http/Controllers/Api');
        $this->generator->createApi($this->config, false);
        $this->assertTrue($this->crud->hasChild('Http/Controllers/Api/TestTableController.php'));
        $contents = $this->crud->getChild('Http/Controllers/Api/TestTableController.php');
        $this->assertTrue(strpos($contents->getContent(), 'class TestTableController extends Controller') !== false);
    }
}
