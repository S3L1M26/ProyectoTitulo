# 📋 Plan de Implementación - Feature Tests (Pruebas de Integración)

## 🎯 **OBJETIVO**

Complementar los **Unit Tests** existentes con **Feature Tests** que prueben flujos completos de la aplicación, incluyendo interacciones con base de datos, relaciones Eloquent y endpoints HTTP.

---

## 📊 **DIFERENCIAS: UNIT vs FEATURE TESTS**

| Aspecto | **Unit Tests** (tests/Unit/) | **Feature Tests** (tests/Feature/) |
|---------|------------------------------|-------------------------------------|
| **Scope** | Métodos individuales aislados | Flujos completos E2E |
| **Base de Datos** | ❌ NO usa BD | ✅ SÍ usa BD (SQLite en memoria) |
| **HTTP Requests** | ❌ NO | ✅ SÍ (`$this->get()`, `$this->post()`) |
| **Relaciones Eloquent** | ❌ Mockeadas | ✅ Reales con `RefreshDatabase` |
| **Velocidad** | ⚡ ~0.2s/test | 🐌 ~2-5s/test |
| **Propósito** | Lógica de negocio | Integración de componentes |
| **Ejemplo** | `new User()` | `User::factory()->create()` |

---

## 🏗️ **ESTRUCTURA PROPUESTA**

```
tests/Feature/
├── Models/
│   ├── UserCompletenessTest.php          📝 NUEVO - Tests de completeness con BD
│   ├── MentorRelationshipsTest.php       📝 NUEVO - Tests de relaciones Mentor
│   └── AprendizAreaInteresTest.php       📝 NUEVO - Tests de relaciones M2M
│
├── Controllers/
│   ├── StudentControllerIntegrationTest.php   📝 NUEVO - Tests E2E de búsqueda
│   ├── MentorControllerTest.php              📝 NUEVO - Tests CRUD de mentores
│   └── ProfileControllerIntegrationTest.php   📝 NUEVO - Tests de actualización
│
├── Jobs/
│   └── SendProfileReminderJobIntegrationTest.php  📝 NUEVO - Tests con BD real
│
└── Auth/  (YA EXISTEN - 6 archivos creados por Laravel Breeze)
    ├── RegistrationTest.php              ✅ EXISTENTE
    ├── AuthenticationTest.php            ✅ EXISTENTE
    ├── EmailVerificationTest.php         ✅ EXISTENTE
    ├── PasswordResetTest.php             ✅ EXISTENTE
    ├── PasswordUpdateTest.php            ✅ EXISTENTE
    └── PasswordConfirmationTest.php      ✅ EXISTENTE
```

---

## 📝 **FEATURE TESTS A CREAR**

### **🔥 PRIORIDAD ALTA - Tests Críticos de Negocio**

#### **1. UserCompletenessTest.php** 
**Objetivo**: Probar cálculos de completeness con datos reales en BD

```php
// tests/Feature/Models/UserCompletenessTest.php
class UserCompletenessTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function student_with_complete_profile_has_100_percent_completeness()
    {
        $user = User::factory()->create(['role' => 'student']);
        
        $aprendiz = Aprendiz::factory()->for($user)->create([
            'semestre' => 5,
            'objetivos' => 'Aprender programación avanzada'
        ]);
        
        $areas = AreaInteres::factory()->count(2)->create();
        $aprendiz->areasInteres()->attach($areas);
        
        $completeness = $user->fresh()->profile_completeness;
        
        $this->assertEquals(100, $completeness['percentage']);
        $this->assertEmpty($completeness['missing_fields']);
        $this->assertCount(3, $completeness['completed_fields']);
    }
    
    /** @test */
    public function student_without_areas_has_partial_completeness()
    {
        $user = User::factory()->create(['role' => 'student']);
        
        Aprendiz::factory()->for($user)->create([
            'semestre' => 3,
            'objetivos' => 'Test'
        ]);
        
        $completeness = $user->fresh()->profile_completeness;
        
        $this->assertEquals(60, $completeness['percentage']); // semestre (35%) + objetivos (25%)
        $this->assertContains('Áreas de interés', $completeness['missing_fields']);
    }
    
    /** @test */
    public function mentor_with_complete_profile_has_100_percent_completeness()
    {
        $user = User::factory()->create(['role' => 'mentor']);
        
        $mentor = Mentor::factory()->for($user)->create([
            'experiencia' => str_repeat('Experiencia detallada ', 10), // >50 chars
            'biografia' => str_repeat('Biografía completa ', 15), // >100 chars
            'años_experiencia' => 5,
            'disponibilidad' => 'Lunes a Viernes 9-17h'
        ]);
        
        $areas = AreaInteres::factory()->count(3)->create();
        $mentor->areasInteres()->attach($areas);
        
        $completeness = $user->fresh()->profile_completeness;
        
        $this->assertEquals(100, $completeness['percentage']);
        $this->assertEmpty($completeness['missing_fields']);
    }
    
    /** @test */
    public function mentor_with_short_fields_fails_validation()
    {
        $user = User::factory()->create(['role' => 'mentor']);
        
        Mentor::factory()->for($user)->create([
            'experiencia' => 'Corto', // <50 chars
            'biografia' => 'Bio breve', // <100 chars
            'años_experiencia' => 0,
            'disponibilidad' => ''
        ]);
        
        $completeness = $user->fresh()->profile_completeness;
        
        $this->assertLessThan(30, $completeness['percentage']);
        $this->assertGreaterThan(3, count($completeness['missing_fields']));
    }
}
```

**Tests a implementar**: 8-10 tests
**Cobertura esperada**: +15% en métodos de User/Aprendiz/Mentor

---

#### **2. StudentControllerIntegrationTest.php**
**Objetivo**: Probar flujo completo de búsqueda de mentores con cache y BD

```php
// tests/Feature/Controllers/StudentControllerIntegrationTest.php
class StudentControllerIntegrationTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function authenticated_student_can_access_mentor_search()
    {
        $user = User::factory()->create(['role' => 'student']);
        
        $response = $this->actingAs($user)->get('/student/dashboard');
        
        $response->assertStatus(200);
        $response->assertViewIs('student.dashboard');
    }
    
    /** @test */
    public function student_sees_mentors_matching_their_areas()
    {
        // Crear estudiante con áreas de interés
        $student = User::factory()->create(['role' => 'student']);
        $aprendiz = Aprendiz::factory()->for($student)->create();
        $area = AreaInteres::factory()->create(['nombre' => 'PHP']);
        $aprendiz->areasInteres()->attach($area);
        
        // Crear mentores (uno con match, otro sin match)
        $matchingMentor = User::factory()->create(['role' => 'mentor', 'name' => 'Mentor PHP']);
        $mentorProfile = Mentor::factory()->for($matchingMentor)->create();
        $mentorProfile->areasInteres()->attach($area);
        
        $otherMentor = User::factory()->create(['role' => 'mentor', 'name' => 'Mentor JS']);
        
        $response = $this->actingAs($student)->get('/student/dashboard');
        
        $response->assertSee('Mentor PHP');
        $response->assertDontSee('Mentor JS');
    }
    
    /** @test */
    public function mentor_suggestions_are_cached_for_performance()
    {
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn(collect([]));
        
        $student = User::factory()->create(['role' => 'student']);
        Aprendiz::factory()->for($student)->create();
        
        $this->actingAs($student)->get('/student/dashboard');
    }
}
```

**Tests a implementar**: 6-8 tests
**Cobertura esperada**: +10% en StudentController

---

#### **3. SendProfileReminderJobIntegrationTest.php**
**Objetivo**: Probar Job con BD real y notificaciones

```php
// tests/Feature/Jobs/SendProfileReminderJobIntegrationTest.php
class SendProfileReminderJobIntegrationTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function job_sends_notification_to_incomplete_student_profile()
    {
        Notification::fake();
        
        $user = User::factory()->create(['role' => 'student', 'email_verified_at' => now()]);
        Aprendiz::factory()->for($user)->create([
            'semestre' => null,
            'objetivos' => null
        ]);
        
        $job = new SendProfileReminderJob($user);
        $job->handle();
        
        Notification::assertSentTo($user, ProfileIncompleteReminder::class);
    }
    
    /** @test */
    public function job_calculates_correct_profile_data_from_database()
    {
        Notification::fake();
        
        $user = User::factory()->create(['role' => 'student', 'email_verified_at' => now()]);
        $aprendiz = Aprendiz::factory()->for($user)->create(['semestre' => 5]);
        
        $job = new SendProfileReminderJob($user);
        $job->handle();
        
        Notification::assertSentTo($user, ProfileIncompleteReminder::class, function ($notification) {
            $data = $notification->toArray();
            return $data['percentage'] === 35; // Solo semestre completo
        });
    }
}
```

**Tests a implementar**: 4-5 tests
**Cobertura esperada**: +5% en Jobs

---

### **🎯 PRIORIDAD MEDIA - Tests de Soporte**

#### **4. MentorRelationshipsTest.php**
**Objetivo**: Probar relaciones y métodos calculados

```php
// tests/Feature/Models/MentorRelationshipsTest.php
class MentorRelationshipsTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function mentor_can_have_multiple_areas_of_interest()
    {
        $mentor = Mentor::factory()->create();
        $areas = AreaInteres::factory()->count(3)->create();
        
        $mentor->areasInteres()->attach($areas);
        
        $this->assertCount(3, $mentor->fresh()->areasInteres);
    }
    
    /** @test */
    public function mentor_stars_rating_is_formatted_correctly()
    {
        $mentor = Mentor::factory()->create(['calificacionPromedio' => 4.7]);
        
        $this->assertEquals('4.7 ⭐', $mentor->stars_rating);
    }
    
    /** @test */
    public function mentor_rating_percentage_is_calculated_correctly()
    {
        $mentor = Mentor::factory()->create(['calificacionPromedio' => 3.5]);
        
        $this->assertEquals(70, $mentor->rating_percentage); // (3.5/5)*100
    }
}
```

**Tests a implementar**: 5-6 tests
**Cobertura esperada**: +8% en Mentor

---

#### **5. ProfileControllerIntegrationTest.php**
**Objetivo**: Probar actualización de perfiles con validación

```php
// tests/Feature/Controllers/ProfileControllerIntegrationTest.php
class ProfileControllerIntegrationTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function student_can_update_their_aprendiz_profile()
    {
        $user = User::factory()->create(['role' => 'student']);
        $aprendiz = Aprendiz::factory()->for($user)->create(['semestre' => 1]);
        
        $response = $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'semestre' => 5,
            'objetivos' => 'Nuevos objetivos'
        ]);
        
        $response->assertRedirect();
        $this->assertEquals(5, $aprendiz->fresh()->semestre);
        $this->assertEquals('Nuevos objetivos', $aprendiz->fresh()->objetivos);
    }
    
    /** @test */
    public function mentor_can_update_their_experience_details()
    {
        $user = User::factory()->create(['role' => 'mentor']);
        $mentor = Mentor::factory()->for($user)->create();
        
        $response = $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'experiencia' => 'Nueva experiencia detallada con más de 50 caracteres',
            'años_experiencia' => 10
        ]);
        
        $response->assertRedirect();
        $this->assertStringContainsString('Nueva experiencia', $mentor->fresh()->experiencia);
    }
}
```

**Tests a implementar**: 6-8 tests
**Cobertura esperada**: +10% en ProfileController

---

## 📊 **MÉTRICAS ESPERADAS**

### **Cobertura Proyectada:**

| Componente | Unit Tests | Feature Tests | Total Coverage |
|------------|-----------|---------------|----------------|
| **Models** | 15% | +25% | **40%** |
| **Controllers** | 10% | +20% | **30%** |
| **Jobs** | 20% | +10% | **30%** |
| **TOTAL** | 15.66% | +18-22% | **35-38%** 🎯 |

### **Tests Totales Proyectados:**

- **Unit Tests actuales**: ~61 tests
- **Feature Tests nuevos**: ~35-40 tests
- **Feature Tests existentes (Auth)**: ~18 tests (ya funcionando)
- **TOTAL**: ~110-120 tests

---

## ⏱️ **ESTIMACIÓN DE TIEMPO**

| Fase | Descripción | Tests | Tiempo Estimado |
|------|-------------|-------|-----------------|
| **Fase 1** | UserCompletenessTest | 8-10 | 2-3 horas |
| **Fase 2** | StudentControllerIntegrationTest | 6-8 | 1.5-2 horas |
| **Fase 3** | SendProfileReminderJobIntegrationTest | 4-5 | 1 hora |
| **Fase 4** | MentorRelationshipsTest | 5-6 | 1.5 horas |
| **Fase 5** | ProfileControllerIntegrationTest | 6-8 | 2 horas |
| **Fase 6** | Verificación y ajustes | - | 1 hora |
| **TOTAL** | | ~35-40 tests | **9-11 horas** |

---

## 🔧 **CONFIGURACIÓN REQUERIDA**

### **Factories Necesarios:**

Ya existen en `database/factories/`:
- ✅ `UserFactory.php`

**A crear:**
- 📝 `AprendizFactory.php`
- 📝 `MentorFactory.php`
- 📝 `AreaInteresFactory.php`

### **Configuración phpunit.xml:**

Ya está configurado correctamente:
```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="CACHE_DRIVER" value="array"/>
<env name="QUEUE_CONNECTION" value="sync"/>
```

---

## ✅ **CHECKLIST DE IMPLEMENTACIÓN**

### **Pre-requisitos:**
- [x] Unit Tests completados y funcionando (Fase 1 y 2)
- [ ] Factories creados para todos los modelos
- [ ] Seeders actualizados si es necesario
- [ ] Migraciones verificadas y funcionando

### **Implementación por Fases:**
- [ ] **Fase 1**: UserCompletenessTest (Alta prioridad)
- [ ] **Fase 2**: StudentControllerIntegrationTest (Alta prioridad)
- [ ] **Fase 3**: SendProfileReminderJobIntegrationTest (Alta prioridad)
- [ ] **Fase 4**: MentorRelationshipsTest (Media prioridad)
- [ ] **Fase 5**: ProfileControllerIntegrationTest (Media prioridad)

### **Verificación:**
- [ ] Todos los tests pasan (Unit + Feature)
- [ ] Cobertura alcanzada: 35-38%
- [ ] Performance aceptable (<3 min para suite completa)
- [ ] Documentación actualizada

---

## 🎯 **BENEFICIOS ESPERADOS**

### **Técnicos:**
✅ Mayor cobertura de código (35-38%)  
✅ Tests de flujos completos E2E  
✅ Detección de bugs de integración  
✅ Validación de relaciones Eloquent  

### **De Negocio:**
✅ Confianza en refactoring  
✅ Regresiones detectadas automáticamente  
✅ Documentación viva de comportamiento  
✅ Facilita onboarding de nuevos devs  

---

## 📚 **RECURSOS Y REFERENCIAS**

### **Laravel Testing Docs:**
- [Database Testing](https://laravel.com/docs/11.x/database-testing)
- [HTTP Tests](https://laravel.com/docs/11.x/http-tests)
- [Mocking](https://laravel.com/docs/11.x/mocking)

### **Best Practices:**
- Usar `RefreshDatabase` en todos los Feature Tests
- Factories para crear datos de prueba
- Assertions específicas para cada caso
- Tests independientes y aislados

---

## 🚫 **FUERA DE SCOPE (Esta Fase)**

- ❌ Browser tests (Dusk)
- ❌ Performance/Load testing
- ❌ E2E tests de frontend (JavaScript)
- ❌ Tests de APIs externas
- ❌ Tests de WebRTC functionality

---

**Creado**: 24 de octubre de 2025  
**Estado**: 📝 **PLANIFICADO** - Pendiente de implementación  
**Responsable**: Equipo de desarrollo  
**Prioridad**: Media (después de completar Unit Tests Fase 2)
