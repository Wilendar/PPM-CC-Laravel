# 14. System (Admin Panel)

[◀ Powrót do spisu treści](README.md)

---

## ⚙️ System - Przegląd

**Uprawnienia:** Admin only (+ Menadżer sync-only dla ERP)

**Status:** ✅ COMPLETED (ETAP_04 - 5 faz A-E)

### 14.1 Ustawienia Systemu
**Route:** `/admin/system-settings`

**Tabs:**
1. Ogólne (nazwa, logo, język, timezone)
2. Email & Notyfikacje (SMTP, powiadomienia)
3. Ceny & Magazyny (domyślna marża, magazyny)
4. Integracje (PrestaShop, BaseLinker timeouts)
5. Zaawansowane (debug, maintenance, cache TTL)

### 14.2 Zarządzanie Użytkownikami
**Route:** `/admin/users`

**Tabela:**
| Nazwa | Email | Rola | Status | Data Utworzenia | Ostatnie Login | Akcje |
|-------|-------|------|--------|-----------------|----------------|-------|
| Jan K. | jan@example.com | Menadżer | Active | 2025-01-15 | 2025-10-20 | [⚙️] |

**7 Ról:** Admin, Menadżer, Redaktor, Magazynier, Handlowiec, Reklamacje, Użytkownik

### 14.3 Integracje ERP (v2.0: Dynamiczne)
**Route:** `/admin/integrations`

**Lista Integracji:**
- BaseLinker (plugin)
- Subiekt GT (plugin)
- Microsoft Dynamics (plugin)
- Custom ERP (możliwość dodawania)

**Endpoints:**
- `/admin/integrations` - lista
- `/admin/integrations/{slug}` - szczegóły
- `/admin/integrations/{slug}/configure` - konfiguracja

### 14.4 Backup & Restore
**Route:** `/admin/backup`

**Auto Backup:**
- Częstotliwość: Daily / Weekly / Monthly
- Miejsce zapisu: Google Drive / SharePoint / NAS Synology

**Manual Backup:**
- Full (database + files)
- Database only
- Files only

### 14.5 Konserwacja Bazy
**Route:** `/admin/maintenance`

**Tasks:**
- Optimize Tables
- Repair Tables
- Clear Old Logs (older than X days)
- Rebuild Indexes
- Vacuum Database

**Scheduled Tasks (cron):**
| Task | Schedule | Last Run | Next Run | Status |
|------|----------|----------|----------|--------|
| Daily Cleanup | Daily 2 AM | 2025-10-20 02:00 | 2025-10-21 02:00 | Success |

### 14.6 Logi Systemowe
**Route:** `/admin/logs`

**Filtry:**
- Level (Debug / Info / Warning / Error / Critical)
- Date range
- Module (Auth / Products / Sync / ERP)
- User

**Log Details Modal:**
- Full message
- Stack trace
- Context data (JSON)
- Related logs (timeline)

### 14.7 Monitoring
**Route:** `/admin/monitoring`

**Real-time Metrics:**
- CPU Usage (gauge)
- Memory Usage (gauge)
- Disk Space (progress)
- Network I/O (line chart)

**Application Metrics:**
- Active Users
- Request Rate (requests/min)
- Response Time (ms)
- Error Rate (%)

**Queue Metrics:**
- Jobs Pending / Processing / Failed
- Average Processing Time

### 14.8 API Management
**Route:** `/admin/api`

**API Keys:**
| Key Name | Key (masked) | Created | Last Used | Permissions | Status | Actions |
|----------|--------------|---------|-----------|-------------|--------|---------|
| Mobile App | sk_live_••••1234 | 2025-01-10 | 2025-10-20 | Read Products | Active | [⚙️] |

**Permissions:**
- Read Products
- Write Products
- Read Orders
- Write Orders
- Admin Access

**API Usage Statistics:**
- Requests per endpoint (bar chart)
- Requests over time (line chart)

---

## 📖 Nawigacja
- **Poprzedni:** [13. Raporty](13_RAPORTY_STATYSTYKI.md)
- **Następny:** [15. Profil](15_PROFIL_UZYTKOWNIKA.md)
- **Powrót:** [README](README.md)
