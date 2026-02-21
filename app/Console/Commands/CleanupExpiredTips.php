<?php

namespace App\Console\Commands;

use App\Models\TipsSuggestion;
use Illuminate\Console\Command;

class CleanupExpiredTips extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tips:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete expired cached tips and suggestions (older than 24 hours)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Cleaning up expired tips and suggestions...');

        $deleted = TipsSuggestion::where('expires_at', '<', now())->delete();

        $this->info("Deleted {$deleted} expired tip(s).");

        return Command::SUCCESS;
    }
}
