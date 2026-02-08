<?php

namespace App\Jobs;

use App\Models\FiltrationProcess;
use App\Services\FiltrationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessFiltrationStageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $filtrationProcessId;
    public string $action;

    /**
     * Create a new job instance.
     */
    public function __construct(int $filtrationProcessId, string $action)
    {
        $this->filtrationProcessId = $filtrationProcessId;
        $this->action = $action;
    }

    /**
     * Execute the job.
     */
    public function handle(FiltrationService $filtrationService): void
    {
        Log::info('ProcessFiltrationStageJob: Executing', [
            'filtration_process_id' => $this->filtrationProcessId,
            'action' => $this->action
        ]);

        // Guard clause: Verify FiltrationProcess is still active
        $filtrationProcess = FiltrationProcess::find($this->filtrationProcessId);
        
        if (!$filtrationProcess) {
            Log::warning('ProcessFiltrationStageJob: Filtration process not found', [
                'filtration_process_id' => $this->filtrationProcessId,
                'action' => $this->action
            ]);
            return;
        }

        if ($filtrationProcess->status !== 'active') {
            Log::info('ProcessFiltrationStageJob: Filtration process not active, skipping', [
                'filtration_process_id' => $this->filtrationProcessId,
                'action' => $this->action,
                'status' => $filtrationProcess->status
            ]);
            return;
        }

        // Execute the appropriate action
        try {
            switch ($this->action) {
                case 'start_stage_2':
                    $filtrationService->startStage($this->filtrationProcessId, 2);
                    break;

                case 'start_stage_3':
                    $filtrationService->startStage($this->filtrationProcessId, 3);
                    break;

                case 'start_stage_4':
                    $filtrationService->startStage($this->filtrationProcessId, 4);
                    break;

                case 'complete_stage_2':
                    $filtrationService->completeStage($this->filtrationProcessId, 2);
                    break;

                case 'complete_stage_3':
                    $filtrationService->completeStage($this->filtrationProcessId, 3);
                    break;

                case 'evaluate_stage_4':
                    $filtrationService->evaluateStage4($this->filtrationProcessId);
                    break;

                default:
                    Log::warning('ProcessFiltrationStageJob: Unknown action', [
                        'filtration_process_id' => $this->filtrationProcessId,
                        'action' => $this->action
                    ]);
                    break;
            }

            Log::info('ProcessFiltrationStageJob: Completed', [
                'filtration_process_id' => $this->filtrationProcessId,
                'action' => $this->action
            ]);

        } catch (\Exception $e) {
            Log::error('ProcessFiltrationStageJob: Failed', [
                'filtration_process_id' => $this->filtrationProcessId,
                'action' => $this->action,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-throw to allow queue retry mechanism
            throw $e;
        }
    }
}
