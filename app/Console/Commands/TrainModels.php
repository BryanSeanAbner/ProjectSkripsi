<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TrainModels extends Command
{
    protected $signature   = 'models:train
                                {--epochs=100 : Jumlah epoch LightGCN}
                                {--embedding=8 : Dimensi embedding LightGCN}
                                {--layers=2 : Jumlah lapisan LightGCN}';
    protected $description = 'Training semua model rekomendasi (CBF + LightGCN) via Python';

    public function handle(): int
    {
        $pythonPath = $this->findPython();
        if (! $pythonPath) {
            $this->error('Python tidak ditemukan. Pastikan Python 3.x ter-install.');
            return self::FAILURE;
        }

        $scriptPath = base_path('python_engine/train_all.py');
        if (! file_exists($scriptPath)) {
            $this->error("Script tidak ditemukan: {$scriptPath}");
            return self::FAILURE;
        }

        $this->info("Python  : {$pythonPath}");
        $this->info("Script  : {$scriptPath}");
        $this->newLine();

        $epochs    = (int) $this->option('epochs');
        $embedding = (int) $this->option('embedding');
        $layers    = (int) $this->option('layers');

        $cmd = "\"{$pythonPath}\" \"{$scriptPath}\" "
             . "--epochs={$epochs} "
             . "--embedding_dim={$embedding} "
             . "--num_layers={$layers}";

        $this->info("Menjalankan: {$cmd}");
        $this->newLine();

        // Stream output ke console secara real-time
        $handle = popen($cmd, 'r');
        if ($handle === false) {
            $this->error('Gagal menjalankan Python script.');
            return self::FAILURE;
        }

        while (! feof($handle)) {
            $line = fgets($handle);
            if ($line !== false) {
                $this->output->write($line);
            }
        }

        $exitCode = pclose($handle);

        $this->newLine();
        if ($exitCode === 0) {
            $this->info('✓ Training selesai! Model weights tersimpan di python_engine/saved_models/');
            return self::SUCCESS;
        }

        $this->error("Training gagal (exit code: {$exitCode})");
        return self::FAILURE;
    }

    private function findPython(): ?string
    {
        // Priority: python3 → python → C:\Python311\python.exe
        $candidates = ['python', 'python3', 'C:\\Python311\\python.exe', 'C:\\Python3\\python.exe'];

        foreach ($candidates as $cmd) {
            $test = shell_exec("\"{$cmd}\" --version 2>&1");
            if ($test && str_starts_with(trim($test), 'Python 3')) {
                return $cmd;
            }
        }
        return null;
    }
}
