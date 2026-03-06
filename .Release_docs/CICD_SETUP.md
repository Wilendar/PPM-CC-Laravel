# PPM - Konfiguracja GitHub Actions CI/CD

## 1. Przeglad

Dwa workflow w `.github/workflows/`:

| Workflow | Plik | Trigger | Cel |
|----------|------|---------|-----|
| CI | `ci.yml` | Push do main/develop, PR do main | Testy, lint, build |
| Deploy | `deploy.yml` | Push do main, workflow_dispatch | Deploy na produkcje |

## 2. Wymagane GitHub Secrets

Konfiguracja: GitHub -> Settings -> Secrets and variables -> Actions -> New repository secret

| Secret | Wartosc | Opis |
|--------|---------|------|
| `HOSTIDO_SSH_KEY` | klucz prywatny OpenSSH | Konwertowany z .ppk (patrz sekcja 3) |
| `HOSTIDO_HOST` | `host379076.hostido.net.pl` | Adres serwera |
| `HOSTIDO_USER` | `host379076` | Uzytkownik SSH |
| `HOSTIDO_PORT` | `64321` | Port SSH |
| `DB_NAME` | `host379076_ppm` | Nazwa bazy |
| `DB_USER` | `host379076_ppm` | Uzytkownik bazy |
| `DB_PASSWORD` | *(haslo z dane_hostingu.md)* | Haslo do bazy |

## 3. Konwersja klucza SSH

GitHub Actions wymaga formatu OpenSSH. Klucz Hostido jest w formacie PuTTY (.ppk).

### Konwersja przez PuTTYgen (GUI)

1. Otworz PuTTYgen
2. File -> Load private key -> wybierz `HostidoSSHNoPass.ppk`
3. Conversions -> Export OpenSSH key
4. Zapisz jako `hostido_openssh_key`
5. Skopiuj zawartosc pliku do GitHub Secret `HOSTIDO_SSH_KEY`

### Konwersja przez CLI

```powershell
# Wymaga puttygen w PATH
puttygen "D:\SSH\Hostido\HostidoSSHNoPass.ppk" -O private-openssh -o hostido_openssh_key

# Wyswietl klucz (skopiuj do GitHub Secrets)
Get-Content hostido_openssh_key

# USUN plik po skopiowaniu!
Remove-Item hostido_openssh_key
```

**WAZNE:** Klucz OpenSSH zaczyna sie od `-----BEGIN OPENSSH PRIVATE KEY-----`. Skopiuj CALY tekst wlacznie z BEGIN/END.

## 4. CI Workflow (ci.yml)

### Co testuje

| Job | Opis | Rownolegly |
|-----|------|-----------|
| `test` | MariaDB 10.11 + PHP 8.3 + testy Laravel | Tak |
| `lint` | Laravel Pint (code style) + PHPStan | Tak |
| `build` | npm build + weryfikacja manifest | Tak |

### Kiedy sie uruchamia
- Push do `main` lub `develop`
- Pull Request do `main`

### Jak sprawdzic wynik
- GitHub -> repo -> Actions -> CI
- Zielona ikonka = OK, czerwona = blad

## 5. CD Workflow (deploy.yml)

### Automatyczny deploy
Kazdy push do `main` uruchamia:
1. CI musi przejsc (gate)
2. Build (PHP + assets)
3. Backup bazy na serwerze
4. rsync plikow
5. composer install + migrate + cache
6. Health check

### Reczny deploy (workflow_dispatch)
1. GitHub -> repo -> Actions -> Deploy
2. Kliknij "Run workflow"
3. Opcjonalnie: zaznacz "Skip migrations" lub "Skip backup"
4. Kliknij "Run workflow"

### Co deployuje
- `app/`, `bootstrap/`, `config/`, `database/`, `resources/`, `routes/`
- `public/build/` (assets)
- `composer.json`, `composer.lock`, `artisan`

### Czego NIE deployuje
- `.env` (konfiguracja produkcyjna na serwerze)
- `vendor/` (instalowany na serwerze via composer)
- `node_modules/` (nie potrzebne na produkcji)
- `storage/logs/` (logi produkcyjne)
- `_TOOLS/`, `_DOCS/`, `.claude/` (narzedzia deweloperskie)
- `tests/` (niepotrzebne na produkcji)

## 6. Rollback

Jesli deploy sie nie powiedzie:
1. Job `rollback` uruchamia sie automatycznie
2. Czysci cache na serwerze
3. Wyswietla instrukcje do recznego restore z backupu

Reczny rollback:
```bash
# SSH na serwer
ls ~/backups/  # znajdz backup
cd ~/backups && gunzip -k backup_XXX.sql.gz
mysql -u host379076_ppm -p host379076_ppm < backup_XXX.sql
cd ~/domains/ppm.mpptrade.pl/public_html && php artisan optimize:clear
```

## 7. Troubleshooting

### Deploy failed - SSH connection refused
- Sprawdz czy `HOSTIDO_PORT` = 64321 (niestandardowy!)
- Sprawdz czy klucz w `HOSTIDO_SSH_KEY` jest w formacie OpenSSH

### Deploy failed - Permission denied
- Klucz SSH moze byc w zlym formacie (PuTTY zamiast OpenSSH)
- Powtorz konwersje z sekcji 3

### Deploy failed - rsync error
- Shared hosting moze nie miec rsync - sprawdz logi
- Alternatywa: scp w deploy.yml

### CI failed - MariaDB connection
- Sprawdz czy service container sie uruchomil (health check w logach)
- Port 3306 musi byc zmapowany

### Build failed - npm
- Sprawdz `package-lock.json` - moze byc out of sync z `package.json`
- `npm ci` wymaga `package-lock.json` w repo
