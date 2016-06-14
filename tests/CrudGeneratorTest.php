<?php

use org\bovigo\vfs\vfsStream;
use Yab\Watts\Generators\CrudGenerator;

class CrudGeneratorTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->generator = new CrudGenerator();
        $this->config = [
            'schema'                     => null,
            'relationships'              => null,
            '_path_facade_'              => vfsStream::url('Facades'),
            '_path_service_'             => vfsStream::url('Services'),
            '_path_repository_'          => vfsStream::url('Repositories/'.ucfirst('testTable')),
            '_path_model_'               => vfsStream::url('Repositories/'.ucfirst('testTable')),
            '_path_controller_'          => vfsStream::url('Http/Controllers'),
            '_path_views_'               => vfsStream::url('resources/views'),
            '_path_tests_'               => vfsStream::url('tests'),
            '_path_routes_'              => vfsStream::url('Http/routes.php'),
            'routes_prefix'              => '',
            'routes_suffix'              => '',
            '_namespace_services_'       => 'App\Services',
            '_namespace_repository_'     => 'App\Repositories\\'.ucfirst('testTable'),
            '_namespace_model_'          => 'App\Repositories\\'.ucfirst('testTable'),
            '_namespace_controller_'     => 'App\Http\Controllers',
            '_lower_case_'               => strtolower('testTable'),
            '_lower_casePlural_'         => str_plural(strtolower('testTable')),
            '_camel_case_'               => ucfirst(camel_case('testTable')),
            '_camel_casePlural_'         => str_plural(camel_case('testTable')),
            'template_source'            => __DIR__.'/../src/Templates',
            'tests_generated'            => 'integration,service,repository',
        ];
    }

    public function testRepositoryGenerator()
    {
        $this->crud = vfsStream::setup("Repositories/TestTable");
        $this->generator->createRepository($this->config);
        $this->assertTrue($this->crud->hasChild('Repositories/TestTable/TestTableRepository.php'));
        $contents = $this->crud->getChild('Repositories/TestTable/TestTableRepository.php');
        $this->assertTrue(strpos($contents->getContent(), 'class TestTableRepository') !== false);
    }

    public function testServiceGenerator()
    {
        $this->crud = vfsStream::setup("Services");
        $this->generator->createService($this->config);
        $this->assertTrue($this->crud->hasChild('Services/TestTableService.php'));
        $contents = $this->crud->getChild('Services/TestTableService.php');
        $this->assertTrue(strpos($contents->getContent(), 'class TestTableService') !== false);
    }

    public function testTestGenerator()
    {
        $this->crud = vfsStream::setup("tests");
        $this->generator->createTests($this->config);
        $this->assertTrue($this->crud->hasChild('tests/TestTableIntegrationTest.php'));
        $contents = $this->crud->getChild('tests/TestTableIntegrationTest.php');
        $this->assertTrue(strpos($contents->getContent(), 'class TestTableIntegrationTest') !== false);
        $this->assertTrue($this->crud->hasChild('tests/TestTableRepositoryTest.php'));
        $contents = $this->crud->getChild('tests/TestTableRepositoryTest.php');
        $this->assertTrue(strpos($contents->getContent(), 'class TestTableRepositoryTest') !== false);
        $this->assertTrue($this->crud->hasChild('tests/TestTableServiceTest.php'));
        $contents = $this->crud->getChild('tests/TestTableServiceTest.php');
        $this->assertTrue(strpos($contents->getContent(), 'class TestTableServiceTest') !== false);
    }

    /*
    |--------------------------------------------------------------------------
    | Other method tests
    |--------------------------------------------------------------------------
    */

    public function testPrepareTableDefinition()
    {
        $table = "id:increments,name:string,details:text";
        $result = $this->generator->prepareTableDefinition($table);

        $this->assertTrue((bool) strstr($result, 'id'));
        $this->assertTrue((bool) strstr($result, 'name'));
        $this->assertTrue((bool) strstr($result, 'details'));
    }

    public function testPrepareTableExample()
    {
        $table = "id:increments,name:string,details:text,created_on:dateTime";
        $result = $this->generator->prepareTableExample($table);

        $this->assertTrue((bool) strstr($result, 'laravel'));
        $this->assertTrue((bool) strstr($result, 'I am Batman'));
        $this->assertTrue((bool) strstr($result, '1'));
    }

}
