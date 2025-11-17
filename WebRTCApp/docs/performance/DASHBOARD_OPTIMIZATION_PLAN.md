# Dashboard Performance Optimization Plan

## 🎯 Objetivo
Reducir el tiempo de carga de los dashboards de estudiantes y mentores de **2-7 segundos a <500ms** para rutas críticas.

---

## ✅ FASE 1: INSTRUMENTACIÓN (COMPLETADO)

### 1.1 Slow Query Logging
**Archivos modificados:**
- `app/Providers/DatabaseQueryServiceProvider.php` (NUEVO)
- `app/Http/Middleware/PerformanceMonitoringMiddleware.php` (MEJORADO)
- `bootstrap/providers.php` (REGISTRADO)

**Funcionalidades:**
- ✅ Listener global para queries >300ms en `DatabaseQueryServiceProvider`
- ✅ Logs incluyen: SQL, bindings, tiempo, conexión, caller (archivo:línea)
- ✅ Middleware de performance mejorado con top 3 queries lentas por request
- ✅ Headers de debug: `X-Performance-Time`, `X-Performance-Queries`, `X-Performance-Memory`
- ✅ Solo activo en modo debug (`APP_DEBUG=true`)

**Logs generados:**
```
🐌 SLOW QUERY DETECTED - Query individual >300ms
⚠️ PERFORMANCE DEGRADATION DETECTED - Request completo con top 3 queries
```

---

## 📊 FASE 2: DIAGNÓSTICO (EN CURSO)

### 2.1 Rutas Críticas Identificadas
```php
// web.php - Dashboard endpoints
Route::get('/student/dashboard', [StudentController::class, 'index'])
    ->middleware(['role:student', 'performance']); // ← Threshold: 500ms

Route::get('/mentor/dashboard', [MentorController::class, 'index'])
    ->middleware(['role:mentor', 'performance']); // ← Threshold: 500ms
```

### 2.2 Código Actual Analizado

#### **StudentController@index**
**Optimizaciones existentes:**
- ✅ Cache en 2 niveles (120s + 600s) con Redis
- ✅ Eager loading de relaciones: `aprendiz.areasInteres`
- ✅ Join optimizado en lugar de whereHas
- ✅ Select específico de campos necesarios
- ✅ Distinct para evitar duplicados
- ✅ Limit 6 mentores

**Query principal:**
```php
User::select('users.id', 'users.name', 'mentors.calificacionPromedio')
    ->join('mentors', 'users.id', '=', 'mentors.user_id')
    ->join('mentor_area_interes', 'mentors.id', '=', 'mentor_area_interes.mentor_id')
    ->where('users.role', 'mentor')
    ->where('mentors.disponible_ahora', true)
    ->whereIn('mentor_area_interes.area_interes_id', $studentAreaIds)
    ->with(['mentor', 'mentor.areasInteres', 'mentorDocuments'])
    ->orderByDesc('mentors.calificacionPromedio')
    ->distinct()
    ->limit(6)
```

**Posibles N+1 queries:**
- `mentor.areasInteres` → Puede generar 1 query por mentor (6 queries adicionales)
- `mentorDocuments` → Filtro WHERE en eager load puede no usar índice

#### **MentorController@index**
**Optimizaciones existentes:**
- ✅ Lazy loading con Inertia (fn())
- ✅ Cache 300s (5 min) para solicitudes
- ✅ Eager loading: `estudiante`, `aprendiz.areasInteres`

**Query principal:**
```php
SolicitudMentoria::where('mentor_id', $user->id)
    ->with(['estudiante:id,name,email', 'aprendiz.areasInteres:id,nombre'])
    ->orderBy('fecha_solicitud', 'desc')
    ->get()
```

**Posibles N+1 queries:**
- `aprendiz.areasInteres` → 1 query por solicitud si hay múltiples solicitudes

---

## 🔍 FASE 3: ACCIONES PENDIENTES

### 3.1 Testing & Recolección de Datos
**PRÓXIMO PASO:**
```bash
# 1. Reiniciar Octane
php artisan octane:reload

# 2. Acceder a dashboards en navegador
- http://localhost/student/dashboard
- http://localhost/mentor/dashboard

# 3. Revisar logs
tail -f storage/logs/laravel.log | grep -E "🐌|⚠️"
```

**Datos a recolectar:**
- SQL de queries lentas (>300ms)
- Bindings y valores reales
- Cantidad total de queries por request
- Tiempo total de ejecución
- Archivo/línea que genera el query (caller)

### 3.2 Índices Verificados
**Tablas críticas con índices existentes:**
- `solicitud_mentorias`: `(mentor_id, estado, fecha_solicitud)`
- `mentorias`: `(mentor_id, estado, fecha)`
- `users`: `(role, id)`

**Índices potenciales a añadir (según logs):**
- `mentors(disponible_ahora, calificacionPromedio)` - Para filtro + orden
- `mentor_area_interes(area_interes_id, mentor_id)` - Para whereIn + join
- `mentor_documents(user_id, status, is_public)` - Para filtro WHERE en eager load

### 3.3 Optimizaciones de Código Planificadas

#### Opción A: Eliminar N+1 en areasInteres
```php
// StudentController - Reemplazar eager load anidado
->with(['mentor.areasInteres:id,nombre'])

// Por: Pre-cargar con join o subquery select
->addSelect([
    'areas' => DB::table('mentor_area_interes')
        ->join('areas_interes', ...)
        ->where('mentor_id', 'mentors.id')
        ->selectRaw('JSON_ARRAYAGG(JSON_OBJECT("id", id, "nombre", nombre))')
])
```

#### Opción B: Cache más granular
```php
// Cachear lista de mentores disponibles (todas las áreas)
// Filtrar en memoria según áreas del estudiante
$allMentors = Cache::remember('active_mentors_with_areas', 600, function() {
    return User::with('mentor.areasInteres')->where(...)->get();
});

$filtered = $allMentors->filter(fn($m) => 
    $m->mentor->areasInteres->pluck('id')->intersect($studentAreaIds)->isNotEmpty()
);
```

#### Opción C: Eager load constraints
```php
// En lugar de cargar todas las areasInteres
->with(['mentor.areasInteres' => function($q) use ($studentAreaIds) {
    $q->whereIn('id', $studentAreaIds); // Solo áreas relevantes
}])
```

---

## 📈 MÉTRICAS DE ÉXITO

### Thresholds Actuales (PerformanceMonitoringMiddleware)
```php
'execution_time' => $isCriticalRoute ? 500 : 1000, // ms
'query_count' => $isCriticalRoute ? 5 : 10,
'memory_usage' => 50 * 1024 * 1024 // 50MB
```

### Objetivos Post-Optimización
- ✅ Dashboard de estudiante: <500ms, <5 queries
- ✅ Dashboard de mentor: <500ms, <5 queries
- ✅ Sin queries N+1 detectadas en logs
- ✅ Cache hit rate >80% (monitorear con Redis stats)

---

## 🔧 COMANDOS ÚTILES

```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log | grep -E "🐌|⚠️"

# Limpiar cache Redis (si necesitas testing limpio)
php artisan cache:clear
redis-cli FLUSHDB

# Ver queries en detalle (habilitar en .env)
APP_LOG_ALL_QUERIES=true  # Agregar esta variable

# Reiniciar Octane tras cambios
php artisan octane:reload

# Ver performance headers en curl
curl -I http://localhost/student/dashboard \
  -H "Cookie: ..." \
  | grep X-Performance
```

---

## 🚨 LOGS RETENIDOS

**Backend logs conservados según instrucción del usuario:**
- ✅ `🔍 EVENT LISTENER COUNT` (EventServiceProvider)
- ✅ `🔔 LISTENER EJECUTADO` (EnviarNotificacionMentoriaConfirmada)
- ✅ `⛔ LISTENER DUPLICATE SKIP` (idempotency guard)
- ✅ `📨 JOB ENCOLADO` (listener dispatch)
- ✅ `🚀 JOB START` (EnviarCorreoMentoria)
- ✅ `✅ JOB SENT EMAIL` (email enviado)
- ✅ `🐌 SLOW QUERY DETECTED` (NUEVO)
- ✅ `⚠️ PERFORMANCE DEGRADATION DETECTED` (NUEVO)

**Frontend logs eliminados:**
- ✅ Removidos console.log de ConfirmarMentoriaModal.jsx
- ✅ Removidos de MentorDetailModal, Register, Users, UpdateMentorProfile, app.jsx
- ✅ Toasts y alerts preservados para UX

---

## 📝 NOTAS TÉCNICAS

### Idempotency Guards (No tocar)
```php
// Controller: mentoria_confirmada_{cid} - TTL 120s
// Listener: mentoria_listener_lock_{mentoria_id}_{cid} - TTL 120s (ajustado)
```

### Redis Cache Keys
```php
'mentor_suggestions_' . md5($studentAreaIds)  // TTL 120s
'mentor_pool_' . md5($studentAreaIds)         // TTL 600s
'mentor_solicitudes_' . $userId               // TTL 300s
```

### Archivos Críticos
```
app/Http/Controllers/Student/StudentController.php
app/Http/Controllers/Mentor/MentorController.php
app/Http/Middleware/PerformanceMonitoringMiddleware.php
app/Providers/DatabaseQueryServiceProvider.php
routes/web.php
```

---

## 🎬 SIGUIENTE SESIÓN

1. **Analizar logs** de queries lentas tras reload
2. **Identificar hot spots** específicos (N+1, falta de índices, joins ineficientes)
3. **Implementar optimizaciones** iterativas:
   - Añadir índices compuestos
   - Refactorizar eager loads problemáticos
   - Ajustar TTLs de cache según patrones de uso
4. **Validar mejoras** con headers X-Performance y logs
5. **Documentar cambios** para mantenimiento futuro
