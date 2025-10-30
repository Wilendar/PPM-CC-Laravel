---
description: Deploy projektu PPM-CC-Laravel na serwer produkcyjny
allowed-tools: Bash, Read
argument-hint: [opcjonalnie: sciezka do pliku]
---

## DEPLOYMENT PPM-CC-LARAVEL

### UWAGA PRZED DEPLOYMENT:
⚠️ To jest deployment na PRODUKCJE: https://ppm.mpptrade.pl
⚠️ Upewnij sie, ze kod jest przetestowany lokalnie!

### Krok 1: Przeczytaj dane hostingu
@_DOCS/dane_hostingu.md

### Krok 2: Parametry deploymentu
**Jezeli podano argument $1:**
- Deploy konkretnego pliku: `$1`

**Jezeli nie podano argumentu:**
- Zapytaj uzytkownika co chce deployowac
- Opcje: caly projekt / konkretny katalog / konkretny plik

### Krok 3: Wykonaj deployment
Uzyj narzedzi SSH do uploadu na serwer Hostido:
- Host: ppm.mpptrade.pl
- SSH Key: D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk
- Narzedzia: pscp, plink

### Krok 4: Post-deployment
Po upload wykonaj na serwerze:
```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

### Krok 5: Weryfikacja
Sprawdz dzialanie na produkcji:
```bash
curl -I https://ppm.mpptrade.pl
```

Lub otwórz w przegladarce i zweryfikuj funkcjonalnosc.

### Krok 6: Raport
Stworz wpis w `_REPORTS/` z informacja o deployment:
- Co zostalo wdrozone
- Kiedy
- Wynik weryfikacji