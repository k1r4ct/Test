<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifica Errore</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            padding: 20px 30px;
            text-align: center;
        }
        .header.critical {
            background: linear-gradient(135deg, #dc3545, #c82333);
        }
        .header.error {
            background: linear-gradient(135deg, #fd7e14, #e55300);
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .header .icon {
            font-size: 40px;
            margin-bottom: 10px;
        }
        .content {
            padding: 30px;
        }
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #6c757d;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 0 4px 4px 0;
        }
        .info-box.critical {
            border-left-color: #dc3545;
            background-color: #fff5f5;
        }
        .info-box.error {
            border-left-color: #fd7e14;
            background-color: #fff8f0;
        }
        .info-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 14px;
            color: #333;
            word-break: break-word;
        }
        .message-box {
            background-color: #2d2d2d;
            color: #f8f8f2;
            padding: 20px;
            border-radius: 6px;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 13px;
            white-space: pre-wrap;
            word-break: break-word;
            margin: 20px 0;
        }
        .context-section {
            margin-top: 25px;
        }
        .context-section h3 {
            font-size: 14px;
            color: #495057;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        .context-table {
            width: 100%;
            border-collapse: collapse;
        }
        .context-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #e9ecef;
            font-size: 13px;
        }
        .context-table td:first-child {
            font-weight: 600;
            color: #495057;
            width: 35%;
            background-color: #f8f9fa;
        }
        .context-table td:last-child {
            color: #333;
            word-break: break-word;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
        }
        .footer a {
            color: #007bff;
            text-decoration: none;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge.critical {
            background-color: #dc3545;
            color: white;
        }
        .badge.error {
            background-color: #fd7e14;
            color: white;
        }
        .timestamp {
            color: #6c757d;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header {{ $level }}">
            <div class="icon">⚠️</div>
            <h1>Errore di Sistema Rilevato</h1>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Level and Timestamp -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <span class="badge {{ $level }}">{{ strtoupper($level) }}</span>
                <span class="timestamp">{{ $occurredAt }}</span>
            </div>

            <!-- Error Message -->
            <div class="info-box {{ $level }}">
                <div class="info-label">Messaggio Errore</div>
                <div class="info-value">{{ $errorMessage }}</div>
            </div>

            <!-- Message in Code Block -->
            <div class="message-box">{{ $errorMessage }}</div>

            <!-- Context Information -->
            @if(!empty($context))
            <div class="context-section">
                <h3>📋 Dettagli Contesto</h3>
                <table class="context-table">
                    @foreach($context as $key => $value)
                    <tr>
                        <td>{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                        <td>
                            @if(is_array($value) || is_object($value))
                                <code>{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code>
                            @elseif(is_bool($value))
                                {{ $value ? 'Sì' : 'No' }}
                            @else
                                {{ $value ?? '-' }}
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </table>
            </div>
            @endif

            <!-- Server Info -->
            <div class="context-section">
                <h3>🖥️ Informazioni Server</h3>
                <table class="context-table">
                    <tr>
                        <td>Ambiente</td>
                        <td>{{ config('app.env', 'production') }}</td>
                    </tr>
                    <tr>
                        <td>URL Applicazione</td>
                        <td>{{ config('app.url', '-') }}</td>
                    </tr>
                    <tr>
                        <td>Data/Ora Server</td>
                        <td>{{ now()->format('d/m/Y H:i:s') }} ({{ config('app.timezone', 'UTC') }})</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>
                Questa email è stata generata automaticamente dal sistema di log di<br>
                <strong>{{ config('app.name', 'Semprechiaro CRM') }}</strong>
            </p>
            <p>
                <a href="{{ config('app.url') }}/admin/system-logs">Vai alla Gestione Log</a>
            </p>
        </div>
    </div>
</body>
</html>
