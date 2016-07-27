<?php

class PrepareTest extends TestCase
{
    public function testHasHandler()
    {
        $command = new Yab\Watts\Console\Prepare();
        $methods = get_class_methods($command);

        $this->assertTrue(in_array('handle', $methods));
    }
}
