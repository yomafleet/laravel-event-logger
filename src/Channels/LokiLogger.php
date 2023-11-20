<?php

namespace Yomafleet\EventLogger\Channels;

use Monolog\Logger;

class LokiLogger
{
    /**
     * Create a custom Monolog instance.
     *
     * @param  array  $config
     * @return \Monolog\Logger
     */
    public function __invoke(array $config)
    {
        $name = isset($config['service']) ? $config['service'].'loki' : 'loki';
        $logger = new Logger($name);
        $logger->pushHandler(new LokiLogHandler($config));

        return $logger;
    }
}
