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
     * lalu jalankan Python pipeline dan redirect ke /dashboard.
     *
     * POST /test/train
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

        $cmd = escapeshellarg($pythonPath) . ' ' .
               escapeshellarg($scriptPath) . ' ' .
               '--user_id ' . $userId .
               ' > ' . escapeshellarg($logPath) . ' 2>&1';

        Log::info("[TestController] Menjalankan: python test_new_user_pipeline.py --user_id {$userId}");

        // Jalankan dan tunggu hingga selesai (blocking)
        $returnCode = null;
        exec($cmd, $output, $returnCode);

        $logContent = file_exists($logPath) ? file_get_contents($logPath) : '';
        Log::info("[TestController] Pipeline exit code: {$returnCode}\n{$logContent}");

        if ($returnCode !== 0) {
            // Clean invalid UTF-8 characters that could break json_encode
            $safeLog = mb_convert_encoding($logContent, 'UTF-8', 'UTF-8');
            return response()->json([
                'error'  => 'Training gagal. Lihat log untuk detail.',
                'detail' => $safeLog,
            ], 500);
        }

        // 3. Set session agar dashboard tahu siapa yang login
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
