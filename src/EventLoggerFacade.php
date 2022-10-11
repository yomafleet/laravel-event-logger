<?php

namespace Yomafleet\EventLogger;

use Illuminate\Support\Facades\Facade;

/**
 * @method static EventLoggerInterface log(string $level, string $message, array $data)
 *
 * @see \Yomafleet\EventLogger\EventLogger;
 */
class EventLoggerFacade extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return EventLogger::class;
    }
}
