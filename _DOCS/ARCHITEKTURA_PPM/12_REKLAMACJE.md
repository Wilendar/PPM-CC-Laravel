# 12. Reklamacje

[◀ Powrót do spisu treści](README.md)

---

## ⚠️ Reklamacje - Przegląd

**Uprawnienia:** Admin, Menadżer, Reklamacje

### 12.1 Lista Reklamacji
**Route:** `/admin/claims`

**Tabela:**
| Nr | Data | Klient | Produkt (SKU) | Typ | Status | Priorytet | Akcje |
|----|------|--------|---------------|-----|--------|-----------|-------|
| RMA-001 | 2025-10-18 | Jan K. | PROD-123 | Wadliwy | W trakcie | High | [⚙️] |

**Status:** Nowa / W trakcie / Zamknięta / Odrzucona
**Priorytet:** Low / Medium / High / Critical

### 12.2 Nowa Reklamacja
**Route:** `/admin/claims/create`

**Formularz:**
- Numer zamówienia (autocomplete)
- Klient
- Produkt (SKU)
- Typ reklamacji
- Opis problemu
- Załączniki (zdjęcia, PDF)

### 12.3 Archiwum
**Route:** `/admin/claims/archive`

**Export:** CSV / PDF Report

---

## 📖 Nawigacja
- **Poprzedni:** [11. Zamówienia](11_ZAMOWIENIA.md)
- **Następny:** [13. Raporty](13_RAPORTY_STATYSTYKI.md)
- **Powrót:** [README](README.md)
