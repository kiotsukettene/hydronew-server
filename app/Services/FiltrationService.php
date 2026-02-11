<?php

namespace App\Services;

use App\Jobs\ProcessFiltrationStageJob;
use App\Models\Device;
use App\Models\FiltrationProcess;
use App\Models\SensorReading;
use App\Models\SensorSystem;
use App\Models\TreatmentReport;
use App\Models\TreatmentStage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FiltrationService
{
    protected MqttService $mqttService;

    public function __construct(MqttService $mqttService)
    {
        $this->mqttService = $mqttService;
    }

    /**
     * Handle pump 3 acknowledgment (Stage 1 start)
     * Ack=1 means pump started successfully
     */
    public function handlePump3Ack(string $deviceSerial): void
    {
        Log::info('FiltrationService: handlePump3Ack', ['serial' => $deviceSerial]);

        try {
            $device = Device::where('serial_number', $deviceSerial)->first();
            if (!$device) {
                Log::warning('FiltrationService: Device not found', ['serial' => $deviceSerial]);
                return;
            }

            DB::transaction(function () use ($device, $deviceSerial) {
                // Create TreatmentReport
                $treatmentReport = TreatmentReport::create([
                    'device_id' => $device->id,
                    'start_time' => now(),
                    'end_time' => null,
                    'final_status' => 'pending',
                    'total_cycles' => null,
                ]);

                // Create FiltrationProcess
                $filtrationProcess = FiltrationProcess::create([
                    'device_id' => $device->id,
                    'treatment_report_id' => $treatmentReport->id,
                    'status' => 'active',
                    'pump_3_state' => true,
                    'valve_1_state' => false,
                    'valve_2_state' => false,
                    'stage_1_started_at' => now(),
                    'stages_2_4_started_at' => null,
                    'restart_count' => 0,
                ]);

                // Create Stage 1 (MFC) - processing
                TreatmentStage::create([
                    'treatment_id' => $treatmentReport->id,
                    'stage_name' => 'MFC',
                    'stage_order' => 1,
                    'status' => 'processing',
                    'started_at' => now(),
                    'completed_at' => null,
                ]);

                Log::info('FiltrationService: Stage 1 started', [
                    'serial' => $deviceSerial,
                    'filtration_process_id' => $filtrationProcess->id,
                    'treatment_report_id' => $treatmentReport->id,
                ]);
            });

            // Publish stage 1 state
            $this->publishStageState($deviceSerial, 1, 'processing');

        } catch (\Exception $e) {
            Log::error('FiltrationService: handlePump3Ack failed', [
                'serial' => $deviceSerial,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle valve 1 state changes
     * State: 1=open, 0=closed
     */
    public function handleValve1State(string $deviceSerial, int $stateValue): void
    {
        Log::info('FiltrationService: handleValve1State', [
            'serial' => $deviceSerial,
            'state' => $stateValue
        ]);

        try {
            $device = Device::where('serial_number', $deviceSerial)->first();
            if (!$device) {
                Log::warning('FiltrationService: Device not found', ['serial' => $deviceSerial]);
                return;
            }

            $filtrationProcess = FiltrationProcess::where('device_id', $device->id)
                ->where('status', 'active')
                ->first();

            if (!$filtrationProcess) {
                Log::info('FiltrationService: No active filtration process', ['serial' => $deviceSerial]);
                return;
            }

            // Update valve state
            $filtrationProcess->update(['valve_1_state' => (bool)$stateValue]);

            // If valve closed (0) and Stage 1 is processing, complete Stage 1 and start Stage 2-4 cycle
            if ($stateValue === 0) {
                $stage1 = TreatmentStage::where('treatment_id', $filtrationProcess->treatment_report_id)
                    ->where('stage_order', 1)
                    ->where('status', 'processing')
                    ->first();

                if ($stage1) {
                    DB::transaction(function () use ($filtrationProcess, $stage1, $deviceSerial) {
                        // Complete Stage 1
                        $stage1->update([
                            'status' => 'passed',
                            'completed_at' => now(),
                        ]);

                        // Update filtration process
                        $filtrationProcess->update([
                            'stages_2_4_started_at' => now(),
                        ]);

                        Log::info('FiltrationService: Stage 1 completed (valve state), starting Stage 2-4 cycle', [
                            'serial' => $deviceSerial,
                            'filtration_process_id' => $filtrationProcess->id,
                        ]);

                        // Publish stage 1 passed
                        $this->publishStageState($deviceSerial, 1, 'passed');

                        // Dispatch all 6 timed jobs for stages 2-4
                        $this->dispatchStage24Jobs($filtrationProcess->id);

                        // Start Stage 2 immediately so frontend gets "processing" without waiting for queue
                        $this->startStage($filtrationProcess->id, 2);
                    });
                } else {
                    Log::warning('FiltrationService: Valve 1 closed but Stage 1 not in processing – skipping completion', [
                        'serial' => $deviceSerial,
                        'treatment_report_id' => $filtrationProcess->treatment_report_id,
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('FiltrationService: handleValve1State failed', [
                'serial' => $deviceSerial,
                'state' => $stateValue,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle valve 1 acknowledgment (when IoT sends ack=1 but no state message).
     * Treats ack as "command executed" and toggles valve state, then publishes state so frontend stays in sync.
     */
    public function handleValve1Ack(string $deviceSerial): void
    {
        Log::info('FiltrationService: handleValve1Ack', ['serial' => $deviceSerial]);

        try {
            $device = Device::where('serial_number', $deviceSerial)->first();
            if (!$device) {
                Log::warning('FiltrationService: Device not found', ['serial' => $deviceSerial]);
                return;
            }

            $filtrationProcess = FiltrationProcess::where('device_id', $device->id)
                ->where('status', 'active')
                ->first();

            if (!$filtrationProcess) {
                Log::info('FiltrationService: No active filtration process', ['serial' => $deviceSerial]);
                return;
            }

            // Ack=1 means command executed; new state is the opposite of current (OPEN/CLOSE toggled)
            $newState = $filtrationProcess->valve_1_state ? 0 : 1;

            // If we would toggle to "open" but Stage 1 was already completed (stages_2_4 started), this is a late ack – keep valve closed
            if ($newState === 1 && $filtrationProcess->stages_2_4_started_at !== null) {
                $newState = 0;
            }
            $filtrationProcess->update(['valve_1_state' => (bool)$newState]);

            // Publish valve 1 state so frontend can update UI
            $this->publishValve1State($deviceSerial, $newState);

            // If valve closed (0) and Stage 1 is processing, complete Stage 1 and start Stage 2-4 cycle
            if ($newState === 0) {
                $stage1 = TreatmentStage::where('treatment_id', $filtrationProcess->treatment_report_id)
                    ->where('stage_order', 1)
                    ->where('status', 'processing')
                    ->first();

                if ($stage1) {
                    DB::transaction(function () use ($filtrationProcess, $stage1, $deviceSerial) {
                        $stage1->update([
                            'status' => 'passed',
                            'completed_at' => now(),
                        ]);
                        $filtrationProcess->update(['stages_2_4_started_at' => now()]);
                        Log::info('FiltrationService: Stage 1 completed (from valve ack), starting Stage 2-4 cycle', [
                            'serial' => $deviceSerial,
                            'filtration_process_id' => $filtrationProcess->id,
                        ]);
                        $this->publishStageState($deviceSerial, 1, 'passed');
                        $this->dispatchStage24Jobs($filtrationProcess->id);

                        // Start Stage 2 immediately so frontend gets "processing" without waiting for queue
                        $this->startStage($filtrationProcess->id, 2);
                    });
                } else {
                    Log::warning('FiltrationService: Valve 1 ack (closed) but Stage 1 not in processing – skipping completion', [
                        'serial' => $deviceSerial,
                        'treatment_report_id' => $filtrationProcess->treatment_report_id,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('FiltrationService: handleValve1Ack failed', [
                'serial' => $deviceSerial,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle valve 2 (drain valve) state changes
     * State: 1=open, 0=closed
     */
    public function handleValve2State(string $deviceSerial, int $stateValue): void
    {
        Log::info('FiltrationService: handleValve2State', [
            'serial' => $deviceSerial,
            'state' => $stateValue
        ]);

        try {
            $device = Device::where('serial_number', $deviceSerial)->first();
            if (!$device) {
                return;
            }

            $filtrationProcess = FiltrationProcess::where('device_id', $device->id)
                ->where('status', 'active')
                ->first();

            if ($filtrationProcess) {
                $filtrationProcess->update(['valve_2_state' => (bool)$stateValue]);
            }

        } catch (\Exception $e) {
            Log::error('FiltrationService: handleValve2State failed', [
                'serial' => $deviceSerial,
                'state' => $stateValue,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle valve 2 (drain valve) acknowledgment. When ack=1, toggle state and publish so frontend stays in sync.
     */
    public function handleValve2Ack(string $deviceSerial): void
    {
        Log::info('FiltrationService: handleValve2Ack', ['serial' => $deviceSerial]);

        try {
            $device = Device::where('serial_number', $deviceSerial)->first();
            if (!$device) {
                Log::warning('FiltrationService: Device not found', ['serial' => $deviceSerial]);
                return;
            }

            $filtrationProcess = FiltrationProcess::where('device_id', $device->id)
                ->where('status', 'active')
                ->first();

            if (!$filtrationProcess) {
                Log::info('FiltrationService: No active filtration process for valve 2 ack', ['serial' => $deviceSerial]);
                return;
            }

            $newState = $filtrationProcess->valve_2_state ? 0 : 1;
            $filtrationProcess->update(['valve_2_state' => (bool)$newState]);
            $this->publishValve2State($deviceSerial, $newState);
        } catch (\Exception $e) {
            Log::error('FiltrationService: handleValve2Ack failed', [
                'serial' => $deviceSerial,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle restart pump acknowledgment
     * Restart from Stage 2 (re-run stages 2-4)
     */
    public function handleRestartPumpAck(string $deviceSerial): void
    {
        Log::info('FiltrationService: handleRestartPumpAck', ['serial' => $deviceSerial]);

        try {
            $device = Device::where('serial_number', $deviceSerial)->first();
            if (!$device) {
                Log::warning('FiltrationService: Device not found', ['serial' => $deviceSerial]);
                return;
            }

            $filtrationProcess = FiltrationProcess::where('device_id', $device->id)
                ->where('status', 'active')
                ->first();

            if (!$filtrationProcess) {
                Log::warning('FiltrationService: No active filtration process for restart', ['serial' => $deviceSerial]);
                return;
            }

            DB::transaction(function () use ($filtrationProcess, $deviceSerial) {
                // Reset stages 2-4 to pending
                TreatmentStage::where('treatment_id', $filtrationProcess->treatment_report_id)
                    ->whereIn('stage_order', [2, 3, 4])
                    ->update([
                        'status' => 'pending',
                        'started_at' => null,
                        'completed_at' => null,
                    ]);

                // Increment restart count
                $filtrationProcess->increment('restart_count');
                $filtrationProcess->update([
                    'stages_2_4_started_at' => now(),
                ]);

                Log::info('FiltrationService: Restarting stages 2-4', [
                    'serial' => $deviceSerial,
                    'filtration_process_id' => $filtrationProcess->id,
                    'restart_count' => $filtrationProcess->restart_count,
                ]);

                // Publish stage states as pending
                $this->publishStageState($deviceSerial, 2, 'pending');
                $this->publishStageState($deviceSerial, 3, 'pending');
                $this->publishStageState($deviceSerial, 4, 'pending');

                // Re-dispatch all timed jobs for stages 2-4
                $this->dispatchStage24Jobs($filtrationProcess->id);
            });

        } catch (\Exception $e) {
            Log::error('FiltrationService: handleRestartPumpAck failed', [
                'serial' => $deviceSerial,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Dispatch all timed jobs for stages 2-4
     */
    protected function dispatchStage24Jobs(int $filtrationProcessId): void
    {
        // Dispatch schedule from T=0 (Stage 2 start):
        // 0s    - start_stage_2    - Immediate
        // 60s   - start_stage_3    - Stage 3 begins
        // 70s   - start_stage_4    - Stage 4 begins (60+10)
        // 1510s - complete_stage_2 - 25min + 10s
        // 1520s - complete_stage_3 - 25min + 20s
        // 1550s - evaluate_stage_4 - 25min + 50s

        ProcessFiltrationStageJob::dispatch($filtrationProcessId, 'start_stage_2')
            ->delay(now()->addSeconds(0));

        ProcessFiltrationStageJob::dispatch($filtrationProcessId, 'start_stage_3')
            ->delay(now()->addSeconds(60));

        ProcessFiltrationStageJob::dispatch($filtrationProcessId, 'start_stage_4')
            ->delay(now()->addSeconds(70));

        ProcessFiltrationStageJob::dispatch($filtrationProcessId, 'complete_stage_2')
            ->delay(now()->addSeconds(1510));

        ProcessFiltrationStageJob::dispatch($filtrationProcessId, 'complete_stage_3')
            ->delay(now()->addSeconds(1520));

        ProcessFiltrationStageJob::dispatch($filtrationProcessId, 'evaluate_stage_4')
            ->delay(now()->addSeconds(1550));

        Log::info('FiltrationService: Dispatched 6 timed jobs for stages 2-4', [
            'filtration_process_id' => $filtrationProcessId
        ]);
    }

    /**
     * Check automatic valve 1 conditions based on sensor data
     * Called from MQTTSensorDataHandlerService after saving sensor readings
     */
    public function checkAutoValveConditions(int $deviceId, string $waterType, array $sensorData): void
    {
        // Only check for dirty_water type
        if ($waterType !== 'dirty_water') {
            return;
        }

        try {
            $filtrationProcess = FiltrationProcess::where('device_id', $deviceId)
                ->where('status', 'active')
                ->first();

            if (!$filtrationProcess) {
                return;
            }

            $device = $filtrationProcess->device;
            $waterLevel = $sensorData['WaterLevel'] ?? $sensorData['water_level'] ?? null;
            $electricCurrent = $sensorData['ElectricCurrent'] ?? $sensorData['electric_current'] ?? null;

            if ($waterLevel === null) {
                return;
            }

            // OPEN condition: water_level >= 100 AND dirty_water.electric_current < 10 AND stage 1 started more than 1 day ago AND valve not open
            if (!$filtrationProcess->valve_1_state && $waterLevel >= 100) {
                $currentOk = $electricCurrent !== null && (float)$electricCurrent < 10;
                $stage1OldEnough = $filtrationProcess->stage_1_started_at &&
                    $filtrationProcess->stage_1_started_at->diffInHours(now()) >= 24;
                if ($currentOk && $stage1OldEnough) {
                    Log::info('FiltrationService: Auto-opening valve 1', [
                        'device_id' => $deviceId,
                        'water_level' => $waterLevel,
                        'electric_current' => $electricCurrent,
                        'stage_1_started_at' => $filtrationProcess->stage_1_started_at,
                    ]);

                    $this->publishCommand("mfc/{$device->serial_number}/valve/1", 'OPEN');
                }
            }

            // CLOSE condition: water_level < 6 only (valve is open and tank drained)
            if ($filtrationProcess->valve_1_state && $waterLevel < 6) {
                Log::info('FiltrationService: Auto-closing valve 1', [
                    'device_id' => $deviceId,
                    'water_level' => $waterLevel,
                ]);

                $this->publishCommand("mfc/{$device->serial_number}/valve/1", 'CLOSE');
            }

        } catch (\Exception $e) {
            Log::error('FiltrationService: checkAutoValveConditions failed', [
                'device_id' => $deviceId,
                'water_type' => $waterType,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Start a stage (create TreatmentStage record with processing status)
     */
    public function startStage(int $filtrationProcessId, int $stageNumber): void
    {
        Log::info('FiltrationService: startStage', [
            'filtration_process_id' => $filtrationProcessId,
            'stage_number' => $stageNumber
        ]);

        try {
            $filtrationProcess = FiltrationProcess::find($filtrationProcessId);
            if (!$filtrationProcess || $filtrationProcess->status !== 'active') {
                Log::info('FiltrationService: Filtration process not active, skipping startStage', [
                    'filtration_process_id' => $filtrationProcessId,
                    'stage_number' => $stageNumber
                ]);
                return;
            }

            $stageNames = [
                2 => 'Natural Filter',
                3 => 'UV Filter',
                4 => 'Clean Water Tank',
            ];

            $stageName = $stageNames[$stageNumber] ?? 'Unknown';
            $device = $filtrationProcess->device;

            // Check if stage already exists
            $stage = TreatmentStage::where('treatment_id', $filtrationProcess->treatment_report_id)
                ->where('stage_order', $stageNumber)
                ->first();

            if ($stage) {
                // Update existing stage to processing
                $stage->update([
                    'status' => 'processing',
                    'started_at' => now(),
                ]);
            } else {
                // Create new stage
                TreatmentStage::create([
                    'treatment_id' => $filtrationProcess->treatment_report_id,
                    'stage_name' => $stageName,
                    'stage_order' => $stageNumber,
                    'status' => 'processing',
                    'started_at' => now(),
                    'completed_at' => null,
                ]);
            }

            Log::info('FiltrationService: Stage started', [
                'filtration_process_id' => $filtrationProcessId,
                'stage_number' => $stageNumber,
                'stage_name' => $stageName
            ]);

            $this->publishStageState($device->serial_number, $stageNumber, 'processing');

        } catch (\Exception $e) {
            Log::error('FiltrationService: startStage failed', [
                'filtration_process_id' => $filtrationProcessId,
                'stage_number' => $stageNumber,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Complete a stage (update TreatmentStage to passed).
     * Ensures order: stage N only completes after stage N-1 is passed (completes previous stage if still processing).
     */
    public function completeStage(int $filtrationProcessId, int $stageNumber): void
    {
        Log::info('FiltrationService: completeStage', [
            'filtration_process_id' => $filtrationProcessId,
            'stage_number' => $stageNumber
        ]);

        try {
            $filtrationProcess = FiltrationProcess::find($filtrationProcessId);
            if (!$filtrationProcess || $filtrationProcess->status !== 'active') {
                Log::info('FiltrationService: Filtration process not active, skipping completeStage', [
                    'filtration_process_id' => $filtrationProcessId,
                    'stage_number' => $stageNumber
                ]);
                return;
            }

            // Ensure previous stage is passed first (handles queue jobs running out of order)
            if ($stageNumber > 2) {
                $prevStage = TreatmentStage::where('treatment_id', $filtrationProcess->treatment_report_id)
                    ->where('stage_order', $stageNumber - 1)
                    ->first();
                if ($prevStage && $prevStage->status !== 'passed') {
                    Log::info('FiltrationService: Completing previous stage first', [
                        'filtration_process_id' => $filtrationProcessId,
                        'completing_stage' => $stageNumber - 1
                    ]);
                    $this->completeStage($filtrationProcessId, $stageNumber - 1);
                }
            }

            $stage = TreatmentStage::where('treatment_id', $filtrationProcess->treatment_report_id)
                ->where('stage_order', $stageNumber)
                ->where('status', 'processing')
                ->first();

            if ($stage) {
                $stage->update([
                    'status' => 'passed',
                    'completed_at' => now(),
                ]);

                $device = $filtrationProcess->device;

                Log::info('FiltrationService: Stage completed', [
                    'filtration_process_id' => $filtrationProcessId,
                    'stage_number' => $stageNumber
                ]);

                $this->publishStageState($device->serial_number, $stageNumber, 'passed');

                // Re-publish previous stage passed so UI stays in sync if an earlier publish was lost
                if ($stageNumber === 3) {
                    $this->publishStageState($device->serial_number, 2, 'passed');
                }
            }

        } catch (\Exception $e) {
            Log::error('FiltrationService: completeStage failed', [
                'filtration_process_id' => $filtrationProcessId,
                'stage_number' => $stageNumber,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Evaluate Stage 4 completion based on AI classification
     */
    public function evaluateStage4(int $filtrationProcessId): void
    {
        Log::info('FiltrationService: evaluateStage4', [
            'filtration_process_id' => $filtrationProcessId
        ]);

        try {
            $filtrationProcess = FiltrationProcess::find($filtrationProcessId);
            if (!$filtrationProcess || $filtrationProcess->status !== 'active') {
                Log::info('FiltrationService: Filtration process not active, skipping evaluateStage4', [
                    'filtration_process_id' => $filtrationProcessId
                ]);
                return;
            }

            $device = $filtrationProcess->device;

            // Ensure stages 2 and 3 are passed before evaluating (handles queue jobs running out of order)
            foreach ([2, 3] as $stageNum) {
                $stage = TreatmentStage::where('treatment_id', $filtrationProcess->treatment_report_id)
                    ->where('stage_order', $stageNum)
                    ->first();
                if ($stage && $stage->status !== 'passed') {
                    Log::info('FiltrationService: Completing stage before evaluateStage4', [
                        'filtration_process_id' => $filtrationProcessId,
                        'stage' => $stageNum
                    ]);
                    $this->completeStage($filtrationProcessId, $stageNum);
                }
            }

            // Evaluate clean_water AI classification across the Stage 4 window.
            $cleanWaterSystem = SensorSystem::where('device_id', $device->id)
                ->where('system_type', 'clean_water')
                ->first();

            $stage4 = TreatmentStage::where('treatment_id', $filtrationProcess->treatment_report_id)
                ->where('stage_order', 4)
                ->first();

            $stage4StartedAt = $stage4?->started_at;
            if (!$stage4StartedAt) {
                // Fallback to a reasonable window if stage start time is missing for any reason.
                $stage4StartedAt = now()->subMinutes(30);
                Log::warning('FiltrationService: Stage 4 started_at missing; using fallback window', [
                    'filtration_process_id' => $filtrationProcessId,
                    'fallback_started_at' => $stage4StartedAt,
                ]);
            }

            $goodCount = 0;
            $badCount = 0;

            if ($cleanWaterSystem) {
                $counts = SensorReading::where('sensor_system_id', $cleanWaterSystem->id)
                    ->whereNotNull('ai_classification')
                    ->where('reading_time', '>=', $stage4StartedAt)
                    ->where('reading_time', '<=', now())
                    ->select('ai_classification', DB::raw('COUNT(*) as cnt'))
                    ->groupBy('ai_classification')
                    ->pluck('cnt', 'ai_classification');

                $goodCount = (int)($counts['good'] ?? 0);
                $badCount = (int)($counts['bad'] ?? 0);
            }

            $total = $goodCount + $badCount;

            // Fail/restart Stage 4 only when bad classifications outnumber good classifications.
            // If there is no data in the window, treat as passed (keeps current behavior).
            if ($total > 0 && $badCount > $goodCount) {
                Log::info('FiltrationService: Stage 4 AI classification majority bad, publishing restart notification', [
                    'filtration_process_id' => $filtrationProcessId,
                    'good_count' => $goodCount,
                    'bad_count' => $badCount,
                    'stage_4_started_at' => $stage4StartedAt,
                ]);

                // Update existing Stage 4 to failed only (do not create new rows)
                if ($stage4) {
                    $stage4->update([
                        'status' => 'failed',
                        'completed_at' => now(),
                    ]);
                }
                $this->publishStageState($device->serial_number, 4, 'failed');

                $this->publishCommand("filtration/{$device->serial_number}/restart", '1');
                return;
            }

            // Otherwise: pass Stage 4 (no data, or good >= bad)
            Log::info('FiltrationService: Stage 4 AI classification summary (treating as passed)', [
                'filtration_process_id' => $filtrationProcessId,
                'good_count' => $goodCount,
                'bad_count' => $badCount,
                'total' => $total,
                'stage_4_started_at' => $stage4StartedAt,
            ]);

            // Complete Stage 4 and mark treatment as success
            DB::transaction(function () use ($filtrationProcess, $device) {
                $stage4 = TreatmentStage::where('treatment_id', $filtrationProcess->treatment_report_id)
                    ->where('stage_order', 4)
                    ->first();

                if ($stage4) {
                    $stage4->update([
                        'status' => 'passed',
                        'completed_at' => now(),
                    ]);
                    $this->publishStageState($device->serial_number, 4, 'passed');
                }

                $filtrationProcess->treatment_report->update([
                    'final_status' => 'success',
                    'end_time' => now(),
                    'total_cycles' => $filtrationProcess->restart_count + 1,
                ]);

                $filtrationProcess->update(['status' => 'completed']);

                Log::info('FiltrationService: Treatment completed successfully', [
                    'filtration_process_id' => $filtrationProcess->id,
                    'restart_count' => $filtrationProcess->restart_count
                ]);
            });

            // Always publish stages 2–4 passed so UI stays in sync (covers any lost earlier publish)
            $this->publishStageState($device->serial_number, 2, 'passed');
            $this->publishStageState($device->serial_number, 3, 'passed');
            $this->publishStageState($device->serial_number, 4, 'passed');

            if ($filtrationProcess->restart_count > 0) {
                $this->publishCommand("filtration/{$device->serial_number}/restart", 'CLOSE');
                $this->publishCommand("reservoir_fallback/{$device->serial_number}/pump/1", 'CLOSE');
                Log::info('FiltrationService: Restart process completed – CLOSE for restart UI and pump', [
                    'serial' => $device->serial_number
                ]);
            }

        } catch (\Exception $e) {
            Log::error('FiltrationService: evaluateStage4 failed', [
                'filtration_process_id' => $filtrationProcessId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Publish MQTT command
     */
    public function publishCommand(string $topic, string $command): void
    {
        $this->mqttService->publish($topic, $command, 1);
    }

    /**
     * Publish Start Process command (OPEN to pump/3). On ack=1, handlePump3Ack runs and publishes stage state.
     */
    public function publishStartProcessCommand(string $deviceSerial): void
    {
        $this->publishCommand("mfc/{$deviceSerial}/pump/3", 'OPEN');
        Log::info('FiltrationService: Published start process command', ['serial' => $deviceSerial]);
    }

    /**
     * Publish Open Valve 1 command. On ack=1, handleValve1Ack runs and publishes valve 1 state.
     */
    public function publishOpenValve1Command(string $deviceSerial): void
    {
        $this->publishCommand("mfc/{$deviceSerial}/valve/1", 'OPEN');
        Log::info('FiltrationService: Published open valve 1 command', ['serial' => $deviceSerial]);
    }

    /**
     * Publish Close Valve 1 command. On ack=1, handleValve1Ack runs and publishes valve 1 state.
     */
    public function publishCloseValve1Command(string $deviceSerial): void
    {
        $this->publishCommand("mfc/{$deviceSerial}/valve/1", 'CLOSE');
        Log::info('FiltrationService: Published close valve 1 command', ['serial' => $deviceSerial]);
    }

    /**
     * Publish Open Drain Valve command (valve/2). On ack=1, handleValve2Ack runs and publishes valve 2 state.
     */
    public function publishOpenDrainValveCommand(string $deviceSerial): void
    {
        $this->publishCommand("mfc_fallback/{$deviceSerial}/valve/2", 'OPEN');
        Log::info('FiltrationService: Published open drain valve command', ['serial' => $deviceSerial]);
    }

    /**
     * Publish Close Drain Valve command (valve/2). On ack=1, handleValve2Ack runs and publishes valve 2 state.
     */
    public function publishCloseDrainValveCommand(string $deviceSerial): void
    {
        $this->publishCommand("mfc_fallback/{$deviceSerial}/valve/2", 'CLOSE');
        Log::info('FiltrationService: Published close drain valve command', ['serial' => $deviceSerial]);
    }

    /**
     * Publish Restart command (OPEN to reservoir pump/1). On ack=1, handleRestartPumpAck runs and publishes stage states.
     */
    public function publishRestartCommand(string $deviceSerial): void
    {
        $this->publishCommand("reservoir_fallback/{$deviceSerial}/pump/1", 'OPEN');
        Log::info('FiltrationService: Published restart command', ['serial' => $deviceSerial]);
    }

    /**
     * Publish valve 1 state so frontend can sync UI (e.g. when only ack received, no state from IoT)
     */
    public function publishValve1State(string $deviceSerial, int $stateValue): void
    {
        $topic = "mfc/{$deviceSerial}/valve/1/state";
        $this->mqttService->publish($topic, (string)$stateValue, 1);
        Log::info('FiltrationService: Published valve 1 state', [
            'topic' => $topic,
            'state' => $stateValue
        ]);
    }

    /**
     * Publish valve 2 (drain) state so frontend can sync UI when ack received.
     */
    public function publishValve2State(string $deviceSerial, int $stateValue): void
    {
        $topic = "mfc_fallback/{$deviceSerial}/valve/2/state";
        $this->mqttService->publish($topic, (string)$stateValue, 1);
        Log::info('FiltrationService: Published valve 2 state', [
            'topic' => $topic,
            'state' => $stateValue
        ]);
    }

    /**
     * Publish stage state for frontend UI sync
     */
    public function publishStageState(string $deviceSerial, int $stageNumber, string $status): void
    {
        $topic = "filtration/{$deviceSerial}/stage/{$stageNumber}/state";
        $this->mqttService->publish($topic, $status, 1);

        Log::info('FiltrationService: Published stage state', [
            'topic' => $topic,
            'status' => $status
        ]);
    }
}
