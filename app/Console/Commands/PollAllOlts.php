<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Olt;
use App\Jobs\PollOltStatus;
use Illuminate\Support\Facades\Log;

class PollAllOlts extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'olt:poll-all {--queue : Dispatch to queue instead of running synchronously}';

    /**
     * The console command description.
     */
    protected $description = 'Poll all active OLTs for status and metrics';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $activeOlts = Olt::active()->get();
        
        if ($activeOlts->isEmpty()) {
            $this->info('No active OLTs found.');
            return 0;
        }

        $this->info("Found {$activeOlts->count()} active OLT(s)");

        if ($this->option('queue')) {
            // Dispatch to queue for async processing
            foreach ($activeOlts as $olt) {
                PollOltStatus::dispatch($olt->id, 30)->onQueue('snmp_polling');
                $this->line("Dispatched polling job for OLT: {$olt->olt_name}");
            }
            $this->info('All polling jobs dispatched to queue.');
        } else {
            // Run synchronously with progress bar
            $bar = $this->output->createProgressBar($activeOlts->count());
            $bar->start();

            $success = 0;
            $failed = 0;

            foreach ($activeOlts as $olt) {
                try {
                    PollOltStatus::dispatchSync($olt->id, 30);
                    $success++;
                } catch (\Exception $e) {
                    $failed++;
                    Log::error("Failed to poll OLT {$olt->olt_name}: " . $e->getMessage());
                }
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info("Polling complete: {$success} successful, {$failed} failed");
        }

        return 0;
    }
}
