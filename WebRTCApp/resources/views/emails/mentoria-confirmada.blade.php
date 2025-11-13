<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentoría confirmada</title>
    <style>
        /* Simple responsive email styles */
        body { font-family: Arial, sans-serif; background-color: #f5f5f5; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; padding: 24px; }
        h1 { font-size: 20px; color: #111827; }
        p { color: #374151; line-height: 1.5; }
        .cta { display: inline-block; padding: 12px 18px; background: #2563eb; color: #ffffff !important; text-decoration: none; border-radius: 6px; }
        .meta { margin-top: 16px; background: #f9fafb; padding: 12px; border-radius: 6px; }
        .footer { margin-top: 24px; font-size: 12px; color: #6b7280; }
    </style>
    <!--[if mso]>
    <style>
    .cta { padding: 12px 18px !important; }
    </style>
    <![endif]-->
    
    <?php /** @var \App\Models\Mentoria $mentoria */ ?>
</head>
<body>
<div class="container">
    <h1>¡Tu mentoría ha sido confirmada!</h1>
    <p>
        Hola, tu sesión con <strong>{{ $mentoria->mentor->name ?? 'tu mentor/a' }}</strong> ha sido confirmada.
    </p>

    <div class="meta">
        <p><strong>Fecha:</strong> {{ \Illuminate\Support\Carbon::parse($mentoria->fecha)->isoFormat('LL') }}</p>
        <p><strong>Hora:</strong> {{ \Illuminate\Support\Carbon::parse($mentoria->hora)->format('H:i') }} (hora local)</p>
        <p><strong>Duración:</strong> {{ $mentoria->duracion_minutos }} minutos</p>
    </div>

    @if(!empty($mentoria->enlace_reunion))
        <p style="margin-top: 16px;">
            <a class="cta" href="{{ $mentoria->enlace_reunion }}" target="_blank" rel="noopener">Unirme a la reunión</a>
        </p>
        <p>O copia este enlace en tu navegador: <br>
            <a href="{{ $mentoria->enlace_reunion }}" target="_blank" rel="noopener">{{ $mentoria->enlace_reunion }}</a>
        </p>
    @endif

    <p class="footer">
        Si no esperabas este correo, puedes ignorarlo.
    </p>
</div>
</body>
</html>
