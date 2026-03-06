# PPM - Git Workflow

## 1. Strategia branchy

```
main (produkcja) ← PR ← develop (daily) ← feature/* | fix/*
```

| Branch | Cel | Deploy |
|--------|-----|--------|
| `main` | Produkcja - stabilny kod | Auto-deploy via GitHub Actions |
| `develop` | Codzienna praca, integracja | Brak auto-deploy |
| `feature/*` | Nowe funkcje | Brak |
| `fix/*` | Poprawki bledow | Brak |

## 2. Release Flow (standardowy)

```
1. git checkout develop
2. git checkout -b feature/nazwa-funkcji
3. ... praca, commity ...
4. git push -u origin feature/nazwa-funkcji
5. Utworz Pull Request: feature/* -> develop
6. Code review + merge do develop
7. Testowanie na develop
8. Utworz Pull Request: develop -> main
9. Merge do main -> automatyczny deploy
```

## 3. Hotfix Flow (pilne poprawki)

```
1. git checkout main
2. git checkout -b fix/nazwa-poprawki
3. ... poprawka ...
4. git push -u origin fix/nazwa-poprawki
5. Utworz Pull Request: fix/* -> main
6. Merge do main -> automatyczny deploy
7. git checkout develop && git merge main  (backport)
```

## 4. Conventional Commits

Format: `<typ>(<zakres>): <opis>`

### Typy

| Typ | Kiedy uzywac | Przyklad |
|-----|-------------|---------|
| `feat` | Nowa funkcja | `feat(products): add bulk price update` |
| `fix` | Poprawka bledu | `fix(auth): resolve OAuth token refresh` |
| `chore` | Utrzymanie, config | `chore(deps): update Laravel to 12.1` |
| `docs` | Dokumentacja | `docs: update deployment guide` |
| `refactor` | Refaktoring bez zmian funkcji | `refactor(import): extract validation trait` |
| `style` | Formatowanie, CSS | `style(admin): fix sidebar alignment` |
| `test` | Testy | `test(products): add SKU validation tests` |
| `perf` | Optymalizacja | `perf(queries): add index to products.sku` |

### Zakresy (scope)

Najczesciej uzywane: `products`, `auth`, `admin`, `import`, `erp`, `dashboard`, `api`, `deploy`, `deps`.

### Przyklady

```
feat(products): add category tree picker component
fix(import): handle empty SKU rows in XLSX
chore(security): rotate API tokens
docs(release): add deployment workflow guide
refactor(dashboard): split monolithic widget into modules
```

## 5. Code Review

### Wymagania przed merge do main
- [ ] Wszystkie testy przechodzace (`php artisan test`)
- [ ] Build przechodzi (`npm run build`)
- [ ] Brak bledow w console (Chrome DevTools)
- [ ] PR review zatwierdzony (min. 1 osoba)
- [ ] Commit messages zgodne z Conventional Commits

### Pull Request - szablon tytulu
```
feat(products): add bulk price update for selected stores
fix(auth): resolve session timeout on OAuth refresh
```

## 6. Tagi i wersjonowanie

Semantic Versioning: `vMAJOR.MINOR.PATCH`

| Czesc | Kiedy inkrementowac |
|-------|-------------------|
| MAJOR | Breaking changes, duze refaktory |
| MINOR | Nowe funkcje (backward compatible) |
| PATCH | Poprawki bledow |

```bash
# Tworzenie taga
git tag -a v1.2.0 -m "Release v1.2.0: bulk price update, ERP sync"
git push origin v1.2.0
```

## 7. Czeste komendy

```bash
# Nowa funkcja
git checkout develop
git pull origin develop
git checkout -b feature/moja-funkcja

# Commit
git add app/Models/Product.php
git commit -m "feat(products): add price history tracking"

# Push i PR
git push -u origin feature/moja-funkcja
# -> GitHub UI: Create Pull Request

# Po merge PR - aktualizacja develop
git checkout develop
git pull origin develop

# Usun stary branch
git branch -d feature/moja-funkcja
```
