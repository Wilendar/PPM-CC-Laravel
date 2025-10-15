# AGENTS.md - Agent Instructions & Workflow

### KRYTYCZNE: NATYCHMIASTOWY DEPLOY PO ZMIANACH

- Po każdej zmianie w repo wykonuj natychmiastowy deploy na Hostido (prod) – bez czekania na osobne potwierdzenie.
- Procedura minimalna (domyślna):
  - Upload: `_TOOLS/hostido_deploy.ps1 -SourcePath "." -TargetPath "/domains/ppm.mpptrade.pl/public_html/"`
  - Komendy: `_TOOLS/hostido_deploy.ps1 -Command "cd domains/ppm.mpptrade.pl/public_html && composer install --no-dev && php artisan migrate --force && php artisan view:clear && php artisan config:clear && php artisan cache:clear"`
  - Health-check: odwiedź `/up`, smoke-test `/admin` (403/200 oraz widżety dashboardu)
  - DryRun:  `_TOOLS/hostido_deploy.ps1 -DryRun -Verbose` (bez uploadu i bez komend) 
  - Uwaga: skrypt deploy wyklucza  `vendor/*` z synchronizacji (remote vendor nie jest usuwany) 
- Backup (zalecany przy migracjach/ryzyku):
  - `_TOOLS/hostido_deploy.ps1 -CreateBackup -BackupName "auto_YYYYMMDD_HHMM"`
- Awaria/Brak dostępu:
  - Jeśli deploy nie jest możliwy (np. brak sieci/SSH), dodaj wpis do `_REPORTS` (⚠️ DEPLOY PENDING) z powodem i następnie wznowisz deployment przy pierwszej możliwości.
- Bezpieczeństwo:
  - Nie wykonuj destrukcyjnych operacji (rm -rf, reset danych) bez wyraźnego polecenia. Zawsze korzystaj ze skryptów w `_TOOLS` i preferuj backup + bezpieczne komendy Laravela.

### ZASADY AKTUALIZACJI PLANU (Plan_Projektu/*)

- Aktualizuj istniejące punkty: edytuj linie bezpośrednio w pliku planu. Nie dopisuj osobnych sekcji typu „AKTUALIZACJA PLANU” na końcu pliku.
- Statusy kroków: stosuj sekwencję: ❌ (nie rozpoczęte) → 🛠️ (w trakcie) → ✅ (ukończone); używaj ⚠️ dla blokerów z krótkim opisem przyczyny i odnośnikiem do blokującego podpunktu.
- Reguła PLIK: dodawaj „└──📁 PLIK: …” WYŁĄCZNIE przy statusie ✅. Wcięcie musi być wyrównane pod linią z ✅. Podawaj klikalne ścieżki względne (np. `app/...`, `resources/...`, `routes/...`).
- Granularność: oznaczaj status na najniższym poziomie (np. 1.1.2.1.4), nie tylko na poziomie nagłówka rodzica. Rodzic może pozostać 🛠️, jeśli część zadań dzieci nadal trwa.
- Spójność numeracji: nie zmieniaj numerów istniejących zadań i nie twórz nowych „ad-hoc” bez decyzji. Pracuj w obrębie wskazanej FAZY/podsekcji.
- Weryfikacja przed ✅: przed zmianą na ✅ upewnij się, że kod istnieje i przechodzi podstawowy smoke-check (np. komponent się renderuje, trasa odpowiada, logika działa).
- Zakres PLIK: wpisuj tylko najważniejsze 3–5 plików dla danego podpunktu – unikaj nadmiernie długich list.
- Raport po zmianie: po istotnej aktualizacji planu dodaj raport do `_REPORTS` zgodnie z szablonem (`[Punkt_Planu]_REPORT.md`) z listą plików i krótkim opisem zmian.

Przykład poprawnej zmiany punktu:

```
- ❌ 1.1.2.1.4 Recent Activity count (last 24h)
+ ✅ 1.1.2.1.4 Recent Activity count (last 24h)
      └──📁 PLIK: app/Http/Livewire/Dashboard/AdminDashboard.php
```

**UWAGA**: Szczegółowa dokumentacja projektu (Stack, Architektura, Encje) → Zobacz [CLAUDE.md](CLAUDE.md)

## Komendy i Workflow

### Development Workflow
```bash
# Lokalne środowisko development
php artisan serve
php artisan migrate
php artisan db:seed

# Build assets
npm install
npm run dev       # Development
npm run build     # Production

# Testy
php artisan test
./vendor/bin/phpunit
```

### Deployment na Hostido
```powershell
# SSH z kluczem PuTTY (ścieżka do klucza)
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

# Test połączenia
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "php -v"

# Deployment commands
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && composer install --no-dev"

# Migracje i cache
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan migrate --force && php artisan config:cache"
```

### Szybki upload pojedynczych plików (Quick Push)

- Narzędzie: `_TOOLS/hostido_quick_push.ps1`
- Zastosowanie: gdy zmieniasz tylko kilka plików (np. Blade/JS/CSS/PHP) i nie potrzebujesz pełnej synchronizacji ani composera.
- Przykłady:
  - Pojedynczy widok + odświeżenie cache widoków:
    `_TOOLS/hostido_quick_push.ps1 -Files @('resources/views/layouts/admin.blade.php') -PostCommand "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear"`
  - Kilka plików:
    `_TOOLS/hostido_quick_push.ps1 -Files @('resources/views/livewire/dashboard/admin-dashboard.blade.php','resources/views/layouts/admin.blade.php') -PostCommand "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear"`
  - Klasa PHP (bez komend po stronie serwera):
    `_TOOLS/hostido_quick_push.ps1 -Files @('app/Http/Controllers/Admin/DashboardController.php')`

Uwaga: pełny skrypt deploy (`_TOOLS/hostido_deploy.ps1`) używa przyrostowego `synchronize remote` (wysyła tylko zmienione pliki) i wyklucza `vendor/*`. Opcjonalny `-Command` (np. `composer install`) uruchamiany jest przed końcowym cache, a następnie post-deploy wykonuje się ponownie, aby domknąć cache po instalacji paczek.

### Kiedy używać którego trybu (When to use)

- Quick Push: pojedyncze/kilka plików (Blade/JS/CSS/PHP) bez zmian zależności i migracji.
  - Przykład: `_TOOLS/hostido_quick_push.ps1 -Files @('resources/views/layouts/admin.blade.php') -PostCommand "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear"`
- UploadOnly + NoDelete: wiele plików w różnych folderach, bez composer/migracji, bezpieczny (nic nie usuwa po stronie serwera).
  - Przykład: `_TOOLS/hostido_deploy.ps1 -UploadOnly -NoDelete -Verbose`
- Pełny deploy + -Command: tylko gdy zmienia się `composer.lock`, są migracje lub istotne zmiany konfiguracji/cache.
  - Przykład: `_TOOLS/hostido_deploy.ps1 -Verbose -Command "cd domains/ppm.mpptrade.pl/public_html && composer install --no-dev && php artisan migrate --force && php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache"`
- Dokumentacja/markdown: Quick Push bez `-PostCommand`.
- ZAWSZE: unikać pełnego build/deploy jeśli nie jest konieczny; preferować Quick Push lub UploadOnly+NoDelete.

### Ręczne połączenie SSH
```bash
# Wymaga klucza SSH (HostidoSSHNoPass.ppk)
ssh -p 64321 host379076@host379076.hostido.net.pl
```

### Baza Danych
```bash
# Migracje
php artisan migrate
php artisan migrate:rollback
php artisan migrate:status

# Seeders
php artisan db:seed
php artisan db:seed --class=ProductSeeder
```

**Funkcjonalności, Integracje, Kolejność Implementacji** → Zobacz [CLAUDE.md](CLAUDE.md)
**Super Admin Account (testing)** → Zobacz [CLAUDE.md](CLAUDE.md) sekcja "Super Admin Account"

### PODSTAWOWE ZASADY

- **Język**: Polski we wszystkich odpowiedziach
- **Dokumentacja**: Aktualizuj AGENTS.md przy milestone/fix błędów + plan projektu przy ukończonych etapach
- **Start sesji**: Odczytaj AGENTS.md projektu + Plan_Projektu.md (utwórz jeśli brak)
- **Git repo**: "wilendar@gmail.com" / "[GITHUB_PASSWORD]" / Token: "[GITHUB_TOKEN]"
- **Środowisko:** Pracujesz w środowisku Windows z Powershell 7, używasz komendy "pwsh", możesz stosować wszystkie funkcje powershell7 jak kolory, animacje, emojii, pamietaj o kodowaniu UTF-8.
- **NIGDY** nie hardcodujesz na sztywno wpisanych wartości w kodzie, chyba, że użytkownik Cię o to wyraźnie poprosi.
- **ZAWSZE** Twórz i aktualizuj listę TODO i pokazuj ją użytkownikowi podczas wykonywania swoich prac.

### ZACHOWAJ PORZĄDEK W PROJEKCIE

- **ZAKAZ** tworzenia plików niezwiązanych z projektem w folderze **root** projektu
- Każdy typ plików powinien mieć swój wyszczególniony folder, np. pliki .txt, .pdf, .md w folderze "_DOCS"
- Wszystkie Raporty Agentów powinny się znajdować w Folderze "_AGENT_REPORTS"
- Wszystkie narzędzia stworzone na potrzeby projektu powinny się znajdować w folderze "_TOOLS"
- Wszystkie pliki/skrypty testowe powinny znajdować się w folderze "_TEST"
- Jeżeli występują pliki niesklasyfikowane, lub nie pasujące do powyższych zasad umieść je w Folderze "_OTHER"

### SYSTEM DOKUMENTACJI PRAC AGENTÓW

- **OBOWIĄZKOWY PLIK .md**: Twórz plik `[Nazwa_planu_punkt_planu]_REPORT.md` z podsumowaniem swoich prac
- **LOKALIZACJA**: Pliki reportów w folderze "_REPORTS"
- **FORMAT RAPORTU AGENTA**:
```
# RAPORT PRACY AGENTA: [punkt_planu_nazwa_punktu]
**Data**: [YYYY-MM-DD HH:MM]
**Zadanie**: [krótki opis zadania]

## ✅ WYKONANE PRACE
- Lista wykonanych zadań
- Ścieżki do utworzonych/zmodyfikowanych plików
- Krótkie opisy zmian

## ⚠️ PROBLEMY/BLOKERY
- Lista napotkanych problemów
- Nierozwiązane kwestie wymagające uwagi

## 📋 NASTĘPNE KROKI
- Co należy zrobić dalej
- Zalecenia dla kolejnych agentów

## 📁 PLIKI
- [nazwa_pliku.ext] - [opis zmian]
- [folder/nazwa_pliku.ext] - [opis zmian]
```

- **PLAN**: Zawsze twórz plan wg. następującego szablonu:

  **KRYTYCZNE** twórz odnośnik do pliku z kodem do podpunktu WYŁĄCZNIE PO UKOŃCZENIU ZADANIA ✅ - NIGDY PRZED: 
          └──📁 PLIK: adres/do/pliku.cs

```
# ❌ 1. ETAP 1
## 	❌ 1.1 Zadanie Etapu 1
### 	❌ 1.1.1 Podzadanie do zadania etapu 1
			❌ 1.1.1.1 Podzadanie do podzadania do zadania etapu
				❌ 1.1.1.1.1 Głębokie podzadanie
```

**UWAAGA!** Plan Tworzysz w Folderze "Plan_Projektu", w tym folderze Każdy ETAP będzie oddzielnym plikiem w którym będą się znajdować szczegółowe i głęboko zagnieżdżone podzadania tego ETAPu. Przekaż agentom jak aktualizować i odczytywać tą strukturę planu.

Korzystaj z następujących oznaczeń statusu planu:
    ❌ Zadanie nie rozpoczęte
    🛠️ Zadanie rozpoczęte, aktualnie trwają nad nim prace
    ✅ Zadanie ukończone - DOPIERO TERAZ dodaj └──📁 PLIK: ścieżka/do/utworzonego/pliku (z wcięciem wyrównanym pod ✅)
    ⚠️ Zadanie z blokerem, odłożone na później, należy do niego wrócić po rozwiązaniu blokera, należy opisać blokera w zadaniu, ze wskazaniem podpunktu w planie który blokuje wykonania tego zadania.

### NARZĘDZIA AI

- **Lokalizacja**: `D:\OneDrive - MPP TRADE\Skrypty\Narzędzia_AI\`
- **Struktura**: `nazwa_narzędzia/` + `nazwa_narzędzia.py` + `README.md`
- **Nazwy**: `explore_*`, `create_*`, `analyze_*`, `migrate_*`, `backup_*`, `test_*`
- **Bezpieczeństwo**: Try-catch + timeout + hash passwords + walidacja
- **Po reorganizacji**: Test imports + requirements.txt + dokumentacja + test uruchomienia

### POWERSHELL - POLSKIE ZNAKI

- **BŁĄD**: PowerShell błędy z ąęćńóśźż → "Missing argument", "Unexpected token"  
- **ROZWIĄZANIE**: NIGDY polskie znaki → ASCII (ą→a, ę→e, ć→c, ń→n, ó→o, ś→s, ź/ż→z)
- **Kodowanie**: UTF-8 bez BOM dla .ps1, testuj składnię

### PLIKI & WERSJE

- **NIE TWÓRZ** wielu wersji tego samego pliku! (build_v1.ps1, build_v2.ps1, etc.)
- **Jeden plik** na funkcjonalność

### KODOWANIE UTF-8

- **PowerShell z polskimi**: UTF-8 z BOM, `$PSDefaultParameterValues['Out-File:Encoding'] = 'utf8BOM'`
- **Python**: `# -*- coding: utf-8 -*-`

### KRYTYCZNE ZASADY RAPORTOWANIA AGENTÓW

- **DOKŁADNOŚĆ POSTĘPU**: Agents MUSZĄ raportować dokładnie które podpunkty ukończone vs nieukończone
- **ZAKAZ**: NIE MOŻESZ raportować ukończenia całego etapu jeśli jakiekolwiek sekcje mają status ❌
- **STATUS ✅**: TYLKO dla faktycznie zrealizowanych zadań z działającym kodem/testami
- **PLIKI**: Dodawanie `└──📁 PLIK: ścieżka/do/pliku` TYLKO po rzeczywistym ukończeniu (z wcięciem wyrównanym pod ✅)
- **PLAN**: W planie aktualizuj ❌→🛠️ gdy rozpoczynasz, 🛠️→✅ gdy faktycznie ukończysz

**PRZYKŁAD PRAWIDŁOWEGO RAPORTOWANIA:**

```
**Status ETAPU:** 🛠️ W TRAKCIE - ukończone 2.1.1, 2.1.2 z 7 głównych sekcji (29% complete)
```

**PRZYKŁAD BŁĘDNEGO RAPORTOWANIA (NIEDOZWOLONE):**

```
**Status ETAP_02**: ✅ **UKOŃCZONY** ← 🚫 BŁĄD! Większość sekcji ma status ❌
```

### INNE

- **Autor**: Kamil Wiliński (nie Claude AI)
- **Środowisko**: Windows + PowerShell 7 (nie WSL/Linux)




