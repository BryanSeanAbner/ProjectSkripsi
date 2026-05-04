@extends('layouts.app', ['hideHeader' => true, 'hideFooter' => true])

@section('styles')
    <style>
        /* ─── Base (sama dengan /login) ─────────────────────────────── */
        .test-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #e8edf2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .test-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.10);
            padding: 50px 48px;
            width: 100%;
            max-width: 560px;
        }

        .test-logo {
            font-size: 28px;
            font-weight: 900;
            color: var(--bg-primary);
            text-align: center;
            margin-bottom: 4px;
            letter-spacing: -1px;
        }

        .test-logo span {
            color: var(--accent);
        }

        .test-subtitle {
            text-align: center;
            font-size: 11px;
            color: var(--text-muted);
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 28px;
        }

        /* ─── Step Progress ──────────────────────────────────────────── */
        .step-progress {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0;
            margin-bottom: 32px;
        }

        .step-dot {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #e8edf2;
            color: #999;
            font-weight: 800;
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            flex-shrink: 0;
        }

        .step-dot.active {
            background: var(--bg-primary);
            color: #fff;
        }

        .step-dot.done {
            background: var(--accent);
            color: var(--bg-primary);
        }

        .step-line {
            flex: 1;
            height: 2px;
            background: #e8edf2;
            transition: background 0.3s;
            max-width: 60px;
        }

        .step-line.done {
            background: var(--accent);
        }

        /* ─── Step Panels ───────────────────────────────────────────── */
        .step-panel {
            display: none;
        }

        .step-panel.active {
            display: block;
        }

        .step-title {
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 6px;
            color: var(--bg-primary);
        }

        .step-desc {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 24px;
        }

        /* ─── Form ──────────────────────────────────────────────────── */
        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--bg-primary);
        }

        /* ─── Article Multi-Select ──────────────────────────────────── */
        .article-search-wrap {
            position: relative;
            margin-bottom: 12px;
        }

        .article-search-wrap input {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid #e0e0e0;
            border-radius: 8px;
            font-size: 13px;
            box-sizing: border-box;
        }

        .article-list {
            max-height: 260px;
            overflow-y: auto;
            border: 1.5px solid #e0e0e0;
            border-radius: 8px;
            padding: 6px;
        }

        .article-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.15s;
            font-size: 13px;
        }

        .article-item:hover {
            background: #f5f7fa;
        }

        .article-item.selected {
            background: #fffbf0;
        }

        .article-item input[type="checkbox"] {
            margin-top: 2px;
            accent-color: var(--accent);
            flex-shrink: 0;
            width: 15px;
            height: 15px;
        }

        .article-item .art-title {
            font-weight: 600;
            line-height: 1.3;
        }

        .article-item .art-section {
            font-size: 10px;
            color: var(--accent);
            font-weight: 700;
            text-transform: uppercase;
            margin-top: 2px;
        }

        .selected-count {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 8px;
        }

        .selected-count strong {
            color: var(--bg-primary);
        }

        /* ─── Buttons ───────────────────────────────────────────────── */
        .btn-primary {
            width: 100%;
            padding: 14px;
            background: var(--bg-primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: opacity 0.2s;
            margin-top: 8px;
        }

        .btn-primary:hover {
            opacity: 0.88;
        }

        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* ─── Loading State ─────────────────────────────────────────── */
        .loading-box {
            text-align: center;
            padding: 30px 0;
        }

        .spinner {
            width: 52px;
            height: 52px;
            border: 5px solid #e8edf2;
            border-top-color: var(--accent);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .loading-box h3 {
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .loading-box p {
            font-size: 13px;
            color: var(--text-muted);
        }

        .log-output {
            background: #0d1117;
            color: #58a6ff;
            font-family: monospace;
            font-size: 12px;
            padding: 14px;
            border-radius: 8px;
            max-height: 160px;
            overflow-y: auto;
            margin-top: 16px;
            text-align: left;
            white-space: pre-wrap;
            display: none;
        }

        /* ─── Error / Alert ─────────────────────────────────────────── */
        .alert-error {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 12px 14px;
            font-size: 13px;
            margin-bottom: 16px;
            display: none;
        }
    </style>
@endsection

@section('content')
    <div class="test-page">
        <div class="test-card">

            <div class="test-logo">ntvnews<span>.id</span></div>
            <div class="test-subtitle">Demo Inference Model</div>

            <!-- Step Progress -->
            <div class="step-progress">
                <div class="step-dot active" id="dot-1">1</div>
                <div class="step-line" id="line-1"></div>
                <div class="step-dot" id="dot-2">2</div>
                <div class="step-line" id="line-2"></div>
                <div class="step-dot" id="dot-3">3</div>
            </div>

            <!-- ═══ STEP 1: Registrasi ═══════════════════════════════════════ -->
            <div class="step-panel active" id="step-1">
                <h2 class="step-title">Registrasi User Baru</h2>
                <p class="step-desc">Buat akun baru untuk menguji pipeline rekomendasi secara end-to-end.</p>

                <div class="alert-error" id="err-step1"></div>

                <div class="form-group">
                    <label>Username</label>
                    <input type="text" id="reg-username" placeholder="contoh: user_baru" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="reg-email" placeholder="user@example.com" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" id="reg-password" placeholder="••••••••" required>
                </div>

                <button class="btn-primary" id="btn-step1">
                    Lanjut — Pilih Artikel <i class="fa-solid fa-arrow-right"></i>
                </button>
            </div>

            <!-- ═══ STEP 2: Pilih Artikel ════════════════════════════════════ -->
            <div class="step-panel" id="step-2">
                <h2 class="step-title">Pilih Artikel</h2>
                <p class="step-desc">Pilih <strong>minimal 2 artikel</strong> yang ingin Anda interaksikan. Ini akan menjadi
                    data interaksi untuk pelatihan model.</p>

                <div class="alert-error" id="err-step2"></div>

                <div class="article-search-wrap">
                    <input type="text" id="art-search" placeholder="🔍  Cari judul artikel...">
                </div>

                <div class="article-list" id="article-list">
                    <div style="text-align:center; padding:20px; color:#999; font-size:13px;">Memuat artikel...</div>
                </div>

                <div class="selected-count">Dipilih: <strong id="sel-count">0</strong> artikel (minimal 2)</div>

                <button class="btn-primary" id="btn-step2" disabled>
                    Mulai Training <i class="fa-solid fa-brain"></i>
                </button>
            </div>

            <!-- ═══ STEP 3: Training ══════════════════════════════════════════ -->
            <div class="step-panel" id="step-3">
                <div class="loading-box">
                    <div class="spinner"></div>
                    <h3>Sedang Melatih Model...</h3>
                    <p>Proses ini membutuhkan waktu beberapa menit.<br>Mohon jangan tutup halaman ini.</p>

                    <div class="log-output" id="log-output"></div>

                    <p style="margin-top:16px; font-size:11px; color:#bbb;">
                        Setelah selesai, Anda akan diarahkan ke dashboard secara otomatis.
                    </p>
                </div>
            </div>

        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {

            // ─── State ────────────────────────────────────────────────────────────
            let newUserId = null;
            let allArticles = [];
            let selectedIds = new Set();

            // ─── Step Navigation ──────────────────────────────────────────────────
            function goToStep(n) {
                document.querySelectorAll('.step-panel').forEach((p, i) => {
                    p.classList.toggle('active', i + 1 === n);
                });
                // Update dots
                for (let i = 1; i <= 3; i++) {
                    const dot = document.getElementById(`dot-${i}`);
                    const line = document.getElementById(`line-${i}`);
                    dot.classList.remove('active', 'done');
                    if (i < n) { dot.classList.add('done'); if (line) line.classList.add('done'); }
                    if (i === n) dot.classList.add('active');
                }
            }

            function showError(id, msg) {
                const el = document.getElementById(id);
                el.textContent = msg;
                el.style.display = 'block';
            }
            function clearError(id) { document.getElementById(id).style.display = 'none'; }

            // ─── STEP 1: Register via Supabase JS ────────────────────────────────
            document.getElementById('btn-step1').addEventListener('click', async () => {
                clearError('err-step1');
                const username = document.getElementById('reg-username').value.trim();
                const email = document.getElementById('reg-email').value.trim();
                const password = document.getElementById('reg-password').value;

                if (!username || !email || !password) {
                    showError('err-step1', 'Semua field harus diisi!'); return;
                }
                if (password.length < 6) {
                    showError('err-step1', 'Password minimal 6 karakter!'); return;
                }

                const btn = document.getElementById('btn-step1');
                btn.disabled = true;
                btn.textContent = 'Mendaftar...';

                try {
                    // Cek apakah user sudah ada berdasarkan email atau username
                    const { data: existingUser } = await supabaseClient
                        .from('users')
                        .select('user_id')
                        .or(`username.eq.${username},email.eq.${email}`)
                        .limit(1)
                        .single();

                    if (existingUser) {
                        throw new Error("Username dan email tersebut telah dipakai, mohon untuk menggunakan username dan email yang baru.");
                    } else {
                        // Karena tabel 'users' di Supabase mungkin tidak memiliki auto-increment,
                        // kita cari nilai user_id terbesar dulu, lalu +1
                        const { data: maxData } = await supabaseClient
                            .from('users')
                            .select('user_id')
                            .order('user_id', { ascending: false })
                            .limit(1)
                            .single();

                        let nextId = 1;
                        if (maxData) {
                            nextId = parseInt(maxData.user_id) + 1;
                        }

                        // Insert user ke tabel users via Supabase
                        const { data, error } = await supabaseClient
                            .from('users')
                            .insert([{ user_id: nextId, username, email, password }])
                            .select('user_id')
                            .single();

                        if (error) throw new Error(error.message);
                        newUserId = data.user_id;
                        console.log('[Test] User baru terdaftar, user_id:', newUserId);
                    }

                    // Load artikel untuk step 2
                    await loadArticles();
                    goToStep(2);

                } catch (e) {
                    showError('err-step1', 'Gagal registrasi: ' + e.message);
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = 'Lanjut — Pilih Artikel <i class="fa-solid fa-arrow-right"></i>';
                }
            });

            // ─── STEP 2: Load Artikel ─────────────────────────────────────────────
            async function loadArticles() {
                const { data, error } = await supabaseClient
                    .from('article')
                    .select('article_id, title, section_id, view_count')
                    .order('publish_date', { ascending: false });

                if (error || !data) return;
                allArticles = data;
                renderArticles(allArticles);
            }

            function renderArticles(articles) {
                const list = document.getElementById('article-list');
                if (!articles.length) {
                    list.innerHTML = '<div style="text-align:center;padding:20px;color:#999">Artikel tidak ditemukan</div>';
                    return;
                }
                list.innerHTML = articles.map(a => `
                            <label class="article-item ${selectedIds.has(a.article_id) ? 'selected' : ''}"
                                   data-id="${a.article_id}">
                                <input type="checkbox" value="${a.article_id}"
                                       ${selectedIds.has(a.article_id) ? 'checked' : ''}
                                       onchange="toggleArticle(${a.article_id}, this.checked, this.closest('label'))">
                                <div style="flex: 1;">
                                    <div class="art-title">${a.title}</div>
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 4px;">
                                        <span class="art-section">${window.getSectionName ? window.getSectionName(a.section_id) : a.section_id}</span>
                                        <span style="font-size: 10px; color: #666; font-weight: bold;"><i class="fa-solid fa-eye"></i> ${a.view_count || 0} views</span>
                                    </div>
                                </div>
                            </label>
                        `).join('');
            }

            // Search filter
            document.getElementById('art-search').addEventListener('input', function () {
                const q = this.value.toLowerCase();
                renderArticles(allArticles.filter(a => a.title.toLowerCase().includes(q)));
            });

            window.toggleArticle = function (id, checked, el) {
                if (checked) selectedIds.add(id);
                else selectedIds.delete(id);
                el.classList.toggle('selected', checked);
                const cnt = selectedIds.size;
                document.getElementById('sel-count').textContent = cnt;
                document.getElementById('btn-step2').disabled = cnt < 2;
            };

            // ─── STEP 3: Trigger Training ─────────────────────────────────────────
            document.getElementById('btn-step2').addEventListener('click', async () => {
                clearError('err-step2');
                if (selectedIds.size < 2) {
                    showError('err-step2', 'Pilih minimal 2 artikel!'); return;
                }

                goToStep(3);

                const logEl = document.getElementById('log-output');
                const messages = [
                    ' ✅ Menyimpan interaksi ke database...',
                    ' ✅ Memuat dataset interaksi terbaru...',
                    ' ✅ Memperbarui view_count artikel...',
                    ' ✅ Menyimpan file csv berversi baru...',
                    ' ✅ Melatih ulang LightGCN dari epoch 0...',
                    ' ✅ Menghitung Popularity-Based Filtering...',
                    ' ✅ Menyimpan rekomendasi ke Supabase...',
                ];
                let msgIdx = 0;
                logEl.style.display = 'block';
                const interval = setInterval(() => {
                    if (msgIdx < messages.length) {
                        logEl.textContent += messages[msgIdx++] + '\n';
                        logEl.scrollTop = logEl.scrollHeight;
                    }
                }, 8000);

                try {
                    // Ambil ID interaksi terbesar dulu karena tabel tidak auto-increment
                    const { data: maxIntData } = await supabaseClient
                        .from('user_interaction')
                        .select('interaction_id')
                        .order('interaction_id', { ascending: false })
                        .limit(1)
                        .single();

                    let nextIntId = 1;
                    if (maxIntData && maxIntData.interaction_id) {
                        nextIntId = parseInt(maxIntData.interaction_id) + 1;
                    }

                    const interactionRows = Array.from(selectedIds).map(artId => ({
                        interaction_id: nextIntId++,
                        user_id: newUserId,
                        article_id: artId
                    }));

                    const { error: insertErr } = await supabaseClient
                        .from('user_interaction')
                        .insert(interactionRows);

                    if (insertErr) {
                        throw new Error('Gagal insert interaksi: ' + insertErr.message);
                    }

                    // 2. Panggil backend PHP untuk menjalankan script Python
                    const resp = await fetch('{{ route("register.train") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({
                            user_id: newUserId
                        }),
                    });

                    clearInterval(interval);
                    const result = await resp.json();

                    if (!resp.ok || result.error) {
                        logEl.textContent += '\n Error: ' + (result.error || 'Unknown error');
                        if (result.detail) logEl.textContent += '\n' + result.detail;
                        logEl.scrollTop = logEl.scrollHeight;
                        return;
                    }

                    logEl.textContent += '\n Training selesai! Mengalihkan ke homepage...\n';
                    logEl.scrollTop = logEl.scrollHeight;

                    // Set session cookie agar homepage tahu user_id
                    setTimeout(() => {
                        window.location.href = '/homepage';
                    }, 2000);

                } catch (e) {
                    clearInterval(interval);
                    logEl.textContent += '\n Gagal menghubungi server: ' + e.message;
                    logEl.scrollTop = logEl.scrollHeight;
                }
            });

        });
    </script>
@endsection