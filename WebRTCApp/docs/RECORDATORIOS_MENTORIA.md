# 📧 Sistema de Recordatorios de Mentoría

## 🎯 Descripción

Sistema automatizado que envía recordatorios por email **24 horas antes** de cada mentoría programada, tanto al mentor como al estudiante.

---

## 📋 Características

- ✅ Envío automático 24 horas antes de la sesión
- ✅ Emails personalizados para mentor y estudiante
- ✅ Incluye toda la información: fecha, hora, enlace Zoom, credenciales
- ✅ Diseño responsive compatible con todos los clientes de email
- ✅ Consejos útiles según el tipo de usuario
- ✅ Prevención de duplicados con flag `recordatorio_enviado`
- ✅ Sistema de reintentos automáticos (3 intentos)
- ✅ Logging detallado para debugging

---

## 🏗️ Arquitectura

```
┌─────────────────────────────────────────────────────┐
│  CRON (cada minuto)                                 │
│  * * * * * php artisan schedule:run                 │
└────────────────┬────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────┐
│  SCHEDULER (routes/console.php)                     │
│  Ejecuta diariamente a las 9:00 AM                  │
│  ├─ mentorias:enviar-recordatorios                  │
│  └─ Logging de éxito/fallo                          │
└────────────────┬────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────┐
│  COMANDO ARTISAN                                    │
│  EnviarRecordatoriosMentorias                       │
│  ├─ Busca mentorías confirmadas para mañana         │
│  ├─ Filtra las que NO tienen recordatorio enviado   │
│  └─ Despacha jobs a la cola                         │
└────────────────┬────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────┐
│  COLA DE JOBS (Redis/Database)                      │
│  EnviarRecordatorioMentoriaJob                      │
│  ├─ Envía email al mentor                           │
│  ├─ Envía email al estudiante                       │
│  ├─ Marca recordatorio_enviado = true               │
│  └─ Logging                                         │
└────────────────┬────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────┐
│  MAILABLE                                           │
│  RecordatorioMentoriaMail                           │
│  └─ Vista: recordatorio-mentoria.blade.php          │
└─────────────────────────────────────────────────────┘
```

---

## 🚀 Uso

### Comando Manual

```bash
# Ejecutar comando normalmente
php artisan mentorias:enviar-recordatorios

# Con información detallada (debugging)
php artisan mentorias:enviar-recordatorios --debug

# Forzar envío incluso si ya se envió antes
php artisan mentorias:enviar-recordatorios --force
```

### Programación Automática

El comando ya está configurado para ejecutarse **automáticamente todos los días a las 9:00 AM**.

#### Desarrollo (Docker)

El scheduler requiere que ejecutes:

```bash
# Opción 1: Ejecutar manualmente para testing
docker compose exec app php artisan schedule:run

# Opción 2: Ejecutar schedule:work (mantiene el proceso activo)
docker compose exec app php artisan schedule:work
```

#### Producción

Agrega este cron job al servidor:

```bash
* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
```

O con Docker:

```bash
* * * * * docker compose -f /ruta/al/docker-compose.yml exec -T app php artisan schedule:run >> /dev/null 2>&1
```

---

## 🧪 Testing

### 1. Poblar Base de Datos con Datos de Prueba

```bash
# Refrescar DB y ejecutar todos los seeders
docker compose exec app php artisan migrate:fresh --seed

# O solo el seeder de mentorías
docker compose exec app php artisan db:seed --class=MentoriaSeeder
```

El seeder crea automáticamente:
- ✅ Mentorías confirmadas para **mañana** (ideal para testing de recordatorios)
- ✅ Mentorías en diferentes fechas futuras
- ✅ Mentorías completadas (pasadas)
- ✅ Enlaces Zoom y credenciales realistas

### 2. Verificar Mentorías Creadas

```bash
docker compose exec app php artisan tinker
```

Dentro de tinker:

```php
// Ver mentorías para mañana
\App\Models\Mentoria::where('estado', 'confirmada')
    ->whereDate('fecha', now()->addDay()->toDateString())
    ->with(['mentor', 'aprendiz'])
    ->get();

// Contar total
\App\Models\Mentoria::whereDate('fecha', now()->addDay()->toDateString())
    ->count();
```

### 3. Ejecutar Comando de Recordatorios

```bash
# Modo debug para ver detalles
docker compose exec app php artisan mentorias:enviar-recordatorios --debug
```

Salida esperada:

```
🔍 Buscando mentorías para enviar recordatorios...
📊 Encontradas 3 mentoría(s) para mañana.
  → Procesando mentoría ID: 1
    Fecha: 2025-11-11 10:00:00
    Mentor: Juan Pérez
    Estudiante: María González
  ✅ Recordatorio programado para mentoría ID: 1
  ✅ Recordatorio programado para mentoría ID: 2
  ✅ Recordatorio programado para mentoría ID: 3

📬 Resumen:
+------------------------+-------+
| Métrica                | Valor |
+------------------------+-------+
| Mentorías encontradas  | 3     |
| Recordatorios enviados | 3     |
| Errores                | 0     |
+------------------------+-------+
```

### 4. Procesar Cola de Jobs

```bash
# Ejecutar worker (procesa jobs)
docker compose exec app php artisan queue:work

# O en segundo plano
docker compose exec -d app php artisan queue:work
```

### 5. Verificar Emails Enviados

#### En desarrollo (Mailtrap):
1. Ve a tu cuenta de Mailtrap
2. Busca emails con asunto: "🔔 Recordatorio: Mentoría mañana"
3. Verifica que se envió uno al mentor y otro al estudiante

#### En logs:
```bash
docker compose logs app | grep "Recordatorio enviado"

# O revisar archivo de log
docker compose exec app tail -f storage/logs/laravel.log
```

Busca líneas como:

```
[2025-11-10 22:00:00] local.INFO: 📧 Recordatorio enviado al mentor 
{"mentoria_id":1,"mentor_email":"mentor@example.com"}

[2025-11-10 22:00:01] local.INFO: 📧 Recordatorio enviado al estudiante 
{"mentoria_id":1,"estudiante_email":"estudiante@example.com"}

[2025-11-10 22:00:02] local.INFO: ✅ Recordatorios de mentoría enviados exitosamente 
{"mentoria_id":1,"fecha":"2025-11-11","hora":"10:00:00"}
```

---

## 📊 Seeders y Carga de Documentos

### Seeders Disponibles

| Seeder | Propósito | Dependencias |
|--------|-----------|--------------|
| `UsersSeeder` | Crea usuarios (mentores, estudiantes, admin) | Ninguna |
| `AreasInteresSeeder` | Crea áreas de interés | Ninguna |
| `AprendizTestSeeder` | Crea perfiles de estudiantes | UsersSeeder |
| `SolicitudMentoriaSeeder` | Crea solicitudes de mentoría | UsersSeeder, AprendizTestSeeder |
| `MentoriaSeeder` | Crea mentorías confirmadas | SolicitudMentoriaSeeder |
| `DocumentosSeeder` | Carga CVs, certificados, avatares | UsersSeeder |

### Ejecutar Todos los Seeders

```bash
docker compose exec app php artisan db:seed
```

### Ejecutar Seeder Específico

```bash
# Solo mentorías
docker compose exec app php artisan db:seed --class=MentoriaSeeder

# Solo documentos
docker compose exec app php artisan db:seed --class=DocumentosSeeder
```

### Carga de Documentos (CVs, Certificados)

**SÍ es posible** cargar archivos en seeders. Hay 3 opciones:

#### Opción 1: Archivos Dummy (Recomendado para desarrollo) ✅

El `DocumentosSeeder` ya está configurado para crear PDFs falsos automáticamente.

```bash
docker compose exec app php artisan db:seed --class=DocumentosSeeder
```

Esto crea:
- CVs falsos para todos los mentores
- Certificados falsos para estudiantes
- Archivos guardados en `storage/app/public/cvs/`

#### Opción 2: Archivos Reales

1. **Crear estructura de carpetas:**

```bash
mkdir -p storage/app/seeders/cvs
mkdir -p storage/app/seeders/certificados
```

2. **Colocar archivos template:**

```
storage/app/seeders/
├── cvs/
│   ├── cv_template.pdf
│   └── cv_senior.pdf
└── certificados/
    └── certificado_template.pdf
```

3. **Modificar `DocumentosSeeder.php`:**

Descomenta la línea:

```php
// $this->cargarDocumentosReales();  // ← Quitar comentario
```

4. **Ejecutar:**

```bash
docker compose exec app php artisan db:seed --class=DocumentosSeeder
```

El seeder copiará los templates a cada usuario.

#### Opción 3: Descargar desde URLs

Para avatares de perfil, el seeder puede descargarlos automáticamente:

```php
// Descomentar en DocumentosSeeder.php:
$this->descargarAvatares();
```

Esto descarga avatares únicos desde [pravatar.cc](https://i.pravatar.cc/).

---

## 🛠️ Configuración

### Variables de Entorno

Asegúrate de tener configurado el email en `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=tu_usuario
MAIL_PASSWORD=tu_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@mentorias.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### Cola (Queue)

Configura el driver de cola en `.env`:

```env
# Para desarrollo
QUEUE_CONNECTION=database

# Para producción (recomendado)
QUEUE_CONNECTION=redis
```

### Timezone

El scheduler usa la zona horaria configurada en `routes/console.php`:

```php
Schedule::command('mentorias:enviar-recordatorios')
    ->dailyAt('09:00')
    ->timezone('America/Santiago')  // ← Ajusta según tu región
```

---

## 📧 Contenido del Email

El email de recordatorio incluye:

### Para el Mentor:
- ✅ Nombre del estudiante
- ✅ Fecha y hora de la sesión
- ✅ Duración
- ✅ Enlace de Zoom + ID + Contraseña
- ✅ Email del estudiante
- ✅ Tips: "Revisa el perfil del estudiante", "Prepara materiales", etc.

### Para el Estudiante:
- ✅ Nombre del mentor
- ✅ Fecha y hora de la sesión
- ✅ Duración
- ✅ Enlace de Zoom + ID + Contraseña
- ✅ Email del mentor
- ✅ Tips: "Prepara tus preguntas", "Ten lista tu libreta", etc.

---

## 🔍 Debugging

### Ver Comandos Programados

```bash
docker compose exec app php artisan schedule:list
```

### Ver Jobs en Cola

```bash
docker compose exec app php artisan queue:monitor
```

### Ver Jobs Fallidos

```bash
docker compose exec app php artisan queue:failed
```

### Reintentar Jobs Fallidos

```bash
# Reintentar todos
docker compose exec app php artisan queue:retry all

# Reintentar uno específico
docker compose exec app php artisan queue:retry <job-id>
```

### Logs

```bash
# Ver logs en tiempo real
docker compose logs app -f

# Buscar recordatorios específicos
docker compose logs app | grep "Recordatorio"

# Ver archivo de log de Laravel
docker compose exec app tail -f storage/logs/laravel.log
```

---

## ⚠️ Solución de Problemas

### El comando no encuentra mentorías

**Verifica:**

```bash
# ¿Hay mentorías para mañana?
docker compose exec app php artisan tinker

\App\Models\Mentoria::whereDate('fecha', now()->addDay()->toDateString())
    ->where('estado', 'confirmada')
    ->count();
```

Si devuelve 0, ejecuta el seeder:

```bash
docker compose exec app php artisan db:seed --class=MentoriaSeeder
```

### Los emails no se envían

**Verifica:**

1. **Queue worker está corriendo:**

```bash
docker compose ps queue
# O
docker compose exec app php artisan queue:work
```

2. **Configuración de email en `.env`:**

```bash
docker compose exec app php artisan config:clear
```

3. **Ver jobs en cola:**

```bash
docker compose exec app php artisan queue:monitor
```

### El scheduler no se ejecuta automáticamente

**En desarrollo con Docker:**

El cron de Laravel NO funciona automáticamente en Docker. Debes ejecutar:

```bash
# Mantener activo (recomendado para dev)
docker compose exec app php artisan schedule:work

# O ejecutar manualmente cada vez
docker compose exec app php artisan schedule:run
```

**En producción:**

Asegúrate de tener el cron job configurado en el servidor.

---

## 📈 Métricas y Monitoreo

### Estadísticas del Comando

El comando muestra un resumen al finalizar:

```
📬 Resumen:
+------------------------+-------+
| Métrica                | Valor |
+------------------------+-------+
| Mentorías encontradas  | 5     |
| Recordatorios enviados | 5     |
| Errores                | 0     |
+------------------------+-------+
```

### Logs Importantes

Busca estos eventos en los logs:

- `📧 Recordatorio enviado al mentor` - Email enviado a mentor
- `📧 Recordatorio enviado al estudiante` - Email enviado a estudiante
- `✅ Recordatorios de mentoría enviados exitosamente` - Job completado
- `❌ Error al enviar recordatorio de mentoría` - Error en job
- `❌ Job de recordatorio falló definitivamente` - Job falló después de 3 intentos

---

## 🎨 Personalización

### Cambiar Hora de Envío

Edita `routes/console.php`:

```php
Schedule::command('mentorias:enviar-recordatorios')
    ->dailyAt('09:00')  // ← Cambia aquí (formato 24h)
```

### Cambiar Anticipación del Recordatorio

Por defecto se envía 24h antes. Para cambiar, edita `EnviarRecordatoriosMentorias.php`:

```php
// Cambiar de 24h a 48h antes:
$manana = $ahora->copy()->addDays(2);  // ← Cambia de 1 a 2
```

### Modificar Diseño del Email

Edita `resources/views/emails/recordatorio-mentoria.blade.php`

---

## 📝 Notas Adicionales

- Los recordatorios se envían **solo a mentorías confirmadas**
- El sistema previene duplicados con el flag `recordatorio_enviado`
- Si un email falla, el job se reintenta 3 veces automáticamente
- Los emails son compatibles con: Gmail, Outlook, Apple Mail, Thunderbird, etc.
- El diseño es responsive (se ve bien en móviles)

---

## ✅ Checklist de Implementación

- [x] Migración de `recordatorio_enviado`
- [x] Mailable creado
- [x] Vista de email diseñada
- [x] Job de cola implementado
- [x] Comando artisan funcional
- [x] Scheduler configurado
- [x] Seeders con datos de prueba
- [x] Documentación completa
- [ ] Testing manual exitoso
- [ ] Configurar cron en producción

---

## 📞 Soporte

Si encuentras algún problema:

1. Revisa los logs: `storage/logs/laravel.log`
2. Ejecuta el comando con `--debug`
3. Verifica la configuración de email en `.env`
4. Asegúrate de que el queue worker esté corriendo

---

**¡Listo para usar! 🎉**
