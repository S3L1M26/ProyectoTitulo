<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Mensaje de Estudiante</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f9fafb; padding:20px; color:#111827; }
        .card { background:#ffffff; border:1px solid #e5e7eb; border-radius:8px; padding:24px; }
        h2 { margin-top:0; font-size:20px; }
        .meta { font-size:14px; color:#374151; margin-bottom:16px; }
        .mensaje { white-space:pre-line; background:#f3f4f6; padding:16px; border-radius:6px; font-size:14px; line-height:1.5; }
        .footer { margin-top:24px; font-size:12px; color:#6b7280; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Nuevo mensaje de {{ $student->name }}</h2>
        <div class="meta">
            <strong>Estudiante:</strong> {{ $student->name }} &lt;{{ $student->email }}&gt;<br>
            <strong>Asunto:</strong> {{ $asunto }}
        </div>
        <div class="mensaje">{{ $mensaje }}</div>
        <p class="footer">Este mensaje fue enviado a través del sistema de mentorías. Por favor no respondas si no deseas continuar la conversación.</p>
    </div>
</body>
</html>
