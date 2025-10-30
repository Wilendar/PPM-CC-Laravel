<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-gray-800 shadow rounded-lg p-6">
        <h2 class="text-xl font-semibold text-white mb-6">
            🔍 Diagnostyka ProductForm
        </h2>

        <div class="space-y-4">
            <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg">
                <p class="text-green-800 dark:text-green-200">
                    ✅ <strong>ProductForm class</strong> - można zainstancjować bez błędów
                </p>
            </div>

            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg">
                <p class="text-blue-800 dark:text-blue-200">
                    🧪 <strong>Test Livewire komponentu:</strong>
                </p>
                <div class="mt-3">
                    <livewire:products.management.product-form />
                </div>
            </div>

            <div class="flex space-x-4">
                <a href="/admin/products/create-test"
                   class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-lg transition-colors">
                    Test HTML form
                </a>
                <a href="/admin/products"
                   class="px-4 py-2 border border-gray-600 text-sm font-medium rounded-lg text-gray-300 bg-gray-800 hover:bg-gray-700 transition-colors">
                    ← Powrót do listy produktów
                </a>
            </div>
        </div>
    </div>
</div>