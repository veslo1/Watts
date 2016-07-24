<?php

class PrepareCommandTest extends TestCase
{
    public function testApiCommand()
    {
        $status = $this->app['Illuminate\Contracts\Console\Kernel']->handle(
            $input = new \Symfony\Component\Console\Input\ArrayInput([
                'command' => 'watts:prepare',
                '--no-interaction' => true
            ]),
            $output = new \Symfony\Component\Console\Output\BufferedOutput
        );

        $this->assertContains('Please create an .env file', $output->fetch());
    }
}
