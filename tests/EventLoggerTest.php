<?php

namespace Tests;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Yomafleet\EventLogger\EventLoggerFacade as EventLogger;

class EventLoggerTest extends TestCase
{
    public function test_logger_not_run_if_disable()
    {
        Config::shouldReceive('get')
            ->once()
            ->with('logging.eventlog.disabled', false)
            ->andReturn(true);

        $level = 'info';
        $message = 'Example';
        $data = [
            'event'      => 'example.dummy',
            'trigger_by' => [
                'id'       => 1,
                'username' => 'Admin',
                'email'    => 'admin@example.com',
            ],
            'type' => 'example',
            'data' => ['key' => 'value'],
        ];

        $logged = EventLogger::log($level, $message, $data);
        $this->assertFalse($logged);
    }

    public function test_log_with_expected_data_structure()
    {
        $level = 'info';
        $message = 'Example';
        $data = [
            'event'      => 'example.dummy',
            'trigger_by' => [
                'id'       => 1,
                'username' => 'Admin',
                'email'    => 'admin@example.com',
            ],
            'type' => 'example',
            'data' => ['key' => 'value'],
        ];

        Log::shouldReceive($level)
            ->once()
            ->with($message, $data);

        EventLogger::log($level, $message, $data);

        $this->addToAssertionCount(1); // does not throw
    }

    public function test_event_log_populate_trigger_key_if_not_provided()
    {
        $level = 'info';
        $message = 'Example';
        $data = [
            'event' => 'example.dummy',
            'type'  => 'example',
            'data'  => ['key' => 'value'],
        ];

        Auth::shouldReceive('user')->once()->andReturn(null);
        Log::shouldReceive($level)
            ->once()
            ->with($message, $data + ['trigger_by' => [
                'id'       => 0,
                'username' => 'system',
                'email'    => '',
            ]]);

        EventLogger::log($level, $message, $data);
    }

    public function test_event_log_throws_if_no_event_key()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('event');

        $level = 'info';
        $message = 'Example';
        $data = [
            'trigger_by' => [
                'id'       => 1,
                'username' => 'Admin',
                'email'    => 'admin@example.com',
            ],
            'type' => 'example',
            'data' => ['key' => 'value'],
        ];

        EventLogger::log($level, $message, $data);
    }

    public function test_event_log_throws_if_no_data_key()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('data');

        $level = 'info';
        $message = 'Example';
        $data = [
            'event'      => 'example.dummy',
            'trigger_by' => [
                'id'       => 1,
                'username' => 'Admin',
                'email'    => 'admin@example.com',
            ],
            'type' => 'example',
        ];

        EventLogger::log($level, $message, $data);
    }

    public function test_event_log_throws_if_trigger_by_is_incomplete()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('username');

        $level = 'info';
        $message = 'Example';
        $data = [
            'event'      => 'example.dummy',
            'trigger_by' => [
                'id' => 1,
            ],
            'type' => 'example',
            'data' => ['key' => 'value'],
        ];

        EventLogger::log($level, $message, $data);
    }

    public function test_event_log_normalize_trigger_by()
    {
        $level = 'info';
        $message = 'Example';
        $data = [
            'event'      => 'example.dummy',
            'trigger_by' => [
                'id'       => 1,
                'username' => 'Admin',
                'email'    => 'admin@example.com',
            ],
            'type' => 'example',
            'data' => ['key' => 'value'],
        ];

        Log::shouldReceive($level)
            ->once()
            ->with($message, $data);

        $dataWithExtraInTriggerer = $data;
        $dataWithExtraInTriggerer['trigger_by']['extra_attributes'] = 'extra';

        EventLogger::log($level, $message, $data);
    }

    public function test_event_log_add_triggerer_as_system_if_not_provided()
    {
        $level = 'info';
        $message = 'Example';
        $data = [
            'event' => 'example.dummy',
            'type'  => 'example',
            'data'  => ['key' => 'value'],
        ];

        $dataWithTriggerer = $data + [
            'trigger_by' => [
                'id'       => 0,
                'username' => 'system',
                'email'    => '',
            ],
        ];

        Log::shouldReceive($level)
            ->once()
            ->with($message, $dataWithTriggerer);

        EventLogger::log($level, $message, $data);
    }

    public function test_event_log_add_triggerer_as_auth_user_if_not_provided()
    {
        $dummy = [
            'id'       => 1,
            'username' => 'Admin',
            'email'    => 'admin@example.com',
        ];
        Auth::shouldReceive('user')
            ->andReturn(new class($dummy) {
                public $data;

                public function __construct($data)
                {
                    $this->data = $data;
                }

                public function toArray()
                {
                    return $this->data;
                }
            });

        $level = 'info';
        $message = 'Example';
        $data = [
            'event' => 'example.dummy',
            'type'  => 'example',
            'data'  => ['key' => 'value'],
        ];

        $dataWithTriggerer = $data + [
            'trigger_by' => $dummy,
        ];

        Log::shouldReceive($level)
            ->once()
            ->with($message, $dataWithTriggerer);

        EventLogger::log($level, $message, $data);
    }

    public function test_event_log_add_target_if_not_provided()
    {
        $level = 'info';
        $message = 'Example';
        $data = [
            'event'      => 'example.dummy',
            'trigger_by' => [
                'id'       => 1,
                'username' => 'Admin',
                'email'    => 'admin@example.com',
            ],
            'data' => ['key' => 'value'],
        ];

        $dataWithTarget = $data + ['type' => 'example'];

        Log::shouldReceive($level)
            ->once()
            ->with($message, $dataWithTarget);

        EventLogger::log($level, $message, $data);
    }
}
