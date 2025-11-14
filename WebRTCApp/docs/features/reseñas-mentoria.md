# Reseñas de Mentoría (T3.7.3)

## Descripción General

El sistema de reseñas permite que estudiantes califiquen y comenten sobre sus mentores después de completar mentorías. Las reseñas son anónimas para el mentor, pero el sistema mantiene un registro del estudiante que realizó la reseña para evitar duplicados.

## Esquema de Base de Datos

### Tabla: `mentor_reviews`

```sql
CREATE TABLE mentor_reviews (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    mentor_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,  -- 1-5 estrellas
    comment TEXT NULLABLE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (mentor_id) REFERENCES mentors(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_mentor_user (mentor_id, user_id)
);
```

### Decisión de Diseño: Constraint UNIQUE

La restricción `UNIQUE (mentor_id, user_id)` garantiza que:
- Un estudiante solo puede tener **una reseña por mentor**
- Si intenta crear una segunda, se usa `updateOrCreate()` para actualizar la existente
- Previene acumulación de reseñas duplicadas
- Mantiene integridad referencial: si se elimina el mentor, se eliminan automáticamente sus reseñas

## Endpoints

### 1. Crear o Actualizar Reseña

**Ruta:** `POST /mentor/{mentor_id}/reviews`

**Middleware:** `auth`, `role:student`

**Payload:**
```json
{
  "rating": 5,
  "comment": "Excelente mentor, muy atento y profesional. Me ayudó muchísimo con mis dudas sobre React."
}
```

**Respuesta (201/200):**
```json
{
  "id": 42,
  "rating": 5,
  "comment": "Excelente mentor, muy atento y profesional...",
  "created_at": "2025-11-14T18:30:00Z",
  "updated_at": "2025-11-14T18:30:00Z"
}
```

**Errores:**
- `401`: No autenticado
- `403`: No es estudiante
- `404`: Mentor no existe
- `422`: Validación fallida (ej: rating fuera de rango 1-5)

---

### 2. Obtener Reseñas de un Mentor

**Ruta:** `GET /mentor/{mentor_id}/reviews` *(Inertia/Frontend)*

**Respuesta:**
```json
{
  "mentor": {
    "id": 7,
    "name": "Juan Pérez",
    "calificacionPromedio": 4.6,
    "totalReviewCount": 5
  },
  "reviews": [
    {
      "id": 1,
      "rating": 5,
      "comment": "Muy buen mentor...",
      "created_at": "2025-11-10T14:20:00Z"
    },
    {
      "id": 2,
      "rating": 4,
      "comment": "Buena atención...",
      "created_at": "2025-11-09T10:15:00Z"
    }
  ]
}
```

**Nota:** Las reseñas no incluyen información del remitente (estudiante) para garantizar anonimato.

---

### 3. Obtener Reseña del Estudiante Actual

**Ruta:** `GET /mentor/{mentor_id}/my-review`

**Middleware:** `auth`, `role:student`

**Respuesta:**
```json
{
  "review": {
    "id": 42,
    "rating": 5,
    "comment": "Excelente mentor...",
    "created_at": "2025-11-14T18:30:00Z",
    "updated_at": "2025-11-14T18:30:00Z",
    "canEdit": true,
    "canDelete": false
  }
}
```

---

## Modelos y Relaciones

### Model: `MentorReview`

```php
namespace App\Models;

class MentorReview extends Model
{
    protected $fillable = ['mentor_id', 'user_id', 'rating', 'comment'];
    
    protected $casts = [
        'rating' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function mentor()
    {
        return $this->belongsTo(Mentor::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scope: Solo reseñas aprobadas (todas son válidas por defecto)
    public function scopeApproved($query)
    {
        return $query->whereNotNull('id'); // Todas las reseñas son válidas
    }

    // Anonimizar: retornar reseña sin datos del usuario
    public function toAnonymous()
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'created_at' => $this->created_at,
        ];
    }
}
```

### Model: `Mentor`

```php
class Mentor extends Model
{
    // Relación: Un mentor tiene muchas reseñas
    public function reviews()
    {
        return $this->hasMany(MentorReview::class);
    }

    // Calcular calificación promedio
    public function updateAverageRating()
    {
        $average = $this->reviews()
            ->avg('rating') ?? 0;
        
        $this->update(['calificacionPromedio' => $average]);
    }
}
```

---

## Comportamiento del Sistema

### Validaciones

```php
$validated = $request->validate([
    'rating' => 'required|integer|min:1|max:5',
    'comment' => 'nullable|string|max:1000',
]);
```

**Reglas:**
- `rating`: Obligatorio, entero entre 1 y 5
- `comment`: Opcional, máximo 1000 caracteres
- Solo estudiantes autenticados pueden crear reseñas
- Un estudiante solo puede tener 1 reseña por mentor

### UpdateOrCreate (Upsert)

Cuando un estudiante intenta crear una reseña para un mentor al que ya reseñó:

```php
$review = MentorReview::updateOrCreate(
    ['mentor_id' => $mentorId, 'user_id' => $userId],  // WHERE
    ['rating' => $data['rating'], 'comment' => $data['comment']]  // UPDATE/INSERT
);
```

**Resultado:**
- Si no existe → Crea nueva reseña (INSERT)
- Si existe → Actualiza rating y comment (UPDATE)
- El `updated_at` se actualiza automáticamente

### Anonimización del Remitente

Cuando se retornan reseñas públicamente:

```php
$anonymousReviews = $mentor->reviews()
    ->select('id', 'rating', 'comment', 'created_at')
    ->get()
    ->map(fn($r) => $r->toAnonymous());
```

**Información que VE el mentor:**
- Rating
- Comentario
- Fecha de creación
- ❌ Nombre del estudiante
- ❌ Email del estudiante
- ❌ ID del estudiante

**Información que VE el estudiante (su propia reseña):**
- Todo lo anterior PLUS
- ✓ Confirmación de que es su reseña
- ✓ Botones de editar/eliminar

### Observer: Recalcular Promedio

Se usa un Observer para actualizar automáticamente `calificacionPromedio` del mentor:

```php
namespace App\Observers;

class MentorReviewObserver
{
    public function created(MentorReview $review)
    {
        $review->mentor->updateAverageRating();
    }

    public function updated(MentorReview $review)
    {
        $review->mentor->updateAverageRating();
    }

    public function deleted(MentorReview $review)
    {
        $review->mentor->updateAverageRating();
    }
}
```

**Flujo:**
1. Estudiante crea/actualiza/elimina reseña
2. Observer detecta el evento
3. Recalcula `avg(rating)` de TODAS las reseñas del mentor
4. Actualiza `mentors.calificacionPromedio`

---

## Cómo Probar Localmente

### 1. Inicializar la Base de Datos

```bash
docker compose exec app php artisan migrate
```

Verifica que se creó la tabla `mentor_reviews`:
```bash
docker compose exec app php artisan tinker
>>> \DB::select("DESCRIBE mentor_reviews");
```

### 2. Crear Datos de Prueba

```bash
docker compose exec app php artisan tinker
```

```php
// Crear estudiante
$student = \App\Models\User::factory()->student()->create([
    'email' => 'student@test.com',
    'name' => 'Ana García'
]);

// Crear mentor
$mentor = \App\Models\User::factory()->mentor()->create([
    'email' => 'mentor@test.com',
    'name' => 'Carlos López'
]);

$mentorProfile = \App\Models\Mentor::factory()->for($mentor)->create([
    'cv_verified' => true,
    'disponible_ahora' => true
]);

// Crear mentoría completada
$mentoria = \App\Models\Mentoria::factory()->for($student, 'aprendiz')->for($mentor, 'mentor')->create([
    'estado' => 'completada',
    'fecha' => now()->subDay()->toDateString(),
    'hora' => '14:00'
]);

// Verificar
$student->id;        // ej: 1
$mentorProfile->id;  // ej: 5
```

### 3. Crear una Reseña (API)

```bash
# Terminal 1: Iniciar app
docker compose exec app php artisan tinker

# Terminal 2: Hacer request con curl
curl -X POST http://localhost:8000/mentor/5/reviews \
  -H "Content-Type: application/json" \
  -H "Cookie: XSRF-TOKEN=...; laravel_session=..." \
  -d '{
    "rating": 5,
    "comment": "Excelente mentor, muy atento y profesional"
  }'
```

**Alternativa (más fácil): Usar Postman o REST Client de VS Code**

1. Autenticarse como estudiante:
   ```
   POST http://localhost:8000/login
   email: student@test.com
   password: password
   ```

2. Crear reseña:
   ```
   POST http://localhost:8000/mentor/5/reviews
   {
     "rating": 5,
     "comment": "Excelente mentor"
   }
   ```

### 4. Verificar en Base de Datos

```bash
docker compose exec app php artisan tinker
>>> \App\Models\MentorReview::all();
>>> \App\Models\Mentor::find(5)->calificacionPromedio;
```

### 5. Ejecutar Tests

```bash
# Tests de MentorReview
docker compose exec app ./vendor/bin/phpunit tests/Unit/MentorReviewModelTest.php
docker compose exec app ./vendor/bin/phpunit tests/Unit/MentorReviewObserverTest.php
docker compose exec app ./vendor/bin/phpunit tests/Feature/MentorReviewFeatureTest.php

# Todos los tests
docker compose exec app php artisan test
```

---

## Decisiones Técnicas

### 1. ¿Por qué `updateOrCreate` en lugar de `create`?

- **Problema:** Un estudiante puede intentar reseñar el mismo mentor dos veces
- **Solución:** `updateOrCreate` automáticamente actualiza si ya existe
- **Beneficio:** No tira error 409 Conflict, sino que silenciosamente actualiza
- **Alternativa rechazada:** `firstOrCreate` requería lógica de actualización manual

### 2. ¿Por qué anonimizar?

- **Privacidad:** El mentor no ve quién lo reseñó
- **Honestidad:** Los estudiantes escriben reseñas más sinceras sin temor a represalias
- **Equidad:** Evita favoritismo o influencia personal

### 3. ¿Por qué Unique Constraint en DB?

- **Integridad:** Imposible tener duplicados a nivel base de datos
- **Performance:** La BD rechaza directamente intentos de inserción duplicate
- **Seguridad:** Protege contra bugs en la aplicación

### 4. ¿Por qué Observer?

- **Separación de responsabilidades:** La lógica de recalcular promedio está aislada
- **Consistencia:** Se ejecuta automáticamente en create/update/delete
- **Mantenibilidad:** Si cambia la fórmula de promedio, solo se modifica en 1 lugar

---

## Casos de Uso

### Caso 1: Crear primera reseña

```
Usuario: Estudiante "Ana"
Acción:  POST /mentor/5/reviews { rating: 5, comment: "Excelente" }
Resultado:
  - Crea MentorReview (id=1, mentor_id=5, user_id=1, rating=5)
  - Mentor.calificacionPromedio = 5.0
  - Retorna 201 Created
```

### Caso 2: Actualizar reseña existente

```
Usuario: Estudiante "Ana" (reseña anterior: rating=5)
Acción:  POST /mentor/5/reviews { rating: 4, comment: "Muy bueno" }
Resultado:
  - Actualiza MentorReview (id=1, rating: 5→4, comment: "Muy bueno")
  - Mentor.calificacionPromedio = 4.0
  - Retorna 200 OK (no 201)
```

### Caso 3: Múltiples estudiantes, promedio correcto

```
Mentor 5 tiene reseñas:
  - Ana: rating=5
  - Bob: rating=3
  - Carol: rating=4
Mentor.calificacionPromedio = (5+3+4)/3 = 4.0
```

### Caso 4: Eliminación de reseña recalcula promedio

```
Usuario: Estudiante "Ana"
Acción:  DELETE /mentor/5/reviews/1
Resultado:
  - Elimina MentorReview id=1 (rating=5)
  - Mentor.calificacionPromedio = (3+4)/2 = 3.5
```

---

## Stack Técnico

| Componente | Tecnología |
|-----------|-----------|
| Modelo ORM | Laravel Eloquent |
| Base de Datos | MySQL 8.0 |
| Validación | Laravel Validation |
| Observadores | Laravel Model Observers |
| Tests | PHPUnit 12 |
| Frontend | React + Inertia.js |

---

## Referencias

- **Modelo:** `app/Models/MentorReview.php`
- **Observer:** `app/Observers/MentorReviewObserver.php`
- **Rutas:** `routes/web.php` (rutas de reseñas)
- **Tests Feature:** `tests/Feature/MentorReviewFeatureTest.php`
- **Tests Unit:** `tests/Unit/MentorReviewModelTest.php`, `tests/Unit/MentorReviewObserverTest.php`
- **Factory:** `database/factories/MentorReviewFactory.php`

---

## Checklist para QA

- [ ] Estudiante puede crear reseña después de mentoría completada
- [ ] Rating solo acepta valores 1-5
- [ ] Comentario es opcional y máximo 1000 caracteres
- [ ] Un estudiante no puede tener 2 reseñas para el mismo mentor (actualiza la existente)
- [ ] `mentors.calificacionPromedio` se actualiza correctamente
- [ ] Las reseñas son anónimas para el mentor (no ve name/email del remitente)
- [ ] El estudiante ve su propia reseña con opción de editar/eliminar
- [ ] Al eliminar reseña, el promedio se recalcula
- [ ] Si se elimina el mentor, se eliminan automáticamente sus reseñas
- [ ] Todos los tests pasan (`php artisan test`)

---

**Versión:** 1.0  
**Última actualización:** 2025-11-14  
**Estado:** Completado y testeado
