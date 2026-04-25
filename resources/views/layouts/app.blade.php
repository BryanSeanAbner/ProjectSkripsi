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
        <a href="{{ url('/') }}" class="header-logo">
            <div class="brand">ntvnews<span class="id-badge">.id</span></div>
            <div class="tagline">mengawal informasi</div>
        </a>
        <nav class="nav-links">
            <a href="#" class="active">NEWS</a>
            <a href="#">HIBURAN</a>
            <a href="#">EKONOMI</a>
            <a href="#">OLAHRAGA</a>
            <a href="#">OTOMOTIF</a>
            <a href="#">ASTACITA</a>
            <a href="#">IBL</a>
        </nav>
        <div class="header-actions">
            <i class="fa-solid fa-magnifying-glass"></i>
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
    </script>
    @yield('scripts')
</body>
</html>
