<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'FlexDash')</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
            width: 100% !important;
            background-color: #F3F4F6;
            color: #1F2937;
            -webkit-font-smoothing: antialiased;
        }
        table {
            border-collapse: collapse;
        }
        img {
            border: 0;
            outline: none;
            text-decoration: none;
        }
        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #F3F4F6;
            padding: 40px 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #FFFFFF;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .header {
            background-color: #0D1E36;
            padding: 24px;
            text-align: center;
        }
        .header-title {
            color: #FFFFFF;
            font-size: 24px;
            font-weight: 700;
            margin: 0;
            letter-spacing: 0.5px;
        }
        .header-subtitle {
            color: #0A7EA5;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            margin: 4px 0 0 0;
            letter-spacing: 1px;
        }
        .content {
            padding: 32px 24px;
            line-height: 1.6;
            color: #374151;
        }
        .content h1 {
            color: #0D1E36;
            font-size: 20px;
            font-weight: 700;
            margin-top: 0;
            margin-bottom: 16px;
        }
        .content p {
            font-size: 15px;
            margin-top: 0;
            margin-bottom: 16px;
        }
        .btn-container {
            text-align: center;
            margin: 28px 0;
        }
        .btn {
            background-color: #0A7EA5;
            color: #FFFFFF !important;
            text-decoration: none;
            padding: 12px 24px;
            font-size: 15px;
            font-weight: 600;
            border-radius: 6px;
            display: inline-block;
            box-shadow: 0 2px 4px rgba(10, 126, 165, 0.2);
            transition: background-color 0.2s ease;
        }
        .btn:hover {
            background-color: #086685;
        }
        .footer {
            background-color: #F9FAFB;
            padding: 24px;
            text-align: center;
            border-top: 1px solid #E5E7EB;
            font-size: 13px;
            color: #6B7280;
        }
        .footer p {
            margin: 0 0 8px 0;
        }
        .footer a {
            color: #0A7EA5;
            text-decoration: none;
        }
        .footer-note {
            font-size: 11px;
            color: #9CA3AF;
            margin-top: 12px !important;
        }
        .code-box {
            background-color: #F3F4F6;
            border: 1px solid #E5E7EB;
            border-radius: 6px;
            padding: 16px;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 4px;
            text-align: center;
            color: #0D1E36;
            margin: 20px auto;
            max-width: 200px;
        }
        .info-card {
            background-color: #F9FAFB;
            border-left: 4px solid #0A7EA5;
            padding: 16px;
            margin: 20px 0;
            border-radius: 0 6px 6px 0;
        }
        .info-card p {
            margin: 0 0 8px 0;
            font-size: 14px;
        }
        .info-card p:last-child {
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <div class="header-title">FlexDash</div>
                <div class="header-subtitle">Sistema POS & Gestión de Inventarios</div>
            </div>
            
            <div class="content">
                @yield('content')
            </div>
            
            <div class="footer">
                <p>&copy; {{ date('Y') }} FlexDash. Todos los derechos reservados.</p>
                <p>Este es un correo automático, por favor no respondas a este mensaje.</p>
                <p class="footer-note">Si no solicitaste este correo o crees que se trata de un error, puedes ignorarlo o contactar a soporte a través de <a href="mailto:soporte@flexdash.app">soporte@flexdash.app</a>.</p>
            </div>
        </div>
    </div>
</body>
</html>
