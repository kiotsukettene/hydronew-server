<?php

namespace App\Services;

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Illuminate\Support\Facades\Log;

class MqttService
{
    protected ?MqttClient $client = null;

    public function __construct()
    {
        // IMPORTANT: Do not touch mqtt config here.
        // This service may be resolved during `artisan package:discover`.
    }

    protected function getClient(): MqttClient
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $connectionName = config('mqtt-client.default_connection');
        $config = config("mqtt-client.connections.$connectionName");

        $host = $config['host'] ?? null;
        $port = $config['port'] ?? null;

        if (!is_string($host) || $host === '' || !is_numeric($port)) {
            throw new \RuntimeException('MQTT is not configured (missing host/port).');
        }

        $clientId = $config['client_id'] ?? ('laravel_' . uniqid());

        return $this->client = new MqttClient(
            $host,
            (int) $port,
            $clientId,
            MqttClient::MQTT_3_1
        );
    }

    protected function connect(): void
    {
        $client = $this->getClient();

        $settings = (new ConnectionSettings())
            ->setUsername(env('MQTT_USERNAME', 'Biotech'))
            ->setPassword(env('MQTT_PASSWORD', ''))
            ->setUseTls(true)
            ->setTlsSelfSignedAllowed(true);

        $client->connect($settings, true);
    }

    public function publish(string $topic, string|array $payload, int $qos = 1, bool $retain = false): void
    {
        try {
            $this->connect();

            $this->client->publish(
                $topic,
                json_encode($payload),
                $qos,
                $retain
            );

            Log::info("Published to MQTT topic {$topic}", ['payload' => $payload, 'retain' => $retain]);

        } catch (\Throwable $e) {
            // This ensures CI/composer scripts won't fail if MQTT isn't configured
            Log::warning("MQTT publish skipped/failed", [
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);
        } finally {
            if ($this->client) {
                try { $this->client->disconnect(); } catch (\Throwable $e) {}
            }
        }
    }
}
