<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nuovo Messaggio sul Ticket</title>
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
            background-color: #007bff;
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
        .ticket-info {
            background-color: #f8f9fa;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .ticket-info p {
            margin: 8px 0;
        }
        .message-box {
            background-color: #e9ecef;
            padding: 15px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
            border-radius: 0 4px 4px 0;
        }
        .message-header {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 10px;
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
        }
        .status-new { background-color: #17a2b8; color: #fff; }
        .status-waiting { background-color: #ffc107; color: #333; }
        .status-resolved { background-color: #28a745; color: #fff; }
        .footer {
            text-align: center;
            margin-top: 20px;
            color: #6c757d;
            font-size: 12px;
            padding: 15px;
        }
        .cta-button {
            display: inline-block;
            background-color: #007bff;
            color: #ffffff;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📩 Nuovo Messaggio sul Ticket #{{$numeroTicket}}</h1>
        </div>
        
        <div class="content">
            <h2 style="margin-top: 0;">Salve {{$nomeUtente}},</h2>
            
            <p>Il ticket relativo al contratto <strong>ID {{$contractID}}</strong> intestato a <strong>{{$nomeCustomer}}</strong> ha ricevuto un nuovo messaggio.</p>
            
            <div class="ticket-info">
                <p><span class="label">Numero Ticket:</span> #{{$numeroTicket}}</p>
                <p><span class="label">Oggetto:</span> {{$oggettoTicket}}</p>
                <p><span class="label">Stato:</span> 
                    <span class="status-badge 
                        @if($statoTicket == 'new') status-new
                        @elseif($statoTicket == 'waiting') status-waiting
                        @elseif($statoTicket == 'resolved') status-resolved
                        @endif">
                        {{$statoTicket}}
                    </span>
                </p>
                <p><span class="label">Data Apertura:</span> {{$dataApertura}}</p>
            </div>

            <div class="message-box">
                <div class="message-header">
                    <strong>{{$mittente}}</strong> ha scritto il {{$dataMessaggio}}:
                </div>
                <p style="margin: 0;">{{$testoMessaggio}}</p>
            </div>

            <p>Si consiglia di accedere alla piattaforma per visualizzare la conversazione completa e rispondere.</p>

            <center>
                <a href="{{$linkTicket}}" class="cta-button">Visualizza Ticket</a>
            </center>
        </div>

        <div class="footer">
            <p>Questa è un'email automatica generata dal sistema di ticketing.</p>
            <p>© {{ date('Y') }} Semprechiaro. Tutti i diritti riservati.</p>
        </div>
    </div>
</body>
</html>