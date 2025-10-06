<!DOCTYPE html>
<html>
<head>
    <title>FORCE DEBUG - Laravel PPM-CC</title>
    <style>
        body {
            font-family: Arial; padding: 20px;
            background: #000; color: #0f0;
            font-size: 14px; line-height: 1.4;
        }
        .box {
            background: #111; padding: 15px;
            margin: 10px 0; border: 1px solid #0f0;
        }
        .red { color: #f00; }
        .yellow { color: #ff0; }
        .green { color: #0f0; }
    </style>
</head>
<body>
    <h1 class="green">🚨 FORCE DEBUG - PPM-CC-Laravel</h1>
    <div class="box">
        <h2 class="yellow">✅ TO JEST NASZA APLIKACJA LARAVEL!</h2>
        <p><strong>Framework:</strong> Laravel {{ app()->version() }}</p>
        <p><strong>Environment:</strong> {{ app()->environment() }}</p>
        <p><strong>URL:</strong> {{ url()->current() }}</p>
        <p><strong>Route:</strong> {{ Route::currentRouteName() ?? 'N/A' }}</p>
        <p><strong>DateTime:</strong> {{ now() }}</p>
        <p><strong>Server:</strong> {{ $_SERVER['SERVER_NAME'] ?? 'N/A' }}</p>
        <p><strong>PHP Version:</strong> {{ phpversion() }}</p>
    </div>

    <div class="box">
        <h2 class="yellow">🔧 CategoryForm Test</h2>
        @php
            $componentExists = class_exists('App\Http\Livewire\Products\Categories\CategoryForm');
            $viewExists = view()->exists('livewire.products.categories.category-form');
        @endphp
        <p><strong>Component exists:</strong> {{ $componentExists ? 'YES' : 'NO' }}</p>
        <p><strong>View exists:</strong> {{ $viewExists ? 'YES' : 'NO' }}</p>

        @if($componentExists && $viewExists)
            <p class="green"><strong>✅ ŁADOWANIE CATEGORYFORM...</strong></p>
            @livewire('products.categories.category-form')
        @else
            <p class="red"><strong>❌ CATEGORYFORM NIE DOSTĘPNY</strong></p>
        @endif
    </div>

    @livewireStyles
    @livewireScripts
</body>
</html>