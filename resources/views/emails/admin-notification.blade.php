<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $notification->title }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            padding: 20px;
            color: white;
            text-align: center;
        }
        .header.critical {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
        }
        .header.high {
            background: linear-gradient(135deg, #ea580c, #c2410c);
        }
        .header.normal {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
        }
        .header.low {
            background: linear-gradient(135deg, #16a34a, #15803d);
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .priority-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            margin-top: 8px;
            background: rgba(255, 255, 255, 0.2);
        }
        .content {
            padding: 30px;
        }
        .notification-meta {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            border-left: 4px solid #2563eb;
        }
        .meta-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .meta-item:last-child {
            margin-bottom: 0;
        }
        .meta-label {
            font-weight: 600;
            color: #4b5563;
        }
        .meta-value {
            color: #6b7280;
        }
        .message {
            font-size: 16px;
            line-height: 1.6;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #2563eb;
        }
        .actions {
            text-align: center;
            margin: 30px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 0 10px;
        }
        .btn:hover {
            background: #1d4ed8;
        }
        .btn.secondary {
            background: #6b7280;
        }
        .btn.secondary:hover {
            background: #4b5563;
        }
        .footer {
            padding: 20px;
            background: #f8f9fa;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }
        .footer a {
            color: #2563eb;
            text-decoration: none;
        }
        .type-icon {
            width: 20px;
            height: 20px;
            display: inline-block;
            vertical-align: middle;
            margin-right: 8px;
        }
        .security-warning {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        .security-warning h3 {
            color: #dc2626;
            margin: 0 0 10px 0;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header {{ strtolower($notification->priority) }}">
            <h1>
                @if($notification->type === 'security')
                    🛡️
                @elseif($notification->priority === 'critical')
                    🚨
                @elseif($notification->priority === 'high')
                    ⚠️
                @elseif($notification->type === 'integration')
                    🔌
                @else
                    🔔
                @endif
                {{ $notification->title }}
            </h1>
            <div class="priority-badge">
                {{ ucfirst($notification->priority) }} Priority
            </div>
        </div>

        <div class="content">
            @if($notification->type === 'security')
                <div class="security-warning">
                    <h3>🔒 Ostrzeżenie bezpieczeństwa</h3>
                    <p>To powiadomienie dotyczy bezpieczeństwa systemu. Prosimy o natychmiastowe przejrzenie i podjęcie odpowiednich działań.</p>
                </div>
            @endif

            <div class="notification-meta">
                <div class="meta-item">
                    <span class="meta-label">Typ:</span>
                    <span class="meta-value">
                        {{ ucfirst(str_replace('_', ' ', $notification->type)) }}
                    </span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Priorytet:</span>
                    <span class="meta-value">{{ ucfirst($notification->priority) }}</span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Utworzono:</span>
                    <span class="meta-value">{{ $notification->created_at->format('d.m.Y H:i:s') }}</span>
                </div>
                @if($notification->creator)
                    <div class="meta-item">
                        <span class="meta-label">Utworzone przez:</span>
                        <span class="meta-value">{{ $notification->creator->name }}</span>
                    </div>
                @endif
            </div>

            <div class="message">
                {!! nl2br(e($notification->message)) !!}
            </div>

            @if($notification->metadata)
                <div class="notification-meta">
                    <h4 style="margin-top: 0; color: #374151;">Dodatkowe informacje:</h4>
                    @foreach($notification->metadata as $key => $value)
                        <div class="meta-item">
                            <span class="meta-label">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                            <span class="meta-value">{{ is_array($value) ? json_encode($value) : $value }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="actions">
                <a href="{{ $notificationUrl }}" class="btn">
                    Zobacz szczegóły
                </a>
                <a href="{{ $dashboardUrl }}" class="btn secondary">
                    Panel administracyjny
                </a>
            </div>

            @if($notification->priority === 'critical')
                <div class="security-warning">
                    <h3>⚡ Wymagane natychmiastowe działanie</h3>
                    <p>To powiadomienie ma priorytet krytyczny i wymaga natychmiastowej uwagi. Proszę zalogować się do panelu administracyjnego i potwierdzić otrzymanie tego powiadomienia.</p>
                </div>
            @endif
        </div>

        <div class="footer">
            <p>
                To wiadomość została wysłana automatycznie z systemu PPM (Prestashop Product Manager).
                <br>
                <a href="{{ $dashboardUrl }}">Zarządzaj powiadomieniami</a> |
                <a href="mailto:admin@mpptrade.pl">Skontaktuj się z administratorem</a>
            </p>
            <p style="margin-top: 15px; font-size: 12px; color: #9ca3af;">
                MPP TRADE - System zarządzania produktami
                <br>
                {{ now()->format('d.m.Y H:i:s') }}
            </p>
        </div>
    </div>
</body>
</html>