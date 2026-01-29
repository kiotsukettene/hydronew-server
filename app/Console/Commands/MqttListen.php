<?php

namespace App\Console\Commands;

use App\Services\MQTTSensorDataHandlerService;
use Illuminate\Console\Command;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Illuminate\Support\Facades\Log;

class MqttListen extends Command
{
    protected $signature = 'mqtt:listen {--device-id=1 : Device ID to associate with readings}';
    protected $description = 'Listen to MQTT sensor topics continuously';

    protected $sensorHandler;

    public function __construct(MQTTSensorDataHandlerService $sensorHandler)
    {
        parent::__construct();
        $this->sensorHandler = $sensorHandler;
    }

    public function handle()
    {
        $deviceId = (int) $this->option('device-id');
        $this->info("Starting MQTT listener for Device ID: {$deviceId}...");

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

        $settings = (new ConnectionSettings())
            ->setUsername(env('MQTT_USERNAME', 'Biotech'))
            ->setPassword(env('MQTT_PASSWORD', ''))
            ->setUseTls(true)
            ->setTlsSelfSignedAllowed(true)
            ->setKeepAliveInterval(60)
            ->setLastWillTopic('device/status')
            ->setLastWillMessage('disconnected')
            ->setLastWillQualityOfService(1);

        try {
            $client->connect($settings, true);
            $this->info("✓ Connected to MQTT broker");

            // Subscribe to sensor topics
            $topics = [
                "hydronew/ai-classification/backend",
            ];

            foreach ($topics as $topic) {
                $client->subscribe($topic, function ($topic, $message) use ($deviceId) {
                    $this->handleMessage($topic, $message, $deviceId);
                }, 1); // QoS level 1 - at least once delivery
                
                $this->info("✓ Subscribed to: {$topic}");
            }

            $this->info("Listening for sensor data... (Press Ctrl+C to stop)");
            $this->newLine();

            // Keep the connection alive and process messages
            $client->loop(true, true);

        } catch (\Exception $e) {
            $this->error("MQTT Error: " . $e->getMessage());
            Log::error("MQTT Connection Error", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        } finally {
            if (isset($client)) {
                $client->disconnect();
                $this->info("Disconnected from MQTT broker");
            }
        }

        return Command::SUCCESS;
    }

    protected function handleMessage(string $topic, string $message, int $deviceId): void
    {
        try {
            $this->line("[{$topic}] Received: " . substr($message, 0, 200) . "...");

            // Decode JSON payload
            $data = json_decode($message, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->warn("⚠ Invalid JSON: " . json_last_error_msg());
                Log::warning("Invalid MQTT JSON payload", [
                    'topic' => $topic,
                    'message' => $message,
                    'error' => json_last_error_msg()
                ]);
                return;
            }

            // Check if this is AI classification topic
                $this->sensorHandler->handleAIClassificationPayload($data);
                $this->info("✓ Processed AI classification data");


            $this->newLine();

        } catch (\Exception $e) {
            $this->error("Error processing message: " . $e->getMessage());
            Log::error("MQTT Message Processing Error", [
                'topic' => $topic,
                'message' => $message,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}