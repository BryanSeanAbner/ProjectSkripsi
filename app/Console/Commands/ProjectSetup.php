<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class ProjectSetup extends Command
{
    protected $signature   = 'project:setup {--skip-python : Lewati instalasi pip requirements}';
    protected $description = 'Setup pertama kali setelah git clone (env, sqlite, migrate, python deps)';

    public function handle(): int
    {
        $this->info('=== Setup ProjectTA ===');
        $this->newLine();

        $this->ensureEnvFile();
        $this->ensureSqliteDatabase();
        $this->runMigrations();

        if (! $this->option('skip-python')) {
            $this->installPythonDependencies();
        }

        $this->newLine();
        $this->info('Setup selesai!');
        $this->line('Jalankan: php artisan serve');
        $this->line('Login demo: 1@gmail.com / password: 1');
        $this->newLine();

        return self::SUCCESS;
    }

    private function ensureEnvFile(): void
    {
        $envPath     = base_path('.env');
        $examplePath = base_path('.env.example');

        if (file_exists($envPath)) {
            $this->line('  .env sudah ada — dilewati.');
            return;
        }

        if (! file_exists($examplePath)) {
            $this->error('  .env.example tidak ditemukan.');
            return;
        }

        copy($examplePath, $envPath);
        $this->info('  .env dibuat dari .env.example');

        $this->callSilent('key:generate', ['--force' => true]);
        $this->info('  APP_KEY digenerate');
    }

    private function ensureSqliteDatabase(): void
    {
        $dbPath = database_path('database.sqlite');

        if (! file_exists($dbPath)) {
            touch($dbPath);
            $this->info('  database/database.sqlite dibuat');
        } else {
            $this->line('  database.sqlite sudah ada — dilewati.');
        }
    }

    private function runMigrations(): void
    {
        $this->info('  Menjalankan migrate...');
        $exitCode = $this->call('migrate', ['--force' => true]);

        if ($exitCode !== self::SUCCESS) {
            $this->warn('  Migrate gagal — pastikan ekstensi PHP sqlite aktif.');
        }
    }

    private function installPythonDependencies(): void
    {
        $requirements = base_path('python_engine/requirements.txt');

        if (! file_exists($requirements)) {
            $this->warn('  python_engine/requirements.txt tidak ditemukan — dilewati.');
            return;
        }

        $python = $this->findPython();

        if ($python === null) {
            $this->warn('  Python 3 tidak ditemukan — lewati pip install.');
            $this->warn('  Fitur generate rekomendasi saat login membutuhkan Python.');
            return;
        }

        $this->info("  Menginstall Python deps via {$python}...");

        $process = new Process([$python, '-m', 'pip', 'install', '-r', $requirements]);
        $process->setWorkingDirectory(base_path('python_engine'));
        $process->setTimeout(600);
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if ($process->isSuccessful()) {
            $this->info('  Python dependencies terinstall.');
        } else {
            $this->warn('  pip install gagal — fitur ML mungkin tidak jalan.');
        }
    }

    private function findPython(): ?string
    {
        $candidates = ['python', 'python3', 'C:\\Python311\\python.exe', 'C:\\Python312\\python.exe'];

        foreach ($candidates as $cmd) {
            $process = new Process([$cmd, '--version']);
            $process->run();

            if ($process->isSuccessful() && str_starts_with(trim($process->getOutput()), 'Python 3')) {
                return $cmd;
            }
        }

        return null;
    }
}
