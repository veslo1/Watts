<?php

class GenerateApiKeyTest extends TestCase
{
    public function testHasHandler()
    {
        $command = new Yab\Watts\Console\GenerateApiKey();
        $methods = get_class_methods($command);

        $this->assertTrue(in_array('handle', $methods));
    }
}
