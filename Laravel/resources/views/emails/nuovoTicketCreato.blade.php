<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ticket Creato</title>
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
            background-color: #17a2b8;
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
        .success-box {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            border-left: 4px solid #17a2b8;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 4px 4px 0;
        }
        .ticket-info {
            background-color: #f8f9fa;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .ticket-info p {
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
            background-color: #17a2b8;
            color: #fff;
        }
        .description-box {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 4px 4px 0;
        }
        .next-steps {
            background-color: #e7f3ff;
            border: 1px solid #b8daff;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .next-steps ul {
            margin: 10px 0 0 0;
            padding-left: 20px;
        }
        .next-steps li {
            margin: 5px 0;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            color: #6c757d;
            font-size: 12px;
            padding: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎫 Ticket #{{$numeroTicket}} Creato</h1>
        </div>
        
        <div class="content">
            <h2 style="margin-top: 0;">Salve {{$nomeUtente}},</h2>
            
            <div class="success-box">
                <p style="margin: 0;"><strong>Conferma:</strong> Il tuo ticket è stato creato correttamente e verrà preso in carico dal nostro team di supporto.</p>
            </div>

            <p>Hai aperto un nuovo ticket per il contratto <strong>ID {{$contractID}}</strong> intestato a <strong>{{$nomeCustomer}}</strong>.</p>
            
            <div class="ticket-info">
                <p><span class="label">Numero Ticket:</span> #{{$numeroTicket}}</p>
                <p><span class="label">Oggetto:</span> {{$titoloTicket}}</p>
                <p><span class="label">Stato:</span> <span class="status-badge">NUOVO</span></p>
                <p><span class="label">Data Apertura:</span> {{$dataApertura}}</p>
            </div>

            @if($descrizioneTicket)
            <div class="description-box">
                <p style="margin: 0 0 10px 0;"><span class="label">📝 Descrizione:</span></p>
                <p style="margin: 0;">{{$descrizioneTicket}}</p>
            </div>
            @endif

            <div class="next-steps">
                <p style="margin: 0;"><strong>📬 Cosa succederà ora?</strong></p>
                <ul>
                    <li>Il tuo ticket verrà esaminato dal nostro team di supporto</li>
                    <li>Riceverai una notifica via email quando ci saranno aggiornamenti</li>
                    <li>Puoi rispondere direttamente dalla piattaforma per aggiungere informazioni</li>
                </ul>
            </div>
        </div>

        <div class="footer">
            <p>Grazie per averci contattato.</p>
            <p>Questa è un'email automatica generata dal sistema di ticketing.</p>
            <p>© {{ date('Y') }} Semprechiaro. Tutti i diritti riservati.</p>
        </div>
    </div>
</body>
</html>