# CRON Setup Guide dla PPM-CC-Laravel Queue Worker

## Problem
Supervisor nie jest dostępny na Hostido (shared hosting). Queue worker musi być uruchamiany automatycznie.

## Rozwiązanie: CRON Job

### 1. Dostęp do CRON Jobs w cPanel Hostido

1. Zaloguj się do cPanel: https://panel.hostido.net.pl
2. Znajdź sekcję "Zaawansowane" → "Zadania Cron" (Cron Jobs)

### 2. Dodaj Cron Job dla Laravel Scheduler

**Częstotliwość:** Co minutę
**Komenda:**
```bash
cd /home/host379076/domains/ppm.mpptrade.pl/public_html && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

**Ustawienia w cPanel:**
- Minuta: `*`
- Godzina: `*`
- Dzień: `*`
- Miesiąc: `*`
- Dzień tygodnia: `*`
- Komenda: (jak wyżej)

### 3. Dodaj Cron Job dla Queue Worker

**Częstotliwość:** Co minutę
**Komenda:**
```bash
cd /home/host379076/domains/ppm.mpptrade.pl/public_html && /usr/bin/php artisan queue:work --stop-when-empty --max-time=3600 >> storage/logs/queue-worker.log 2>&1
```

**Wyjaśnienie parametrów:**
- `--stop-when-empty` - worker zatrzymuje się gdy queue jest pusta (nie blokuje następnego crona)
- `--max-time=3600` - maksymalny czas wykonywania: 1 godzina
- `>> storage/logs/queue-worker.log` - logi do pliku

**Ustawienia w cPanel:**
- Minuta: `*`
- Godzina: `*`
- Dzień: `*`
- Miesiąc: `*`
- Dzień tygodnia: `*`
- Komenda: (jak wyżej)

### 4. Weryfikacja

Sprawdź logi po kilku minutach:
```bash
ssh host379076@host379076.hostido.net.pl -p 64321
cd domains/ppm.mpptrade.pl/public_html
tail -f storage/logs/queue-worker.log
```

Sprawdź czy jobs są przetwarzane:
```bash
php artisan queue:monitor
```

### 5. Monitoring

**Sprawdź failed jobs:**
```bash
php artisan queue:failed
```

**Restart failed jobs:**
```bash
php artisan queue:retry all
```

**Wyczyść failed jobs:**
```bash
php artisan queue:flush
```

## Alternatywne Rozwiązanie: Keep-Alive Script

Jeśli CRON nie działa, możesz utworzyć keep-alive script:

**Plik:** `_TOOLS/queue-worker-keepalive.sh`
```bash
#!/bin/bash

while true; do
    cd /home/host379076/domains/ppm.mpptrade.pl/public_html
    /usr/bin/php artisan queue:work --stop-when-empty --max-time=3600
    sleep 60
done
```

Uruchom w tle:
```bash
nohup bash _TOOLS/queue-worker-keepalive.sh &
```

## Troubleshooting

**Problem:** Jobs nie są przetwarzane
**Rozwiązanie:** Sprawdź logi i upewnij się że queue connection jest ustawiona na 'database' w .env

**Problem:** CRON nie wykonuje się
**Rozwiązanie:** Sprawdź cPanel logs i upewnij się że ścieżka PHP jest poprawna

**Problem:** Timeout errors
**Rozwiązanie:** Zwiększ `--max-time` lub ogranicz size jobs

## Status

- [ ] CRON Jobs skonfigurowane w cPanel
- [ ] Logi queue workera działają
- [ ] Import PrestaShop jobs są przetwarzane
- [ ] Monitoring ustawiony
