<!DOCTYPE html>
<html>
<head>
    <title>Simple Test</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f0f0f0; }
        .test-box { background: white; padding: 20px; border-radius: 8px; margin: 10px 0; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>🧪 PPM-CC-Laravel Test Page</h1>

    <div class="test-box">
        <h2>✅ Podstawowe informacje</h2>
        <p><strong>Laravel Version:</strong> {{ app()->version() }}</p>
        <p><strong>PHP Version:</strong> {{ phpversion() }}</p>
        <p><strong>Environment:</strong> {{ app()->environment() }}</p>
        <p><strong>Current URL:</strong> {{ url()->current() }}</p>
        <p><strong>Current Time:</strong> {{ now() }}</p>
    </div>

    <div class="test-box">
        <h2>📝 Routes Test</h2>
        <p><strong>Categories Index:</strong>
            @php
                $indexRoute = rescue(function () {
                    return route('admin.products.categories.index');
                }, false);
            @endphp
            @if($indexRoute)
                <a href="{{ $indexRoute }}" class="success">✅ OK</a>
            @else
                <span class="error">❌ Route not found</span>
            @endif
        </p>
        <p><strong>Categories Create:</strong>
            @php
                $createRoute = rescue(function () {
                    return route('admin.products.categories.create');
                }, false);
            @endphp
            @if($createRoute)
                <a href="{{ $createRoute }}" class="success">✅ OK</a>
            @else
                <span class="error">❌ Route not found</span>
            @endif
        </p>
    </div>

    <div class="test-box">
        <h2>🗄️ Database Test</h2>
        <p><strong>Categories Table:</strong>
            @php
                $categoryCount = rescue(function () {
                    return \App\Models\Category::count();
                }, null);
            @endphp
            @if($categoryCount !== null)
                <span class="success">✅ OK - {{ $categoryCount }} kategorii</span>
            @else
                <span class="error">❌ Database connection error</span>
            @endif
        </p>
    </div>

    <div class="test-box">
        <h2>🔧 Livewire Component Test</h2>
        <p><strong>CategoryForm Component:</strong>
            @php
                $componentExists = class_exists('App\Http\Livewire\Products\Categories\CategoryForm');
            @endphp
            @if($componentExists)
                <span class="success">✅ Component class exists</span>
            @else
                <span class="error">❌ Component class not found</span>
            @endif
        </p>

        <p><strong>Component View:</strong>
            @php
                $viewExists = view()->exists('livewire.products.categories.category-form');
            @endphp
            @if($viewExists)
                <span class="success">✅ View exists</span>
            @else
                <span class="error">❌ View not found</span>
            @endif
        </p>
    </div>

    <div class="test-box">
        <h2>🚀 Next Steps</h2>
        <p>Jeśli wszystkie testy przechodzą, problem może być w samym komponencie CategoryForm.</p>
        <p><a href="/debug-category-form" style="background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;">Test CategoryForm Component</a></p>
    </div>
</body>
</html>