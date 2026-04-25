@extends('layouts.app')

@section('styles')
<style>
.article-container {
    max-width: 1100px;
    margin: 40px auto;
    padding: 0 20px;
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 50px;
}

.breadcrumb {
    font-size: 11px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
}
.breadcrumb .tag {
    background: var(--accent);
    color: var(--bg-primary);
    padding: 2px 8px;
    text-transform: uppercase;
}
.breadcrumb span {
    color: var(--text-muted);
}

.article-title {
    font-size: 36px;
    font-weight: 800;
    line-height: 1.2;
    margin-bottom: 30px;
    color: var(--text-main);
}

.author-block {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border-color);
}
.author-info {
    display: flex;
    align-items: center;
    gap: 15px;
}
.author-info img {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
}
.author-info .name {
    font-weight: 700;
    font-size: 15px;
    margin-bottom: 2px;
}
.author-info .role {
    font-size: 12px;
    color: var(--text-muted);
}
.author-actions {
    display: flex;
    gap: 10px;
}
.author-actions button {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: none;
    background: #f0f0f0;
    color: #333;
    cursor: pointer;
    transition: background 0.2s;
}
.author-actions button:hover {
    background: #e0e0e0;
}

.article-hero-img {
    width: 100%;
    height: auto;
    border-radius: 8px;
    margin-bottom: 10px;
}
.article-caption {
    font-size: 12px;
    color: var(--text-muted);
    font-style: italic;
    text-align: center;
    margin-bottom: 40px;
}

.article-body {
    font-size: 16px;
    line-height: 1.8;
    color: #333;
}
.article-body p {
    margin-bottom: 20px;
}
.article-body blockquote {
    font-style: italic;
    font-weight: 600;
    font-size: 18px;
    border-left: 4px solid var(--accent);
    padding-left: 20px;
    margin: 30px 0;
    color: var(--bg-primary);
}

/* Sidebar Berita Serupa */
.sidebar-cbf {
    margin-top: 15px;
}
.sidebar-title {
    font-size: 22px;
    font-weight: 800;
    margin-bottom: 25px;
    color: var(--text-main);
}
.cbf-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.cbf-card {
    display: block;
    border-radius: 8px;
    overflow: hidden;
    position: relative;
    cursor: pointer;
}
.cbf-card img {
    width: 100%;
    height: 160px;
    object-fit: cover;
    display: block;
    transition: transform 0.3s;
}
.cbf-card:hover img {
    transform: scale(1.05);
}
.cbf-card .cbf-tag {
    position: absolute;
    bottom: 10px;
    left: 10px;
    background: #e53935;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 2px;
    text-transform: uppercase;
}
.cbf-card h4 {
    margin-top: 12px;
    font-size: 15px;
    font-weight: 700;
    line-height: 1.4;
    color: var(--text-main);
}
</style>
@endsection

@section('content')
<div class="article-container">
    <!-- Main Content -->
    <div id="article-content">
        <div style="text-align:center; padding:50px;">Memuat Artikel...</div>
    </div>

    <!-- Sidebar CBF -->
    <div class="sidebar-cbf">
        <h3 class="sidebar-title">Berita Serupa</h3>
        <div class="cbf-list" id="cbf-list">
            <!-- Rendered by JS -->
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', async () => {
    // ID artikel disuntikkan dari route/controller (misal melalui endpoint GET /article/{id})
    const articleId = {{ $id }};

    async function loadArticle() {
        if(!supabaseClient) return;
        const { data, error } = await supabaseClient
            .from('article')
            .select('*')
            .eq('article_id', articleId)
            .single();

        if(data) {
            const pubDate = new Date(data.publish_date);
            const dateStr = pubDate.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute:'2-digit' }) + ' WIB';

            // Split content per line for paragraphs
            const paragraphs = data.content ? data.content.split('\n').filter(p => p.trim() !== '').map(p => `<p>${p}</p>`).join('') : '<p>Belum ada isi konten.</p>';

            const html = `
                <div class="breadcrumb">
                    <div class="tag">${window.getSectionName ? window.getSectionName(data.section_id) : data.section_id}</div>
                    <span>${dateStr}</span>
                </div>
                
                <h1 class="article-title">${data.title}</h1>
                
                <div class="author-block">
                    <div class="author-info">
                        <img src="https://ui-avatars.com/api/?name=Jurnalis+NTV&background=170b3b&color=fff" alt="Siti Nurhaliza">
                        <div>
                            <div class="name">Siti Nurhaliza</div>
                            <div class="role">Jurnalis NTV News</div>
                        </div>
                    </div>
                    <div class="author-actions">
                        <button><i class="fa-solid fa-share-nodes"></i></button>
                        <button id="btn-bookmark"><i class="fa-regular fa-bookmark"></i></button>
                    </div>
                </div>

                <img src="${data.photo_url || 'https://via.placeholder.com/800x450?text=Gambar+Artikel'}" alt="Hero Image" class="article-hero-img">
                <div class="article-caption">Ilustrasi artikel. (Foto: Dok. Pribadi)</div>

                <div class="article-body">
                    ${paragraphs}
                </div>
            `;
            document.getElementById('article-content').innerHTML = html;

            // Fitur Bookmark
            const btnBookmark = document.getElementById('btn-bookmark');
            const userId = {{ session('active_user_id', Auth::id() ?? 1) }}; // Dari session
            
            // Cek apakah sudah di-bookmark
            const { data: bData } = await supabaseClient
                .from('user_bookmarks')
                .select('*')
                .eq('user_id', userId)
                .eq('article_id', articleId)
                .single();

            if (bData) {
                btnBookmark.innerHTML = '<i class="fa-solid fa-bookmark"></i>';
                btnBookmark.style.color = '#ffb400';
            }

            btnBookmark.addEventListener('click', async () => {
                const { data: exist } = await supabaseClient
                    .from('user_bookmarks')
                    .select('*')
                    .eq('user_id', userId)
                    .eq('article_id', articleId)
                    .single();

                if (exist) {
                    await supabaseClient.from('user_bookmarks').delete().eq('user_id', userId).eq('article_id', articleId);
                    btnBookmark.innerHTML = '<i class="fa-regular fa-bookmark"></i>';
                    btnBookmark.style.color = '#333';
                    alert('Dihapus dari Bookmark!');
                } else {
                    await supabaseClient.from('user_bookmarks').insert([{ user_id: userId, article_id: articleId }]);
                    btnBookmark.innerHTML = '<i class="fa-solid fa-bookmark"></i>';
                    btnBookmark.style.color = '#ffb400';
                    alert('Berhasil disimpan ke Bookmark!');
                }
            });

            // Simulasi catat Histori Baca (Masuk ke user_interaction saat buka artikel)
            await supabaseClient.from('user_interaction').insert([{
                user_id: userId,
                article_id: articleId,
            }]);

        } else {
            document.getElementById('article-content').innerHTML = `<h2>Artikel tidak ditemukan!</h2>`;
        }
    }

    async function loadCBF() {
        if(!supabaseClient) return;
        
        // 1. Ambil similar_article_id dari tabel article_similarity berdasarkan article_id saat ini
        const { data: similarityData, error: simError } = await supabaseClient
            .from('article_similarity')
            .select('*')
            .eq('article_id', articleId)
            .order('rank_position', { ascending: true })
            .limit(5);

        if(similarityData && similarityData.length > 0) {
            // 2. Kumpulkan IDs
            const similarIds = similarityData.map(item => item.similar_article_id);

            // 3. Tarik data artikelnya
            const { data: articles, error: artError } = await supabaseClient
                .from('article')
                .select('article_id, title, photo_url, section_id')
                .in('article_id', similarIds);

            if(articles) {
                let html = '';
                // Looping sesuai urutan di similarityData agar rank/posisi terjaga
                similarityData.forEach(sim => {
                    const articleData = articles.find(a => a.article_id === sim.similar_article_id);
                    if(!articleData) return;

                    html += `
                        <a href="/article/${articleData.article_id}" class="cbf-card">
                            <div style="position:relative;">
                                <img src="${articleData.photo_url || 'https://via.placeholder.com/400x250?text=Serupa'}" alt="Similar">
                                <div class="cbf-tag">${window.getSectionName ? window.getSectionName(articleData.section_id) : articleData.section_id}</div>
                            </div>
                            <h4>${articleData.title}</h4>
                        </a>
                    `;
                });
                document.getElementById('cbf-list').innerHTML = html;
            }
        }
    }

    loadArticle();
    loadCBF();
});
</script>
@endsection
