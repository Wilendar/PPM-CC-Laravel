# API INTEGRATION PATTERN - Prawdziwe Połączenia z Fallback

**Status**: ⚠️ ONGOING - Pattern do implementacji
**Priorytet**: KRYTYCZNY dla wszystkich zewnętrznych API
**Typ**: Architecture / Integration

## 🚨 OPIS PROBLEMU

Różnice między symulowanymi a prawdziwymi testami API powodują niespójności w statusach połączeń między komponentami (kreatory vs panele zarządzania).

### Objawy problematycznego kodu
- ❌ Tylko symulacja bez prób rzeczywistego API
- ❌ Różne wyniki w kreatorach vs panelach zarządzania
- ❌ Brak informacji o prawdziwych błędach API
- ❌ Niespójne statusy połączeń w bazie danych

### Przykład błędnego kodu
```php
// ❌ ŹŁLE - tylko symulacja, brak prawdziwego testu
$result = ['success' => true, 'message' => 'Symulacja'];
return $result;
```

## ✅ ROZWIĄZANIE - WZORZEC API Z FALLBACK

### Standardowy wzorzec implementacji
```php
try {
    // 1. ZAWSZE próbuj prawdziwego API jako pierwsze
    $apiService = new ExternalApiService();
    $result = $apiService->testConnection($config);

    if ($result['success']) {
        // Sukces - prawdziwe dane
        return $this->handleSuccessfulConnection($result);
    } else {
        throw new \Exception($result['message']);
    }

} catch (\Exception $apiException) {
    // 2. Fallback dla development/błędów API
    Log::warning('API connection failed, using fallback', [
        'api_endpoint' => $config['url'],
        'error' => $apiException->getMessage()
    ]);

    // Symulacja z komunikatem informacyjnym
    return $this->handleFallbackConnection($apiException);
}
```

## 🛡️ KRYTYCZNE ZASADY

1. **ZAWSZE** próbuj prawdziwego API jako pierwsze
2. **LOGUJ** błędy API dla debugowania (`Log::warning()`)
3. **OZNACZAJ** symulowane wyniki w komunikatach użytkownika
4. **SPÓJNOŚĆ** - ten sam wzorzec w kreatorach i panelach zarządzania
5. **BAZA DANYCH** - aktualizuj status na podstawie rzeczywistego wyniku

### Implementacja handleFallbackConnection()
```php
private function handleFallbackConnection(\Exception $exception): array
{
    return [
        'success' => false, // lub true z oznaczeniem symulacji
        'message' => 'Symulacja połączenia (API niedostępne)',
        'response_time' => round(mt_rand(80, 300) + mt_rand(0, 50)/10, 1),
        'details' => [
            'simulated' => true,
            'original_error' => $exception->getMessage(),
            'fallback_reason' => 'API connection failed',
            'timestamp' => now()->toISOString()
        ]
    ];
}
```

## 📋 IMPLEMENTACJA PATTERN

### PrestaShop API Test
```php
public function testPrestaShopConnection(array $config): array
{
    try {
        // Prawdziwe połączenie PrestaShop API
        $client = new GuzzleHttp\Client();
        $response = $client->get($config['url'] . '/api/', [
            'auth' => [$config['api_key'], ''],
            'timeout' => 10,
            'verify' => false
        ]);

        if ($response->getStatusCode() === 200) {
            return [
                'success' => true,
                'message' => 'Połączenie z PrestaShop pomyślne',
                'response_time' => $response->getHeaderLine('X-Response-Time'),
                'version' => $this->extractPrestaShopVersion($response),
                'details' => ['simulated' => false]
            ];
        }

        throw new \Exception('Unexpected status: ' . $response->getStatusCode());

    } catch (\Exception $e) {
        Log::warning('PrestaShop API connection failed', [
            'url' => $config['url'],
            'error' => $e->getMessage()
        ]);

        return [
            'success' => false,
            'message' => 'Błąd połączenia z PrestaShop (sprawdź URL i klucz API)',
            'details' => [
                'simulated' => true,
                'error' => $e->getMessage(),
                'suggestion' => 'Sprawdź URL sklepu i poprawność klucza API'
            ]
        ];
    }
}
```

### ERP Integration Test
```php
public function testERPConnection(string $type, array $config): array
{
    try {
        switch ($type) {
            case 'baselinker':
                return $this->testBaselinkerAPI($config);
            case 'subiekt':
                return $this->testSubiektConnection($config);
            case 'dynamics':
                return $this->testDynamicsConnection($config);
        }
    } catch (\Exception $e) {
        Log::warning("ERP {$type} connection failed", [
            'type' => $type,
            'error' => $e->getMessage(),
            'config' => array_keys($config) // nie loguj sensitive danych
        ]);

        return [
            'success' => false,
            'message' => "Błąd połączenia z systemem {$type}",
            'details' => [
                'simulated' => true,
                'erp_type' => $type,
                'error' => $e->getMessage()
            ]
        ];
    }
}
```

## 🔍 SPÓJNOŚĆ MIĘDZY KOMPONENTAMI

### Problem: Różne wyniki w kreatorach vs panelach
```php
// ❌ BŁĄD - kreator zawsze sukces, panel prawdziwy test
// AddShop wizard
return ['success' => true]; // fake

// ShopManager panel
return $this->realApiTest(); // real
```

### Rozwiązanie: Ujednolicona metoda
```php
// ✅ DOBRZE - jedna metoda dla obu
trait ApiTestingTrait
{
    public function performApiTest(array $config): array
    {
        // Ten sam kod w kreatorze i panelu zarządzania
        return $this->testConnectionWithFallback($config);
    }
}

// Używane w obu komponentach
class AddShop extends Component
{
    use ApiTestingTrait;

    public function testConnection()
    {
        $result = $this->performApiTest($this->getConfig());
        // Jednorodne wyniki
    }
}

class ShopManager extends Component
{
    use ApiTestingTrait;

    public function testShopConnection($shopId)
    {
        $config = $this->getShopConfig($shopId);
        $result = $this->performApiTest($config);
        // Identyczne wyniki jak w kreatorze
    }
}
```

## 📋 CHECKLIST IMPLEMENTACJI

- [ ] Czy próbujesz prawdziwego API jako pierwsze?
- [ ] Czy loguje się błędy API (`Log::warning()`)?
- [ ] Czy fallback jest oznaczony jako symulacja?
- [ ] Czy kreator i panel używają tej samej metody?
- [ ] Czy baza danych jest aktualizowana prawidłowym statusem?
- [ ] Czy error messages są user-friendly?
- [ ] Czy sensitive data nie są logowane?

## 💡 PRZYKŁADY IMPLEMENTACJI W PROJEKCIE

### Już zaimplementowane
- `ShopManager::testConnection()` - prawdziwy test z fallback
- Synchronizacja po usunięciu sklepu

### Wymagające aktualizacji
- `AddShop::testConnection()` - ujednolicić z ShopManager
- Wszystkie metody synchronizacji ERP/PrestaShop
- Import/Export API calls
- Backup verification calls

## 🎯 KORZYŚCI PATTERN

1. **Spójność** - identyczne zachowanie wszędzie
2. **Debugowanie** - rzeczywiste błędy w logach
3. **Monitoring** - tracking prawdziwych problemów API
4. **User Experience** - jasne komunikaty o statusie
5. **Development** - fallback gdy API niedostępne

## 🔗 POWIĄZANE SERVICES

**Do aktualizacji:**
- `app/Services/PrestaShopService.php`
- `app/Services/BaselinkerService.php`
- `app/Services/ERPService.php`
- `app/Services/BackupVerificationService.php`

**Komponenty wymagające unifikacji:**
- `AddShop.php` vs `ShopManager.php`
- `ERPManager.php` connection tests
- `ImportManager.php` API validations