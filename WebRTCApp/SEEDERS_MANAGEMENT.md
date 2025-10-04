# 🛠️ Gestión de Usuarios de Prueba

## 📋 Comando Disponible

### **`test:reset-users`** ⚡
Comando especializado para eliminar usuarios de prueba y regenerarlos.
**⚠️ Solo entorno local** - Bloqueado automáticamente fuera del entorno local.

#### **Funcionalidad:**
- 🔍 Identifica automáticamente usuarios creados por seeders
- 🗑️ Elimina usuarios de prueba y sus relaciones
- 🌱 Ejecuta seeders para regenerar datos
- �️ Protege usuarios reales (no los toca)

#### **Sintaxis:**
```bash
php artisan test:reset-users [--force]
```

#### **Opciones:**
- `--force` - Saltar confirmación (útil para scripts automatizados)

---

## 🔍 Patrones de Detección

El comando identifica automáticamente usuarios de prueba usando estos patrones:

### **Patrones de Email:**
- `*.test@*` - Emails con .test
- `*@example.com` - Emails de ejemplo
- `mentor@*` - Emails que empiecen con mentor
- `aprendiz@*` - Emails que empiecen con aprendiz
- `estudiante*@*` - Emails que empiecen con estudiante

### **Patrones de Nombre:**
- `Mentor *` - Nombres que empiecen con Mentor
- `Estudiante *` - Nombres que empiecen con Estudiante
- `Test *` - Nombres que empiecen con Test

### **⚠️ Usuarios Reales Protegidos:**
Los usuarios que NO coincidan con estos patrones permanecen intactos.

---

## 🔒 Seguridad

### **Protección de Entorno:**
- ✅ **Solo entorno local**: El comando está completamente bloqueado fuera del entorno `local`
- �️ **Identificación inteligente**: Solo elimina usuarios que coinciden con patrones de prueba
- � **Vista previa**: Muestra qué usuarios serán eliminados antes de proceder
- ⚠️ **Confirmación**: Requiere confirmación manual (excepto con `--force`)

---

## 🎯 Casos de Uso Comunes

### **🔄 Reset Estándar (Recomendado)**
Elimina solo datos de prueba, mantiene usuarios reales:
```bash
# Con confirmación
php artisan seeders:manage reset

# Sin confirmación
php artisan seeders:manage reset --force

# Comando rápido
php artisan test:reset --quick
```

### **👥 Solo Usuarios de Prueba**
```bash
php artisan seeders:manage reset --only=users --force
```

### **🏷️ Solo Áreas de Interés**
```bash
php artisan seeders:manage reset --only=areas --force
```

### **🗑️ Truncate Total (⚠️ Peligroso)**
Elimina TODO incluyendo usuarios reales:
```bash
php artisan seeders:manage truncate
```

### **♻️ Refresh Completo**
Reinicia toda la base de datos:
```bash
# Completo
php artisan seeders:manage refresh --force

# Comando rápido
php artisan test:reset --full --quick
```

### **🌱 Solo Seeders**
Agrega datos sin eliminar existentes:
```bash
php artisan seeders:manage seed --force
```

---

## 🔍 Identificación de Datos de Seeder

### **Usuarios de Prueba Detectados:**
- Email contiene: `.test@`, `@example.com`
- Email inicia con: `mentor@`, `aprendiz@`, `estudiante`
- Nombre inicia con: `Mentor `, `Estudiante `

### **Datos Seguros:**
Los usuarios reales (registrados manualmente) se mantienen intactos en operaciones `reset`.

---

## 🚨 Advertencias Importantes

### **⚠️ RESET vs TRUNCATE:**
- **`reset`** = Solo datos de seeder (SEGURO)
- **`truncate`** = TODO incluyendo usuarios reales (PELIGROSO)

### **💾 Backup Recomendado:**
```bash
# Crear backup antes de operaciones peligrosas
docker exec webrtcapp-mysql mysqldump -u laravel -psecret laravel > backup.sql
```

### **🔄 Restaurar Backup:**
```bash
# Solo si es necesario
docker exec -i webrtcapp-mysql mysql -u laravel -psecret laravel < backup.sql
```

---

## 📊 Información Post-Ejecución

Después de cada comando, verás:
- ✅ Estado de la operación
- 👥 Cantidad de usuarios por rol
- 🏷️ Áreas de interés disponibles
- 🔗 Links de acceso rápido

---

## 🎮 Ejemplos Prácticos

### **Desarrollo Diario:**
```bash
# Reset rápido para pruebas
php artisan test:reset --quick
```

### **Preparar Demo:**
```bash
# Reset completo para demo limpia
php artisan test:reset --full --quick
```

### **Debugging:**
```bash
# Solo resetear usuarios manteniendo áreas
php artisan seeders:manage reset --only=users --force
```

### **Fresh Start:**
```bash
# Empezar desde cero
php artisan seeders:manage refresh --force
```

### **Ver Estado Actual:**
```bash
# Estadísticas básicas
php artisan db:stats

# Estadísticas detalladas
php artisan db:stats --detailed

# Solo datos de prueba
php artisan db:stats --test-data
```

---

## 🚀 Deployment y Producción

### **🛡️ Seguridad Automática:**
- El comando `test:reset-users` está **completamente bloqueado** fuera del entorno `local`
- No necesita configuración adicional para ser seguro en producción
- Los usuarios reales nunca se ven afectados

### **� Para Producción:**
Si necesitas gestionar datos en producción, usa los comandos nativos de Laravel:
```bash
# Ejecutar seeders en producción
php artisan db:seed

# Migración completa (cuidado)
php artisan migrate:fresh --seed
```

---

## 📝 Resumen

| Comando | Entorno | Descripción |
|---------|---------|-------------|
| `test:reset-users` | 🟢 Solo Local | Elimina usuarios de prueba y ejecuta seeders |
| `test:reset-users --force` | 🟢 Solo Local | Lo mismo sin confirmación |
| `db:seed` | 🌍 Todos | Comando nativo de Laravel para seeders |

### **✅ Ventajas:**
- **Simple**: Un solo comando para el caso de uso más común
- **Seguro**: Solo funciona en entorno local
- **Inteligente**: Identifica automáticamente usuarios de prueba
- **Rápido**: Perfecto para desarrollo diario

### **🎯 Caso de Uso Principal:**
"Actualicé mi seeder de usuarios y necesito regenerar los datos de prueba sin tocar las áreas de interés ni usuarios reales"

```bash
php artisan test:reset-users --force
```