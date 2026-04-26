@extends('layouts.app')

@section('styles')
    <style>
        .profile-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
            min-height: 60vh;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 40px;
            position: relative;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 12px;
            background: #333;
            object-fit: cover;
        }

        .profile-info h1 {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .profile-info p {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 20px;
        }

        .profile-stats {
            display: flex;
            gap: 40px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-item h3 {
            font-size: 24px;
            font-weight: 800;
            color: #e53935;
            margin-bottom: 2px;
        }

        .stat-item span {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
        }

        .btn-edit-profile {
            position: absolute;
            top: 10px;
            right: 0;
            padding: 8px 16px;
            background: #e8f0fe;
            color: #1a73e8;
            font-weight: 600;
            font-size: 13px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-edit-profile:hover {
            background: #d2e3fc;
        }

        .profile-tabs {
            display: flex;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 30px;
            gap: 30px;
        }

        .profile-tabs button {
            background: none;
            border: none;
            padding: 15px 0;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-muted);
            cursor: pointer;
            position: relative;
            letter-spacing: 0.5px;
        }

        .profile-tabs button.active {
            color: var(--text-main);
        }

        .profile-tabs button.active::after {
            content: "";
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--accent);
            border-radius: 3px 3px 0 0;
        }

        .saved-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
        }
    </style>
@endsection

@section('content')
    <div class="profile-container">

        <div class="profile-header">
            <img src="https://ui-avatars.com/api/?name=Loading&background=170b3b&color=fff&size=120&rounded=true"
                alt="Avatar" class="profile-avatar" id="profile-avatar-img">

            <div class="profile-info">
                <h1 id="profile-username">Memuat...</h1>
                <p id="profile-email">Memuat info...</p>

                <div class="profile-stats">
                    <div class="stat-item">
                        <h3 id="count-saved">0</h3>
                        <span>SAVED ARTICLES</span>
                    </div>
                    <div class="stat-item">
                        <h3 id="count-history">0</h3>
                        <span>READING HISTORY</span>
                    </div>
                </div>
            </div>

            <button class="btn-edit-profile">Edit Profile</button>
        </div>

        <div class="profile-tabs" id="profile-tabs">
            <button class="active" data-target="saved">SAVED ARTICLES</button>
            <button data-target="history">READING HISTORY</button>
            <button data-target="settings">SETTINGS</button>
        </div>

        <!-- Saved Articles Grid -->
        <div class="saved-grid" id="saved-grid">
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #666;">Memuat Bookmark...</div>
        </div>
        
        <!-- Reading History Grid -->
        <div id="history-container" style="display: none;">
            <div class="saved-grid" id="history-grid">
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #666;">Memuat Histori...</div>
            </div>
            <div style="text-align: center; margin-top: 30px;">
                <button id="btn-load-more-history" style="display: none; background: #f0f0f0; color: #333; font-weight: 700; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; transition: background 0.2s;">
                    Lihat Histori Lainnya <i class="fa-solid fa-angle-down"></i>
                </button>
            </div>
        </div>

        <!-- Settings Grid -->
        <div id="settings-grid" style="display: none; padding-bottom: 40px;">
            <div style="background:#fff; padding:30px; border-radius:12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); max-width:600px; margin: 0 auto;">
                <h2 style="font-size:20px; font-weight:800; margin-bottom:20px;">Edit Profile</h2>
                <div style="display:flex; flex-direction:column; gap:15px;">
                    <div>
                        <label style="font-weight:700; font-size:12px; color:#666;">Photo Profile (URL)</label>
                        <input type="text" id="edit-photo" placeholder="https://ui-avatars.com/api/?name=User" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; margin-top:5px;">
                    </div>
                    <div>
                        <label style="font-weight:700; font-size:12px; color:#666;">Username</label>
                        <input type="text" id="edit-username" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; margin-top:5px;">
                    </div>
                    <div>
                        <label style="font-weight:700; font-size:12px; color:#666;">Email</label>
                        <input type="email" id="edit-email" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; margin-top:5px;">
                    </div>
                    <div>
                        <label style="font-weight:700; font-size:12px; color:#666;">New Password</label>
                        <input type="password" id="edit-password" placeholder="Kosongkan jika tidak ingin diubah" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; margin-top:5px;">
                    </div>
                    <button id="btn-save-profile" style="background:var(--accent); color:#fff; font-weight:800; padding:12px; border:none; border-radius:6px; cursor:pointer; margin-top:10px;">Simpan Perubahan</button>
                </div>

                <hr style="margin:30px 0; border:none; border-top:1px solid #eee;">
                
                <h2 style="font-size:20px; font-weight:800; margin-bottom:20px; color:#e53935;">Account Actions</h2>
                <button id="btn-logout" style="background:#e53935; color:#fff; font-weight:800; padding:12px 20px; border:none; border-radius:6px; cursor:pointer;">Logout</button>
            </div>
        </div>

    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            // ID User dari session Laravel (reliable untuk semua kasus login)
            const userId = {{ session('active_user_id', Auth::id() ?? 1) }};

            if (!supabaseClient) return;

            // 1. Fetch User Info dari Supabase
            async function loadUserInfo() {
                const { data, error } = await supabaseClient
                    .from('users')
                    .select('*')
                    .eq('user_id', userId)
                    .single();

                if (data) {
                    const username = data.username || userId;
                    document.getElementById('profile-username').innerText = username;
                    document.getElementById('profile-email').innerText = data.email || 'user@example.com';
                    // Check if they have a custom photo URL in DB (if column exists, otherwise fallback to avatar)
                    const avatarUrl = data.photo_url || `https://ui-avatars.com/api/?name=${username}&background=170b3b&color=fff&size=120&rounded=true`;
                    document.getElementById('profile-avatar-img').src = avatarUrl;
                    
                    // Fill Settings Inputs
                    document.getElementById('edit-username').value = data.username || '';
                    document.getElementById('edit-email').value = data.email || '';
                    document.getElementById('edit-photo').value = data.photo_url || '';
                } else {
                    document.getElementById('profile-username').innerText = userId;
                }
            }

            // Fungsi helper untuk merender grid card
            function renderGrid(containerId, items, noDataMsg, append = false) {
                const container = document.getElementById(containerId);
                if (!items || items.length === 0) {
                    if (!append) {
                        container.innerHTML = `<div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #666;">${noDataMsg}</div>`;
                    }
                    return;
                }

                let html = '';
                items.forEach(item => {
                    const art = item.article;
                    if (!art) return;
                    html += `
                        <a href="/article/${art.article_id}" class="card-sm">
                            <img src="${art.photo_url || 'https://via.placeholder.com/400x300?text=News'}" alt="News">
                            <div class="card-body">
                                <div class="tag" style="background:var(--accent); color:var(--bg-primary); padding:2px 6px; border-radius:2px; font-size:10px; font-weight:800; display:inline-block; margin-bottom:8px;">${window.getSectionName ? window.getSectionName(art.section_id) : art.section_id}</div>
                                <h3 style="font-size:16px; margin-bottom:10px;">${art.title}</h3>
                                <div class="card-footer" style="padding-top:10px; margin-top:auto;">
                                    <span>${new Date(art.publish_date || Date.now()).toLocaleDateString()}</span>
                                    <i class="fa-solid fa-clock-rotate-left" style="color:var(--text-muted);"></i>
                                </div>
                            </div>
                        </a>
                    `;
                });
                if (append) {
                    container.innerHTML += html;
                } else {
                    container.innerHTML = html;
                }
            }

            // 2. Fetch Saved Articles (Bookmarks)
            async function loadSaved() {
                const { data: bookmarks, error } = await supabaseClient
                    .from('user_bookmarks')
                    .select('*')
                    .eq('user_id', userId)
                    .order('created_at', { ascending: false });

                if (bookmarks && bookmarks.length > 0) {
                    const articleIds = bookmarks.map(b => b.article_id);
                    const { data: articles } = await supabaseClient
                        .from('article')
                        .select('article_id, title, photo_url, section_id, publish_date')
                        .in('article_id', articleIds);

                    const merged = bookmarks.map(b => {
                        return { article: articles ? articles.find(a => a.article_id === b.article_id) : null };
                    });
                    
                    document.getElementById('count-saved').innerText = merged.length;
                    renderGrid('saved-grid', merged, 'Belum ada artikel yang disimpan.');
                } else {
                    document.getElementById('count-saved').innerText = 0;
                    renderGrid('saved-grid', [], 'Belum ada artikel yang disimpan. Silakan tekan tombol bookmark di halaman baca!');
                }
            }

            // 3. Fetch Reading History (Interaction)
            let allUniqueHistoryIds = [];
            let currentHistoryPage = 0;
            const historyPerPage = 9;

            async function loadHistory() {
                // Fetch ALL interactions for the user to get the true total count
                const { data: history, error } = await supabaseClient
                    .from('user_interaction')
                    .select('article_id')
                    .eq('user_id', userId)
                    .order('interaction_id', { ascending: false });

                if (history && history.length > 0) {
                    // Filter unique article_id agar tidak double
                    allUniqueHistoryIds = [...new Set(history.map(h => h.article_id))];
                    
                    // Set total count di header
                    document.getElementById('count-history').innerText = allUniqueHistoryIds.length;
                    
                    // Bersihkan grid
                    document.getElementById('history-grid').innerHTML = '';
                    currentHistoryPage = 0;
                    
                    // Load halaman pertama
                    await loadHistoryPage();
                } else {
                    document.getElementById('count-history').innerText = 0;
                    renderGrid('history-grid', [], 'Belum ada histori membaca.');
                }
            }

            async function loadHistoryPage() {
                const startIdx = currentHistoryPage * historyPerPage;
                const endIdx = startIdx + historyPerPage;
                const pageIds = allUniqueHistoryIds.slice(startIdx, endIdx);
                
                if (pageIds.length === 0) return;

                const { data: articles } = await supabaseClient
                    .from('article')
                    .select('article_id, title, photo_url, section_id, publish_date')
                    .in('article_id', pageIds);

                const merged = pageIds.map(id => {
                    return { article: articles ? articles.find(a => a.article_id === id) : null };
                }).filter(m => m.article !== null);

                // Tambahkan (append) ke grid, bukan me-replace
                const isFirstPage = currentHistoryPage === 0;
                renderGrid('history-grid', merged, 'Belum ada histori membaca.', !isFirstPage);
                
                currentHistoryPage++;
                
                // Tampilkan atau sembunyikan tombol Load More
                const btnLoadMore = document.getElementById('btn-load-more-history');
                if (currentHistoryPage * historyPerPage >= allUniqueHistoryIds.length) {
                    btnLoadMore.style.display = 'none';
                } else {
                    btnLoadMore.style.display = 'inline-block';
                }
            }

            document.getElementById('btn-load-more-history').addEventListener('click', async function() {
                const prevText = this.innerHTML;
                this.innerHTML = 'Memuat...';
                await loadHistoryPage();
                this.innerHTML = prevText;
            });

            // Tabs Logic
            const tabs = document.querySelectorAll('#profile-tabs button');
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    // Remove active from all
                    tabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');

                    // Hide all grids
                    document.getElementById('saved-grid').style.display = 'none';
                    document.getElementById('history-container').style.display = 'none';
                    document.getElementById('settings-grid').style.display = 'none';

                    // Show target
                    const target = tab.getAttribute('data-target');
                    if (target === 'history') {
                        document.getElementById('history-container').style.display = 'block';
                    } else if (target === 'settings') {
                        document.getElementById('settings-grid').style.display = 'block';
                    } else {
                        document.getElementById(target + '-grid').style.display = 'grid';
                    }
                });
            });

            // Edit Profile Button Click
            document.querySelector('.btn-edit-profile').addEventListener('click', () => {
                document.querySelector('#profile-tabs button[data-target="settings"]').click();
            });

            // Save Profile Action
            document.getElementById('btn-save-profile').addEventListener('click', async () => {
                const username = document.getElementById('edit-username').value;
                const email = document.getElementById('edit-email').value;
                const photo_url = document.getElementById('edit-photo').value;
                const password = document.getElementById('edit-password').value;
                
                let updateData = { username, email, photo_url };
                if(password.trim() !== '') {
                    updateData.password = password;
                }

                const btn = document.getElementById('btn-save-profile');
                btn.innerText = "Menyimpan...";

                const { data, error } = await supabaseClient
                    .from('users')
                    .update(updateData)
                    .eq('user_id', userId);

                btn.innerText = "Simpan Perubahan";

                if(!error) {
                    alert('Profile berhasil diupdate!');
                    document.getElementById('edit-password').value = '';
                    loadUserInfo(); // reload header info
                } else {
                    alert('Gagal update: ' + error.message);
                }
            });

            // Logout Action
            document.getElementById('btn-logout').addEventListener('click', () => {
                window.location.href = '/login';
            });

            // Initialize
            loadUserInfo();
            loadSaved();
            loadHistory();
        });
    </script>
@endsection