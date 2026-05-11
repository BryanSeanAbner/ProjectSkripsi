<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestController extends Controller
{
    /**
     * Tampilkan halaman /test
     */
    public function index()
    {
        return view('test');
    }

    /**
     * Simpan interaksi artikel user baru ke tabel user_interaction,
     * lalu jalankan Python pipeline dan redirect ke /homepage.
     *
     * POST /test/train
     * Body JSON: { "user_id": 3050 }
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

        Log::info("[Test] Menjalankan: python test_new_user_pipeline.py --user_id {$userId}");

        // 3. Set session agar homepage tahu siapa yang login
        $request->session()->put('active_user_id', $userId);

        // Stream output ke frontend agar UI bisa parse Epoch 1-500 secara real-time
        return response()->stream(function () use ($cmd, $userId, $logPath) {
            $stderr = fopen('php://stderr', 'w');
            fwrite($stderr, "\n" . str_repeat('=', 60) . "\n");
            fwrite($stderr, "  [Test] Pipeline dimulai — User ID: {$userId}\n");
            fwrite($stderr, str_repeat('=', 60) . "\n");

            $handle = popen($cmd, 'r');
            $logContent = '';

            if ($handle) {
                while (!feof($handle)) {
                    $line = fgets($handle);
                    if ($line !== false) {
                        $logContent .= $line;
                        $trimmed = rtrim($line);
                        
                        // Output ke terminal
                        fwrite($stderr, "  [Python] " . $trimmed . "\n");
                        fflush($stderr);
                        
                        // Output ke client (JSON line)
                        echo json_encode(['line' => $trimmed]) . "\n";
                        ob_flush();
                        flush();
                    }
                }
                $returnCode = pclose($handle);
            } else {
                $returnCode = -1;
                fwrite($stderr, "  [ERROR] Gagal menjalankan Python!\n");
                echo json_encode(['error' => 'Gagal menjalankan Python']) . "\n";
            }

            fwrite($stderr, "\n  [Test] Pipeline selesai — exit code: {$returnCode}\n");
            fwrite($stderr, str_repeat('=', 60) . "\n\n");
            fclose($stderr);

            file_put_contents($logPath, $logContent);
            Log::info("[Test] Pipeline exit code: {$returnCode}");

            if ($returnCode === 0) {
                echo json_encode(['success' => true, 'user_id' => $userId]) . "\n";
            } else {
                echo json_encode(['error' => 'Training gagal', 'code' => $returnCode]) . "\n";
            }
        }, 200, [
            'Cache-Control' => 'no-cache',
            'Content-Type'  => 'application/x-ndjson',
            'X-Accel-Buffering' => 'no', // For Nginx if used
        ]);
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
