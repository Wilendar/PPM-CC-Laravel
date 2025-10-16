<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Test CSS Loading</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Application CSS -->
    <link href="/public/css/app.css" rel="stylesheet">
    <link href="/public/css/admin/layout.css" rel="stylesheet">
    <link href="/public/css/admin/components.css" rel="stylesheet">
    <link href="/public/css/products/category-form.css" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white p-8">
    <h1 class="text-4xl font-bold mb-4">Test CSS Loading</h1>

    <div class="grid grid-cols-2 gap-4">
        <div class="bg-gray-800 p-4 rounded">
            <h2 class="text-xl font-semibold mb-2">Tailwind Classes Test</h2>
            <p class="text-green-400">If this text is green, Tailwind works!</p>
            <button class="bg-blue-500 hover:bg-blue-600 px-4 py-2 rounded mt-2">
                Test Button
            </button>
        </div>

        <div class="bg-gray-800 p-4 rounded">
            <h2 class="text-xl font-semibold mb-2">Custom CSS Test</h2>
            <div class="enterprise-card">
                <p>Testing enterprise-card class from category-form.css</p>
            </div>
            <div class="admin-header mt-2">
                <p>Testing admin-header class from layout.css</p>
            </div>
        </div>
    </div>

    <div class="mt-8 bg-yellow-900 border-2 border-yellow-600 p-4 rounded">
        <h3 class="text-yellow-300 font-bold">CSS Files Status:</h3>
        <ul id="css-status" class="mt-2 text-sm">
            <li>Checking CSS files...</li>
        </ul>
    </div>

    <script>
        // Check CSS files
        const cssFiles = [
            '/public/css/app.css',
            '/public/css/admin/layout.css',
            '/public/css/admin/components.css',
            '/public/css/products/category-form.css'
        ];

        const statusList = document.getElementById('css-status');
        statusList.innerHTML = '';

        cssFiles.forEach(async (file) => {
            try {
                const response = await fetch(file);
                const li = document.createElement('li');
                li.textContent = `${file}: ${response.ok ? '✅ OK' : '❌ Failed'} (${response.status})`;
                li.className = response.ok ? 'text-green-400' : 'text-red-400';
                statusList.appendChild(li);
            } catch(e) {
                const li = document.createElement('li');
                li.textContent = `${file}: ❌ Error - ${e.message}`;
                li.className = 'text-red-400';
                statusList.appendChild(li);
            }
        });
    </script>
</body>
</html>