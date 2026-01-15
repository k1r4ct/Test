<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Aggiornamento Stato Contratto</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #fd7e14;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            border-radius: 4px 4px 0 0;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 22px;
        }
        .content {
            padding: 20px;
            background-color: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 0 0 4px 4px;
        }
        .alert-box {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            border-left: 4px solid #fd7e14;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 4px 4px 0;
        }
        .contract-info {
            background-color: #f8f9fa;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .contract-info p {
            margin: 8px 0;
        }
        .label {
            font-weight: bold;
            color: #495057;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            background-color: #fd7e14;
            color: #fff;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            color: #6c757d;
            font-size: 12px;
            padding: 15px;
        }
        .info-note {
            background-color: #e7f3ff;
            border: 1px solid #b6d4fe;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 14px;
        }
        .cta-button {
            display: inline-block;
            background-color: #fd7e14;
            color: #ffffff;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 15px;
            font-weight: bold;
        }
        .cta-button:hover {
            background-color: #e96b02;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Aggiornamento Contratto #{{ $contrattoId }}</h1>
        </div>
        
        <div class="content">
            <h2 style="margin-top: 0;">Salve {{ $nomeSeu }},</h2>
            
            <div class="alert-box">
                <p style="margin: 0;"><strong>⚠️ Attenzione!</strong> Lo stato di un tuo contratto è stato aggiornato.</p>
            </div>

            <p>Il contratto <strong>ID #{{ $contrattoId }}</strong> intestato a <strong>{{ $nomeContraente }}</strong> ha cambiato stato.</p>
            
            <div class="contract-info">
                <p><span class="label">ID Contratto:</span> #{{ $contrattoId }}</p>
                <p><span class="label">Codice:</span> {{ $codiceContratto }}</p>
                <p><span class="label">Contraente:</span> {{ $nomeContraente }}</p>
                <p><span class="label">Prodotto:</span> {{ $nomeProdotto }}</p>
                <p><span class="label">Data Stipula:</span> {{ $dataStipula }}</p>
                <p><span class="label">Nuovo Stato:</span> <span class="status-badge">{{ $statoContratto }}</span></p>
                @if($macroStato)
                <p><span class="label">Fase:</span> {{ $macroStato }}</p>
                @endif
            </div>

            <div class="info-note">
                <strong>💡 Nota:</strong> Ti consigliamo di accedere alla piattaforma per verificare i dettagli del contratto e le eventuali note del backoffice.
            </div>

            <center>
                <a href="{{ $linkContratto }}" class="cta-button">Visualizza Contratto</a>
            </center>
        </div>

        <div class="footer">
            <p>Questa è un'email automatica generata dal sistema.</p>
            <p>© {{ date('Y') }} Semprechiaro. Tutti i diritti riservati.</p>
        </div>
    </div>
</body>
</html>