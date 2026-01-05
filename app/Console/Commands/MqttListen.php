<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

class MqttListen extends Command
{
    protected $signature = 'mqtt:listen';
    protected $description = 'Listen to MQTT topics continuously';

    public function handle()
    {
        $this->info("Starting MQTT listener...");

        // Get default connection from config
        $connectionName = config('mqtt-client.default_connection');
        $config = config("mqtt-client.connections.$connectionName");

        $clientId = $config['client_id'] ?? 'laravel_' . uniqid();

        $client = new MqttClient(
            $config['host'],
            $config['port'],
            $clientId,
            MqttClient::MQTT_3_1
        );

        $settingsArray = $config['connection_settings'] ?? [];
        $settings = (new ConnectionSettings())
            ->setUsername(env('MQTT_USERNAME', 'Biotech'))
            ->setPassword(env('MQTT_PASSWORD', ''))
            ->setUseTls(true)
            ->setTlsSelfSignedAllowed(true); // only if using self-signed certs

        $client->connect($settings, true);

        $client->subscribe('test/topic', function ($topic, $message) {
            $this->info("Received on {$topic}: {$message}");
        });

        $client->loop(true); // blocks and listens forever
    }
}
