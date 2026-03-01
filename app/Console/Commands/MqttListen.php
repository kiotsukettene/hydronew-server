<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Jobs\CheckDeviceOfflineJob;
use App\Services\MQTTSensorDataHandlerService;
use App\Services\FiltrationService;
use Illuminate\Console\Command;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Illuminate\Support\Facades\Log;

class MqttListen extends Command
{
    protected $signature = 'mqtt:listen {--device-id=1 : Device ID to associate with readings}';
    protected $description = 'Listen to MQTT sensor topics continuously (runs forever until process is stopped; auto-reconnects on disconnect)';

    protected $sensorHandler;
    protected $filtrationService;

    public function __construct(MQTTSensorDataHandlerService $sensorHandler, FiltrationService $filtrationService)
    {
        parent::__construct();
        $this->sensorHandler = $sensorHandler;
        $this->filtrationService = $filtrationService;
    }

    public function handle()
    {
        $deviceId = (int) $this->option('device-id');
        $this->info("Starting MQTT listener for Device ID: {$deviceId}...");

        // Get default connection from config
        $connectionName = config('mqtt-client.default_connection');
        $config = config("mqtt-client.connections.$connectionName");

        // Keep-alive: 120 seconds to reduce "No ping response in time" on slow/unstable networks
        $keepAlive = (int) env('MQTT_KEEP_ALIVE_INTERVAL', 120);

        // Persistent session: broker keeps subscriptions and delivers messages that arrived while offline
        $usePersistentSession = filter_var(env('MQTT_USE_PERSISTENT_SESSION', true), FILTER_VALIDATE_BOOLEAN);

        $settings = (new ConnectionSettings())
            ->setUsername(env('MQTT_USERNAME', 'Biotech'))
            ->setPassword(env('MQTT_PASSWORD', ''))
            ->setUseTls(true)
            ->setTlsSelfSignedAllowed(true)
            ->setKeepAliveInterval($keepAlive)
            ->setLastWillTopic('device/status')
            ->setLastWillMessage('disconnected')
            ->setLastWillQualityOfService(1);

        $reconnectDelay = (int) env('MQTT_RECONNECT_DELAY_SECONDS', 10);

        // Unique suffix per user/device: hydronew_backend_{serial} or hydronew_backend_user_{id} or hydronew_backend_device_{id}
        $clientIdSuffix = $this->resolveClientIdSuffix($deviceId);

        // Run forever: each reconnection uses a NEW client instance so subscriptions and message loop work correctly
        while (true) {
            $client = null;
            try {
                // Persistent session requires stable client ID so broker can restore subscriptions and deliver queued messages
                $baseId = $config['client_id'] ?? 'laravel_mqtt_hydronew';
                $clientId = $usePersistentSession
                    ? ($baseId . '_' . $clientIdSuffix)
                    : ($baseId . '_' . $clientIdSuffix . '_' . uniqid());
                $client = new MqttClient(
                    $config['host'],
                    $config['port'],
                    $clientId,
                    MqttClient::MQTT_3_1
                );

                // cleanSession=false: broker keeps subscriptions and delivers offline messages on reconnect
                $useCleanSession = !$usePersistentSession;
                $client->connect($settings, $useCleanSession);
                $this->info("✓ Connected to MQTT broker (keep-alive: {$keepAlive}s, client: {$clientId}, persistent: " . ($usePersistentSession ? 'yes' : 'no') . ")");

                // Brief delay so broker connection is fully ready before we send subscriptions
                usleep(500000); // 0.5 second

                // Subscribe to sensor topics and filtration topics
                $topics = [
                    "hydronew/ai-classification/backend",
                    "biotech/+/heartbeat",
                    // Filtration topics with wildcards
                    "mfc/+/pump/3/ack",
                    "mfc/+/pump/3/state",
                    "mfc/+/valve/1/ack",
                    "mfc/+/valve/1/state",
                    "mfc_fallback/+/valve/2/ack",
                    "mfc_fallback/+/valve/2/state",
                    "reservoir_fallback/+/pump/1/ack",
                    "reservoir_fallback/+/pump/1/state",
                ];

                foreach ($topics as $topic) {
                    $client->subscribe($topic, function ($topic, $message) use ($deviceId) {
                        $this->handleMessage($topic, $message, $deviceId);
                    }, 1); // QoS level 1 - at least once delivery
                    
                    $this->info("✓ Subscribed to: {$topic}");
                }

                $this->info("Listening for sensor data... (Press Ctrl+C to stop)");
                $this->newLine();

                // Run loop until connection dies (exitWhenQueuesEmpty=false so we don't exit before SUBACKs are processed)
                $client->loop(true, false);

            } catch (\Exception $e) {
                $this->error("MQTT Error: " . $e->getMessage());
                Log::error("MQTT Connection Error", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                // Safely disconnect if still connected (avoids throw in finally)
                if (isset($client) && $client->isConnected()) {
                    try {
                        $client->disconnect();
                    } catch (\Throwable $discEx) {
                        Log::warning("MQTT disconnect after error", ['error' => $discEx->getMessage()]);
                    }
                }

                $this->warn("Reconnecting in {$reconnectDelay} seconds...");
                sleep($reconnectDelay);
                continue;
            } finally {
                // Safely disconnect (never throw from finally - client may already be dead)
                if (isset($client)) {
                    try {
                        if ($client->isConnected()) {
                            $client->disconnect();
                            $this->info("Disconnected from MQTT broker");
                        }
                    } catch (\Throwable $e) {
                        Log::debug("MQTT disconnect in finally", ['error' => $e->getMessage()]);
                    }
                }
            }
        }

        return Command::SUCCESS;
    }

    protected function handleMessage(string $topic, string $message, int $deviceId): void
    {
        try {
            $this->line("[{$topic}] Received: " . substr($message, 0, 200) . "...");

            // Route to appropriate handler based on topic
            if ($topic === 'hydronew/ai-classification/backend') {
                $this->handleAIClassificationTopic($message);
            } elseif (preg_match('#^biotech/([^/]+)/heartbeat$#', $topic, $matches)) {
                $this->handleHeartbeatTopic($matches[1]);
            } else {
                $this->handleFiltrationTopic($topic, $message);
            }

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

    protected function handleAIClassificationTopic(string $message): void
    {
        // Decode JSON payload
        $data = json_decode($message, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->warn("⚠ Invalid JSON: " . json_last_error_msg());
            Log::warning("Invalid MQTT JSON payload", [
                'message' => $message,
                'error' => json_last_error_msg()
            ]);
            return;
        }

        $this->sensorHandler->handleAIClassificationPayload($data);
        $this->info("✓ Processed AI classification data");
    }

    /**
     * Handle biotech/{serial}/heartbeat – device is online.
     * Updates device status and last_heartbeat_at; if a paused treatment has water in anode, re-opens valve 1.
     * Dispatches a job to run in 90s: if no newer heartbeat by then, device is marked offline.
     */
    protected function handleHeartbeatTopic(string $serial): void
    {
        $this->info("✓ Heartbeat received for device {$serial}");
        $this->filtrationService->onDeviceOnline($serial);

        CheckDeviceOfflineJob::dispatch($serial)
            ->delay(now()->addSeconds(90));
    }

    protected function handleFiltrationTopic(string $topic, string $message): void
    {
        // Parse message as integer (ack/state values are always integers: 1 or 0)
        $value = (int)trim($message);

        // Topic patterns:
        // mfc/{serial}/pump/3/ack
        // mfc/{serial}/pump/3/state
        // mfc/{serial}/valve/1/ack
        // mfc/{serial}/valve/1/state
        // mfc_fallback/{serial}/valve/2/ack
        // mfc_fallback/{serial}/valve/2/state
        // reservoir_fallback/{serial}/pump/1/ack
        // reservoir_fallback/{serial}/pump/1/state

        // Parse pump/3 ack: only process when ack=1 (command executed). ack=0 means did not execute.
        if (preg_match('#^mfc/([^/]+)/pump/3/ack$#', $topic, $matches)) {
            $serial = $matches[1];
            if ($value !== 1) {
                $this->warn("⚠ Pump 3 ack=0 for device {$serial} (command did not execute, skipping)");
                return;
            }
            $this->info("✓ Pump 3 ack received for device {$serial}");
            $this->filtrationService->handlePump3Ack($serial);
            return;
        }

        // Parse valve/1 ack: when ack=1, update state and publish so frontend stays in sync (in case IoT didn't send state)
        if (preg_match('#^mfc/([^/]+)/valve/1/ack$#', $topic, $matches)) {
            $serial = $matches[1];
            if ($value !== 1) {
                $this->warn("⚠ Valve 1 ack=0 for device {$serial} (command did not execute, skipping)");
                return;
            }
            $this->info("✓ Valve 1 ack received for device {$serial}");
            $this->filtrationService->handleValve1Ack($serial);
            return;
        }

        // Parse valve/1 state (we track state changes from IoT)
        if (preg_match('#^mfc/([^/]+)/valve/1/state$#', $topic, $matches)) {
            $serial = $matches[1];
            $this->info("✓ Valve 1 state={$value} for device {$serial}");
            $this->filtrationService->handleValve1State($serial, $value);
            return;
        }

        // Parse valve/2 ack (drain valve): when ack=1, update state and publish so frontend stays in sync
        if (preg_match('#^mfc_fallback/([^/]+)/valve/2/ack$#', $topic, $matches)) {
            $serial = $matches[1];
            if ($value !== 1) {
                $this->warn("⚠ Valve 2 (drain) ack=0 for device {$serial} (command did not execute, skipping)");
                return;
            }
            $this->info("✓ Valve 2 (drain) ack received for device {$serial}");
            $this->filtrationService->handleValve2Ack($serial);
            return;
        }

        // Parse valve/2 state (drain valve)
        if (preg_match('#^mfc_fallback/([^/]+)/valve/2/state$#', $topic, $matches)) {
            $serial = $matches[1];
            $this->info("✓ Valve 2 (drain) state={$value} for device {$serial}");
            $this->filtrationService->handleValve2State($serial, $value);
            return;
        }

        // Parse restart pump/1 ack: only process when ack=1 (command executed). ack=0 means did not execute.
        if (preg_match('#^reservoir_fallback/([^/]+)/pump/1/ack$#', $topic, $matches)) {
            $serial = $matches[1];
            if ($value !== 1) {
                $this->warn("⚠ Restart pump ack=0 for device {$serial} (command did not execute, skipping)");
                return;
            }
            $this->info("✓ Restart pump ack received for device {$serial}");
            $this->filtrationService->handleRestartPumpAck($serial);
            return;
        }

        // Log unhandled filtration topics for debugging
        Log::debug("MqttListen: Unhandled filtration topic", [
            'topic' => $topic,
            'message' => $message
        ]);
    }

    /**
     * Resolve a unique suffix for the MQTT client ID: user ID of the user connected to this device.
     * Format: "user_{id}". Fallback "device_{id}" if no user is linked.
     */
    protected function resolveClientIdSuffix(int $deviceId): string
    {
        $device = Device::find($deviceId);

        if (!$device) {
            return 'device_' . $deviceId;
        }

        $firstUser = $device->users()->first();
        if ($firstUser) {
            return 'user_' . $firstUser->id;
        }

        return 'device_' . $deviceId;
    }
}