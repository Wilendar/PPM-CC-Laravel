<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Not Found</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-container {
            text-align: center;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            margin: 1rem;
        }
        .error-code {
            font-size: 6rem;
            font-weight: 300;
            color: #667eea;
            margin: 0;
            line-height: 1;
        }
        .error-message {
            font-size: 1.5rem;
            color: #4a5568;
            margin: 1rem 0;
            font-weight: 500;
        }
        .error-description {
            color: #718096;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        .back-button {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: background-color 0.2s ease;
        }
        .back-button:hover {
            background: #5a67d8;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">404</div>
        <div class="error-message">Strona nie została znaleziona</div>
        <div class="error-description">
            Przepraszamy, ale strona, której szukasz, nie istnieje lub została przeniesiona.
        </div>
        <a href="{{ url('/') }}" class="back-button">Wróć do strony głównej</a>
    </div>
</body>
</html>