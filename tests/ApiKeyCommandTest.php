<?php

class ApiKeyCommandTest extends TestCase
{
    public function testApiCommand()
    {
        file_put_contents(getcwd().'/.env', '');

        $status = $this->app['Illuminate\Contracts\Console\Kernel']->handle(
            $input = new \Symfony\Component\Console\Input\ArrayInput([
                'command' => 'watts:api',
                '--no-interaction' => true
            ]),
            $output = new \Symfony\Component\Console\Output\BufferedOutput
        );

        $this->assertContains('API_KEY=', file_get_contents(getcwd().'/.env'));
        $this->assertContains('Now build your minions!', $output->fetch());
        unlink(getcwd().'/.env');
    }
}
