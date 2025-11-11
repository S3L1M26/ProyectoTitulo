# Sistema de Conclusión de Mentorías

## 📋 Descripción General

Este documento describe la implementación del sistema de conclusión de mentorías y la regla de negocio que previene solicitudes duplicadas mientras hay una mentoría activa con el mismo mentor.

## 🎯 Objetivos

1. **Prevenir spam de solicitudes**: Un estudiante solo puede tener una mentoría activa por mentor
2. **Permitir mentorías recurrentes**: Una vez concluida, el estudiante puede solicitar nuevamente
3. **Control del mentor**: Solo el mentor puede marcar una mentoría como concluida
4. **Feedback claro al usuario**: Mensajes informativos sobre el estado de las mentorías
5. **Testing rápido**: Permitir crear sesiones el mismo día (mínimo 1 minuto en el futuro)

## ⚙️ Configuración de Validación

### Reglas de Fecha y Hora

La validación permite crear mentorías con las siguientes restricciones:

- ✅ **Fecha mínima**: Hoy (mismo día)
- ✅ **Hora**: Cualquier hora válida (validación en controlador con timezone)
- ❌ **No permitido**: Fechas pasadas (validado por `after_or_equal:today`)
- ❌ **No permitido**: Hora pasada en el momento de confirmación (validado con `isPast()`)

**Ejemplo válido**: Puedes programar para cualquier hora del día actual, siempre que no sea en el pasado.

**Implementación**:

La validación se divide en dos niveles:

1. **FormRequest** (`ConfirmarMentoriaRequest.php`):
   - Valida formato de fecha y hora
   - Previene fechas pasadas a nivel de día (`after_or_equal:today`)
   - No valida timezone (evita falsos positivos)

2. **Controlador** (`MentoriaController.php`):
   - Considera timezone del usuario o del servidor
   - Valida con `isPast()` para prevenir horas pasadas
   - Manejo de errores con Inertia (`back()->withErrors()`)

```php
// En el controlador
$tz = $request->input('timezone', config('app.timezone', 'UTC'));
$start = Carbon::createFromFormat('Y-m-d H:i', $request->string('fecha') . ' ' . $request->string('hora'), $tz);

if ($start->isPast()) {
    return back()->withErrors(['hora' => 'La fecha/hora no puede ser en el pasado.'])->withInput();
}
```

**Nota**: La validación en el FormRequest fue simplificada para evitar problemas de timezone. La validación definitiva se hace en el controlador donde tenemos acceso completo al contexto.

## 🏗️ Arquitectura

### Backend

#### 1. Modelo: SolicitudMentoria.php
```php
// Método estático para verificar mentorías activas
public static function tieneMentoriaActivaConMentor($estudianteId, $mentorId)
{
    return Mentoria::where('aprendiz_id', $estudianteId)
        ->where('mentor_id', $mentorId)
        ->where('estado', 'confirmada')
        ->exists();
}
```

#### 2. Controlador: SolicitudMentoriaController.php

**Validación en solicitud de mentoría:**
```php
// En método store()
if (SolicitudMentoria::tieneMentoriaActivaConMentor($aprendiz->id, $request->mentor_id)) {
    throw ValidationException::withMessages([
        'mentor_id' => 'Ya tienes una mentoría activa con este mentor. Solo puedes tener una mentoría activa por mentor.'
    ]);
}
```

**API endpoint para verificación:**
```php
// GET /api/aprendiz/{aprendizId}/has-active-mentoria/{mentorId}
public function hasActiveMentoria($aprendizId, $mentorId)
{
    $hasActive = SolicitudMentoria::tieneMentoriaActivaConMentor($aprendizId, $mentorId);
    return response()->json(['hasActiveMentoria' => $hasActive]);
}
```

**Invalidación de caché:**
```php
// Al aceptar/rechazar solicitud
Cache::forget("mentor_solicitudes_{$solicitud->mentor_id}");
Cache::forget("mentor_pending_solicitudes_{$solicitud->mentor_id}");
```

#### 3. Controlador: MentoriaController.php

**Método para concluir mentoría:**
```php
// POST /mentor/mentorias/{id}/concluir
public function concluir($id)
{
    $mentoria = Mentoria::findOrFail($id);
    
    // Verificar que el usuario autenticado sea el mentor
    if ($mentoria->mentor_id !== auth()->id()) {
        return back()->with('error', 'No tienes permiso para concluir esta mentoría.');
    }
    
    // Actualizar estado
    $mentoria->update(['estado' => 'completada']);
    
    // Invalidar cachés relevantes
    Cache::forget("mentor_solicitudes_{$mentoria->mentor_id}");
    Cache::forget("student_mentorias_{$mentoria->aprendiz_id}");
    Cache::forget("mentor_pending_solicitudes_{$mentoria->mentor_id}");
    
    return back()->with('success', 'La mentoría ha sido marcada como concluida exitosamente.');
}
```

#### 4. Rutas: routes/web.php
```php
// Ruta para concluir mentoría (Inertia)
Route::post('/mentor/mentorias/{id}/concluir', [MentoriaController::class, 'concluir'])
    ->name('mentor.mentorias.concluir');

// API para verificar mentoría activa
Route::get('/api/aprendiz/{aprendizId}/has-active-mentoria/{mentorId}', 
    [SolicitudMentoriaController::class, 'hasActiveMentoria']);
```

### Frontend

#### 1. MentoriaCard.jsx

**Estados:**
```javascript
const [showConcluirModal, setShowConcluirModal] = useState(false);
const [concluyendo, setConcluyendo] = useState(false);
```

**Handler de conclusión:**
```javascript
const handleConcluirMentoria = () => {
    setConcluyendo(true);
    router.post(route('mentor.mentorias.concluir', mentoria.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            setShowConcluirModal(false);
            toast.success('✅ Mentoría concluida exitosamente');
        },
        onError: (errors) => {
            toast.error(errors.error || '❌ Error al concluir la mentoría');
        },
        onFinish: () => {
            setConcluyendo(false);
        }
    });
};
```

**Botón de conclusión:**
```jsx
{/* Solo para mentores con mentorías confirmadas */}
{esParaMentor && mentoria.estado === 'confirmada' && (
    <button
        onClick={() => setShowConcluirModal(true)}
        className="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition-colors"
    >
        ✅ Concluir Mentoría
    </button>
)}
```

**Modal de confirmación:**
```jsx
{showConcluirModal && (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div className="bg-white rounded-lg shadow-xl p-6 max-w-md mx-4">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">
                ✅ ¿Concluir mentoría?
            </h3>
            <p className="text-gray-600 mb-6">
                ¿Estás seguro de que deseas marcar esta mentoría con <strong>{nombreOtro}</strong> como concluida?
                <br /><br />
                Al concluir, el estudiante podrá solicitar una nueva sesión contigo.
            </p>
            {/* Botones de acción */}
        </div>
    </div>
)}
```

#### 2. MentorDetailModal.jsx

**Estados y efecto:**
```javascript
const [hasActiveMentoria, setHasActiveMentoria] = useState(false);
const [checkingActiveMentoria, setCheckingActiveMentoria] = useState(false);

useEffect(() => {
    if (isOpen && mentor && aprendiz) {
        setCheckingActiveMentoria(true);
        axios.get(`/api/aprendiz/${aprendiz.id}/has-active-mentoria/${mentor.id}`)
            .then(response => {
                setHasActiveMentoria(response.data.hasActiveMentoria);
            })
            .catch(error => {
                console.error('Error checking active mentoria:', error);
                setHasActiveMentoria(false);
            })
            .finally(() => {
                setCheckingActiveMentoria(false);
            });
    }
}, [isOpen, mentor, aprendiz]);
```

**Mensaje informativo:**
```jsx
{hasActiveMentoria && (
    <div className="mb-4 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <div className="flex items-start">
            <svg className="w-5 h-5 text-yellow-600 mr-2 mt-0.5 flex-shrink-0">...</svg>
            <div>
                <h5 className="font-semibold text-yellow-800 mb-1">
                    Ya tienes una mentoría activa con este mentor
                </h5>
                <p className="text-sm text-yellow-700">
                    Solo puedes tener una mentoría activa por mentor. Una vez que el mentor 
                    marque la mentoría actual como concluida, podrás solicitar una nueva sesión.
                </p>
            </div>
        </div>
    </div>
)}
```

**Botón deshabilitado:**
```jsx
<button
    onClick={() => setIsSolicitudFormOpen(true)}
    disabled={mentor.mentor.disponible_ahora != 1 || hasActiveMentoria || checkingActiveMentoria}
    className={`... ${
        mentor.mentor.disponible_ahora == 1 && !hasActiveMentoria && !checkingActiveMentoria
            ? 'bg-blue-600 text-white hover:bg-blue-700'
            : 'bg-gray-300 text-gray-500 cursor-not-allowed'
    }`}
    title={hasActiveMentoria ? 'Ya tienes una mentoría activa con este mentor' : '...'}
>
    {checkingActiveMentoria ? 'Verificando...' : 
     (hasActiveMentoria ? 'Mentoría Activa' : 
     (mentor.mentor.disponible_ahora == 1 ? 'Solicitar Mentoría' : 'No Disponible'))}
</button>
```

## 🔄 Flujo de Usuario

### Escenario 1: Primera Solicitud
1. Estudiante selecciona un mentor
2. Sistema verifica: no hay mentoría activa ✅
3. Estudiante envía solicitud
4. Mentor acepta y confirma
5. Mentoría queda en estado `confirmada`
6. Botón "Solicitar Mentoría" se deshabilita para ese mentor

### Escenario 2: Intento de Solicitud Duplicada
1. Estudiante intenta solicitar al mismo mentor
2. Sistema detecta mentoría activa ❌
3. Modal muestra mensaje informativo en amarillo
4. Botón "Solicitar Mentoría" deshabilitado
5. Tooltip explica: "Ya tienes una mentoría activa con este mentor"

### Escenario 3: Conclusión y Nueva Solicitud
1. Mentor completa la sesión
2. Mentor presiona "Concluir Mentoría"
3. Modal de confirmación aparece
4. Mentor confirma
5. Estado cambia a `completada`
6. Cachés se invalidan
7. Estudiante puede solicitar nuevamente ✅

## 🗄️ Estados de Mentoría

| Estado | Descripción | Puede solicitar de nuevo |
|--------|-------------|--------------------------|
| `confirmada` | Mentoría activa y programada | ❌ No |
| `completada` | Sesión finalizada exitosamente | ✅ Sí |
| `cancelada` | Mentoría cancelada | ✅ Sí |

## 🔧 Invalidación de Caché

### Claves de caché afectadas:
- `mentor_solicitudes_{mentor_id}` - Lista de solicitudes del mentor
- `mentor_pending_solicitudes_{mentor_id}` - Contador para badge de notificaciones
- `student_mentorias_{aprendiz_id}` - Lista de mentorías del estudiante

### Operaciones que invalidan caché:
1. Aceptar solicitud
2. Rechazar solicitud
3. Confirmar mentoría
4. Cancelar mentoría
5. **Concluir mentoría** ⭐

## 🧪 Testing

### Prueba Manual Completa

```bash
# 1. Login como estudiante
# 2. Solicitar mentoría a un mentor
# 3. Verificar que no puedes solicitar de nuevo (botón deshabilitado + mensaje)
# 4. Login como mentor
# 5. Aceptar solicitud
# 6. Confirmar mentoría
# 7. Verificar botón "Concluir Mentoría" visible
# 8. Concluir mentoría
# 9. Login como estudiante
# 10. Verificar que ahora puedes solicitar de nuevo ✅
```

### Casos de Prueba

#### Test 1: Prevención de duplicados
- ✅ No permite solicitar si hay mentoría `confirmada`
- ✅ Muestra mensaje informativo claro
- ✅ Botón deshabilitado con tooltip

#### Test 2: Conclusión por mentor
- ✅ Solo mentor propietario puede concluir
- ✅ Modal de confirmación funciona
- ✅ Toast de éxito se muestra
- ✅ Estado cambia a `completada`

#### Test 3: Habilitación post-conclusión
- ✅ API `/has-active-mentoria` devuelve `false`
- ✅ Botón "Solicitar Mentoría" se habilita
- ✅ Nueva solicitud se procesa correctamente

#### Test 4: Cache invalidation
- ✅ Badge de notificaciones se actualiza
- ✅ Lista de mentorías se refresca
- ✅ No hay datos obsoletos

## 📊 Mejoras Futuras

1. **Historial de mentorías**: Vista para ver todas las mentorías completadas
2. **Ratings post-conclusión**: Permitir calificar después de concluir
3. **Recordatorios de conclusión**: Notificar al mentor después de la fecha programada
4. **Analytics**: Métricas de tasa de conclusión por mentor
5. **Reactivación**: Permitir reactivar mentorías canceladas

## 🐛 Troubleshooting

### Problema: Botón sigue deshabilitado después de concluir
**Solución**: Verificar que los cachés se están invalidando correctamente
```php
Cache::forget("mentor_pending_solicitudes_{$mentoria->mentor_id}");
```

### Problema: Modal no aparece al presionar "Concluir"
**Solución**: Verificar estado `showConcluirModal` y que el evento `onClick` esté funcionando

### Problema: Error 403 al concluir
**Solución**: Verificar que el usuario autenticado sea el mentor propietario
```php
if ($mentoria->mentor_id !== auth()->id()) {
    return back()->with('error', 'No tienes permiso...');
}
```

### Problema: API devuelve siempre `true`
**Solución**: Verificar que la consulta use `aprendiz_id` y no `estudiante_id`
```php
->where('aprendiz_id', $estudianteId) // ✅ Correcto
->where('estudiante_id', $estudianteId) // ❌ Incorrecto
```

## 📝 Notas de Implementación

1. **Constraint de Inertia**: Los endpoints regulares (no `/api/*`) deben usar `back()` o `redirect()`, nunca `response()->json()`
2. **Relaciones Laravel**: `Mentoria->mentor` devuelve `User`, no `Mentor`
3. **Queue Worker**: Requiere reinicio después de cambios en Jobs
4. **React Suspense**: `MentorDetailModal` se carga con lazy loading para optimización

## 📚 Referencias

- [Documentación de Recordatorios](./RECORDATORIOS_MENTORIA.md)
- [Planning de Features](./planning/)
- [Testing Guidelines](./testing/)
