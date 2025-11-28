# Encuesta vocacional (backend)

## Flujo funcional
- Página: `GET /student/vocacional` (Inertia). Carga historial (últimos 10) y el último snapshot para el alumno autenticado (`StudentController::vocationalSurvey`).
- Guardado: `POST /student/vocacional` (`VocationalSurveyController::store`). Aplica validación, calcula el ICV y redirige de vuelta a la página con `success`.
- Historial API: `GET /api/student/vocational-surveys` devuelve todos los snapshots del alumno autenticado ordenados descendente (`VocationalSurveyController::index`).
- Último snapshot API: `GET /api/student/vocational-surveys/latest` devuelve solo el más reciente (`VocationalSurveyController::show`).
- Middleware: todas las rutas están bajo `auth`, `verified` y `role:student`.

## Validaciones del formulario
Campos requeridos (enteros entre 1 y 5):
- `clarity_interest`
- `confidence_area`
- `platform_usefulness`
- `mentorship_usefulness`

Opcional:
- `recent_change_reason` (`string|max:200`)

## Cálculo del ICV (Índice de Claridad Vocacional)
Fórmula en `VocationalSurveyController::store`:
```
icv = round((clarity_interest + confidence_area + platform_usefulness + mentorship_usefulness) / 4, 2)
```
Interpretación sugerida:
- `4.0 - 5.0`: alta claridad y confianza en las áreas de interés.
- `3.0 - 3.99`: claridad moderada; revisar motivos de cambio reciente si existe.
- `< 3.0`: claridad baja; sugerir acciones de acompañamiento/mentoría adicional.

## Persistencia y snapshot
Tabla `vocational_surveys`:
- `student_id` (FK a `users`)
- `clarity_interest`, `confidence_area`, `platform_usefulness`, `mentorship_usefulness` (TINYINT 1-5)
- `recent_change_reason` (nullable, 200 chars)
- `icv` (float con 2 decimales)
- `created_at` (usado para ordenar snapshots)

Cada envío crea un nuevo snapshot; el historial se ordena por `created_at` descendente. Los datos devueltos por los endpoints incluyen el `student_id` para permitir filtrado/seguridad en cache o tests.

## Operativa para desarrolladores
- Migrar estructura: `php artisan migrate --path=database/migrations/2025_11_27_000300_create_vocational_surveys_table.php` (o `php artisan migrate` si ya está incluida en el batch).
- Poblar datos de prueba: usar `VocationalSurveyFactory` (incluye cálculo de `icv` coherente). Ejemplo en tinker:
  ```php
  $user = \App\Models\User::factory()->student()->create();
  \App\Models\VocationalSurvey::factory()->for($user, 'student')->create();
  ```
- Probar el formulario (backend): autenticarse como estudiante y enviar `POST /student/vocacional` con los campos anteriores; verificar redirección con flash `success`.
- Pruebas automáticas específicas:
  - Feature: `php artisan test tests/Feature/VocationalSurveyFeatureTest.php`
  - Unit: `php artisan test tests/Unit/VocationalSurveyICVTest.php`
  - Filtro global: `php artisan test --filter=VocationalSurvey`

Las pruebas cubren validaciones, cálculo de ICV, creación de snapshot y recuperación de historial/último registro sin depender de frontend.
