<!DOCTYPE html>
<html lang="pl" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 Diagnostyka /create</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .wire-snapshot {
            background: #1f2937;
            color: #10b981;
            font-family: monospace;
            font-size: 11px;
            word-break: break-all;
            max-height: 200px;
            overflow-y: auto;
        }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen">

<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="bg-gray-800 rounded-lg p-6">
        <h1 class="text-2xl font-bold text-green-400 mb-6">🔍 Diagnostyka ProductForm /create</h1>

        <!-- Test 1: Check if ProductForm class exists -->
        <div class="mb-6 p-4 border border-gray-700 rounded-lg">
            <h3 class="text-lg font-semibold text-blue-400 mb-3">🧪 Test 1: Klasa ProductForm</h3>
            @php
                $classExists = false;
                $classError = '';
                try {
                    $classExists = class_exists('App\\Http\\Livewire\\Products\\Management\\ProductForm');
                    if ($classExists) {
                        $reflection = new ReflectionClass('App\\Http\\Livewire\\Products\\Management\\ProductForm');
                        $classInfo = "Plik: " . $reflection->getFileName();
                    }
                } catch (Exception $e) {
                    $classError = $e->getMessage();
                }
            @endphp

            @if($classExists)
                <div class="text-green-400">✅ Klasa ProductForm istnieje</div>
                <div class="text-gray-400 text-sm mt-1">{{ $classInfo ?? '' }}</div>
            @else
                <div class="text-red-400">❌ Klasa ProductForm nie istnieje</div>
                @if($classError)
                    <div class="text-red-300 text-sm mt-1">Błąd: {{ $classError }}</div>
                @endif
            @endif
        </div>

        <!-- Test 2: Check Livewire component registration -->
        <div class="mb-6 p-4 border border-gray-700 rounded-lg">
            <h3 class="text-lg font-semibold text-blue-400 mb-3">🧪 Test 2: Rejestracja Livewire</h3>
            @php
                $livewireRegistered = false;
                $livewireError = '';
                try {
                    // Check if component is registered in Livewire
                    $componentManager = app('livewire');
                    $livewireRegistered = method_exists($componentManager, 'getClass') && $componentManager->getClass('products.management.product-form');
                } catch (Exception $e) {
                    $livewireError = $e->getMessage();
                }
            @endphp

            @if($livewireRegistered)
                <div class="text-green-400">✅ Komponent Livewire jest zarejestrowany</div>
            @else
                <div class="text-yellow-400">⚠️ Sprawdzenie rejestracji Livewire</div>
                @if($livewireError)
                    <div class="text-red-300 text-sm mt-1">Błąd: {{ $livewireError }}</div>
                @endif
            @endif
        </div>

        <!-- Test 3: Try to instantiate component -->
        <div class="mb-6 p-4 border border-gray-700 rounded-lg">
            <h3 class="text-lg font-semibold text-blue-400 mb-3">🧪 Test 3: Instancjacja komponentu</h3>
            @php
                $componentInstance = null;
                $componentError = '';
                try {
                    if ($classExists) {
                        $componentInstance = new App\Http\Livewire\Products\Management\ProductForm();
                        $componentInstance->mount();
                    }
                } catch (Exception $e) {
                    $componentError = $e->getMessage();
                }
            @endphp

            @if($componentInstance)
                <div class="text-green-400">✅ Komponent można zainstancjować</div>
                <div class="text-gray-400 text-sm mt-1">
                    isEditMode: {{ $componentInstance->isEditMode ? 'true' : 'false' }}<br>
                    activeTab: {{ $componentInstance->activeTab }}<br>
                    product_type_id: {{ $componentInstance->product_type_id }}
                </div>
            @else
                <div class="text-red-400">❌ Błąd podczas instancjacji komponentu</div>
                @if($componentError)
                    <div class="text-red-300 text-sm mt-1">Błąd: {{ $componentError }}</div>
                @endif
            @endif
        </div>

        <!-- Test 4: Try to render Livewire component -->
        <div class="mb-6 p-4 border border-gray-700 rounded-lg">
            <h3 class="text-lg font-semibold text-blue-400 mb-3">🧪 Test 4: Renderowanie Livewire</h3>

            @php
                $livewireOutput = '';
                $livewireError = '';
                $hasWireSnapshot = false;
                $wireSnapshotContent = '';

                try {
                    // Try to render embed-product-form
                    if (view()->exists('pages.embed-product-form')) {
                        ob_start();
                        echo view('pages.embed-product-form')->render();
                        $livewireOutput = ob_get_clean();

                        // Check for wire:snapshot
                        if (str_contains($livewireOutput, 'wire:snapshot')) {
                            $hasWireSnapshot = true;
                            if (preg_match('/wire:snapshot="([^"]*)"/', $livewireOutput, $matches)) {
                                $wireSnapshotContent = htmlspecialchars(substr($matches[1], 0, 300)) . (strlen($matches[1]) > 300 ? '...' : '');
                            }
                        }
                    } else {
                        $livewireError = 'View pages.embed-product-form not found';
                    }
                } catch (Exception $e) {
                    if (ob_get_level()) ob_end_clean();
                    $livewireError = $e->getMessage();
                }
            @endphp

            @if($livewireError)
                <div class="text-red-400">❌ Błąd renderowania: {{ $livewireError }}</div>
            @else
                <div class="text-green-400 mb-3">✅ Komponent renderuje się</div>

                <!-- Check for wire:snapshot -->
                @if($hasWireSnapshot)
                    <div class="text-red-400 mb-2">🚨 ZNALEZIONO wire:snapshot - formularz pokazuje surowe dane!</div>

                    @if($wireSnapshotContent)
                        <div class="wire-snapshot p-2 rounded mb-2">
                            <strong>Fragment wire:snapshot:</strong><br>
                            {{ $wireSnapshotContent }}
                        </div>
                    @endif

                    <div class="text-orange-300 text-sm">
                        ⚠️ To oznacza, że Livewire nie może poprawnie wyrenderować komponentu!
                    </div>
                @else
                    <div class="text-green-400">✅ Brak wire:snapshot - formularz renderuje się poprawnie!</div>
                @endif

                <!-- Show output size and summary -->
                <div class="text-gray-400 text-sm mt-2">
                    Rozmiar output: {{ strlen($livewireOutput) }} znaków
                    @if($hasWireSnapshot)
                        <span class="text-red-400">(zawiera wire:snapshot)</span>
                    @endif
                </div>
            @endif
        </div>

        <!-- Test 5: Database connections -->
        <div class="mb-6 p-4 border border-gray-700 rounded-lg">
            <h3 class="text-lg font-semibold text-blue-400 mb-3">🧪 Test 5: Połączenia z bazą danych</h3>

            @php
                $dbTests = [
                    'categories' => ['model' => 'App\\Models\\Category', 'count' => 0],
                    'product_types' => ['model' => 'App\\Models\\ProductType', 'count' => 0],
                    'shops' => ['model' => 'App\\Models\\PrestaShopShop', 'count' => 0],
                ];

                foreach ($dbTests as $key => &$test) {
                    try {
                        $test['count'] = $test['model']::count();
                        $test['status'] = 'success';
                    } catch (Exception $e) {
                        $test['error'] = $e->getMessage();
                        $test['status'] = 'error';
                    }
                }
            @endphp

            @foreach($dbTests as $name => $test)
                <div class="mb-2">
                    @if($test['status'] === 'success')
                        <span class="text-green-400">✅ {{ ucfirst($name) }}: {{ $test['count'] }} rekordów</span>
                    @else
                        <span class="text-red-400">❌ {{ ucfirst($name) }}: {{ $test['error'] ?? 'Błąd' }}</span>
                    @endif
                </div>
            @endforeach
        </div>

        <!-- Navigation -->
        <div class="flex space-x-4">
            <a href="/admin/products/create"
               class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                🎯 Test głównego /create
            </a>
            <a href="/admin/products/create-test"
               class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                📝 Test HTML form
            </a>
            <a href="/admin/products"
               class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
                ← Lista produktów
            </a>
        </div>
    </div>
</div>

</body>
</html>