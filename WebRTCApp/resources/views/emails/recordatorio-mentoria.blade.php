<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recordatorio de Mentoría</title>
    <style>
        /* Simple responsive email styles */
        body { font-family: Arial, sans-serif; background-color: #f5f5f5; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; padding: 24px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 24px; }
        .content { padding: 20px; }
        h2 { font-size: 18px; color: #111827; margin-top: 0; }
        p { color: #374151; line-height: 1.6; margin: 12px 0; }
        .alert-box { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px; margin: 20px 0; border-radius: 4px; }
        .alert-box p { color: #92400e; margin: 0; }
        .cta { display: inline-block; padding: 14px 28px; background: #2563eb; color: #ffffff !important; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 16px 0; }
        .cta:hover { background: #1d4ed8; }
        .meta { margin-top: 20px; background: #f9fafb; padding: 16px; border-radius: 6px; border: 1px solid #e5e7eb; }
        .meta-item { margin: 8px 0; }
        .meta-item strong { color: #1f2937; display: inline-block; min-width: 100px; }
        .zoom-info { background: #eff6ff; padding: 12px; border-radius: 4px; margin-top: 12px; }
        .zoom-info p { color: #1e40af; font-size: 13px; margin: 4px 0; }
        .tips { background: #f0fdf4; padding: 16px; border-radius: 6px; margin-top: 20px; border-left: 4px solid #22c55e; }
        .tips h3 { color: #166534; margin-top: 0; font-size: 16px; }
        .tips ul { color: #166534; margin: 8px 0; padding-left: 20px; }
        .tips li { margin: 6px 0; }
        .footer { margin-top: 24px; padding-top: 20px; border-top: 1px solid #e5e7eb; font-size: 12px; color: #6b7280; text-align: center; }
        .signature { margin-top: 20px; padding-top: 16px; border-top: 1px solid #e5e7eb; color: #6b7280; font-style: italic; }
    </style>
    <!--[if mso]>
    <style>
    .cta { padding: 14px 28px !important; }
    .header { background: #667eea !important; }
    </style>
    <![endif]-->
    
    <?php /** @var \App\Models\Mentoria $mentoria */ ?>
    <?php /** @var string $tipoDestinatario */ ?>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🔔 Recordatorio de Mentoría</h1>
    </div>

    <div class="content">
        @if($tipoDestinatario === 'mentor')
            <h2>¡Hola {{ $mentoria->mentor->name ?? 'Mentor/a' }}!</h2>
            <p>
                Este es un recordatorio amigable de que tienes una sesión de mentoría programada para <strong>mañana</strong> 
                con <strong>{{ $mentoria->aprendiz->name ?? 'tu estudiante' }}</strong>.
            </p>
        @else
            <h2>¡Hola {{ $mentoria->aprendiz->name ?? 'Estudiante' }}!</h2>
            <p>
                Este es un recordatorio amigable de que tienes una sesión de mentoría programada para <strong>mañana</strong> 
                con <strong>{{ $mentoria->mentor->name ?? 'tu mentor/a' }}</strong>.
            </p>
        @endif

        <div class="alert-box">
            <p><strong>⏰ Tu mentoría es mañana a las {{ \Illuminate\Support\Carbon::parse($mentoria->hora)->format('H:i') }}</strong></p>
        </div>

        <div class="meta">
            <div class="meta-item">
                <strong>📅 Fecha:</strong> 
                {{ \Illuminate\Support\Carbon::parse($mentoria->fecha)->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
            </div>
            <div class="meta-item">
                <strong>🕐 Hora:</strong> 
                {{ \Illuminate\Support\Carbon::parse($mentoria->hora)->format('H:i') }} (hora local)
            </div>
            <div class="meta-item">
                <strong>⏱️ Duración:</strong> 
                {{ $mentoria->duracion_minutos }} minutos
            </div>
            
            @if($tipoDestinatario === 'mentor')
                <div class="meta-item">
                    <strong>👤 Estudiante:</strong> 
                    {{ $mentoria->aprendiz->name ?? 'N/A' }}
                </div>
                @if($mentoria->aprendiz->email)
                    <div class="meta-item">
                        <strong>📧 Email:</strong> 
                        {{ $mentoria->aprendiz->email }}
                    </div>
                @endif
            @else
                <div class="meta-item">
                    <strong>👨‍🏫 Mentor:</strong> 
                    {{ $mentoria->mentor->name ?? 'N/A' }}
                </div>
                @if($mentoria->mentor->email)
                    <div class="meta-item">
                        <strong>📧 Email:</strong> 
                        {{ $mentoria->mentor->email }}
                    </div>
                @endif
            @endif

            @if(!empty($mentoria->enlace_reunion))
                <div class="zoom-info">
                    <p><strong>🎥 Enlace de Zoom:</strong></p>
                    <p>
                        <a href="{{ $mentoria->enlace_reunion }}" target="_blank" rel="noopener" style="color: #2563eb; word-break: break-all;">
                            {{ $mentoria->enlace_reunion }}
                        </a>
                    </p>
                    @if(!empty($mentoria->zoom_meeting_id))
                        <p><strong>ID de reunión:</strong> {{ $mentoria->zoom_meeting_id }}</p>
                    @endif
                    @if(!empty($mentoria->zoom_password))
                        <p><strong>Contraseña:</strong> {{ $mentoria->zoom_password }}</p>
                    @endif
                </div>
            @endif
        </div>

        @if(!empty($mentoria->enlace_reunion))
            <p style="text-align: center; margin-top: 24px;">
                <a class="cta" href="{{ $mentoria->enlace_reunion }}" target="_blank" rel="noopener">
                    🎥 Unirme a la reunión Zoom
                </a>
            </p>
        @endif

        <div class="tips">
            <h3>💡 Consejos para una sesión exitosa:</h3>
            <ul>
                @if($tipoDestinatario === 'mentor')
                    <li>Revisa el perfil y objetivos del estudiante antes de la sesión</li>
                    <li>Prepara materiales o recursos que puedan ser útiles</li>
                    <li>Ten a mano ejemplos prácticos de tu experiencia</li>
                    <li>Únete unos minutos antes para verificar tu conexión</li>
                @else
                    <li>Prepara tus preguntas con anticipación</li>
                    <li>Ten lista tu libreta para tomar notas</li>
                    <li>Verifica tu conexión a internet y el audio/video</li>
                    <li>Únete unos minutos antes de la hora programada</li>
                @endif
                <li>Encuentra un lugar tranquilo y con buena iluminación</li>
                <li>Ten agua cerca para mantenerte hidratado/a</li>
            </ul>
        </div>

        @if($tipoDestinatario === 'mentor')
            <div class="signature">
                <p>
                    Gracias por dedicar tu tiempo a compartir tu conocimiento y experiencia. 
                    ¡Tu apoyo hace la diferencia en el crecimiento profesional de nuestros estudiantes! 🌟
                </p>
            </div>
        @else
            <div class="signature">
                <p>
                    ¡Aprovecha al máximo esta oportunidad de aprendizaje! 
                    Recuerda que tu mentor está aquí para ayudarte a crecer profesionalmente. 🚀
                </p>
            </div>
        @endif

        <div class="footer">
            <p>Este es un recordatorio automático enviado 24 horas antes de tu mentoría.</p>
            <p>Si necesitas cancelar o reprogramar, por favor hazlo con anticipación.</p>
            <p style="margin-top: 12px;">
                © {{ date('Y') }} Sistema de Mentorías. Todos los derechos reservados.
            </p>
        </div>
    </div>
</div>
</body>
</html>
