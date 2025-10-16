<!DOCTYPE html>
<html>
<head>
    <title>Debug CategoryForm</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    @livewireStyles
</head>
<body class="p-4">
    <h1>Debug CategoryForm Test</h1>
    <div class="alert alert-info">
        Testowanie komponentu CategoryForm
    </div>

    @php
        $componentExists = class_exists('App\Http\Livewire\Products\Categories\CategoryForm');
        $viewExists = view()->exists('livewire.products.categories.category-form');
    @endphp

    @if($componentExists && $viewExists)
        <div class="alert alert-success">
            <strong>✅ Ładowanie komponentu CategoryForm...</strong>
        </div>
        @livewire('products.categories.category-form')
    @else
        <div class="alert alert-danger">
            <strong>❌ BŁĄD:</strong> Nie można załadować komponentu CategoryForm
        </div>

        <div class="alert alert-info">
            <h4>Diagnostyka:</h4>
            <ul>
                <li><strong>Component class exists:</strong> {{ $componentExists ? 'TAK' : 'NIE' }}</li>
                <li><strong>View exists:</strong> {{ $viewExists ? 'TAK' : 'NIE' }}</li>
                <li><strong>Livewire registered:</strong> {{ app()->bound('livewire') ? 'TAK' : 'NIE' }}</li>
            </ul>
        </div>
    @endif

    @livewireScripts
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>