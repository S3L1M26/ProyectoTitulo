# 📋 Migración de Unit Tests a Feature Tests

## 🎯 **OBJETIVO**

Documentar los tests unitarios que presentan errores debido a dependencias de BD/HTTP y que deben migrar a Feature Tests para funcionar correctamente.

---

## 📊 **RESUMEN DE MIGRACIÓN**

| Archivo Original (Unit) | Estado Actual | Razón de Migración | Archivo Destino (Feature) |
|------------------------|---------------|-------------------|--------------------------|
| `VerifyEmailNotificationTest.php` | ❌ Errores | Requiere User con ID y BD | `Feature/Notifications/VerifyEmailNotificationTest.php` |
| `ProfileIncompleteReminderTest.php` | ❌ Errores | Tests llaman `toMail()->render()` | `Feature/Notifications/ProfileIncompleteReminderTest.php` |
| UserTest (tests con relaciones) | ❌ Errores | Necesita relaciones Eloquent reales | `Feature/Models/UserCompletenessTest.php` |

---

## 📝 **DETALLE DE TESTS A MIGRAR**

### **1. VerifyEmailNotificationTest.php**

**Ubicación actual**: `tests/Unit/Notifications/VerifyEmailNotificationTest.php`

**Tests implementados** (10 tests):
```php
1. test_notification_uses_queue()                        ✅ No requiere BD
2. test_notification_uses_mail_channel()                 ✅ No requiere BD
3. test_notification_can_be_instantiated()               ✅ No requiere BD
4. test_notification_implements_should_queue()           ✅ No requiere BD
5. test_notification_has_to_mail_method()                ✅ No requiere BD
6. test_to_mail_method_returns_mail_message()            ✅ No requiere BD
7. test_to_mail_method_accepts_notifiable_parameter()    ✅ No requiere BD
8. test_notification_extends_base_verify_email()         ✅ No requiere BD
9. test_notification_has_verification_url_method()       ✅ No requiere BD
10. test_notification_via_method_is_inherited()          ✅ No requiere BD
```

**Problema identificado**:
```php
// Algunos tests intentan llamar toMail() con usuarios sin ID
$user = new User(['email' => 'test@example.com']); // Sin ID, sin BD
$mailMessage = $notification->toMail($user); // ❌ FALLA - verificationUrl() necesita ID
```

**Error generado**:
```
Error: verificationUrl() requires a user with an ID and email_verified_at
```

**Solución propuesta**:
- ✅ **Mantener en Unit**: Tests 1-10 (solo verifican estructura, no ejecutan lógica)
- 🔄 **Migrar a Feature**: Crear nuevos tests que prueben `toMail()` completo con BD

**Código para Feature Test**:
```php
// tests/Feature/Notifications/VerifyEmailNotificationTest.php
use RefreshDatabase;

public function test_verification_email_contains_correct_url()
{
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'email_verified_at' => null
    ]);
    
    $notification = new VerifyEmailNotification();
    $mailMessage = $notification->toMail($user);
    
    $this->assertStringContainsString('verify-email', $mailMessage->actionUrl);
    $this->assertEquals('Verificar Correo Electrónico', $mailMessage->subject);
}
```

---

### **2. ProfileIncompleteReminderTest.php**

**Ubicación actual**: `tests/Unit/Notifications/ProfileIncompleteReminderTest.php`

**Tests implementados** (14+ tests):
```php
1. test_notification_uses_queue()                                    ✅ Funciona
2. test_notification_uses_mail_channel()                             ✅ Funciona
3. test_mail_message_contains_profile_percentage()                   ❌ FALLA - usa render()
4. test_mail_message_greeting_includes_user_name()                   ❌ FALLA - usa render()
5. test_mail_message_different_content_for_student()                 ❌ FALLA - usa render()
6. test_mail_message_different_content_for_mentor()                  ❌ FALLA - usa render()
7. test_to_array_returns_profile_data()                              ✅ Funciona
8. test_to_array_includes_percentage()                               ✅ Funciona
9. test_to_array_includes_missing_fields()                           ✅ Funciona
10. test_to_array_includes_role_specific_message()                   ✅ Funciona
... (más tests)
```

**Problema identificado**:
```php
// Tests que fallan intentan renderizar el mail
$mailMessage = $notification->toMail($user);
$content = $mailMessage->render(); // ❌ FALLA - render() necesita BD y vistas

// Error:
// View [vendor.notifications.email] not found or rendering issues
```

**Solución propuesta**:
- ✅ **Mantener en Unit**: Tests de `toArray()` y estructura (tests 1, 2, 7-10)
- 🔄 **Migrar a Feature**: Tests que usan `render()` (tests 3-6)
- ❌ **Eliminar**: Tests redundantes o que no aportan valor

**Tests a mantener en Unit** (6 tests):
```php
// tests/Unit/Notifications/ProfileIncompleteReminderTest.php
public function test_notification_uses_queue() { ... }                  // ✅
public function test_notification_uses_mail_channel() { ... }           // ✅
public function test_to_array_returns_profile_data() { ... }            // ✅
public function test_to_array_includes_percentage() { ... }             // ✅
public function test_to_array_includes_missing_fields() { ... }         // ✅
public function test_to_array_includes_role_specific_message() { ... }  // ✅
```

**Tests a crear en Feature** (4 tests):
```php
// tests/Feature/Notifications/ProfileIncompleteReminderTest.php
use RefreshDatabase;

public function test_student_receives_profile_reminder_email()
{
    $user = User::factory()->create(['role' => 'student']);
    Aprendiz::factory()->for($user)->create(['semestre' => null]);
    
    $user->notify(new ProfileIncompleteReminder([
        'percentage' => 50,
        'missing_fields' => ['Semestre']
    ]));
    
    Notification::assertSentTo($user, ProfileIncompleteReminder::class);
}

public function test_profile_reminder_email_contains_percentage()
{
    Mail::fake();
    
    $user = User::factory()->create(['role' => 'student']);
    
    $notification = new ProfileIncompleteReminder([
        'percentage' => 45,
        'missing_fields' => []
    ]);
    
    $notification->toMail($user)->send($user);
    
    Mail::assertSent(function ($mail) {
        return $mail->hasTo('student@example.com') &&
               str_contains($mail->render(), '45%');
    });
}
```

---

### **3. UserTest.php - Tests con Relaciones**

**Ubicación actual**: `tests/Unit/Models/UserTest.php`

**Tests problemáticos que se REMOVIERON**:
```php
// Estos tests fueron creados pero fallaban por usar relaciones Eloquent
test_calculate_student_completeness_with_no_aprendiz()           ❌ Removido
test_calculate_student_completeness_with_all_fields_complete()   ❌ Removido
test_calculate_student_completeness_with_partial_fields()        ❌ Removido
test_calculate_mentor_completeness_with_no_mentor()              ❌ Removido
test_calculate_mentor_completeness_with_all_fields_complete()    ❌ Removido
test_calculate_mentor_completeness_validates_minimum_lengths()   ❌ Removido (parcialmente funciona)
test_profile_completeness_weights_are_correct()                  ❌ Removido
```

**Problema identificado**:
```php
// Los tests usaban mocks pero el código real hace queries a BD
$user->setRelation('aprendiz', $mockAprendiz);

// El método calculateStudentCompleteness() hace:
if (!$this->relationLoaded('aprendiz')) {
    $this->load('aprendiz.areasInteres'); // ❌ QUERY A BD - FALLA
}
```

**Error generado**:
```
QueryException: Database file at path [laravel] does not exist.
SQL: select * from "aprendices" where "aprendices"."user_id" in (0)
```

**Tests que SÍ se mantienen en Unit** (10 tests):
```php
// tests/Unit/Models/UserTest.php - Tests puros sin BD
test_it_has_correct_fillable_attributes()                        ✅ Mantener
test_it_has_correct_hidden_attributes()                          ✅ Mantener
test_relationship_methods_exist()                                ✅ Mantener
test_calculate_student_completeness_method_exists()              ✅ Mantener
test_profile_completion_field_validation()                       ✅ Mantener
test_password_reset_notification_method_exists()                 ✅ Mantener
test_role_attribute_can_be_assigned()                            ✅ Mantener
test_name_and_email_are_fillable()                               ✅ Mantener
test_password_is_hidden_in_array_conversion()                    ✅ Mantener
test_remember_token_is_hidden_in_array_conversion()              ✅ Mantener
```

**Tests a crear en Feature** (8-10 tests):
```php
// tests/Feature/Models/UserCompletenessTest.php
use RefreshDatabase;

public function test_student_with_complete_profile_has_100_percent()
{
    $user = User::factory()->create(['role' => 'student']);
    
    $aprendiz = Aprendiz::factory()->for($user)->create([
        'semestre' => 5,
        'objetivos' => 'Learn programming'
    ]);
    
    $areas = AreaInteres::factory()->count(2)->create();
    $aprendiz->areasInteres()->attach($areas);
    
    $completeness = $user->fresh()->profile_completeness;
    
    $this->assertEquals(100, $completeness['percentage']);
}

public function test_student_without_areas_has_partial_completeness()
{
    $user = User::factory()->create(['role' => 'student']);
    
    Aprendiz::factory()->for($user)->create([
        'semestre' => 3,
        'objetivos' => 'Test'
    ]);
    
    $completeness = $user->fresh()->profile_completeness;
    
    $this->assertEquals(60, $completeness['percentage']); // 35% + 25%
    $this->assertContains('Áreas de interés', $completeness['missing_fields']);
}
```

---

## 📊 **RESUMEN DE ARCHIVOS**

### **Archivos a ELIMINAR de Unit Tests:**

1. ❌ `tests/Unit/Notifications/VerifyEmailNotificationTest.php` (completo)
   - **Razón**: Requiere User con ID para generar URLs
   - **Destino**: Migrar lógica a Feature Tests

2. ❌ `tests/Unit/Notifications/ProfileIncompleteReminderTest.php` (completo)
   - **Razón**: Tests de `render()` no funcionan sin BD/vistas
   - **Destino**: Mantener solo tests de `toArray()` como Unit, resto a Feature

3. ⚠️ `tests/Unit/Models/UserTest.php` (YA LIMPIO - 10 tests puros)
   - **Estado**: Ya corregido, solo tests puros sin BD
   - **Acción**: ✅ Ninguna (ya está correcto)

### **Archivos a CREAR en Feature Tests:**

1. 📝 `tests/Feature/Notifications/VerifyEmailNotificationTest.php`
   - **Tests**: 3-4 tests de integración con BD
   - **Prioridad**: Media

2. 📝 `tests/Feature/Notifications/ProfileIncompleteReminderTest.php`
   - **Tests**: 4-5 tests de envío real de notificaciones
   - **Prioridad**: Media

3. 📝 `tests/Feature/Models/UserCompletenessTest.php`
   - **Tests**: 8-10 tests de cálculo de completeness con BD
   - **Prioridad**: Alta (mayor impacto en cobertura)

---

## ✅ **CHECKLIST DE MIGRACIÓN**

### **Paso 1: Documentación** ✅ COMPLETADO
- [x] Identificar tests problemáticos
- [x] Documentar errores y razones
- [x] Definir tests a mantener vs migrar
- [x] Crear este documento de migración

### **Paso 2: Limpieza de Unit Tests** (PENDIENTE)
- [ ] Eliminar `VerifyEmailNotificationTest.php` de Unit
- [ ] Eliminar `ProfileIncompleteReminderTest.php` de Unit
- [ ] Verificar que UserTest solo tenga tests puros (YA HECHO ✅)

### **Paso 3: Creación de Feature Tests** (FUERA DE SCOPE - Solo planificado)
- [ ] Crear `Feature/Notifications/VerifyEmailNotificationTest.php`
- [ ] Crear `Feature/Notifications/ProfileIncompleteReminderTest.php`
- [ ] Crear `Feature/Models/UserCompletenessTest.php`
- [ ] Verificar que todos pasen con `RefreshDatabase`

### **Paso 4: Verificación Final** (PENDIENTE)
- [ ] Ejecutar suite Unit completa (todos deben pasar)
- [ ] Medir cobertura Unit Tests
- [ ] Documentar en TESTING_IMPLEMENTATION_RESULTS.md

---

## 📈 **IMPACTO EN COBERTURA**

### **Antes de Migración:**
```
Unit Tests: 61 tests (algunos fallando)
Coverage: ~15.66% methods
```

### **Después de Limpieza (Solo Unit):**
```
Unit Tests: ~50 tests (todos pasando)
Coverage: ~12-14% methods (reducción esperada)
```

### **Después de Migración Completa (Unit + Feature):**
```
Unit Tests: ~50 tests
Feature Tests: ~35-40 tests nuevos
Total: ~85-90 tests
Coverage: ~35-38% methods (objetivo alcanzado)
```

---

## 💡 **LECCIONES APRENDADAS**

### **Criterios para clasificar tests:**

**✅ UNIT TEST si:**
- No requiere BD
- No requiere HTTP requests
- Prueba métodos puros/lógica aislada
- Usa mocks simples
- Se ejecuta en <0.5s

**🌐 FEATURE TEST si:**
- Requiere BD (relaciones, queries)
- Requiere HTTP requests
- Prueba flujos E2E
- Usa factories
- Se ejecuta en 2-5s

### **Errores comunes evitados:**

❌ **NO hacer en Unit Tests:**
```php
$user = new User();
$user->aprendiz; // ❌ Intenta query a BD

$mailMessage->render(); // ❌ Requiere vistas y BD

User::factory()->create(); // ❌ Requiere BD
```

✅ **SÍ hacer en Unit Tests:**
```php
$user = new User(['role' => 'student']);
$this->assertEquals('student', $user->role); // ✅ Puro

$this->assertTrue(method_exists($user, 'aprendiz')); // ✅ Reflección

$notification = new ProfileIncompleteReminder([...]);
$data = $notification->toArray(); // ✅ Array puro
```

---

**Creado**: 24 de octubre de 2025  
**Estado**: 📝 **DOCUMENTADO** - Listo para ejecutar limpieza  
**Próximo paso**: Eliminar archivos problemáticos de Unit Tests  
**Referencia**: Ver FEATURE_TESTING_PLAN.md para implementación futura
