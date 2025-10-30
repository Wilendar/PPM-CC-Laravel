# 15. Profil Użytkownika

[◀ Powrót do spisu treści](README.md)

---

## 👤 Profil - Przegląd

**Uprawnienia:** Wszyscy (własny profil)

### 15.1 Edycja Profilu
**Route:** `/profile/edit`

**Formularz:**
- Imię i nazwisko
- Email (unique)
- Telefon
- Zmiana hasła
- Avatar (upload)

### 15.2 Aktywne Sesje
**Route:** `/profile/sessions`

**Tabela:**
| Urządzenie | IP | Lokalizacja | Ostatnia Aktywność | Akcje |
|------------|----|-----------|--------------------|-------|
| Chrome (Windows) | 192.168.1.100 | Warszawa, PL | 5 min temu | ● Current |
| Safari (iPhone) | 10.0.0.5 | Kraków, PL | 2h temu | [Wyloguj] |

### 15.3 Historia Aktywności
**Route:** `/profile/activity`

**Timeline:**
- Login/Logout events
- Zmiany produktów
- Akcje admin (jeśli admin)
- Failed login attempts

### 15.4 Ustawienia Powiadomień
**Route:** `/profile/notifications`

**Email Notifications:**
- ☑️ Niski stan magazynowy
- ☑️ Błędy synchronizacji
- ☐ Nowe reklamacje
- ☐ Nowe zamówienia

**Push Notifications (future):**
- ☐ Real-time alerts

---

## 📖 Nawigacja
- **Poprzedni:** [14. System](14_SYSTEM_ADMIN.md)
- **Następny:** [16. Pomoc](16_POMOC.md)
- **Powrót:** [README](README.md)
