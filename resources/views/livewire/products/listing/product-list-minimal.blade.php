<div class="min-h-screen bg-gray-50 dark:bg-gray-900 p-6">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-2xl font-bold text-white mb-4">Lista Produktów</h1>

        <div class="bg-gray-800 rounded-lg shadow p-6">
            @if($products->count() > 0)
                <div class="space-y-4">
                    @foreach($products as $product)
                        <div class="border border-gray-700 rounded-lg p-4">
                            <h3 class="font-semibold text-white">{{ $product->name }}</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">SKU: {{ $product->sku }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Typ: {{ $product->product_type }}</p>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <p class="text-gray-500 dark:text-gray-400">Brak produktów do wyświetlenia</p>
                    <a href="{{ route('admin.products.create') }}"
                       class="inline-block mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Dodaj pierwszy produkt
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>