<?php

namespace Yomafleet\EventLogger;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class EventLogger implements EventLoggerInterface
{
    /**
     * Log data with rigid format.
     *
     * @param string $level
     * @param string $message
     * @param array  $data
     *
     * @return bool
     */
    public function log(string $level, string $message, array $data)
    {
        $logDisable = Config::get('logging.eventlog.disabled', false);

        if ($logDisable) {
            return false;
        }

        $data = $this->tryPopulateMendatoryData($data);

        $this->validate($data);

        $data = $this->normalizeTriggerer($data);

        Log::$level($message, $data);

        return true;
    }

    protected function normalizeTriggerer($data)
    {
        $names = Config::get(
            'logging.eventlog.triggerer.names',
            ['name', 'username']
        );

        $nameKey = '';

        foreach ($names as $name) {
            if (isset($data['trigger_by'][$name])) {
                $nameKey = $name;
            }
        }

        if (!$nameKey) {
            throw new \InvalidArgumentException(
                "There is no name related field for 'trigger_by'"
            );
        }

        // set triggerer name in 'name' key
        $data['trigger_by']['name'] = $data['trigger_by'][$nameKey];

        $keys = Config::get(
            'logging.eventlog.triggerer.fields',
            ['id', 'name', 'email']
        );

        foreach ($keys as $key) {
            if (!isset($data['trigger_by'][$key])) {
                throw new \InvalidArgumentException(
                    "Key named '{$key}' is not found in 'trigger_by'"
                );
            }
        }

        $data['trigger_by'] = array_intersect_key(
            $data['trigger_by'],
            array_flip($keys)
        );

        return $data;
    }

    /**
     * Try to populate some mendatory data if not provided.
     *
     * @param array $data
     *
     * @return array
     */
    protected function tryPopulateMendatoryData(array $data)
    {
        // try to add triggerer if not specified
        if (!isset($data['trigger_by'])) {
            /** @var mixed */
            $user = Auth::user() ?? null;
            $data['trigger_by'] = $user
                ? $user?->toArray()
                : ['id' => 0, 'name' => 'system', 'email' => ''];
        }

        // get type from event key if not specified
        if (!isset($data['type']) && isset($data['event'])) {
            $data['type'] = explode('.', $data['event'])[0];
        }

        return $data;
    }

    /**
     * Validate whether given data has required attributes.
     *
     * @param array $data
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    protected function validate(array $data)
    {
        if (!isset($data['event'])) {
            throw new \InvalidArgumentException(
                "Key named 'event' is not found!"
            );
        }

        if (!isset($data['data'])) {
            throw new \InvalidArgumentException(
                "Key named 'data' is not found!"
            );
        }
    }
}
