# PPM - Prestashop Product Manager
## 🎨 Color & Style Guide

### 📋 **OPIS DOKUMENTU**
Dokumentacja kolorów, stylów i komponentów wizualnych używanych w aplikacji PPM dla MPP TRADE.

---

## 🟠 **MARKA MPP TRADE - GŁÓWNE KOLORY**

### **Primary Colors (Orange)**
```css
/* Główny kolor brandowy MPP TRADE */
--mpp-primary: #e0ac7e;           /* Orange primary */  
--mpp-primary-dark: #d1975a;      /* Orange hover/active */
```

**Użycie:**
- 🔘 Przyciski główne (CTA)
- 🔘 Logo accenty 
- 🔘 Aktywne linki w nawigacji
- 🔘 Focus states w formularzach
- 🔘 Progress bars (główne)

### **Kolory pomocnicze**
```css
/* Blue - dla systemowych akcji */
--ppm-primary: #2563eb;           /* Blue primary */
--ppm-primary-dark: #1d4ed8;      /* Blue hover */

/* Green - dla sukcesów/pozytywnych akcji */
--ppm-secondary: #059669;         /* Green success */
--ppm-secondary-dark: #047857;    /* Green hover */

/* Red - dla błędów/ostrzeżeń */
--ppm-accent: #dc2626;           /* Red error */
--ppm-accent-dark: #b91c1c;      /* Red hover */
```

---

## 🌙 **DARK THEME - SCHEMAT KOLORÓW**

### **Tła (Backgrounds)**
```css
/* Główny gradient tła */
.bg-main-gradient {
    background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
}

/* Welcome page gradient */
.bg-welcome-gradient {
    background: linear-gradient(to bottom right, #1f2937, #111827, #000000);
}

/* Elementy interfejsu */
--bg-card: rgba(31, 41, 55, 0.8);           /* Karty/panele */
--bg-card-hover: rgba(55, 65, 81, 0.8);     /* Hover karty */
--bg-nav: rgba(31, 41, 55, 0.5);            /* Nawigacja */
--bg-input: #374151;                         /* Pola input */
```

### **Bordery i separatory**
```css
--border-primary: rgba(75, 85, 99, 0.2);    /* Główne bordery */
--border-secondary: rgba(107, 114, 128, 0.3); /* Drugorzędne */
--border-accent: #e0ac7e;                    /* Aktywne bordery */
```

### **Teksty**
```css
/* Hierarchy tekstów w dark mode */
--text-primary: #ffffff;        /* rgb(255, 255, 255) - główny tekst */
--text-secondary: #f3f4f6;      /* rgb(243, 244, 246) - gray-100 */
--text-muted: #d1d5db;          /* rgb(209, 213, 219) - gray-300 */
--text-subtle: #9ca3af;         /* rgb(156, 163, 175) - gray-400 */
--text-disabled: #6b7280;       /* rgb(107, 114, 128) - gray-500 */
```

---

## 🎯 **KOMPONENTY UI**

### **Przyciski**
```css
/* Przycisk główny (MPP orange) */
.btn-primary {
    background: linear-gradient(45deg, #e0ac7e, #d1975a);
    color: white;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: linear-gradient(45deg, #d1975a, #c08449);
    transform: scale(1.05);
    box-shadow: 0 10px 25px rgba(224, 172, 126, 0.3);
}

/* Przycisk drugorzędny */
.btn-secondary {
    background: rgba(31, 41, 55, 0.8);
    border: 1px solid rgba(75, 85, 99, 0.3);
    color: #f3f4f6;
}
```

### **Karty i panele**
```css
/* Karta główna */
.card {
    background: rgba(31, 41, 55, 0.8);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(75, 85, 99, 0.2);
    border-radius: 0.75rem; /* rounded-xl */
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

/* Karta z hover efektem */
.card-hover:hover {
    background: rgba(55, 65, 81, 0.8);
    transform: translateY(-2px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
}
```

### **Formularze**
```css
/* Input field */
.form-input {
    background: #374151; /* gray-700 */
    border: 1px solid #4b5563; /* gray-600 */
    color: white;
    border-radius: 0.375rem;
}

.form-input:focus {
    border-color: #e0ac7e;
    box-shadow: 0 0 0 3px rgba(224, 172, 126, 0.1);
    outline: none;
}

/* Label */
.form-label {
    color: #d1d5db; /* gray-300 */
    font-weight: 500;
    margin-bottom: 0.5rem;
}
```

---

## ✨ **EFEKTY WIZUALNE**

### **Shadows & Glows**
```css
/* Standard shadow */
.shadow-soft {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 
                0 4px 6px -4px rgba(0, 0, 0, 0.1);
}

/* Enhanced shadow */
.shadow-3xl {
    box-shadow: 0 35px 60px -12px rgba(0, 0, 0, 0.25);
}

/* Orange glow dla ważnych elementów */
.glow-orange {
    box-shadow: 0 0 20px rgba(224, 172, 126, 0.3);
}
```

### **Backdrop Effects**
```css
/* Glass morphism effect */
.glass-effect {
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    background: rgba(31, 41, 55, 0.8);
}

/* Grid pattern (background) */
.bg-grid-pattern {
    background-image: 
        linear-gradient(rgba(255, 255, 255, 0.05) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
    background-size: 50px 50px;
    opacity: 0.1;
}

/* Mobile grid adjustment */
@media (max-width: 640px) {
    .bg-grid-pattern { 
        background-size: 30px 30px; 
    }
}
```

### **Animacje**
```css
/* Loading animation */
.loading-fade {
    animation: fadeInOut 1.5s ease-in-out infinite;
}

@keyframes fadeInOut {
    0%, 100% { opacity: 0.4; }
    50% { opacity: 1; }
}

/* Slide up animation dla widgets */
.widget-enter {
    animation: slideInUp 0.3s ease-out;
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Pulse dla live data */
.pulse-dot {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(224, 172, 126, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(224, 172, 126, 0); }
    100% { box-shadow: 0 0 0 0 rgba(224, 172, 126, 0); }
}
```

---

## 📱 **TYPOGRAFIA**

### **Font Family**
```css
font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
```

**Gdzie włączyć:**
```html
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
```

### **Text Sizes & Weights**
```css
/* Heading hierarchy */
.text-display {
    font-size: 3rem;     /* 48px - hero headings */
    font-weight: 700;
    line-height: 1.1;
    letter-spacing: -0.025em;
}

.text-h1 {
    font-size: 2.25rem;  /* 36px - page headings */
    font-weight: 700;
    line-height: 1.2;
}

.text-h2 {
    font-size: 1.875rem; /* 30px - section headings */
    font-weight: 600;
    line-height: 1.3;
}

.text-h3 {
    font-size: 1.5rem;   /* 24px - subsection headings */
    font-weight: 600;
    line-height: 1.4;
}

/* Body text */
.text-body {
    font-size: 1rem;     /* 16px - standard body */
    font-weight: 400;
    line-height: 1.6;
}

.text-small {
    font-size: 0.875rem; /* 14px - small text */
    font-weight: 400;
    line-height: 1.5;
}

.text-caption {
    font-size: 0.75rem;  /* 12px - captions */
    font-weight: 500;
    line-height: 1.4;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
```

---

## 📐 **SPACING & LAYOUT**

### **Grid System**
```css
/* Container widths */
.container-sm { max-width: 576px; }
.container-md { max-width: 768px; }
.container-lg { max-width: 1024px; }
.container-xl { max-width: 1280px; }
.container-2xl { max-width: 1536px; }

/* Spacing scale (Tailwind-based) */
.space-1 { margin/padding: 0.25rem; }  /* 4px */
.space-2 { margin/padding: 0.5rem; }   /* 8px */
.space-3 { margin/padding: 0.75rem; }  /* 12px */
.space-4 { margin/padding: 1rem; }     /* 16px */
.space-6 { margin/padding: 1.5rem; }   /* 24px */
.space-8 { margin/padding: 2rem; }     /* 32px */
.space-12 { margin/padding: 3rem; }    /* 48px */
.space-16 { margin/padding: 4rem; }    /* 64px */
```

### **Border Radius**
```css
.rounded-sm { border-radius: 0.125rem; }   /* 2px */
.rounded { border-radius: 0.25rem; }       /* 4px */
.rounded-md { border-radius: 0.375rem; }   /* 6px */
.rounded-lg { border-radius: 0.5rem; }     /* 8px */
.rounded-xl { border-radius: 0.75rem; }    /* 12px */
.rounded-2xl { border-radius: 1rem; }      /* 16px */
.rounded-full { border-radius: 9999px; }   /* Circle */
```

---

## 🎛️ **STATUS COLORS & INDICATORS**

### **System Status**
```css
/* Success/Online */
--status-success: #059669;        /* Green-600 */
--status-success-bg: #dcfdf4;     /* Green-50 */
--status-success-border: #34d399; /* Green-400 */

/* Warning/Pending */
--status-warning: #d97706;        /* Amber-600 */
--status-warning-bg: #fef3c7;     /* Amber-50 */
--status-warning-border: #fbbf24; /* Amber-400 */

/* Error/Offline */
--status-error: #dc2626;          /* Red-600 */
--status-error-bg: #fef2f2;       /* Red-50 */
--status-error-border: #f87171;   /* Red-400 */

/* Info/Processing */
--status-info: #2563eb;           /* Blue-600 */
--status-info-bg: #eff6ff;        /* Blue-50 */
--status-info-border: #60a5fa;    /* Blue-400 */
```

### **Progress Bars**
```css
/* Primary progress (orange) */
.progress-primary {
    background: #374151; /* Base gray-700 */
}

.progress-primary .bar {
    background: linear-gradient(90deg, #e0ac7e, #d1975a);
}

/* System metrics progress */
.progress-cpu { background: #3b82f6; }      /* Blue */
.progress-memory { background: #059669; }   /* Green */
.progress-disk { background: #8b5cf6; }     /* Purple */
.progress-network { background: #f59e0b; }  /* Amber */
```

---

## 🌐 **RESPONSIVE BREAKPOINTS**

```css
/* Mobile First Approach */
/* xs: 0px - 475px (default) */
/* sm: 576px+ */
@media (min-width: 576px) { /* Small devices */ }

/* md: 768px+ */  
@media (min-width: 768px) { /* Medium devices */ }

/* lg: 1024px+ */
@media (min-width: 1024px) { /* Large devices */ }

/* xl: 1280px+ */
@media (min-width: 1280px) { /* Extra large devices */ }

/* 2xl: 1536px+ */
@media (min-width: 1536px) { /* 2X Extra large devices */ }
```

### **Mobile Adjustments**
```css
/* Logo size na mobile */
@media (max-width: 640px) {
    .logo-responsive {
        max-width: 300px;
        height: 120px;
    }
}

/* Grid pattern adjustment */
@media (max-width: 640px) {
    .bg-grid-pattern {
        background-size: 30px 30px;
    }
}
```

---

## 🔧 **KLASY POMOCNICZE**

### **Accessibility**
```css
/* Screen reader only */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* Focus visible dla keyboard navigation */
.focus-visible:focus-visible {
    outline: 2px solid #e0ac7e;
    outline-offset: 2px;
}
```

### **Utilities**
```css
/* Smooth scrolling */
.scroll-smooth {
    scroll-behavior: smooth;
}

/* Antialiasing */
.antialiased {
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

/* Overflow handling */
.overflow-hidden { overflow: hidden; }
.overflow-x-hidden { overflow-x: hidden; }
.overflow-y-auto { overflow-y: auto; }
```

---

## 📝 **USAGE EXAMPLES**

### **Standardowy widget admina**
```html
<div class="card glass-effect p-6 hover:card-hover">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-h3 text-primary">Widget Title</h3>
        <div class="pulse-dot w-3 h-3 bg-status-success rounded-full"></div>
    </div>
    <div class="space-y-4">
        <div class="progress-primary">
            <div class="bar" style="width: 75%"></div>
        </div>
        <p class="text-body text-muted">Widget description</p>
    </div>
</div>
```

### **Przycisk CTA**
```html
<button class="btn-primary px-8 py-4 rounded-full shadow-soft hover:glow-orange transition-all duration-300">
    <svg class="w-5 h-5 mr-3" fill="currentColor">...</svg>
    Zaloguj się do systemu
</button>
```

---

## 🎯 **BRAND CONSISTENCY CHECKLIST**

### ✅ **Obowiązkowe elementy:**
- [ ] **Logo MPP TRADE** w header (max-width: 900px na desktop)
- [ ] **Orange accent** (#e0ac7e) w głównych CTA
- [ ] **Dark theme** jako domyślny
- [ ] **Inter font** we wszystkich tekstach
- [ ] **Glass morphism effects** w kartach i panelach
- [ ] **Smooth transitions** (0.3s ease)
- [ ] **Consistent spacing** (Tailwind scale)

### ✅ **Responsywność:**
- [ ] Mobile-first design
- [ ] Touch-friendly buttons (min 44px)
- [ ] Readable typography na małych ekranach
- [ ] Proper grid adjustments

### ✅ **Accessibility:**
- [ ] Contrast ratio min 4.5:1
- [ ] Focus visible states
- [ ] Screen reader support
- [ ] Keyboard navigation

---

**Ostatnia aktualizacja:** 2025-01-11  
**Wersja:** 1.0  
**Autor:** Claude Code PPM Team  