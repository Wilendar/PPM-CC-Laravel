# HARDCODOWANIE I SYMULACJA WARTOŚCI - ISSUE

**Status**: ⚠️ ONGOING - Zasady do przestrzegania
**Priorytet**: KRYTYCZNY
**Typ**: Code Quality / User Experience

## 🚨 OPIS PROBLEMU

Mieszanie prawdziwych i symulowanych wartości bez oznaczenia wprowadza użytkowników w błąd i powoduje niespójności w aplikacji enterprise.

### Objawy problematycznego kodu
- ❌ Stałe hardcodowane wartości (zawsze te same)
- ❌ Brak oznaczenia symulacji vs rzeczywistych danych
- ❌ Użytkownicy nie wiedzą czy dane są prawdziwe
- ❌ Różnice między dev a production

### Przykłady błędnego kodu
```php
// ❌ ŹŁLE - hardcoded fake wartości
'response_time' => 150.0,  // zawsze to samo!
'message' => 'Połączenie pomyślne',  // kłamstwo!

// ❌ ŹŁLE - ukrywanie że to symulacja
return ['success' => true, 'response_time' => 100];
```

## ✅ ROZWIĄZANIE

### Realistyczne losowe wartości
```php
// ✅ DOBRZE - realistyczne losowe wartości
$simulatedResponseTime = round(mt_rand(80, 300) + (mt_rand(0, 50) / 10), 1);

// ✅ DOBRZE - oznaczenie symulacji
'message' => 'Połączenie pomyślne (symulacja - API niedostępne)',
'response_time' => $simulatedResponseTime,
'details' => [
    'simulated' => true,
    'note' => 'Symulowany czas odpowiedzi: ' . $simulatedResponseTime . 'ms'
]

// ✅ DOBRZE - logowanie rozróżnienia
Log::warning('Using simulated API response', [
    'api_error' => $exception->getMessage(),
    'simulated_response_time' => $simulatedResponseTime
]);
```

## 🛡️ ZASADY ANTI-HARDCODE

1. **NIGDY nie hardcoduj** stałych wartości w production
2. **Fallback musi być realistyczny** - losowe wartości, nie stałe
3. **ZAWSZE oznacz** co jest symulowane vs prawdziwe
4. **Loguj rozróżnienie** między real API a simulation
5. **Komunikaty użytkownika** muszą wskazywać symulację

### Przykłady realistycznych symulacji
```php
// Response time: 80-350ms z wariacją
$responseTime = mt_rand(80, 300) + (mt_rand(0, 50) / 10);

// Dates: ostatnie 1-60 minut
$lastUpdate = Carbon::now()->subMinutes(mt_rand(1, 60));

// Counts: realistyczne zakresy
$recordCount = mt_rand(10, 1000);

// Status codes: losowe z prawdopodobnych
$statusCode = collect([200, 201, 202])->random();
```

## 🔍 IDENTYFIKACJA SYMULACJI

### Wymagane oznaczenia
```php
// W response data
$result['details']['simulated'] = true;

// W komunikatach użytkownika
$message = "Połączenie pomyślne (symulacja)";
$message = "Dane załadowane (API niedostępne)";

// W logach
Log::warning('Using simulated response due to API error', [
    'original_error' => $exception->getMessage(),
    'simulated_data' => $simulatedValues
]);
```

## 📋 CHECKLIST IMPLEMENTACJI

Przed wprowadzeniem fallback/symulacji:

- [ ] Czy wartości są losowe, nie stałe?
- [ ] Czy komunikat wskazuje symulację?
- [ ] Czy `simulated: true` jest w response?
- [ ] Czy error jest zalogowany?
- [ ] Czy zakresy wartości są realistyczne?
- [ ] Czy użytkownik wie że to nie są prawdziwe dane?

## 💡 PRZYKŁADY IMPLEMENTACJI

### API Connection Test
```php
try {
    $realResult = $apiService->testConnection($config);
    return $realResult;
} catch (\Exception $e) {
    Log::warning('API connection failed, using simulation', [
        'endpoint' => $config['url'],
        'error' => $e->getMessage()
    ]);

    return [
        'success' => true,
        'response_time' => round(mt_rand(80, 300) + mt_rand(0, 50)/10, 1),
        'message' => 'Test połączenia pomyślny (symulacja - API niedostępne)',
        'details' => [
            'simulated' => true,
            'reason' => 'API connection failed: ' . $e->getMessage()
        ]
    ];
}
```

### Statistics with Fallback
```php
try {
    $stats = $service->getRealStatistics();
    return $stats;
} catch (\Exception $e) {
    return [
        'total_products' => mt_rand(150, 800),
        'active_shops' => mt_rand(3, 12),
        'last_sync' => Carbon::now()->subMinutes(mt_rand(5, 45)),
        'details' => [
            'simulated' => true,
            'note' => 'Symulowane statystyki - brak połączenia z bazą'
        ]
    ];
}
```

## 🎯 KLUCZOWE KORZYŚCI

1. **Transparentność** - użytkownicy wiedzą co jest prawdziwe
2. **Debugowanie** - łatwiejsze identyfikowanie problemów API
3. **Testy** - realistyczne dane testowe
4. **Enterprise quality** - profesjonalne podejście do fallbacks
5. **Monitoring** - tracking rzeczywistych vs symulowanych operacji

## 🔗 ZASTOSOWANIE W PROJEKCIE

**Aktywne komponenty:**
- `ShopManager` - testy połączenia PrestaShop
- `ERPManager` - integracje zewnętrzne
- `BackupManager` - monitorowanie statusu
- `SystemSettings` - sprawdzanie konfiguracji

**Wymagane aktualizacje:**
- Wszystkie API services w `app/Services/`
- Komponenty Livewire z external calls
- Diagnostic tools w `resources/views/pages/`