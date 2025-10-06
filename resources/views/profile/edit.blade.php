<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - PPM</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 dark:bg-gray-900">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 p-8 rounded-lg shadow-lg max-w-md w-full">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Edit Profile</h1>

            <p class="text-gray-600 dark:text-gray-400 mb-4">
                This is a placeholder profile edit page.
            </p>

            <div class="space-y-4">
                <a href="{{ route('profile.show') }}"
                   class="inline-block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                    Back to Profile
                </a>

                <a href="/admin"
                   class="inline-block px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition-colors">
                    Admin Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>