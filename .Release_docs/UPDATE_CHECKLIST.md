# PPM - Checklist Aktualizacji

## PRZED AKTUALIZACJA

- [ ] `git status` - brak niezacommitowanych zmian
- [ ] `git checkout develop && git pull` - aktualny branch
- [ ] `php artisan test` - wszystkie testy PASS
- [ ] `npm run build` - build przechodzi bez bledow
- [ ] Review zmian: `git diff develop..feature/xxx` lub PR na GitHub
- [ ] Sprawdz czy migracje nie sa destrukcyjne (drop table, remove column)

## DEPLOY

### Opcja A: Reczny deploy (deploy.ps1)

```powershell
# Przejdz na main i merge
git checkout main
git merge develop
git push origin main

# Deploy
cd .Release_docs/scripts
.\deploy.ps1 -Mode full
```

### Opcja B: Automatyczny deploy (GitHub Actions)

```bash
# Merge develop do main przez PR na GitHub
# -> GitHub Actions automatycznie uruchomi deploy
```

## PO AKTUALIZACJI

### Weryfikacja podstawowa
- [ ] HTTP 200: `curl -s -o /dev/null -w "%{http_code}" https://ppm.mpptrade.pl`
- [ ] Strona laduje sie poprawnie (brak bialej strony)
- [ ] CSS/JS zaladowane (brak brakujacych stylów)

### Weryfikacja funkcjonalna
- [ ] Dashboard: widgety laduja sie poprawnie
- [ ] Produkty: lista produktow wyswietla sie
- [ ] Admin Panel: sidebar widoczny, nawigacja dziala
- [ ] Formularze: zapis dziala (test edycji produktu)

### Weryfikacja techniczna
- [ ] Console errors: brak (Chrome DevTools -> Console)
- [ ] Network errors: brak 404/500 (Chrome DevTools -> Network)
- [ ] Queue: `php artisan queue:failed` - puste (brak failed jobs)
- [ ] Logi: `tail storage/logs/laravel.log` - brak krytycznych bledow

### Weryfikacja backapu
- [ ] Backup utworzony: sprawdz `~/backups/` na serwerze
- [ ] Rozmiar backupu prawidlowy (> 1MB)

## ROLLBACK (jesli problem)

### Opcja A: Przez skrypt
```powershell
.\deploy.ps1 -Mode rollback
```

### Opcja B: Recznie
```bash
# SSH na serwer
cd ~/backups
ls -lh  # znajdz ostatni backup

# Restore
gunzip -k backup_YYYYMMDD_HHMMSS.sql.gz
mysql -u host379076_ppm -p host379076_ppm < backup_YYYYMMDD_HHMMSS.sql

# Clear cache
cd ~/domains/ppm.mpptrade.pl/public_html
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

### Opcja C: Git revert
```bash
git revert HEAD
git push origin main
# -> GitHub Actions automatycznie zdeploy'uje revert
```

## NOTATKI

- Backup wykonywany jest AUTOMATYCZNIE przed kazdym `deploy.ps1 -Mode full`
- Aby pominac backup: `deploy.ps1 -Mode full -SkipBackup`
- Retencja backupow: ostatnie 5 na serwerze
- Logi deploy'u: wyswietlane na konsoli (kolorowy output)
