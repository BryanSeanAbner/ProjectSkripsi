<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestController extends Controller
{
    /**
     * Tampilkan halaman /register
     */
    public function index()
    {
        return view('register');
    }

    /**
     * Simpan interaksi artikel user baru ke tabel user_interaction,
     * lalu jalankan Python pipeline dan redirect ke /homepage.
     *
     * POST /register/train
     * Body JSON: { "user_id": 3050, "article_ids": [101, 205, 310] }
     */
    public function train(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
        ]);

        $userId = (int) $request->user_id;

        // Note: Interaksi sudah di-insert oleh frontend (via JS Supabase Client)
        // untuk menghindari issue koneksi DB IPv6 di localhost.
        
        // 2. Jalankan Python pipeline
        $pythonPath  = $this->getPythonExecutable();
        $scriptPath  = base_path('python_engine/test_new_user_pipeline.py');
        $logPath     = storage_path('logs/test_pipeline_' . $userId . '.log');

        // -u = unbuffered Python output
        $cmd = escapeshellarg($pythonPath) . ' -u ' .
               escapeshellarg($scriptPath) . ' ' .
               '--user_id ' . $userId .
               ' 2>&1';

        Log::info("[Register] Menjalankan: python test_new_user_pipeline.py --user_id {$userId}");

        // Tulis header ke terminal (STDERR agar muncul di php artisan serve)
        $stderr = fopen('php://stderr', 'w');
        fwrite($stderr, "\n" . str_repeat('=', 60) . "\n");
        fwrite($stderr, "  [Register] Pipeline dimulai — User ID: {$userId}\n");
        fwrite($stderr, str_repeat('=', 60) . "\n");

        // Jalankan dengan popen agar output muncul REAL-TIME di terminal
        $handle = popen($cmd, 'r');
        $logContent = '';

        if ($handle) {
            while (!feof($handle)) {
                $line = fgets($handle);
                if ($line !== false) {
                    $logContent .= $line;
                    fwrite($stderr, "  [Python] " . rtrim($line) . "\n");
                    fflush($stderr);
                }
            }
            $returnCode = pclose($handle);
        } else {
            $returnCode = -1;
            fwrite($stderr, "  [ERROR] Gagal menjalankan Python!\n");
        }

        fwrite($stderr, "\n  [Register] Pipeline selesai — exit code: {$returnCode}\n");
        fwrite($stderr, str_repeat('=', 60) . "\n\n");
        fclose($stderr);

        // Simpan log ke file
        file_put_contents($logPath, $logContent);
        Log::info("[Register] Pipeline exit code: {$returnCode}");

        if ($returnCode !== 0) {
            $safeLog = mb_convert_encoding($logContent, 'UTF-8', 'UTF-8');
            return response()->json([
                'error'  => 'Training gagal. Lihat log untuk detail.',
                'detail' => $safeLog,
            ], 500);
        }

        // 3. Set session agar homepage tahu siapa yang login
        $request->session()->put('active_user_id', $userId);

        return response()->json(['success' => true, 'user_id' => $userId]);
    }

    /**
     * Deteksi path Python yang tersedia di sistem.
     */
    private function getPythonExecutable(): string
    {
        foreach (['python', 'python3', 'py'] as $py) {
            exec("where {$py} 2>nul", $out, $code);
            if ($code === 0 && !empty($out)) {
                return trim($out[0]);
            }
        }
        return 'python'; // fallback
    }
}
