#!/bin/bash
# Disable Telescope on production

cd /home/host379076/domains/ppm.mpptrade.pl/public_html

# Add Telescope disable to .env
echo "" >> .env
echo "# Disable Telescope in production - OOM fix 2026-01-19" >> .env
echo "TELESCOPE_ENABLED=false" >> .env
echo "TELESCOPE_QUERY_WATCHER=false" >> .env

# Clear config cache
php artisan config:clear

echo "Telescope disabled in .env"
grep TELESCOPE .env
