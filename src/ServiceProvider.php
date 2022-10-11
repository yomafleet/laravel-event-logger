<?php

namespace Yomafleet\EventLogger;

use Illuminate\Support\ServiceProvider as BaseProvider;

class ServiceProvider extends BaseProvider
{
    public array $bindings = [
        EventLoggerInterface::class => EventLogger::class,
    ];
}
