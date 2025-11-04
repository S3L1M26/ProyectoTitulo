# 🏆 Guía de Buenas Prácticas - Pruebas Unitarias

## 📋 **PRINCIPIOS FUNDAMENTALES**

### **🎯 Objetivos de las Pruebas Unitarias**
1. **Validar Lógica de Negocio**: Verificar que cada unidad funcione correctamente
2. **Detectar Regresiones**: Identificar cambios que rompan funcionalidad existente
3. **Documentar Comportamiento**: Servir como documentación viva del código
4. **Facilitar Refactoring**: Permitir cambios seguros con confianza

### **⚡ Regla de Oro**
> **"Una prueba unitaria debe ejecutar UNA unidad de código y verificar UN comportamiento específico"**

---

## 🏗️ **ESTRUCTURA Y ORGANIZACIÓN**

### **📁 Organización de Archivos**
```
tests/Unit/
├── Models/              # Tests de modelos Eloquent
│   ├── UserTest.php
│   ├── MentorTest.php
│   └── ...
├── Controllers/         # Tests de controladores (lógica pura)
│   ├── StudentControllerTest.php
│   └── ...
├── Services/           # Tests de servicios de negocio
├── Helpers/           # Tests de helpers y utilidades
└── Rules/             # Tests de reglas de validación
```

### **📝 Convenciones de Nombres**
```php
// ✅ CORRECTO: Descriptivo y específico
public function test_user_has_correct_fillable_attributes()
public function test_mentor_calculates_rating_percentage_correctly()
public function test_cache_key_generation_is_consistent()

// ❌ INCORRECTO: Vago y genérico
public function test_user()
public function test_method()
public function test_works()
```

---

## 🧪 **ANATOMÍA DE UN TEST PERFECTO**

### **🏛️ Patrón AAA (Arrange-Act-Assert)**
```php
public function test_user_calculates_profile_completeness_correctly()
{
    // 🔧 ARRANGE: Preparar datos de prueba
    $user = new User([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'role' => 'student'
    ]);
    
    // ⚡ ACT: Ejecutar la acción a probar
    $completeness = $user->calculateProfileCompleteness();
    
    // ✅ ASSERT: Verificar el resultado esperado
    $this->assertIsArray($completeness);
    $this->assertArrayHasKey('percentage', $completeness);
    $this->assertGreaterThanOrEqual(0, $completeness['percentage']);
    $this->assertLessThanOrEqual(100, $completeness['percentage']);
}
```

### **🎯 Características de un Buen Test**
1. **Rápido**: Ejecuta en menos de 100ms
2. **Independiente**: No depende de otros tests
3. **Repetible**: Mismo resultado en cada ejecución
4. **Auto-verificable**: Pasa o falla automáticamente
5. **Oportuno**: Se escribe junto con el código

---

## 🛠️ **CONFIGURACIÓN Y SETUP**

### **⚙️ Setup Correcto**
```php
<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

class UserTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Configuración simple y clara
        Notification::fake();
        
        // Evitar configuraciones complejas en setUp()
        $this->artisan('config:clear');
    }

    protected function tearDown(): void
    {
        // Limpiar recursos si es necesario
        parent::tearDown();
    }
}
```

### **🗃️ Configuración de Base de Datos**
```xml
<!-- phpunit.xml - Configuración optimizada -->
<php>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
    <env name="CACHE_DRIVER" value="array"/>
    <env name="QUEUE_CONNECTION" value="sync"/>
    <env name="SESSION_DRIVER" value="array"/>
    <env name="MAIL_MAILER" value="array"/>
</php>
```

---

## 🎯 **ESTRATEGIAS DE TESTING**

### **🏷️ Testing de Models**
```php
// ✅ BUENA PRÁCTICA: Test de atributos y comportamiento
public function test_mentor_casts_calificacion_promedio_to_float()
{
    $mentor = new Mentor();
    $mentor->calificacionPromedio = '4.5';
    
    $this->assertIsFloat($mentor->calificacionPromedio);
    $this->assertEquals(4.5, $mentor->calificacionPromedio);
}

// ✅ BUENA PRÁCTICA: Test de relaciones
public function test_user_has_one_aprendiz()
{
    $user = new User();
    $relation = $user->aprendiz();
    
    $this->assertInstanceOf(HasOne::class, $relation);
    $this->assertEquals('user_id', $relation->getForeignKeyName());
}
```

### **🎮 Testing de Controllers**
```php
// ✅ BUENA PRÁCTICA: Test de estructura sin dependencias complejas
public function test_student_controller_has_required_methods()
{
    $controller = new StudentController();
    
    $reflection = new \ReflectionClass($controller);
    $this->assertTrue($reflection->hasMethod('index'));
    $this->assertTrue($reflection->hasMethod('getMentorSuggestions'));
}

// ✅ BUENA PRÁCTICA: Test de lógica pura
public function test_cache_key_generation_is_deterministic()
{
    $areaIds = [3, 1, 2];
    sort($areaIds);
    $key = md5(implode(',', $areaIds));
    
    $this->assertEquals(md5('1,2,3'), $key);
    $this->assertEquals(32, strlen($key));
}
```

---

## 🚫 **QUÉ EVITAR**

### **❌ Anti-Patrones Comunes**
```php
// ❌ MALO: Test que depende de base de datos real
public function test_user_saves_to_database()
{
    $user = User::create(['name' => 'Test']);
    $this->assertDatabaseHas('users', ['name' => 'Test']);
}

// ❌ MALO: Test que depende de servicios externos
public function test_sends_email_to_external_service()
{
    $user = new User();
    $user->sendRealEmail(); // Llamada a servicio externo
}

// ❌ MALO: Test que testea múltiples comportamientos
public function test_user_everything()
{
    // Testing 10 cosas diferentes en un solo test
}
```

### **⚠️ Problemas a Evitar**
1. **Dependencias de Red**: Nunca hacer llamadas HTTP reales
2. **Estado Compartido**: Tests que dependen de otros tests
3. **Configuración Compleja**: Setup que toma más tiempo que el test
4. **Mocking Excesivo**: Usar mocks cuando no es necesario
5. **Tests Frágiles**: Que se rompen con cambios menores

---

## 🔧 **HERRAMIENTAS Y UTILIDADES**

### **🧰 Herramientas Útiles**
```php
// Reflexión para acceder a métodos privados
$reflection = new \ReflectionClass($controller);
$method = $reflection->getMethod('privateMethod');
$method->setAccessible(true);
$result = $method->invoke($controller, $parameters);

// Faking de servicios Laravel
Cache::fake();
Queue::fake();
Notification::fake();
Mail::fake();

// Assertions útiles
$this->assertInstanceOf(ExpectedClass::class, $object);
$this->assertArrayHasKey('key', $array);
$this->assertCount(3, $collection);
$this->assertStringContains('substring', $string);
```

### **📊 Comandos de Análisis**
```bash
# Ejecutar con cobertura
php artisan test --coverage

# Filtrar tests específicos
php artisan test --filter=UserTest

# Mostrar output detallado
php artisan test --verbose

# Parar en el primer fallo
php artisan test --stop-on-failure
```

---

## 📈 **MÉTRICAS Y OBJETIVOS**

### **🎯 Objetivos de Cobertura**
- **Models**: 80%+ (alta lógica de negocio)
- **Controllers**: 60%+ (lógica de presentación)
- **Services**: 90%+ (lógica crítica)
- **Helpers**: 95%+ (utilidades puras)

### **⏱️ Objetivos de Performance**
- **Test individual**: < 100ms
- **Suite completa**: < 2 minutos
- **Setup/teardown**: < 10ms por test

---

## 🚀 **WORKFLOW DE DESARROLLO**

### **🔄 Ciclo TDD (Opcional)**
```
1. 🔴 RED: Escribir test que falle
2. 🟢 GREEN: Escribir código mínimo para pasar
3. 🔵 REFACTOR: Mejorar el código manteniendo tests verdes
```

### **✅ Checklist Pre-Commit**
- [ ] Todos los tests unitarios pasan
- [ ] Nuevas funcionalidades tienen tests
- [ ] Tests son rápidos y específicos
- [ ] No hay dependencias externas
- [ ] Nombres de tests son descriptivos

---

## 🎓 **EJEMPLOS PRÁCTICOS**

### **📚 Template de Test Model**
```php
<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\YourModel;

class YourModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Setup mínimo y necesario
    }

    public function test_has_correct_fillable_attributes()
    {
        $model = new YourModel();
        $expected = ['attribute1', 'attribute2', 'attribute3'];
        
        $this->assertEquals($expected, $model->getFillable());
    }

    public function test_casts_attributes_correctly()
    {
        $model = new YourModel();
        
        $this->assertEquals('boolean', $model->getCasts()['is_active']);
        $this->assertEquals('datetime', $model->getCasts()['created_at']);
    }
}
```

### **📚 Template de Test Controller**
```php
<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Http\Controllers\YourController;

class YourControllerTest extends TestCase
{
    public function test_extends_base_controller()
    {
        $controller = new YourController();
        
        $this->assertInstanceOf(\App\Http\Controllers\Controller::class, $controller);
    }

    public function test_has_required_public_methods()
    {
        $controller = new YourController();
        $reflection = new \ReflectionClass($controller);
        
        $this->assertTrue($reflection->hasMethod('index'));
        $this->assertTrue($reflection->getMethod('index')->isPublic());
    }
}
```

---

## 🎯 **OBJETIVOS FUTUROS**

### **📅 Roadmap de Testing**
1. **Fase Actual**: Pruebas unitarias básicas ✅
2. **Fase 2**: Feature tests para endpoints
3. **Fase 3**: Browser tests con Laravel Dusk
4. **Fase 4**: Performance testing automatizado
5. **Fase 5**: CI/CD con testing automático

### **🔮 Visión a Largo Plazo**
- **100% Cobertura** en lógica crítica
- **Cero regresiones** en producción
- **Testing automático** en cada commit
- **Documentación viva** del comportamiento

---

**🏆 Recuerda: "Un buen test es aquel que falla por las razones correctas y pasa cuando el código funciona como se espera."**