# 11. Zamówienia

[◀ Powrót do spisu treści](README.md)

---

## 📋 Zamówienia - Przegląd

**Uprawnienia:** Admin, Menadżer, Handlowiec

### 11.1 Lista Zamówień
**Route:** `/admin/orders`

**Tabela:**
| Nr | Data | Klient | Źródło | Status | Pozycje | Wartość | Akcje |
|----|------|--------|--------|--------|---------|---------|-------|
| ORD-001 | 2025-10-22 | Jan Kowalski | PrestaShop | Pending | 5 | 1,250 PLN | [⚙️] |

**Status:** Pending / Confirmed / Shipped / Delivered / Cancelled

### 11.2 Rezerwacje z Kontenera
**Route:** `/admin/orders/reservations`

**Kontener Selector:**
- Kontenery "W kontenerze" lub "W trakcie przyjęcia"
- Dostępność produktów per kontener

**Restrictions Handlowiec:**
- Brak widoczności cen zakupu
- Tylko Detaliczne/Dealer prices

### 11.3 Historia Zamówień
**Route:** `/admin/orders/history`

**Export:** CSV / PDF

---

## 📖 Nawigacja
- **Poprzedni:** [10. Dostawy](10_DOSTAWY_KONTENERY.md)
- **Następny:** [12. Reklamacje](12_REKLAMACJE.md)
- **Powrót:** [README](README.md)
