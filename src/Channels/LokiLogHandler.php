<?php

namespace Yomafleet\EventLogger\Channels;

use Carbon\Carbon;
use Monolog\Logger;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Http;
use Monolog\Handler\AbstractProcessingHandler;
use Yomafleet\EventLogger\Exceptions\MalformedURLException;
use Yomafleet\EventLogger\Exceptions\ConfigurationMissingException;

class LokiLogHandler extends AbstractProcessingHandler
{
    protected string $url;

    protected array $config;

    /**
     * @param array $config
     * @param int|string $level  The minimum logging level at which this handler will be triggered
     * @param bool       $bubble Whether the messages that are handled can bubble up the stack or not
     *
     * @phpstan-param Level|LevelName|LogLevel::* $level
     */
    public function __construct(array $config, $level = Logger::DEBUG, bool $bubble = true)
    {
        parent::__construct($level, $bubble);

        $this->config = $config;
        $this->url = $this->buildUrlFromConfig($config);
    }

    protected function buildUrlFromConfig(array $config)
    {
        if (!isset($config['url'])) {
            throw new ConfigurationMissingException("Configuration: 'url' is missing");
        }

        $url = $config['url'];

        if (!URL::isValidUrl($url)) {
            throw new MalformedURLException();
        }

        if (isset($config['id']) && $config['token']) {
            // amend url with basith auth
            $parsed = parse_url($url);

            $build = Str::of($parsed['scheme'])
                ->append('://')
                ->append($config['id'])
                ->append(':')
                ->append($config['token'])
                ->append('@')
                ->append($parsed['host']);

            if (isset($parsed['port'])) {
                $build
                    ->append(':')
                    ->append($parsed['port']);
            }

            $build->append($parsed['path']);

            $url = (string) $build;
        }

        return $url;
    }

    /**
     * @inheritDoc
     */
    protected function write(array $record): void
    {
        $this->validate();

        $record = $this->formatRecord($record);

        $this->send($record);
    }

    /**
     * Formats the given record
     *
     * @param array $record
     * @return array
     */
    protected function formatRecord(array $record): array
    {
        $nanoEpoch = Carbon::now()->timestamp * 1_000_000_000;

        $record = $this->trimRecord($record);

        return [
            'streams' => [
                [
                    'stream' => ['service' => $this->config['service']],
                    'values' => [
                        [(string) $nanoEpoch, json_encode($record)]
                    ]
                ]
            ],
        ];
    }

     /**
     * Trims the given record
     *
     * @param array $record
     * @return array
     */
    protected function trimRecord(array $record): array
    {
        // remove formatted
        unset($record['formatted']);

        // flatten context
        if (isset($record['context'])) {
            $record = array_merge($record, $record['context']);
            unset($record['context']);
        }

        return $record;
    }

    /**
     * Send the recored to Loki server
     *
     * @param array $record
     * @return void
     */
    protected function send(array $record)
    {
        $promised = Http::async()
            ->asJson()
            ->post($this->url, $record)
            ->then(function ($response) use ($record) {
                if (!$response->successful()) {
                    Log::channel($this->getErrorLogClient())->alert(
                        "Loki log error",
                        ['error' => $response->toException(), 'record' => $record]
                    );
                }
            });

        $promised->wait();
    }

    /**
     * Gets the log client to puts log if Loki respond with error
     *
     * @return string
     */
    protected function getErrorLogClient()
    {
        return isset($this->config['error_client'])
            ? $this->config['error_client']
            : 'daily';
    }

    /**
     * Validates the required parameters
     *
     * @return void
     */
    protected function validate()
    {
        if (!isset($this->config['service'])) {
            throw new ConfigurationMissingException("Configuration: 'service' is missing");
        }

        if (!$this->url) {
            throw new ConfigurationMissingException("Configuration: 'url' is missing");
        }
    }
}
