<!DOCTYPE html>
<html lang="pl" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test - Dodaj nowy produkt</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen">
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-gray-800 shadow rounded-lg p-6">
        <h2 class="text-xl font-semibold text-white mb-6">
            Test: Prosty formularz dodawania produktu
        </h2>

        <form method="POST" action="#" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="sku" class="block text-sm font-medium text-gray-300 mb-2">
                        SKU produktu <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="sku" name="sku" required
                           class="w-full rounded-lg border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-300 mb-2">
                        Nazwa produktu <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" required
                           class="w-full rounded-lg border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            <div class="flex justify-end space-x-3">
                <a href="{{ route('admin.products.index') }}"
                   class="px-4 py-2 border border-gray-600 text-sm font-medium rounded-lg text-gray-300 bg-gray-800 hover:bg-gray-700 transition-colors">
                    Anuluj
                </a>
                <button type="submit"
                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">
                    Zapisz produkt
                </button>
            </div>
        </form>

        <div class="mt-8 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg">
            <p class="text-sm text-blue-800 dark:text-blue-200">
                <strong>Test diagnostyczny:</strong> Ten formularz używa standardowego HTML bez Livewire.
                Jeśli ten formularz działa poprawnie, problem jest w konfiguracji Livewire.
            </p>
        </div>

        <div class="mt-4">
            <a href="/admin/products/create" class="text-blue-600 dark:text-blue-400 hover:underline">
                ← Powrót do pełnego formularza Livewire
            </a>
        </div>
    </div>
</div>
</body>
</html>