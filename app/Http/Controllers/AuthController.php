<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateRecommendationsJob;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Langkah 1: Hubungi Supabase REST API untuk verifikasi user (bypassing PDO IPv6 DNS error)
        $supabaseUrl = env('SUPABASE_URL');
        $supabaseKey = env('SUPABASE_SERVICE_KEY', env('SUPABASE_ANON_KEY'));

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'apikey'        => $supabaseKey,
                'Authorization' => "Bearer {$supabaseKey}",
            ])->get("{$supabaseUrl}/rest/v1/users", [
                'email'  => 'eq.' . $credentials['email'],
                'select' => 'user_id,email,password'
            ]);

            if ($response->successful()) {
                $users = $response->json();
                if (count($users) > 0) {
                    $userRecord = $users[0];
                    $userId = $userRecord['user_id'];
                    $storedPassword = $userRecord['password'];
                    $inputPassword = $credentials['password'];
                    
                    // Cek apakah password cocok (Plain Text atau Bcrypt Hash)
                    $passwordMatches = false;
                    if ($storedPassword === $inputPassword) {
                        $passwordMatches = true; // User baru dari /register (plain text)
                    } elseif (\Illuminate\Support\Facades\Hash::check($inputPassword, $storedPassword)) {
                        $passwordMatches = true; // User dari dataset original (bcrypt)
                    }

                    if ($passwordMatches) {
                        $request->session()->regenerate();
                        $request->session()->put('active_user_id', $userId);
                        GenerateRecommendationsJob::dispatch($userId);
                        return redirect()->intended('/homepage');
                    } else {
                        return back()->withErrors(['email' => 'Password yang Anda masukkan salah.']);
                    }
                }
            } else {
                \Log::warning('[Login] Supabase API error: ' . $response->body());
                return back()->withErrors(['email' => 'Gagal menghubungi server database.']);
            }
        } catch (\Exception $e) {
            \Log::error('[Login] Supabase request exception: ' . $e->getMessage());
            return back()->withErrors(['email' => 'Terjadi kesalahan sistem saat login.']);
        }

        // Jika sampai di sini berarti user tidak ditemukan di Supabase
        return back()->withErrors([
            'email' => 'Email tersebut belum terdaftar. Silakan daftar melalui halaman /register terlebih dahulu.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->forget('active_user_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
