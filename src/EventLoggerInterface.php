<?php

namespace Yomafleet\EventLogger;

interface EventLoggerInterface
{
    /**
     * Log data with rigid format.
     *
     * @param string $level
     * @param string $message
     * @param array  $data
     *
     * @return void
     */
    public function log(string $level, string $message, array $data);
}
