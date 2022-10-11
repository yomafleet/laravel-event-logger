# Event Logger

Event logging with yomafleet's prefer format. Use [monolog package](https://github.com/Seldaek/monolog) underlying.

## Installtion

`composer require yomafleet/event-logger`

## Parameters

| name                | type   | description                                                                                                                       |
| ------------------- | ------ | --------------------------------------------------------------------------------------------------------------------------------- |
| $level              | String | Log level as in [RFC 5424](https://datatracker.ietf.org/doc/html/rfc5424)                                                         |
| $message            | String | Short and descriptive message                                                                                                     |
| $data               | Array  | Payload array with required keys.                                                                                                 |
| $data['event']      | String | Name of the event separated by dot. For example, `user.updated`                                                                   |
| $data['data']       | Array  | Arbitrary data to log.                                                                                                            |
| $data['trigger_by'] | Array  | The trigger of the given event. If not provided, this app will try to guess from authenticated user first, then as a system user. |
| $data['type]        | String | Type of the $data['data'], for example, `user`. If not provided, the first segement of $data['event'] will be added.              |

## Usage

```php
<?php

namespace App;

use Yomafleet\EventLogger\EventLoggerFacade as EventLogger;

class Example
{
    public function example()
    {
        //...
        //...
        //...
        EventLogger::log(
            'info',
            'example',
            $data
        );
    }
}

```
