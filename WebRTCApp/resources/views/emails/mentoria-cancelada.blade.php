<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentoría Cancelada</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #ef4444;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f9fafb;
            padding: 30px;
            border: 1px solid #e5e7eb;
            border-top: none;
        }
        .info-box {
            background-color: white;
            border-left: 4px solid #ef4444;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box strong {
            color: #1f2937;
        }
        .button {
            display: inline-block;
            background-color: #3b82f6;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
            font-weight: bold;
        }
        .button:hover {
            background-color: #2563eb;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>❌ Mentoría Cancelada</h1>
    </div>
    
    <div class="content">
        <p>Hola <strong>{{ $solicitud->estudiante->name }}</strong>,</p>
        
        <p>Lamentamos informarte que tu mentoría programada ha sido cancelada por el mentor.</p>
        
        <div class="info-box">
            <p><strong>Mentor:</strong> {{ $mentoria->mentor->name }}</p>
            <p><strong>Fecha programada:</strong> {{ \Carbon\Carbon::parse($mentoria->fecha)->format('d/m/Y') }}</p>
            <p><strong>Hora:</strong> {{ \Carbon\Carbon::parse($mentoria->hora)->format('H:i') }}</p>
        </div>
        
        <h3>¿Qué puedes hacer ahora?</h3>
        <p>No te preocupes, puedes reagendar esta mentoría con el mismo mentor desde tu panel de solicitudes.</p>
        
        <p style="text-align: center;">
            <a href="{{ route('student.solicitudes') }}" class="button">
                Ver Mis Solicitudes
            </a>
        </p>
        
        <p style="margin-top: 30px; font-size: 14px; color: #6b7280;">
            Tu solicitud de mentoría está ahora marcada como "Cancelada" y disponible para ser reprogramada.
            El mentor podrá confirmar una nueva fecha y hora cuando estés listo.
        </p>
    </div>
    
    <div class="footer">
        <p>Este es un correo automático. Por favor no respondas a este mensaje.</p>
        <p>&copy; {{ date('Y') }} Sistema de Mentorías. Todos los derechos reservados.</p>
    </div>
</body>
</html>
