# PPM-CC-Laravel - Vite CSS Refactoring & Deployment Guide

## 🎯 Cel refactoringu

Kompletne przeniesienie stylów inline z plików Blade do zewnętrznych plików CSS z użyciem Vite w Laravel 12.x zgodnie z best practices.

## 📁 Nowa struktura CSS

```
resources/
├── css/
│   ├── app.css                     # Główny plik + zmienne CSS
│   ├── admin/
│   │   ├── layout.css             # Layout panelu admin
│   │   └── components.css         # Komponenty admin (przyciski, tabele, modalne)
│   └── products/
│       └── category-form.css      # Style formularza kategorii
└── js/
    └── app.js                     # Import CSS + Alpine.js
```

## ⚙️ Konfiguracja Vite

### `vite.config.js`
```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/admin/layout.css',
                'resources/css/admin/components.css',
                'resources/css/products/category-form.css',
                'resources/js/app.js',
            ],
            refresh: [
                'resources/views/**',
                'app/Http/Livewire/**',
                'resources/js/**',
            ],
        }),
    ],
    build: {
        manifest: true,
        outDir: 'public/build',
        rollupOptions: {
            output: {
                manualChunks: {
                    alpine: ['alpinejs'],
                    admin: [
                        'resources/css/admin/layout.css',
                        'resources/css/admin/components.css'
                    ],
                    products: [
                        'resources/css/products/category-form.css'
                    ]
                }
            }
        }
    },
    server: {
        hmr: {
            host: 'localhost',
        },
    },
});
```

### `package.json`
```json
{
    "name": "ppm-cc-laravel",
    "scripts": {
        "dev": "vite",
        "build": "vite build",
        "preview": "vite preview"
    },
    "devDependencies": {
        "@alpinejs/persist": "^3.13.0",
        "alpinejs": "^3.13.0",
        "axios": "^1.6.4",
        "laravel-vite-plugin": "^1.0.0",
        "vite": "^5.0.0"
    }
}
```

## 🏗️ Zmiany w Layout

### `resources/views/layouts/admin.blade.php`
```blade
<!-- PRZED (inline styles + CDN) -->
<script src="https://cdn.tailwindcss.com"></script>
@stack('styles')
<style>
    /* inline styles */
</style>

<!-- PO (Vite assets) -->
@vite(['resources/css/app.css', 'resources/css/admin/layout.css', 'resources/css/admin/components.css', 'resources/css/products/category-form.css', 'resources/js/app.js'])
```

### `resources/js/app.js`
```javascript
import './bootstrap';
import '../css/app.css';  // ✅ Import CSS
import Alpine from 'alpinejs';
import persist from '@alpinejs/persist'
// ... reszta kodu
```

## 🎨 Wyekstraktowane style

### 1. **Admin Layout** (`admin/layout.css`)
- Admin header & navigation
- Sidebar z animacjami
- Dashboard widgets
- Loading states
- Responsive design

### 2. **Admin Components** (`admin/components.css`)
- Enterprise cards & panels
- Dropdown menus z z-index fix
- Button system (primary/secondary/danger)
- Form components
- Data tables
- Modal dialogs
- Notifications
- Pagination

### 3. **Category Form** (`products/category-form.css`)
- Category form container
- Enterprise form system
- Tabs system
- Dark alerts
- Breadcrumb styles
- Category picker/tree
- Enterprise animations:
  - fadeInUp, shimmer, slideInLeft
  - Success pulse, error shake
  - Floating labels
  - Rich text editor
  - Multi-select dropdown

### 4. **Global Styles** (`app.css`)
- CSS Variables (colors, fonts, shadows)
- Tailwind imports
- Base styles
- Focus states
- Scrollbars
- Selection
- Print styles

## 🚀 Development Workflow

### 1. **Development Mode**
```powershell
# Start Vite dev server
.\_TOOLS\build_assets.ps1 -Dev

# Watch mode (auto-reload)
.\_TOOLS\build_assets.ps1 -Watch
```

### 2. **Production Build**
```powershell
# Build tylko
.\_TOOLS\build_assets.ps1

# Build + Deploy na Hostido
.\_TOOLS\build_assets.ps1 -Deploy
```

### 3. **Hot Module Replacement**
- Automatyczne odświeżanie na zmiany CSS/JS
- Zachowanie stanu Alpine.js
- Szybkie iteracje developmentu

## 📦 Deployment na Hostido

### Automatyczny deployment:
```powershell
.\_TOOLS\build_assets.ps1 -Deploy
```

### Kroki manualne:
1. **Build assets lokalnie:**
   ```bash
   npm run build
   ```

2. **Upload build directory:**
   ```powershell
   pscp -r -i $HostidoKey -P 64321 "public/build" host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/public/
   ```

3. **Clear Laravel cache:**
   ```powershell
   plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear"
   ```

## ✅ Co zostało usunięte

### ❌ Usunięte elementy:
- `@push('styles')` w plikach Blade
- Wszystkie `<style>` bloki inline
- `@stack('styles')` w layoutach
- CDN Tailwind CSS (zastąpione przez Vite)
- Duplikaty stylów między plikami

### ✅ Zachowane funkcjonalności:
- Wszystkie animacje enterprise
- Dark theme support
- Responsive design
- Alpine.js integracja
- Z-index fixes dla dropdown
- Loading states
- Form validation styles

## 🔧 Troubleshooting

### Problem: Assets nie ładują się
**Rozwiązanie:**
```bash
php artisan view:clear
php artisan cache:clear
```

### Problem: CSS nie aktualizuje się
**Rozwiązanie:**
1. Sprawdź czy Vite dev server jest uruchomiony
2. Sprawdź czy plik jest includowany w `vite.config.js`
3. Hard refresh (Ctrl+F5)

### Problem: Styles nie działają w produkcji
**Rozwiązanie:**
```bash
npm run build
# Deploy build directory na serwer
```

### Problem: Alpine.js nie działa po refactoringu
**Sprawdź:**
- Import Alpine.js w `resources/js/app.js`
- `@vite(['resources/js/app.js'])` w layout
- Brak konfliktów JavaScript

## 📊 Performance Improvements

- **Bundle Splitting**: Oddzielne chunki dla admin/products
- **CSS Minification**: Automatyczna w production
- **Hot Module Replacement**: Szybszy development
- **Tree Shaking**: Usuwanie nieużywanego kodu
- **Asset Hashing**: Cache busting w production

## 🎉 Rezultat

- ✅ Usunięto ~400 linii inline CSS z `category-form.blade.php`
- ✅ Uporządkowano wszystkie style w logiczną strukturę
- ✅ Włączono hot reload dla szybkiego developmentu
- ✅ Zachowano wszystkie animacje i funkcjonalności
- ✅ Przygotowano automatyczny deployment
- ✅ Zgodność z Laravel 12.x best practices