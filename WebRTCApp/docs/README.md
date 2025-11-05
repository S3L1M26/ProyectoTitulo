# Documentación del Proyecto

Esta carpeta contiene toda la documentación técnica del proyecto WebRTC App, organizada por contexto.

## Estructura de Carpetas

### 📁 testing/
Documentación relacionada con testing y aseguramiento de calidad.

- **EVIDENCIA_TESTING.md** - Evidencia general de testing
- **FEATURE_TESTING_PLAN.md** - Plan de tests de feature
- **FINAL_TESTING_SUMMARY.md** - Resumen final de testing
- **INTEGRATION_TEST_FIX_SUMMARY.md** - Resumen de correcciones de tests de integración
- **JIRA_TESTING_SUMMARY.md** - Resumen de testing según tickets JIRA
- **MENTOR_CV_TESTING_EVIDENCE.md** - Evidencia de testing del módulo de CV de mentores (82 tests, 205 assertions)
- **STUDENT_CERTIFICATE_TESTING_EVIDENCE.md** - Evidencia de testing del módulo de certificados de estudiantes (54 tests, 180 assertions)
- **TESTING_IMPLEMENTATION_RESULTS.md** - Resultados de la implementación de tests
- **UNIT_TESTING_BEST_PRACTICES.md** - Mejores prácticas para unit testing
- **UNIT_TESTING_PLAN.md** - Plan de unit testing
- **UNIT_TO_FEATURE_MIGRATION.md** - Guía de migración de unit tests a feature tests

### 📁 deployment/
Documentación relacionada con despliegue y configuración de producción.

- **DEPLOYMENT.md** - Guía completa de despliegue

### 📁 performance/
Documentación sobre optimización y rendimiento.

- **OPTIMIZATION_COMPLETE.md** - Documentación de optimizaciones completadas
- **performance-analysis.md** - Análisis de rendimiento del sistema
- **PERFORMANCE_MAINTENANCE_GUIDE.md** - Guía de mantenimiento de performance

### 📁 database/
Documentación relacionada con base de datos.

- **database-optimization-results.md** - Resultados de optimización de base de datos
- **SEEDERS_MANAGEMENT.md** - Gestión de seeders y datos de prueba

## Estadísticas del Proyecto

### Testing
- **Total de Tests**: 316 tests
- **Total de Assertions**: 820
- **Tasa de Éxito**: 100%
- **Cobertura**: >85% en archivos críticos

### Módulos Principales
- **Certificados de Estudiantes**: Sistema completo de carga, validación OCR y verificación
- **CVs de Mentores**: Sistema completo de carga, validación OCR, puntuación y acceso público
- **Performance**: Optimizaciones de caché, queries y middleware de monitoreo
- **Deployment**: Configuración de Docker, Nginx y producción

## Navegación Rápida

- **¿Nuevo en el proyecto?** → Comienza con el [README principal](../README.md)
- **¿Necesitas hacer testing?** → Revisa [testing/UNIT_TESTING_BEST_PRACTICES.md](testing/UNIT_TESTING_BEST_PRACTICES.md)
- **¿Vas a desplegar?** → Consulta [deployment/DEPLOYMENT.md](deployment/DEPLOYMENT.md)
- **¿Optimizando performance?** → Lee [performance/PERFORMANCE_MAINTENANCE_GUIDE.md](performance/PERFORMANCE_MAINTENANCE_GUIDE.md)

## Contribuir

Al agregar nueva documentación, sigue esta estructura:
1. Identifica el contexto (testing, deployment, performance, database, etc.)
2. Coloca el archivo en la carpeta correspondiente
3. Actualiza este README.md con el nuevo documento
4. Usa nombres descriptivos en inglés o español consistente
