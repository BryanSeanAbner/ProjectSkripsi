@extends('layouts.app', ['hideHeader' => true, 'hideFooter' => true])

@section('content')
<div class="login-page">
    <div class="login-card">
        <div class="login-logo">
            ntvnews<span>.id</span>
        </div>
        <div class="login-subtitle">mengawal informasi</div>
        
        <h2>Selamat Datang Kembali</h2>
        <p class="welcome-msg">Masuk untuk melanjutkan kurasi berita Anda dan mengelola preferensi bacaan.</p>

        @if($errors->any())
            <div style="background-color: #fee2e2; color: #dc2626; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; text-align: left; border: 1px solid #fca5a5;">
                <i class="fa-solid fa-circle-exclamation" style="margin-right: 5px;"></i>
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ url('/login') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="email">Alamat Email</label>
                <div class="input-icon-wrapper">
                    <i class="fa-regular fa-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="nama@contoh.com" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">
                    Kata Sandi <a href="#" class="forgot-pw">Lupa Kata Sandi?</a>
                </label>
                <div class="input-icon-wrapper">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                    <i class="fa-regular fa-eye-slash toggle-pw" id="togglePassword"></i>
                </div>
            </div>

            <button type="submit" class="btn-primary">
                Masuk <i class="fa-solid fa-arrow-right"></i>
            </button>
        </form>

        <div class="divider">Atau Lanjutkan Dengan</div>

        <button class="social-btn">
            <!-- Simple google colored G using fontawesome or image -->
            <img src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" alt="Google"> Google
        </button>
        <button class="social-btn" style="color: #1877F2;">
            <i class="fa-brands fa-facebook" style="margin-right: 10px; font-size: 18px;"></i> Facebook
        </button>

        <div class="register-link">
            Belum memiliki akun? <a href="#">Daftar sekarang</a>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');

    togglePassword.addEventListener('click', function (e) {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
    });
</script>
@endsection
