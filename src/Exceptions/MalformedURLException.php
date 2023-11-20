<?php

namespace Yomafleet\EventLogger\Exceptions;

use Exception;
use Throwable;

class MalformedURLException extends Exception
{
    private const DEFAULT_MESSAGE = 'Malformed URL';

    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        $message = $message ?: static::DEFAULT_MESSAGE;
        parent::__construct($message, $code, $previous);
    }
}
