# PPM - Przewodnik Deployment

## 1. Przeglad

Deployment na produkcje (`ppm.mpptrade.pl`) odbywa sie dwoma sposobami:

| Metoda | Kiedy uzywac | Automatyzacja |
|--------|-------------|---------------|
| `deploy.ps1` | Reczny deploy z lokalnej maszyny | Skrypt PowerShell |
| GitHub Actions | Auto-deploy przy push do main | CI/CD pipeline |

**Serwer produkcyjny:** Hostido.net.pl (shared hosting, brak Node.js/npm).
Assets budowane sa lokalnie i uploadowane jako statyczne pliki.

## 2. deploy.ps1 - Tryby pracy

Lokalizacja: `.Release_docs/scripts/deploy.ps1`

### 2.1 Full Deploy (domyslny)

```powershell
.\deploy.ps1 -Mode full
```

Wykonuje: backup DB -> npm build -> upload assets + PHP -> composer install -> migrate -> cache -> verify.

### 2.2 Assets Only

```powershell
.\deploy.ps1 -Mode assets
```

Tylko: npm build -> upload CSS/JS/manifest -> cache clear -> verify.
Uzywaj po zmianach w CSS, JavaScript, obrazkach.

### 2.3 PHP Only

```powershell
.\deploy.ps1 -Mode php
```

Tylko: upload PHP -> composer install -> migrate -> cache -> verify.
Uzywaj po zmianach w kodzie PHP bez zmian frontend.

### 2.4 Migrate Only

```powershell
.\deploy.ps1 -Mode migrate
```

Tylko: php artisan migrate --force + cache clear.

### 2.5 Rollback

```powershell
.\deploy.ps1 -Mode rollback
```

Wyswietla liste backupow na serwerze, pozwala wybrac i przywrocic.

## 3. Parametry dodatkowe

| Parametr | Opis |
|----------|------|
| `-SkipBackup` | Pomija backup DB (domyslnie backup ON) |
| `-SkipBuild` | Pomija npm run build |
| `-SkipMigrate` | Pomija php artisan migrate |
| `-DryRun` | Symulacja - wyswietla co zrobi, ale NIE wykonuje |
| `-Verbose` | Szczegolowe logi |

### Przyklady

```powershell
# Deploy z pomieciem backapu (szybszy)
.\deploy.ps1 -Mode full -SkipBackup

# Tylko build i upload assets, bez migracji
.\deploy.ps1 -Mode assets -SkipMigrate

# Symulacja pelnego deploy
.\deploy.ps1 -Mode full -DryRun

# PHP deploy ze szczegolowym logiem
.\deploy.ps1 -Mode php -Verbose
```

## 4. Backup

### Automatyczny backup
Kazdy deploy (z wyjatkiem `-SkipBackup`) automatycznie tworzy backup bazy:
- Lokalizacja na serwerze: `~/backups/`
- Format: `backup_YYYYMMDD_HHMMSS.sql.gz`
- Retencja: ostatnie 5 backupow (starsze usuwane automatycznie)

### Reczny backup
```powershell
# Przez SSH
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i "D:\SSH\Hostido\HostidoSSHNoPass.ppk" -batch "mysqldump --single-transaction host379076_ppm | gzip > ~/backups/manual_backup.sql.gz"
```

## 5. Rollback

### Przez skrypt
```powershell
.\deploy.ps1 -Mode rollback
# Wyswietli liste backupow -> wybierz -> restore
```

### Recznie przez SSH
```bash
# Lista backupow
ls -lh ~/backups/

# Restore
cd ~/backups && gunzip -k backup_YYYYMMDD_HHMMSS.sql.gz
mysql -u host379076_ppm -p host379076_ppm < backup_YYYYMMDD_HHMMSS.sql
cd ~/domains/ppm.mpptrade.pl/public_html && php artisan cache:clear && php artisan config:clear && php artisan view:clear
```

## 6. Vite Manifest - KRYTYCZNY KROK

Laravel szuka `public/build/manifest.json` (root), ale Vite generuje `.vite/manifest.json`.

**deploy.ps1 robi to automatycznie**, ale przy recznym deploy:
```powershell
pscp -i $Key -P 64321 "public/build/.vite/manifest.json" host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/public/build/manifest.json
```

Bez tego kroku: stare CSS/JS mimo uploadu nowych plikow.

## 7. GitHub Actions

Push do `main` uruchamia automatyczny deploy. Szczegoly: [CICD_SETUP.md](CICD_SETUP.md)

## 8. Troubleshooting

### CSS nie aktualizuje sie
1. Sprawdz czy `npm run build` przeszedl
2. Sprawdz hash w manifest.json - czy sie zmienil
3. Upload WSZYSTKIE assets (nie tylko zmienione)
4. Wyczysc cache: `php artisan view:clear && cache:clear`
5. Hard refresh w przegladarce: Ctrl+Shift+R

### 500 Internal Server Error
```bash
# Sprawdz logi
tail -50 storage/logs/laravel.log

# Czesto pomaga
php artisan optimize:clear
composer dump-autoload
```

### Class not found po deploy
```bash
composer dump-autoload --optimize
php artisan optimize:clear
```

### Timeout przy upload
- Duze pliki (composer.lock, vendor/) moga trwac dluzej
- Uzyj `-Mode php` zamiast `full` dla szybszego deploy
