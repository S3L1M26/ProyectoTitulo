# 📊 Plan de Optimización de Performance - Plataforma de Mentoría

**Fecha de creación:** 5 de noviembre de 2025  
**Estado actual:** Fase 1 aplicada parcialmente  
**Objetivo:** Reducir tiempos de navegación de ~800ms a <100ms

---

## ✅ Fase 1: Quick Wins (COMPLETADO)

### 1.1 Cache de Laravel (✅ Aplicado)
```bash
php artisan route:cache
php artisan config:cache
php artisan view:cache
php artisan debugbar:clear
```

**Resultado esperado:** 15-20% mejora en response time base

---

### 1.2 Cache Agresivo en Controladores (⚠️ PARCIAL)

#### ✅ Implementado:

**HandleInertiaRequests.php:**
```php
// Cache de contador de notificaciones: 30 segundos
Cache::remember('student_unread_notifications_' . $user->id, 30, ...)

// Cache de completitud de perfil: 5 minutos
Cache::remember('profile_completeness_' . $user->id, 300, ...)
```

**SolicitudMentoriaController::misSolicitudes():**
```php
// Cache de solicitudes: 2 minutos
Cache::remember('student_solicitudes_' . $estudiante->id, 120, ...)
```

**SolicitudMentoriaController::misNotificaciones():**
```php
// Cache de notificaciones: 1 minuto
Cache::remember('student_notifications_' . $estudiante->id, 60, ...)

// Cache de contador: 30 segundos
Cache::remember('student_unread_notifications_' . $estudiante->id, 30, ...)
```

---

#### ❌ PENDIENTE: Invalidación de Cache

**CRÍTICO:** Añadir invalidación de cache cuando los datos cambian para evitar mostrar información desactualizada.

**Archivos a modificar:** `app/Http/Controllers/SolicitudMentoriaController.php`

**Cambio 1: store() - Al crear solicitud**
```php
// Después de: ProcessSolicitudMentoria::dispatch($solicitud, 'created');
Cache::forget('student_solicitudes_' . $estudiante->id);
```

**Cambio 2: accept() - Al aceptar solicitud**
```php
// Después de: ProcessSolicitudMentoria::dispatch($solicitud, 'accepted');
Cache::forget('student_solicitudes_' . $solicitud->estudiante_id);
Cache::forget('student_notifications_' . $solicitud->estudiante_id);
Cache::forget('student_unread_notifications_' . $solicitud->estudiante_id);
```

**Cambio 3: reject() - Al rechazar solicitud**
```php
// Después de: ProcessSolicitudMentoria::dispatch($solicitud, 'rejected');
Cache::forget('student_solicitudes_' . $solicitud->estudiante_id);
Cache::forget('student_notifications_' . $solicitud->estudiante_id);
Cache::forget('student_unread_notifications_' . $solicitud->estudiante_id);
```

**Cambio 4: marcarComoLeida() - Al marcar notificación**
```php
// Después de: $notification->markAsRead();
Cache::forget('student_notifications_' . $estudiante->id);
Cache::forget('student_unread_notifications_' . $estudiante->id);
```

**Cambio 5: marcarTodasComoLeidas() - Al marcar todas**
```php
// Después de: ->update(['read_at' => now()]);
Cache::forget('student_notifications_' . $estudiante->id);
Cache::forget('student_unread_notifications_' . $estudiante->id);
```

**Impacto esperado:** Datos siempre actualizados + navegación rápida

---

## 🔄 Fase 2: Optimización Media (PLANIFICADO)

### 2.1 Database Indexes

**Problema:** Queries lentas en tablas grandes sin índices apropiados.

**Archivo:** `database/migrations/YYYY_MM_DD_add_performance_indexes.php`

```php
Schema::table('solicitud_mentorias', function (Blueprint $table) {
    // Para filtrar por estudiante y estado
    $table->index(['estudiante_id', 'estado'], 'idx_estudiante_estado');
    
    // Para filtrar por mentor y estado
    $table->index(['mentor_id', 'estado'], 'idx_mentor_estado');
    
    // Para ordenar por fecha
    $table->index('created_at', 'idx_created_at');
    
    // Para filtrar por fecha de solicitud
    $table->index('fecha_solicitud', 'idx_fecha_solicitud');
});

Schema::table('notifications', function (Blueprint $table) {
    // Para obtener notificaciones no leídas de un usuario
    $table->index(
        ['notifiable_id', 'notifiable_type', 'read_at'], 
        'idx_notifiable_read'
    );
    
    // Para filtrar por tipo
    $table->index('type', 'idx_notification_type');
});

Schema::table('mentors', function (Blueprint $table) {
    // Para filtrar mentores disponibles
    $table->index('disponible_ahora', 'idx_disponible');
    
    // Para ordenar por calificación
    $table->index('calificacionPromedio', 'idx_calificacion');
});
```

**Comandos:**
```bash
php artisan make:migration add_performance_indexes
# Editar archivo con los índices de arriba
php artisan migrate
```

**Impacto esperado:** 40-60% reducción en tiempo de queries

---

### 2.2 Optimización de Queries con Select Específico

**Problema:** Se cargan todos los campos cuando solo se necesitan algunos.

**Archivo:** `app/Http/Controllers/Student/StudentController.php`

**Cambio en buildMentorSuggestionsQuery():**
```php
// ACTUAL (carga todos los campos)
$mentors = User::select('users.id', 'users.name', 'mentors.calificacionPromedio')

// OPTIMIZADO (especificar solo campos necesarios en relaciones)
$mentors = User::select('users.id', 'users.name', 'users.email')
    ->join(...)
    ->with([
        'mentor' => function($query) {
            $query->select([
                'id', 'user_id', 
                'biografia', 'años_experiencia', 
                'disponible_ahora', 'calificacionPromedio', 
                'cv_verified'
            ]); // Sin campos innecesarios
        },
        'mentor.areasInteres:id,nombre', // Solo ID y nombre
    ])
```

**Impacto esperado:** 20-30% reducción en tamaño de respuesta

---

### 2.3 Lazy Loading para Dashboard

**Problema:** El dashboard carga solicitudes pendientes aunque no siempre se usen.

**Archivo:** `app/Http/Controllers/Student/StudentController.php`

**Cambio en index():**
```php
// ACTUAL
public function index() {
    $solicitudes = SolicitudMentoria::where(...)->get();
    
    return Inertia::render('Student/Dashboard/Index', [
        'mentorSuggestions' => ...,
        'aprendiz' => ...,
        'solicitudesPendientes' => $solicitudes, // Carga innecesaria
    ]);
}

// OPTIMIZADO
public function index() {
    return Inertia::render('Student/Dashboard/Index', [
        'mentorSuggestions' => $this->getMentorSuggestions(),
        'aprendiz' => $student->aprendiz,
        // Removed: solicitudesPendientes
        // Se cargará solo cuando se abra el modal de solicitud
    ]);
}

// Nueva ruta para cargar solo IDs cuando se necesiten
public function solicitudesPendientesIds() {
    return SolicitudMentoria::where('estudiante_id', auth()->id())
        ->where('estado', 'pendiente')
        ->pluck('mentor_id'); // Solo IDs, no objetos completos
}
```

**Ruta nueva en web.php:**
```php
Route::get('/student/solicitudes/pendientes-ids', [StudentController::class, 'solicitudesPendientesIds'])
    ->middleware(['auth', 'verified', 'role:student'])
    ->name('student.solicitudes.pendientes-ids');
```

**Impacto esperado:** Dashboard 30-40% más rápido

---

## 📈 Fase 3: Monitoreo y Alertas (FUTURO)

### 3.1 Laravel Telescope

**Instalación:**
```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

**Configuración en .env:**
```env
TELESCOPE_ENABLED=true
TELESCOPE_DRIVER=database
```

**Uso:**
- Acceder a: `/telescope`
- Monitorear queries lentas (> 100ms)
- Ver jobs fallidos
- Analizar uso de cache

---

### 3.2 Redis Monitoring

**Herramienta:** RedisInsight

**Instalación:**
```bash
# Descargar desde: https://redis.io/insight/
```

**Métricas a monitorear:**
- Memoria usada por cache
- Hit rate de cache (debe ser > 80%)
- Comandos lentos
- Cantidad de keys

**Comandos útiles:**
```bash
# Conectar a Redis del contenedor
docker compose exec redis redis-cli

# Ver estadísticas
INFO stats

# Ver uso de memoria
INFO memory

# Listar keys de cache
KEYS student_*

# Limpiar cache manual si es necesario
FLUSHDB
```

---

### 3.3 Alertas de Performance

**Archivo:** `app/Http/Middleware/PerformanceMonitor.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class PerformanceMonitor
{
    public function handle($request, Closure $next)
    {
        $start = microtime(true);
        
        $response = $next($request);
        
        $duration = (microtime(true) - $start) * 1000; // ms
        
        // Alerta si el request tarda más de 1 segundo
        if ($duration > 1000) {
            Log::warning('Slow request detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'duration_ms' => round($duration, 2),
                'user_id' => auth()->id(),
            ]);
        }
        
        return $response;
    }
}
```

**Registro en Kernel.php:**
```php
protected $middlewareGroups = [
    'web' => [
        // ...
        \App\Http\Middleware\PerformanceMonitor::class,
    ],
];
```

---

## 🎯 Checklist de Implementación

### Fase 1 - Inmediato (Esta semana)
- [x] Cache de Laravel (routes, config, views)
- [x] Cache en HandleInertiaRequests
- [x] Cache en misSolicitudes()
- [x] Cache en misNotificaciones()
- [ ] **PENDIENTE:** Invalidación de cache (5 lugares en SolicitudMentoriaController)

### Fase 2 - Corto Plazo (Próximas 2 semanas)
- [ ] Crear migration con indexes
- [ ] Ejecutar migration en desarrollo
- [ ] Optimizar queries con select específico
- [ ] Implementar lazy loading en dashboard
- [ ] Testing de performance antes/después

### Fase 3 - Largo Plazo (Próximo mes)
- [ ] Instalar Laravel Telescope
- [ ] Configurar RedisInsight
- [ ] Implementar PerformanceMonitor middleware
- [ ] Establecer métricas baseline
- [ ] Configurar alertas de Slack/Email

---

## 📊 Métricas Objetivo

| Acción | Actual | Fase 1 | Fase 2 | Fase 3 |
|--------|--------|--------|--------|--------|
| Navegar Dashboard → Solicitudes | ~800ms | ~200ms | ~80ms | ~50ms |
| Navegar Dashboard → Notificaciones | ~600ms | ~150ms | ~50ms | ~30ms |
| Cambiar tab en Solicitudes | ~400ms | ~100ms | ~30ms | ~20ms |
| Marcar notificación leída | ~300ms | ~300ms | ~150ms | ~100ms |
| Cache Hit Rate | 0% | 70% | 85% | 90%+ |

---

## 🔧 Comandos de Mantenimiento

### Limpiar cache cuando sea necesario
```bash
# Limpiar todo el cache de Laravel
php artisan optimize:clear

# Solo cache de aplicación
php artisan cache:clear

# Solo cache de Redis
docker compose exec redis redis-cli FLUSHDB

# Recompilar cache
php artisan optimize
```

### Verificar estado de colas
```bash
# Ver trabajos en cola
php artisan queue:monitor

# Ver trabajos fallidos
php artisan queue:failed

# Reintentar trabajos fallidos
php artisan queue:retry all

# Limpiar trabajos fallidos
php artisan queue:flush
```

### Análisis de queries
```bash
# Ver queries lentas en logs
tail -f storage/logs/laravel.log | grep "Slow query"

# Ver queries en Debugbar
# Acceder a la app y revisar la barra inferior
```

---

## 📝 Notas Importantes

1. **Cache TTL (Time To Live):**
   - Notificaciones: 30-60 segundos (datos cambian frecuentemente)
   - Solicitudes: 2 minutos (cambios menos frecuentes)
   - Perfil: 5 minutos (datos casi estáticos)
   - Sugerencias de mentores: 10 minutos (datos estáticos)

2. **Invalidación de Cache:**
   - Siempre invalidar cache cuando los datos cambien
   - Usar `Cache::forget()` o `Cache::tags()->flush()`
   - Considerar usar Cache Tags para invalidación grupal

3. **Múltiples Sesiones:**
   - Cache compartido entre sesiones (Redis)
   - Sesiones no interfieren entre sí
   - Queue workers no duplican trabajos

4. **Testing:**
   - Probar con cache vacío
   - Probar con cache lleno
   - Verificar invalidación correcta
   - Medir tiempos antes/después

---

## 🚀 Próximos Pasos Inmediatos

1. **Aplicar invalidación de cache** (30 minutos)
   - Editar `SolicitudMentoriaController.php`
   - Añadir 5 líneas de `Cache::forget()`
   - Probar flujo completo

2. **Crear migration de indexes** (1 hora)
   - Ejecutar `php artisan make:migration`
   - Añadir índices a 3 tablas
   - Migrar en desarrollo

3. **Testing de performance** (30 minutos)
   - Medir tiempos con Debugbar
   - Documentar mejoras
   - Ajustar TTL si es necesario

---

**Última actualización:** 5 de noviembre de 2025  
**Responsable:** Equipo de Desarrollo  
**Prioridad:** Alta
