<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Lead Notification</title>
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
            <h1>Nuovo Amico Presentato</h1>
        </div>
        
        <div class="content">
            <h2>Gentile {{$nameInvitato}}</h2>
            <p>Sei stato presentato come Amico sul portale Semprechiaro</p>
            
            <!-- You'll need to pass these variables from your LeadMail class -->
            <ul>
                <li><strong>Sei Stato invitato da:</strong> {{ $nameInvitante ?? 'Not provided' }}</li>
                <li><strong>Email:</strong> {{ $mail_cliente_sc ?? 'Not provided' }}</li>
            </ul>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} Semprechiaro. All rights reserved.</p>
        </div>
    </div>
</body>
</html>