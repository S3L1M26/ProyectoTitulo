# Configuración de API de Zoom

## Descripción General

Esta guía documenta la configuración e integración de la API de Zoom para la creación automática de reuniones de mentoría. El sistema utiliza autenticación Server-to-Server OAuth para generar reuniones sin intervención del usuario.

## Requisitos Previos

- Cuenta de Zoom Pro, Business o Enterprise
- Acceso al Zoom Marketplace
- Permisos de administrador en la cuenta de Zoom

## Creación de App Server-to-Server en Zoom

### Paso 1: Acceder al Zoom Marketplace

1. Ir a [Zoom App Marketplace](https://marketplace.zoom.us/)
2. Hacer clic en "Sign In" con tu cuenta de Zoom
3. Hacer clic en "Develop" en el menú superior
4. Seleccionar "Build App"

### Paso 2: Crear Nueva App

1. Seleccionar tipo de app: **"Server-to-Server OAuth"**
2. Hacer clic en "Create"
3. Ingresar la información de la app:
   - **App Name:** `Sistema de Mentorías` (o el nombre de tu aplicación)
   - **Short Description:** Breve descripción de la funcionalidad
   - **Company Name:** Nombre de tu organización
   - **Developer Name:** Tu nombre
   - **Developer Email:** Tu email de contacto

4. Hacer clic en "Continue"

### Paso 3: Configurar Información de la App

1. En la sección "App Credentials", encontrarás:
   - **Account ID**
   - **Client ID**
   - **Client Secret**

2. **¡IMPORTANTE!** Copiar y guardar estos valores de forma segura. Los necesitarás para la configuración del sistema.

### Paso 4: Agregar Scopes (Permisos)

Los siguientes scopes son **requeridos** para la funcionalidad del sistema:

#### Scopes Obligatorios

| Scope | Descripción | Uso en el Sistema |
|-------|-------------|-------------------|
| `meeting:write` | Crear reuniones | Crear reuniones de mentoría |
| `meeting:read` | Leer información de reuniones | Obtener detalles de reuniones creadas |
| `meeting:update` | Actualizar reuniones | Modificar horarios o configuración |
| `meeting:delete` | Eliminar reuniones | Cancelar mentorías |
| `user:read` | Leer información de usuarios | Verificar cuenta del mentor |

#### Cómo Agregar Scopes

1. En la sección "Scopes", hacer clic en "Add Scopes"
2. Buscar y seleccionar cada uno de los scopes listados arriba
3. Hacer clic en "Done"
4. Hacer clic en "Continue"

### Paso 5: Activar la App

1. Revisar toda la información ingresada
2. Hacer clic en "Activate your app"
3. La app ahora está activa y lista para usar

## Configuración en el Sistema

### Variables de Entorno

Agregar las siguientes variables en el archivo `.env`:

```env
# Zoom API Configuration
ZOOM_ACCOUNT_ID=your_account_id_here
ZOOM_CLIENT_ID=your_client_id_here
ZOOM_CLIENT_SECRET=your_client_secret_here
ZOOM_API_BASE_URL=https://api.zoom.us/v2
```

**Reemplazar:**
- `your_account_id_here` con el Account ID de tu app
- `your_client_id_here` con el Client ID de tu app
- `your_client_secret_here` con el Client Secret de tu app

### Verificación de Configuración

Ejecutar el siguiente comando para verificar la configuración:

```bash
php artisan tinker
```

Luego ejecutar:

```php
$zoom = app(\App\Services\ZoomService::class);
$token = $zoom->getAccessToken();
echo "Token obtenido exitosamente: " . substr($token, 0, 20) . "...";
```

Si la configuración es correcta, deberías ver el mensaje de éxito.

## Uso en el Sistema

### Servicio ZoomService

El sistema utiliza `App\Services\ZoomService` para interactuar con la API de Zoom.

#### Crear Reunión

```php
use App\Services\ZoomService;

$zoomService = app(ZoomService::class);

$meetingData = $zoomService->createMeeting([
    'topic' => 'Mentoría: Laravel Avanzado',
    'type' => 2, // Reunión programada
    'start_time' => '2024-01-15T10:00:00Z',
    'duration' => 60, // minutos
    'timezone' => 'America/Santiago',
    'agenda' => 'Sesión de mentoría sobre Laravel',
    'settings' => [
        'host_video' => true,
        'participant_video' => true,
        'join_before_host' => false,
        'mute_upon_entry' => true,
        'waiting_room' => false,
        'audio' => 'both',
        'auto_recording' => 'none',
    ]
]);

// $meetingData contendrá:
// - id: ID de la reunión
// - join_url: URL para unirse
// - start_url: URL para el host
// - password: Contraseña de la reunión
```

#### Obtener Detalles de Reunión

```php
$meeting = $zoomService->getMeeting($meetingId);

echo "Join URL: " . $meeting['join_url'];
echo "Password: " . $meeting['password'];
```

#### Actualizar Reunión

```php
$zoomService->updateMeeting($meetingId, [
    'start_time' => '2024-01-15T11:00:00Z',
    'duration' => 90,
]);
```

#### Eliminar Reunión

```php
$zoomService->deleteMeeting($meetingId);
```

## Estructura de Respuesta de la API

### Crear Reunión - Respuesta Exitosa

```json
{
  "uuid": "abc123def456",
  "id": 123456789,
  "host_id": "host_user_id",
  "topic": "Mentoría: Laravel Avanzado",
  "type": 2,
  "start_time": "2024-01-15T10:00:00Z",
  "duration": 60,
  "timezone": "America/Santiago",
  "created_at": "2024-01-10T15:30:00Z",
  "join_url": "https://zoom.us/j/123456789?pwd=xxxxx",
  "start_url": "https://zoom.us/s/123456789?zak=xxxxx",
  "password": "abc123",
  "settings": {
    "host_video": true,
    "participant_video": true,
    "join_before_host": false,
    "mute_upon_entry": true,
    "waiting_room": false,
    "audio": "both",
    "auto_recording": "none"
  }
}
```

## Manejo de Errores

### Errores Comunes

#### 1. Invalid Access Token (401)

**Error:**
```json
{
  "code": 124,
  "message": "Invalid access token."
}
```

**Causa:** Token de acceso expirado o inválido

**Solución:**
1. Verificar que las credenciales en `.env` son correctas
2. El sistema regenera automáticamente el token si expira
3. Verificar que la app está activa en Zoom Marketplace

#### 2. User Does Not Exist (404)

**Error:**
```json
{
  "code": 1001,
  "message": "User does not exist: user_id"
}
```

**Causa:** El usuario especificado no existe en la cuenta de Zoom

**Solución:**
1. Usar `me` como user_id para el usuario autenticado de la app
2. Verificar que el usuario existe en la cuenta de Zoom

#### 3. Meeting Does Not Exist (404)

**Error:**
```json
{
  "code": 3001,
  "message": "Meeting does not exist: meeting_id"
}
```

**Causa:** El ID de reunión no existe o fue eliminado

**Solución:**
1. Verificar que el meeting_id es correcto
2. La reunión pudo haber sido eliminada manualmente en Zoom

#### 4. Rate Limit Exceeded (429)

**Error:**
```json
{
  "code": 429,
  "message": "Rate limit exceeded"
}
```

**Causa:** Demasiadas peticiones en poco tiempo

**Solución:**
1. Implementar retry con backoff exponencial
2. Revisar límites de la API de Zoom (depende del plan)
3. El sistema implementa automáticamente retry en estos casos

## Límites de la API

| Plan | Límite de Peticiones |
|------|---------------------|
| Free | No disponible para Server-to-Server |
| Pro | 100 peticiones/día |
| Business | 300 peticiones/día |
| Enterprise | Contactar a Zoom |

**Nota:** Los límites pueden variar. Verificar en la documentación oficial de Zoom.

## Monitoreo y Logs

### Logs del Sistema

El sistema registra todas las interacciones con la API de Zoom:

```php
// Ver logs de Zoom
tail -f storage/logs/laravel.log | grep "ZoomService"
```

### Información Registrada

- Creación de reuniones exitosas
- Errores de API
- Tokens de acceso generados
- Reuniones eliminadas o actualizadas

## Troubleshooting Común

### Problema: "Invalid Client Credentials"

**Síntomas:** Error 401 al intentar obtener token de acceso

**Solución:**
1. Verificar que `ZOOM_CLIENT_ID` y `ZOOM_CLIENT_SECRET` son correctos
2. Verificar que la app está activa en Zoom Marketplace
3. Regenerar credenciales si es necesario (en Zoom Marketplace)

### Problema: Reuniones no se crean

**Síntomas:** Error al crear reunión, respuesta 400

**Solución:**
1. Verificar formato de fecha/hora (debe ser ISO 8601: `YYYY-MM-DDTHH:MM:SSZ`)
2. Verificar que la zona horaria es válida
3. Revisar que todos los scopes necesarios están configurados
4. Verificar logs para detalles del error

### Problema: Join URL no funciona

**Síntomas:** Usuarios no pueden unirse a la reunión

**Solución:**
1. Verificar que la reunión no ha sido eliminada
2. Verificar que la fecha/hora es correcta
3. Verificar que `join_before_host` está configurado apropiadamente
4. Comprobar que la contraseña (si hay) es correcta

### Problema: Token expira constantemente

**Síntomas:** Errores frecuentes de "Invalid Access Token"

**Solución:**
1. Server-to-Server tokens son válidos por 1 hora
2. El sistema debe regenerar automáticamente tokens expirados
3. Verificar implementación de `getAccessToken()` en `ZoomService`
4. Implementar caché de tokens si es necesario

## Seguridad

### Mejores Prácticas

1. **Nunca** compartir credenciales de Zoom (Client ID, Secret, Account ID)
2. **Nunca** commitear credenciales en Git
3. Usar variables de entorno para todas las credenciales
4. Rotar credenciales periódicamente (cada 6 meses)
5. Monitorear logs para detectar uso anómalo
6. Limitar scopes solo a los necesarios

### Variables de Entorno en Producción

En producción, configurar variables de entorno de forma segura:

```bash
# En el servidor
php artisan config:cache
php artisan optimize
```

## Testing

### Tests Unitarios

El sistema incluye tests para el servicio de Zoom:

```bash
# Ejecutar tests de Zoom
php artisan test --filter ZoomServiceTest
```

### Mock de API para Tests

```php
// En tests, usar mocks para evitar llamadas reales
Http::fake([
    'https://zoom.us/oauth/token' => Http::response([
        'access_token' => 'fake_token',
        'token_type' => 'bearer',
        'expires_in' => 3600,
    ]),
    'https://api.zoom.us/v2/users/me/meetings' => Http::response([
        'id' => 123456789,
        'join_url' => 'https://zoom.us/j/123456789',
        'password' => 'test123',
    ]),
]);
```

## Recursos Adicionales

- [Zoom API Documentation](https://developers.zoom.us/docs/api/)
- [Server-to-Server OAuth](https://developers.zoom.us/docs/internal-apps/s2s-oauth/)
- [Meeting API Reference](https://developers.zoom.us/docs/api/rest/reference/zoom-api/methods/#operation/meetingCreate)
- [Rate Limits](https://developers.zoom.us/docs/api/rest/rate-limits/)
- [Webhooks](https://developers.zoom.us/docs/api/rest/webhook-reference/)

## Changelog

### v1.0.0 (2024-01-10)
- Implementación inicial de integración con Zoom
- Creación, actualización y eliminación de reuniones
- Manejo de errores y reintentos automáticos
- Documentación completa

### Próximas Mejoras

- [ ] Implementar webhooks de Zoom para notificaciones en tiempo real
- [ ] Agregar grabación automática de reuniones
- [ ] Implementar waiting room configurable
- [ ] Soporte para reuniones recurrentes
- [ ] Dashboard de métricas de reuniones Zoom
