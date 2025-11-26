# Event Logger

Event logging with Yomafleet's preferred format. Built on top of [Monolog](https://github.com/Seldaek/monolog) with support for Loki logging.

## Requirements

- PHP 8.2 or higher
- Laravel 12.x
- Monolog 3.x (automatically installed with Laravel 12)

## Installation

```bash
composer require yomafleet/event-logger
```

The service provider will be automatically registered.

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
        $data = [
            'event' => 'user.updated',
            'data' => [
                'user_id' => 123,
                'changes' => ['email' => 'new@example.com']
            ],
            'trigger_by' => [
                'id' => 1,
                'name' => 'Admin',
                'email' => 'admin@example.com'
            ],
            'type' => 'user'
        ];

        EventLogger::log('info', 'User profile updated', $data);
    }
}
```

## Configuration

### Event Log Settings

Add to your `config/logging.php`:

```php
'eventlog' => [
    'disabled' => env('EVENTLOG_DISABLED', false),
    'dispatch' => [
        'connection' => env('EVENTLOG_DISPATCH_CONNECTION', false), // e.g., 'redis'
        'after_commit' => env('EVENTLOG_DISPATCH_AFTERCOMMIT', true),
        'queue' => env('EVENTLOG_DISPATCH_QUEUE', 'default'),
    ],
    'triggerer' => [
        'fields' => ['id', 'name', 'email'], // Required fields in trigger_by
        'names' => ['name', 'username'], // Accepted name field variants
        'strict_mode' => false, // Throw exception if fields are missing
    ],
],
```

### Loki Logger Configuration

To use the Loki logging channel, add to your `config/logging.php` channels:

```php
'loki' => [
    'driver' => 'custom',
    'id' => env('LOKI_ID'),
    'token' => env('LOKI_TOKEN'),
    'url' => env('LOKI_URL', 'http://localhost:3100/loki/api/v1/push'),
    'via' => \Yomafleet\EventLogger\Channels\LokiLogger::class,
    'service' => env('LOKI_SERVICE_NAME', 'laravel-app'),
    'error_client' => env('LOKI_ERROR_CLIENT', 'daily')
],
```

Then add to your `.env`:

```env
LOKI_URL=http://your-loki-server:3100/loki/api/v1/push
LOKI_ID=your-loki-user
LOKI_TOKEN=your-loki-token
LOKI_SERVICE_NAME=your-app-name
```

## Features

- **Automatic trigger detection**: Automatically populates `trigger_by` from authenticated user
- **Queue support**: Optionally dispatch logging to queue for async processing
- **Loki integration**: Built-in support for Grafana Loki logging
- **Laravel 12 optimized**: Built specifically for Laravel 12 with Monolog 3
- **Configurable validation**: Strict or lenient mode for required fields

## Testing

Run tests with Docker:

```bash
./test.sh
```

Or manually:

```bash
docker-compose up -d
docker-compose exec app composer install
docker-compose exec app ./vendor/bin/phpunit
```

## License

MIT
