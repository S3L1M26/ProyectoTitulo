# Seeders Pendientes para Funcionalidad de Mentorías

Este documento lista los seeders que deben ser implementados una vez completada la funcionalidad de creación de reuniones Zoom.

---

## 📋 Lista de Seeders a Implementar

### 1. **MentoriaSeeder**

**Descripción:**  
Genera datos de prueba de mentorías con diferentes estados y configuraciones.

**Datos a generar:**
- 3-5 mentorías en estado `confirmada` (próximas)
- 2-3 mentorías en estado `completada` (pasadas)
- 1-2 mentorías en estado `cancelada`

**Distribución:**
- Mentorías para hoy (1-2)
- Mentorías para esta semana (2-3)
- Mentorías próximas (dentro de 30 días)
- Mentorías pasadas (últimos 30 días)

**Relaciones necesarias:**
- Debe usar usuarios existentes (mentores y aprendices del `UsersSeeder`)
- Debe vincular solicitudes existentes del `SolicitudMentoriaSeeder` (si existe)
- Alternativamente, crear solicitudes sobre la marcha

**Datos de Zoom:**
- Generar enlaces de Zoom ficticios con formato realista
- IDs de reunión numéricos (11 dígitos)
- Passwords alfanuméricos (6 caracteres)

**Ejemplo de uso:**
```php
// Mentoría confirmada próxima
Mentoria::factory()
    ->confirmada()
    ->proxima()
    ->create([
        'aprendiz_id' => User::where('role', 'student')->first()->id,
        'mentor_id' => User::where('role', 'mentor')->first()->id,
    ]);

// Mentoría completada con notas
Mentoria::factory()
    ->completada()
    ->create([
        'notas_mentor' => 'Excelente sesión, el estudiante mostró gran interés.',
        'notas_aprendiz' => 'Muy útil, aprendí mucho sobre React Hooks.',
    ]);

// Mentoría para hoy
Mentoria::factory()
    ->hoy()
    ->create();
```

**Consideraciones:**
- Asegurar que las fechas/horas no se solapen para el mismo mentor
- Validar que los aprendices tengan certificado verificado
- Validar que los mentores tengan CV verificado
- Generar duraciones variadas: 30, 45, 60, 90, 120 minutos

---

### 2. **ActualizarSolicitudesMentoriaSeeder** (Opcional)

**Descripción:**  
Actualiza solicitudes de mentoría existentes para vincularlas con mentorías creadas.

**Objetivo:**
- Cambiar estado de algunas solicitudes de `pendiente` a `aceptada`
- Asociar mentorías programadas a solicitudes aceptadas
- Mantener coherencia entre solicitud y mentoría

**Ejemplo:**
```php
$solicitud = SolicitudMentoria::pendientes()->first();
$solicitud->aceptar();

Mentoria::factory()->create([
    'solicitud_id' => $solicitud->id,
    'aprendiz_id' => $solicitud->estudiante_id,
    'mentor_id' => $solicitud->mentor_id,
]);
```

---

## 🎯 Criterios de Aceptación para Seeders

Una vez implementados, los seeders deben cumplir:

✅ **Integridad de datos:**
- Todas las relaciones (aprendiz, mentor, solicitud) deben existir
- No deben haber foreign key violations
- Estados deben ser coherentes con las fechas

✅ **Variedad de escenarios:**
- Al menos 3 estados diferentes (confirmada, completada, cancelada)
- Al menos 2 rangos de tiempo (pasadas, próximas)
- Al menos 2 duraciones diferentes

✅ **Datos realistas:**
- Enlaces de Zoom con formato correcto
- Fechas lógicas (completadas en el pasado, confirmadas en el futuro)
- Notas opcionales solo en mentorías completadas

✅ **Reusabilidad:**
- Seeders pueden ejecutarse múltiples veces sin errores
- Uso de `firstOrCreate()` o `truncate()` según corresponda

---

## 🚀 Comandos para Ejecutar Seeders

Una vez implementados:

```bash
# Ejecutar solo el seeder de mentorías
php artisan db:seed --class=MentoriaSeeder

# Ejecutar todos los seeders
php artisan db:seed

# Refrescar DB y seedear (CUIDADO: borra todos los datos)
php artisan migrate:fresh --seed
```

---

## 📊 Estado Actual de la Base de Datos

### Tablas relacionadas existentes:
- ✅ `users` (con roles mentor/student)
- ✅ `aprendices` (perfiles de estudiantes)
- ✅ `mentors` (perfiles de mentores)
- ✅ `solicitud_mentorias` (solicitudes de mentoría)
- ✅ `mentorias` (✨ NUEVA - creada en TASK-001)

### Seeders existentes:
- `UsersSeeder` - Crea usuarios de prueba
- `AreasInteresSeeder` - Crea áreas de interés
- `AprendizTestSeeder` - Crea perfiles de aprendices

---

## 📝 Notas de Implementación

### Dependencias entre seeders:
```
DatabaseSeeder
  ├─ UsersSeeder (debe ejecutarse primero)
  ├─ AreasInteresSeeder
  ├─ AprendizTestSeeder
  ├─ (SolicitudMentoriaSeeder - si existe)
  └─ MentoriaSeeder (debe ejecutarse al final)
```

### Factory disponible:
El `MentoriaFactory` ya está implementado con los siguientes estados:
- `confirmada()` - Estado por defecto
- `completada()` - Con fecha pasada y notas opcionales
- `cancelada()` - Mentoría cancelada
- `hoy()` - Programada para hoy
- `proxima()` - Dentro de los próximos 7 días
- `sinEnlace()` - Sin datos de Zoom (para flujo manual)

---

## ✅ Checklist de Implementación

- [ ] Crear `MentoriaSeeder.php`
- [ ] Implementar lógica de generación de datos
- [ ] Validar que no hay errores de foreign keys
- [ ] Probar con `php artisan db:seed --class=MentoriaSeeder`
- [ ] Verificar datos en DB con `php artisan tinker`
- [ ] Actualizar `DatabaseSeeder` para incluir nuevo seeder
- [ ] Documentar casos edge (opcional)

---

**Fecha de creación:** 2025-11-06  
**Relacionado con:** TASK-001, TASK-002  
**Estado:** Pendiente de implementación tras completar funcionalidad Zoom
