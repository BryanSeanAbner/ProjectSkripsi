@extends('layouts.app', ['hideHeader' => true, 'hideFooter' => true])

@section('styles')
    <style>
        .register-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #e8edf2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .register-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 48px 44px;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.08);
            color: var(--text-main);
        }

        .register-logo {
            text-align: center;
            margin-bottom: 6px;
        }

        .register-logo img {
            height: 35px;
        }

        .register-subtitle {
            font-size: 11px;
            text-align: center;
            color: var(--text-muted);
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 36px;
        }

        .register-card h2 {
            font-size: 22px;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 6px;
        }

        .register-card .sub {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 28px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            color: var(--text-main);
            background: #fafafa;
            transition: border-color 0.2s, background 0.2s;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--bg-primary);
            background: #fff;
        }

        .btn-register {
            width: 100%;
            padding: 14px;
            background: var(--bg-primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 8px;
            transition: background 0.2s, transform 0.1s;
        }

        .btn-register:hover {
            background: #2a1760;
            transform: translateY(-1px);
        }

        .btn-register:disabled {
            background: #b0b8cc;
            cursor: not-allowed;
            transform: none;
        }

        .alert-error {
            background: #fff0f0;
            color: #c0392b;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 13px;
            margin-bottom: 18px;
            display: none;
        }

        .alert-success {
            background: #f0fff4;
            color: #27ae60;
            border: 1px solid #b7e4c7;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 13px;
            margin-bottom: 18px;
            display: none;
        }

        .login-link {
            text-align: center;
            margin-top: 24px;
            font-size: 13px;
            color: var(--text-muted);
        }

        .login-link a {
            color: var(--bg-primary);
            font-weight: 700;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .divider {
            border: none;
            border-top: 1px solid var(--border-color);
            margin: 20px 0;
        }
    </style>
@endsection

@section('content')
    <div class="register-page">
        <div class="register-card">
            <div class="register-logo">
                <img src="{{ asset('img/logo2.svg') }}" alt="ntvnews.id">
            </div>
            <div class="register-subtitle">Buat Akun Baru</div>

            <h2>Daftar Sekarang</h2>
            <p class="sub">Buat akun dan temukan berita yang relevan untuk Anda.</p>

            <div class="alert-error" id="err-register"></div>
            <div class="alert-success" id="success-register"></div>

            <div class="form-group">
                <label>Username</label>
                <input type="text" id="reg-username" placeholder="contoh: john_doe" autocomplete="off">
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" id="reg-email" placeholder="email@example.com" autocomplete="off">
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" id="reg-password" placeholder="••••••">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Gender</label>
                    <select id="reg-gender">
                        <option value="">-- Pilih --</option>
                        <option value="male">Laki-laki</option>
                        <option value="female">Perempuan</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Usia</label>
                    <input type="number" id="reg-age" placeholder="25" min="10" max="100">
                </div>
            </div>

            <button class="btn-register" id="btn-register">
                Daftar &amp; Lihat Berita <i class="fa-solid fa-arrow-right"></i>
            </button>

            <div class="login-link">
                Sudah punya akun? <a href="/login">Masuk di sini</a>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.getElementById('btn-register').addEventListener('click', async () => {
            const errEl = document.getElementById('err-register');
            const successEl = document.getElementById('success-register');
            errEl.style.display = 'none';
            successEl.style.display = 'none';

            const username = document.getElementById('reg-username').value.trim();
            const email = document.getElementById('reg-email').value.trim();
            const password = document.getElementById('reg-password').value;
            const gender = document.getElementById('reg-gender').value;
            const age = parseInt(document.getElementById('reg-age').value) || null;

            if (!username || !email || !password) {
                errEl.textContent = 'Username, email, dan password wajib diisi!';
                errEl.style.display = 'block';
                return;
            }

            if (password.length < 6) {
                errEl.textContent = 'Password minimal 6 karakter!';
                errEl.style.display = 'block';
                return;
            }

            const btn = document.getElementById('btn-register');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mendaftar...';

            try {
                if (!supabaseClient) throw new Error('Koneksi Supabase gagal.');

                // Cek duplikat username/email
                const { data: existing } = await supabaseClient
                    .from('users')
                    .select('user_id')
                    .or(`username.eq.${username},email.eq.${email}`)
                    .limit(1)
                    .maybeSingle();

                if (existing) {
                    throw new Error('Username atau email sudah terdaftar. Silakan gunakan yang lain.');
                }

                // Ambil user_id terbesar, lalu +1
                const { data: maxRow } = await supabaseClient
                    .from('users')
                    .select('user_id')
                    .order('user_id', { ascending: false })
                    .limit(1)
                    .maybeSingle();

                const nextId = maxRow ? (parseInt(maxRow.user_id) + 1) : 1;

                // Insert user baru
                const { data: newUser, error: insertErr } = await supabaseClient
                    .from('users')
                    .insert([{
                        user_id: nextId,
                        username: username,
                        email: email,
                        password: password,
                        gender: gender || null,
                        age: age
                    }])
                    .select('user_id')
                    .single();

                if (insertErr) throw new Error(insertErr.message);

                successEl.textContent = 'Akun berhasil dibuat! Mengalihkan ke halaman berita...';
                successEl.style.display = 'block';

                // Simpan user_id ke session lewat Laravel
                await fetch('/register/session', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ user_id: newUser.user_id })
                });

                setTimeout(() => {
                    window.location.href = '/welcome';
                }, 1200);

            } catch (e) {
                errEl.textContent = e.message;
                errEl.style.display = 'block';
                btn.disabled = false;
                btn.innerHTML = 'Daftar &amp; Lihat Berita <i class="fa-solid fa-arrow-right"></i>';
            }
        });
    </script>
@endsection