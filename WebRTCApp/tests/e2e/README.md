# Tests E2E con Playwright

Tests end-to-end para el flujo completo de mentorías usando Playwright.

## 📋 Requisitos Previos

- Node.js >= 16
- Docker y Docker Compose (para la aplicación Laravel)
- Base de datos de testing con datos de prueba

## 🚀 Instalación

### 1. Instalar Playwright

```bash
npm install -D @playwright/test
npx playwright install
```

Esto instalará:
- Playwright Test Framework
- Navegadores necesarios (Chromium, Firefox, WebKit)

### 2. Verificar Instalación

```bash
npx playwright --version
```

## 🗂️ Estructura de Archivos

```
tests/e2e/
├── helpers/
│   └── auth.js           # Helpers de autenticación
├── mocks/
│   └── zoom.js           # Mocks de Zoom API
├── results/
│   ├── artifacts/        # Screenshots y videos
│   ├── html/            # Reporte HTML
│   └── screenshots/      # Screenshots de tests exitosos
├── mentor-flow.spec.js   # Tests del flujo del mentor
├── student-flow.spec.js  # Tests del flujo del estudiante
└── README.md            # Este archivo

playwright.config.js      # Configuración de Playwright
scripts/setup-e2e.sh      # Script de preparación de BD
```

## 🧪 Ejecutar Tests E2E

### ⚡ Resumen Rápido

```bash
# 1. Cambiar temporalmente la conexión de BD en .env
#    Editar .env y cambiar: DB_CONNECTION=testing

# 2. Preparar datos de prueba
docker compose exec app bash scripts/setup-e2e.sh

# 3. Ejecutar tests (desde Windows, fuera de Docker)
npm run test:e2e:chromium

# 4. Restaurar .env
#    Volver a: DB_CONNECTION=mysql
```

### 📝 Pasos Detallados

#### Paso 1: Configurar Conexión de BD

Editar el archivo `.env` y cambiar temporalmente:

```env
# Cambiar de:
DB_CONNECTION=mysql

# A:
DB_CONNECTION=testing
```

**Explicación:** Esto hace que Laravel use la conexión `testing` definida en `config/database.php`, que apunta a `DB_TEST_DATABASE=webrtc_testing`.

#### Paso 2: Preparar Base de Datos

```bash
docker compose exec app bash scripts/setup-e2e.sh
```

Este comando:
- ✅ Limpia caché de configuración
- ✅ Ejecuta `migrate:fresh` en `webrtc_testing`
- ✅ Crea usuarios de prueba (mentor@test.com, student@test.com)
- ✅ Crea 1 solicitud pendiente, 1 aceptada, 1 mentoría confirmada

#### Paso 3: Ejecutar Tests

**IMPORTANTE:** Ejecutar desde tu máquina Windows (fuera de Docker) porque el contenedor Alpine no tiene las dependencias de Chromium.

```powershell
# Desde PowerShell en Windows
npm run test:e2e:chromium
```

**Alternativa:** Si tienes Chromium instalado en Docker, puedes intentar:

```bash
docker compose exec vite npm run test:e2e:chromium
```

#### Paso 4: Restaurar Configuración

Después de ejecutar los tests, volver a cambiar `.env`:

```env
# Restaurar a:
DB_CONNECTION=mysql
```

## 📊 Reportes

Después de ejecutar los tests, se generan automáticamente:

- **Reporte HTML**: `tests/e2e/results/html/index.html`
- **JSON**: `tests/e2e/results/results.json`
- **Screenshots**: `tests/e2e/results/screenshots/`
- **Videos**: `tests/e2e/results/artifacts/` (solo en fallos)

## 🔧 Configuración

### Usuarios de Prueba

Los tests esperan que existan estos usuarios en la BD de testing:

```
# Mentor
Email: mentor@test.com
Password: password

# Estudiante
Email: student@test.com
Password: password
```

### Preparar Base de Datos

El script `setup-e2e.sh` automáticamente:

1. Ejecuta `migrate:fresh` en `webrtc_testing`
2. Ejecuta el seeder `E2ETestSeeder`
3. Crea 3 usuarios, 4 solicitudes, 3 mentorías

```bash
# Ejecutar desde contenedor app
docker compose exec app bash scripts/setup-e2e.sh
```

**IMPORTANTE:** Recuerda cambiar `DB_DATABASE=webrtc_testing` en `.env` antes de ejecutar los tests E2E.

## 📝 Tests Implementados

### Flujo del Mentor (`mentor-flow.spec.js`)

1. ✅ **Login como mentor**
2. ✅ **Ver solicitudes pendientes**
3. ✅ **Aceptar solicitud**
4. ✅ **Confirmar mentoría** (genera reunión Zoom con mock)
5. ✅ **Verificar datos de reunión**

**Tests adicionales:**
- Ver detalles de mentoría confirmada
- Cancelar una mentoría
- Manejo de errores de Zoom API

### Flujo del Estudiante (`student-flow.spec.js`)

1. ✅ **Login como estudiante**
2. ✅ **Ver dashboard con mentorías**
3. ✅ **Ver mentoría confirmada**
4. ✅ **Click en "Unirse a reunión"**
5. ✅ **Verificar redirección a Zoom**

**Tests adicionales:**
- Ver notificaciones de mentoría confirmada
- Ver información del mentor
- Ver contador de tiempo hasta la mentoría
- Validaciones de estado vacío

## 🎭 Mocks de Zoom API

Los tests interceptan todas las llamadas a Zoom API y retornan respuestas simuladas:

- `POST https://zoom.us/oauth/token` → Token OAuth
- `POST https://api.zoom.us/v2/users/me/meetings` → Crear reunión
- `GET https://api.zoom.us/v2/meetings/{id}` → Obtener detalles
- `DELETE https://api.zoom.us/v2/meetings/{id}` → Cancelar reunión
- `PATCH https://api.zoom.us/v2/meetings/{id}` → Actualizar reunión

**ID de reunión mock**: `999888777`  
**Join URL**: `https://zoom.us/j/999888777?pwd=mockpassword`

## 🐛 Debugging

### Ver tests ejecutándose en tiempo real

```bash
npx playwright test --headed --workers=1
```

### Pausar ejecución en un punto específico

En el test, añadir:

```javascript
await page.pause();
```

### Inspeccionar selector

```bash
npx playwright codegen http://localhost:8000
```

### Ver trazas de ejecución

```bash
npx playwright show-trace tests/e2e/results/artifacts/trace.zip
```

## 📸 Screenshots Automáticos

Los screenshots se capturan en:

- ✅ **Éxito**: `tests/e2e/results/screenshots/*.png` (manual en tests)
- ❌ **Fallo**: `tests/e2e/results/artifacts/*.png` (automático)

## 🎥 Videos

Los videos se graban automáticamente solo cuando un test falla:

- Ubicación: `tests/e2e/results/artifacts/`
- Formato: WebM
- Se eliminan automáticamente si el test pasa

## 🔍 Solución de Problemas

### Error: "Browser not found"

```bash
npx playwright install
```

### Error: "Cannot find module '@playwright/test'"

```bash
npm install -D @playwright/test
```

### Error: "Timeout waiting for selector"

- Verificar que la aplicación está corriendo en `http://localhost:8000`
- Verificar que los selectores son correctos
- Aumentar timeout en `playwright.config.js`

### Tests fallan con "Login failed"

- Verificar que los usuarios existen en la BD de testing
- Verificar credenciales en `tests/e2e/helpers/auth.js`
- Verificar que la sesión de Laravel funciona correctamente

### Screenshots no se guardan

- Crear directorio manualmente: `mkdir -p tests/e2e/results/screenshots`
- Verificar permisos de escritura

## 🔄 CI/CD Integration

### GitHub Actions

```yaml
name: E2E Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-node@v3
        with:
          node-version: '18'
      - name: Install dependencies
        run: npm ci
      - name: Install Playwright
        run: npx playwright install --with-deps
      - name: Run E2E tests
        run: npm run test:e2e
      - uses: actions/upload-artifact@v3
        if: always()
        with:
          name: playwright-report
          path: tests/e2e/results/html/
```

## 📚 Recursos

- [Playwright Docs](https://playwright.dev/docs/intro)
- [Best Practices](https://playwright.dev/docs/best-practices)
- [Debugging Guide](https://playwright.dev/docs/debug)
- [CI/CD Guide](https://playwright.dev/docs/ci)

## ✅ Criterios de Aceptación Cumplidos

- ✅ Test: login como mentor → aceptar solicitud → generar Zoom → confirmar
- ✅ Test: login como aprendiz → ver mentoría confirmada → click "Unirse"
- ✅ Mock de API Zoom en tests
- ✅ Screenshots en caso de fallo

## 🎯 Siguientes Pasos

1. Crear seeder específico para E2E (`E2ETestSeeder`)
2. Ejecutar `npm install -D @playwright/test`
3. Ejecutar `npx playwright install`
4. Configurar usuarios de prueba en BD
5. Ejecutar tests: `npm run test:e2e:ui`

---

**Autor**: Equipo de Desarrollo  
**Fecha**: Noviembre 2025  
**Versión**: 1.0.0
