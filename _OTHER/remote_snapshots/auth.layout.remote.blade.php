<!DOCTYPE html>
<html lang="pl" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'PPM - Prestashop Product Manager')</title>
    
    <meta name="description" content="System zarządzania produktami PPM - Logowanie do aplikacji enterprise dla MPP TRADE">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap&subset=latin-ext" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'Noto Sans', 'Liberation Sans', 'DejaVu Sans', 'sans-serif'],
                    },
                    colors: {
                        'ppm': {
                            'primary': '#2563eb',
                            'primary-dark': '#1d4ed8',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="text-center">
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                PPM - Product Manager
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                System zarz?dzania produktami
            </p>
        </div>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
            @yield('content')
        </div>
    </div>
</body>
</html>
