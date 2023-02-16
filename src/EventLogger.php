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

    /**
     * Normalized "trigger_by" through configuration.
     *
     * @param array $data
     * @return array
     */
    protected function normalizeTriggerer($data)
    {
        $nameVariants = ['name', 'username'];
        $names = Config::get(
            'logging.eventlog.triggerer.names',
            $nameVariants
        );

        $nameKey = '';

        foreach ($names as $name) {
            if (isset($data['trigger_by'][$name])) {
                $nameKey = $name;
                break;
            }
        }

        if (!$nameKey) {
            throw new \InvalidArgumentException(
                "There is no name related field for 'trigger_by'"
            );
        }

        // set triggerer name in 'name' key
        $data['trigger_by']['name'] = $data['trigger_by'][$nameKey];
        
        // remove extra name variants
        $junkNameVariants = array_filter($nameVariants, fn($v) => $v !== 'name');
        foreach ($junkNameVariants as $junk) {
            unset($data['trigger_by'][$junk]);
        }

        $data['trigger_by'] = $this->checkTriggerFields($data['trigger_by']);

        return $data;
    }

    /**
     * Check triggerer's fields against sensible default or through
     * configuration and determine whether to throw or just add empty
     * value according to strict-mode configuration.
     *
     * @param array $triggerBy
     * @param array|null $keys
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function checkTriggerFields($triggerBy, $keys = null)
    {
        $defaultTriggererKeys = [
            'id',
            'name',
            'email',
            'phone',
        ];

        if ($keys === null) {
            $keys = Config::get(
                'logging.eventlog.triggerer.fields',
                $defaultTriggererKeys
            );
        }

        $isStrictTriggerer = Config::get(
            'logging.eventlog.triggerer.strict_mode',
            false
        );

        foreach ($keys as $key) {
            if (!isset($triggerBy[$key])) {
                if ($isStrictTriggerer) {
                    throw new \InvalidArgumentException(
                        "Key named '{$key}' is not found in 'trigger_by'"
                    );
                }

                $triggerBy[$key] = "";
            }
        }

        return $triggerBy;
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
                ? $user?->unsetRelations()?->toArray()
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
