@extends('layouts.app')

@section('styles')
<style>
/* Dashboard Styles - Reused for Section */
.dashboard-container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}

.hero-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
    margin-bottom: 40px;
}

.hero-main {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.hero-top-card {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    color: #fff;
    cursor: pointer;
}

.hero-top-card img {
    width: 100%;
    height: 380px;
    object-fit: cover;
    display: block;
    transition: transform 0.3s;
}

.hero-top-card:hover img {
    transform: scale(1.02);
}

.hero-top-card .overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(transparent, rgba(0,0,0,0.9));
    padding: 30px 20px 20px;
}

.hero-top-card .tag {
    background: var(--accent);
    color: var(--bg-primary);
    font-size: 11px;
    font-weight: 700;
    padding: 4px 8px;
    text-transform: uppercase;
    display: inline-block;
    margin-bottom: 10px;
}

.hero-top-card h1 {
    font-size: 26px;
    font-weight: 700;
    line-height: 1.3;
    margin-bottom: 8px;
}

.hero-top-card p {
    font-size: 14px;
    color: #ddd;
    margin-bottom: 15px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.hero-top-card .meta {
    font-size: 12px;
    color: #aaa;
}

.hero-sub-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

.card-sm {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.card-sm:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
}

.card-sm img {
    width: 100%;
    height: 140px;
    object-fit: cover;
}

.card-sm .card-body {
    padding: 15px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.card-sm .tag {
    font-size: 10px;
    color: var(--accent);
    text-transform: uppercase;
    font-weight: 700;
    margin-bottom: 6px;
}

.card-sm h3 {
    font-size: 15px;
    font-weight: 700;
    margin-bottom: 8px;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.card-sm p {
    font-size: 12px;
    color: var(--text-muted);
    margin-bottom: 15px;
    flex: 1;
}

.card-sm .card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 11px;
    color: #999;
}


/* Sidebar Terpopuler */
.sidebar-box {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.03);
}

.section-title {
    font-size: 18px;
    font-weight: 800;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--bg-primary);
}
.section-title::before {
    content: "";
    width: 4px;
    height: 20px;
    background: var(--accent);
    border-radius: 4px;
}

.popular-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.popular-item {
    display: flex;
    gap: 15px;
    align-items: flex-start;
    padding-bottom: 15px;
    border-bottom: 1px solid var(--border-color);
}
.popular-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.popular-item .number {
    font-size: 32px;
    font-weight: 800;
    color: #e0e0e0;
    line-height: 1;
}
.popular-item .details .tag {
    font-size: 10px;
    color: var(--accent);
    font-weight: 700;
    text-transform: uppercase;
    margin-bottom: 4px;
}
.popular-item .details h4 {
    font-size: 14px;
    font-weight: 600;
    line-height: 1.4;
}

/* Grid Bawah */
.bottom-grid {
    display: block;
    margin-top: 30px;
}

.news-list-horiz {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.card-horiz {
    display: flex;
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.03);
    cursor: pointer;
    transition: transform 0.2s;
}
.card-horiz:hover {
    transform: translateX(4px);
}
.card-horiz img {
    width: 220px;
    height: 140px;
    object-fit: cover;
}
.card-horiz .card-body {
    padding: 15px 20px;
    display: flex;
    flex-direction: column;
}

.card-recommendation {
    display: flex;
    flex-direction: column;
    background: #fff;
    border-bottom: 1px solid var(--border-color);
    padding-bottom: 15px;
    margin-bottom: 15px;
}
.card-recommendation:last-child {
    border-bottom: none;
    margin-bottom: 0;
}
.card-recommendation img {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 12px;
}
.card-recommendation .tag {
    font-size: 10px;
    color: var(--bg-primary);
    font-weight: 700;
    margin-bottom: 4px;
    text-transform: uppercase;
}
.card-recommendation h4 {
    font-size: 15px;
    font-weight: 700;
    margin-bottom: 6px;
}
.card-recommendation p {
    font-size: 12px;
    color: var(--text-muted);
}
.btn-load-more {
    display: block;
    width: 100%;
    padding: 15px;
    background: #f5f5f5;
    color: var(--bg-primary);
    text-align: center;
    font-weight: 800;
    font-size: 14px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    margin-top: 20px;
    text-transform: uppercase;
    transition: background 0.3s;
}
.btn-load-more:hover {
    background: #e0e0e0;
}
</style>
@endsection

@section('content')
<div class="dashboard-container">
    
    <div style="margin-bottom: 30px;">
        <h2 style="font-size:28px; font-weight:800; text-transform:uppercase; color:var(--bg-primary); display:flex; align-items:center; gap:10px;">
            <span style="display:inline-block; width:8px; height:28px; background:var(--accent); border-radius:4px;"></span>
            <span id="page-section-title">KATEGORI</span>
        </h2>
    </div>

    <div class="hero-grid">
        <div class="hero-main">
            <!-- Hero Top Card (Article 0) -->
            <div id="hero-top-article">
                <div style="padding:20px; text-align:center;">Memuat Artikel...</div>
            </div>

            <!-- Sub Grid (Article 1, 2, 3) -->
            <div class="hero-sub-grid" id="hero-sub-articles">
            </div>
        </div>

        <!-- Sidebar Terpopuler -->
        <div class="sidebar-box">
            <h3 class="section-title">Terpopuler</h3>
            <div class="popular-list" id="popular-list">
            </div>
        </div>
    </div>

    <!-- Bottom Grid -->
    <div class="bottom-grid">
        <!-- Berita Terkini -->
        <div>
            <h3 class="section-title">Berita Terkini Lainnya</h3>
            <div class="news-list-horiz" id="terkini-list">
            </div>
            <button id="btn-load-more" class="btn-load-more">Lihat Berita Lainnya</button>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', async () => {
    const sectionId = "{{ $sectionId }}";
    
    const getSecName = (id) => window.getSectionName ? window.getSectionName(id) : id;

    // Delay sedikit agar script layout memuat sectionsMap dulu
    setTimeout(async () => {
        document.getElementById('page-section-title').innerText = getSecName(sectionId);

        if (!supabaseClient) return;
        
        // Fetch ALL recent articles for THIS section
        const { data: articles, error } = await supabaseClient
            .from('article')
            .select('*')
            .eq('section_id', sectionId)
            .order('publish_date', { ascending: false })
            .limit(20);

        if (articles && articles.length > 0) {
            // 1. Hero (Index 0)
            const hero = articles[0];
            document.getElementById('hero-top-article').innerHTML = `
                <a href="/article/${hero.article_id}" class="hero-top-card" style="display:block">
                    <img src="${hero.photo_url || 'https://via.placeholder.com/800x400?text=News'}" alt="News">
                    <div class="overlay">
                        <div class="tag">${getSecName(hero.section_id)}</div>
                        <h1>${hero.title}</h1>
                        <p>${hero.content ? hero.content.substring(0, 100) + '...' : ''}</p>
                        <div class="meta">${new Date(hero.publish_date).toLocaleDateString()}</div>
                    </div>
                </a>
            `;

            // 2. Sub Hero (Index 1, 2, 3)
            let subHtml = '';
            for (let i = 1; i <= 3; i++) {
                if (articles[i]) {
                    subHtml += `
                    <a href="/article/${articles[i].article_id}" class="card-sm">
                        <img src="${articles[i].photo_url || 'https://via.placeholder.com/400x300?text=News'}" alt="News">
                        <div class="card-body">
                            <div class="tag">${getSecName(articles[i].section_id)}</div>
                            <h3>${articles[i].title}</h3>
                            <div class="card-footer">
                                <span>${new Date(articles[i].publish_date).toLocaleDateString()}</span>
                                <i class="fa-regular fa-bookmark"></i>
                            </div>
                        </div>
                    </a>
                    `;
                }
            }
            document.getElementById('hero-sub-articles').innerHTML = subHtml;

            // 3. Terpopuler Sidebar (Index 4, 5, 6, 7, 8)
            let popHtml = '';
            let rank = 1;
            for (let i = 4; i <= 8; i++) {
                if (articles[i]) {
                    popHtml += `
                    <a href="/article/${articles[i].article_id}" class="popular-item" style="display:flex">
                        <div class="number">${rank++}</div>
                        <div class="details">
                            <div class="tag">${getSecName(articles[i].section_id)}</div>
                            <h4>${articles[i].title}</h4>
                        </div>
                    </a>
                    `;
                }
            }
            document.getElementById('popular-list').innerHTML = popHtml;

            // 4. Terkini List Bawah (Index 9 - 13)
            let horizHtml = '';
            for (let i = 9; i <= 13; i++) {
                if (articles[i]) {
                    horizHtml += renderCardHoriz(articles[i], getSecName);
                }
            }
            document.getElementById('terkini-list').innerHTML = horizHtml;

        } else {
            document.getElementById('hero-top-article').innerHTML = '<div style="padding:40px; text-align:center;">Belum ada artikel untuk kategori ini.</div>';
        }
    }, 150);

    // Fungsi helper render horizontal card
    function renderCardHoriz(art, getSecName) {
        return `
            <a href="/article/${art.article_id}" class="card-horiz">
                <img src="${art.photo_url || 'https://via.placeholder.com/400x300?text=News'}" alt="News">
                <div class="card-body">
                    <div class="tag" style="color:var(--accent); font-weight:700; font-size:10px; margin-bottom:5px; text-transform:uppercase;">${getSecName(art.section_id)}</div>
                    <h3 style="font-size:16px; margin-bottom:8px;">${art.title}</h3>
                    <p style="font-size:13px; color:#666; margin-bottom:10px;">${art.content ? art.content.substring(0, 80) + '...' : ''}</p>
                    <div style="font-size:11px; color:#999; margin-top:auto;">${new Date(art.publish_date).toLocaleDateString()}</div>
                </div>
            </a>
        `;
    }

    // Load More Logic
    let currentOffset = 14; // start after index 13
    const btnLoadMore = document.getElementById('btn-load-more');
    if(btnLoadMore) {
        btnLoadMore.addEventListener('click', async () => {
            btnLoadMore.innerText = "Memuat...";
            const { data: moreArticles, error } = await supabaseClient
                .from('article')
                .select('*')
                .eq('section_id', sectionId)
                .order('publish_date', { ascending: false })
                .range(currentOffset, currentOffset + 4);

            if (moreArticles && moreArticles.length > 0) {
                let moreHtml = '';
                moreArticles.forEach(art => {
                    moreHtml += renderCardHoriz(art, getSecName);
                });
                document.getElementById('terkini-list').insertAdjacentHTML('beforeend', moreHtml);
                currentOffset += moreArticles.length;
                btnLoadMore.innerText = "Lihat Berita Lainnya";
            } else {
                btnLoadMore.innerText = "Tidak Ada Berita Lainnya";
                btnLoadMore.disabled = true;
                btnLoadMore.style.background = "#eee";
                btnLoadMore.style.color = "#aaa";
                btnLoadMore.style.cursor = "not-allowed";
            }
        });
    }

});
</script>
@endsection