<?php

namespace App\Services;

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Illuminate\Support\Facades\Log;

class MqttService
{
    protected MqttClient $client;

    public function __construct()
    {
        $connectionName = config('mqtt-client.default_connection');
        $config = config("mqtt-client.connections.$connectionName");

        $host = $config['host'] ?? null;
        $port = $config['port'] ?? null;

        // If config isn't available (common during CI/composer scripts), don't hard-crash.
        if (!is_string($host) || $host === '' || !is_numeric($port)) {
            throw new \RuntimeException(
                'MQTT is not configured. Check config(mqtt-client.*) and ensure host/port are set.'
            );
        }

        $clientId = $config['client_id'] ?? ('laravel_' . uniqid());

        return new MqttClient(
            $host,
            (int) $port,
            $clientId,
            MqttClient::MQTT_3_1
        );
    }

    protected function connect(): void
    {
        $settings = (new ConnectionSettings())
            ->setUsername(env('MQTT_USERNAME', 'Biotech'))
            ->setPassword(env('MQTT_PASSWORD', ''))
            ->setUseTls(true)
            ->setTlsSelfSignedAllowed(true);

        $this->client->connect($settings, true);
    }

    /**
     * Publish a message to a topic.
     * @param string $topic
     * @param string|array $payload
     * @param int $qos
     * @param bool $retain Retain the message for offline subscribers
     */
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

        } catch (\Exception $e) {
            Log::error("Failed to publish MQTT message", [
                'topic' => $topic,
                'payload' => $payload,
                'retain' => $retain,
                'error' => $e->getMessage()
            ]);
        } finally {
            $this->client->disconnect();
        }
    }
}
