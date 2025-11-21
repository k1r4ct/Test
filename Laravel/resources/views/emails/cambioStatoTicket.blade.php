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
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        .content {
            padding: 20px;
            background-color: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
        .ticket-info {
            background-color: #f8f9fa;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .message-box {
            background-color: #e9ecef;
            padding: 15px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }
        .label {
            font-weight: bold;
            color: #495057;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>il tuo Ticket con id {{$numeroTicket}} è stato chiuso</h1>
        </div>
        
        <div class="content">
            <h2>Salve {{$nomeUtente}}</h2>
            <p>il contratto con ID {{$contractID}} intestato al contraente {{$nomeCustomer}} in cui è stato aperto un ticket è stato chiuso ed archiviato dal backoffice. In caso di ulteriori problemi, puoi aprire nuovamente il ticket in qualsiasi momento.
                In tal caso si consiglia di accedere al programma per leggere la conversazione. Grazie.</p>
            
            <div class="ticket-info">
                <p><span class="label">Numero Ticket:</span> #{{$numeroTicket}}</p>
                <p><span class="label">Stato:</span> {{$statoTicket}}</p>
                <p><span class="label">Data Apertura:</span> {{$dataApertura}}</p>
            </div>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} Semprechiaro. All rights reserved.</p>
        </div>
    </div>
</body>
</html>