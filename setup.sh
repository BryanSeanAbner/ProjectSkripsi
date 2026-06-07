#!/usr/bin/env bash
set -euo pipefail

echo ""
echo "=== Setup ProjectTA ==="
echo ""

if [ ! -d vendor ]; then
  echo ">> composer install..."
  composer install --no-interaction
fi

echo ">> php artisan project:setup..."
php artisan project:setup

echo ""
echo "Selesai! Jalankan:"
echo "  php artisan serve"
echo ""
echo "Login demo: 1@gmail.com / password: 1"
echo ""
