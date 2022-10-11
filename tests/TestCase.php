<?php

namespace Tests;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * Get package providers.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array<int, string>
     */
    protected function getPackageProviders($app)
    {
        return [
            'Yomafleet\EventLogger\ServiceProvider',
        ];
    }

    /**
     * Override application aliases.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array<string, string>
     */
    protected function getPackageAliases($app)
    {
        return [
            'EventLogger' => 'Yomafleet\EventLogger\EventLoggerFacade',
        ];
    }
}
