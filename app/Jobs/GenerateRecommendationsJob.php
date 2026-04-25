<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateRecommendationsJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $timeout = 300; // 5 menit max
    public int $tries   = 2;

    public function __construct(public readonly int $userId)
    {
        //
    }

    public function handle(): void
    {
        $pythonPath = $this->findPython();
        $scriptPath = base_path('python_engine/generate_recommendations.py');

        if (! file_exists($scriptPath)) {
            Log::error("[RecommendJob] Script tidak ditemukan: {$scriptPath}");
            return;
        }

        $cmd = "\"{$pythonPath}\" \"{$scriptPath}\" --user_id={$this->userId} 2>&1";

        Log::info("[RecommendJob] User {$this->userId} → memulai generate recs...");
        Log::info("[RecommendJob] CMD: {$cmd}");

        $output   = [];
        $exitCode = 0;
        exec($cmd, $output, $exitCode);

        $outputStr = implode("\n", $output);
        Log::info("[RecommendJob] Output:\n{$outputStr}");

        if ($exitCode !== 0) {
            Log::error("[RecommendJob] Gagal (exit code {$exitCode}) untuk user {$this->userId}");
        } else {
            Log::info("[RecommendJob] Selesai untuk user {$this->userId}");
        }
    }

    private function findPython(): string
    {
        $candidates = ['python', 'python3', 'C:\\Python311\\python.exe'];
        foreach ($candidates as $cmd) {
            $test = shell_exec("\"{$cmd}\" --version 2>&1");
            if ($test && str_starts_with(trim($test), 'Python 3')) {
                return $cmd;
            }
        }
        return 'python';
    }
}
