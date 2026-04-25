<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ntvnews.id - Mengawal Informasi</title>
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom Styles -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    @yield('styles')
</head>
<body>

    @if (!isset($hideHeader))
    <header>
        <a href="{{ url('/dashboard') }}" class="header-logo">
            <div class="brand">ntvnews<span class="id-badge">.id</span></div>
            <div class="tagline">mengawal informasi</div>
        </a>
        <nav class="nav-links" id="main-nav-links">
            <!-- Dimuat dinamis oleh JS -->
            <a href="#">Memuat...</a>
        </nav>
        <div class="header-actions">
            <form action="{{ url('/dashboard') }}" method="GET" style="display:flex; align-items:center; background:rgba(255,255,255,0.1); border-radius:20px; padding:2px 10px;">
                <input type="text" name="q" placeholder="Cari Berita..." style="background:transparent; border:none; color:#fff; outline:none; font-size:12px; width:120px;" value="{{ request('q') }}">
                <button type="submit" style="background:none; border:none; color:#fff; cursor:pointer;"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
            <a href="{{ url('/profile') }}"><i class="fa-regular fa-user"></i></a>
            <a href="#" class="btn-live">LIVE</a>
        </div>
    </header>
    @endif

    <main>
        @yield('content')
    </main>

    @if (!isset($hideFooter))
    <footer>
        <div class="footer-container">
            <div>
                <div class="footer-brand">NTV News</div>
                <div class="footer-text">Part of NT Corp Media</div>
            </div>
            <div class="footer-col">
                <h4>TENTANG KAMI</h4>
                <ul>
                    <li><a href="#">Tentang</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Disclaimer</a></li>
                    <li><a href="#">Pedoman Media Siber</a></li>
                    <li><a href="#">Kontak & Iklan</a></li>
                    <li><a href="#">Hak Jawab</a></li>
                    <li><a href="#">Tim Redaksi</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>TV NETWORK</h4>
                <ul>
                    <li><a href="#">ntv</a></li>
                    <li><a href="#">harum tv</a></li>
                    <li><a href="#">bhineka tv</a></li>
                    <li><a href="#">gold tv</a></li>
                    <li><a href="#">reouters tv</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>NETWORK</h4>
                <ul>
                    <li><a href="#">sahabat</a></li>
                    <li><a href="#">ntvnews</a></li>
                    <li><a href="#">healthpedia</a></li>
                    <li><a href="#">teknospace</a></li>
                    <li><a href="#">jurnalmu</a></li>
                    <li><a href="#">kamutahu</a></li>
                    <li><a href="#">okedeh</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            Copyright &copy; {{ date('Y') }} NTVnews.id. All right reserved
        </div>
    </footer>
    @endif

    <!-- Supabase JS for realtime -->
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
    <script>
        // Setup Supabase Client
        const supabaseUrl = '{{ env('SUPABASE_URL') }}';
        // Menggunakan SERVICE_KEY eksklusif untuk bypass Row Level Security agar UI di localhost 100% muncul
        const supabaseKey = '{{ env('SUPABASE_SERVICE_KEY') }}';
        
        let supabaseClient = null;
        if(supabaseUrl && supabaseKey) {
             supabaseClient = supabase.createClient(supabaseUrl, supabaseKey);
        }

        // Global Sections Map & Loader
        window.sectionsMap = {
            'S001': 'News',
            'S002': 'Hiburan',
            'S003': 'Ekonomi',
            'S004': 'Olahraga',
            'S005': 'Otomotif',
            'S006': 'Astacita',
            'S007': 'Ibl'
        }; // Fallback statis jika database lambat

        window.getSectionName = function(sectionId) {
            return window.sectionsMap[sectionId] || sectionId || 'NEWS';
        };

        async function initGlobalLayout() {
            if(!supabaseClient) return;
            const { data, error } = await supabaseClient
                .from('section')
                .select('*')
                .order('section_id', { ascending: true });

            if(data && data.length > 0) {
                const nav = document.getElementById('main-nav-links');
                if(nav) {
                    const currentPath = window.location.pathname;
                    let html = `<a href="{{ url('/dashboard') }}" ${currentPath === '/dashboard' || currentPath === '/' ? 'class="active"' : ''}>BERANDA</a>`;
                    data.forEach((sec) => {
                        window.sectionsMap[sec.section_id] = sec.section_name;
                        const isActive = currentPath.includes('/section/' + sec.section_id) ? 'class="active"' : '';
                        html += `<a href="/section/${sec.section_id}" ${isActive}>${sec.section_name.toUpperCase()}</a>`;
                    });
                    nav.innerHTML = html;
                }
            }
        }
        initGlobalLayout();
    </script>
    @yield('scripts')
</body>
</html>
