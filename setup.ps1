# Setup pertama kali setelah git clone (Windows / Laragon)
Write-Host ""
Write-Host "=== Setup ProjectTA ===" -ForegroundColor Cyan
Write-Host ""

if (-not (Test-Path "vendor")) {
    Write-Host ">> composer install..." -ForegroundColor Yellow
    composer install --no-interaction
    if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
}

Write-Host ">> php artisan project:setup..." -ForegroundColor Yellow
php artisan project:setup
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

Write-Host ""
Write-Host "Selesai! Jalankan:" -ForegroundColor Green
Write-Host "  php artisan serve"
Write-Host ""
Write-Host "Login demo: 1@gmail.com / password: 1" -ForegroundColor Gray
Write-Host ""
