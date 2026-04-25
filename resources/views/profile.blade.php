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
            /* Orange/Red accent stats */
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

        .btn-load-more {
            display: block;
            margin: 40px auto;
            padding: 12px 30px;
            background: #e8f0fe;
            color: #1a73e8;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            letter-spacing: 0.5px;
        }
    </style>
@endsection

@section('content')
    <div class="profile-container">

        <div class="profile-header">
            <img src="https://ui-avatars.com/api/?name=Bryan+Sean&background=170b3b&color=fff&size=120&rounded=true"
                alt="Avatar" class="profile-avatar">

            <div class="profile-info">
                <h1>{{ Auth::check() ? Auth::user()->username : 'Bryan Sean' }}</h1>
                <p>Journalism Enthusiast & Premium Subscriber</p>

                <div class="profile-stats">
                    <div class="stat-item">
                        <h3>142</h3>
                        <span>SAVED ARTICLES</span>
                    </div>
                    <div class="stat-item">
                        <h3>7</h3>
                        <span>TOPICS FOLLOWED</span>
                    </div>
                </div>
            </div>

            <button class="btn-edit-profile">Edit Profile</button>
        </div>

        <div class="profile-tabs">
            <button class="active">SAVED ARTICLES</button>
            <button>READING HISTORY</button>
            <button>ACCOUNT SETTINGS</button>
        </div>

        <div class="saved-grid" id="saved-grid">
            <!-- Rendered by JS -->
        </div>

        <button class="btn-load-more">LOAD MORE SAVED ARTICLES</button>

    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            async function loadSaved() {
                if (!supabaseClient) return;
                // Fetch random articles for demonstration of saved list 
                // In real app, this would query a user_saved_articles table
                const { data, error } = await supabaseClient
                    .from('article')
                    .select('*')
                    .limit(3);

                if (data) {
                    let html = '';
                    data.forEach(item => {
                        html += `
                        <a href="/article/${item.article_id}" class="card-sm">
                            <img src="${item.photo_url || 'https://via.placeholder.com/400x300?text=News'}" alt="News">
                            <div class="card-body">
                                <div class="tag" style="background:var(--accent); color:var(--bg-primary); padding:2px 6px; border-radius:2px; font-size:10px; font-weight:800; display:inline-block; margin-bottom:8px;">${item.section_id || 'NEWS'}</div>
                                <h3 style="font-size:16px; margin-bottom:10px;">${item.title}</h3>
                                <p style="font-size:13px;">${item.content ? item.content.substring(0, 70) + '...' : ''}</p>
                                <div class="card-footer" style="padding-top:10px; margin-top:auto;">
                                    <span>${new Date(item.publish_date).toLocaleDateString()}</span>
                                    <i class="fa-solid fa-bookmark" style="color:var(--accent);"></i>
                                </div>
                            </div>
                        </a>
                    `;
                    });
                    document.getElementById('saved-grid').innerHTML = html;
                }
            }

            loadSaved();
        });
    </script>
@endsection