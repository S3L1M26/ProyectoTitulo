# 🛠️ Gestión de Datos de Desarrollo

## 📋 Comandos Disponibles

### **`test:reset-users`** ⚡
Comando especializado para eliminar usuarios de prueba y regenerarlos.
**⚠️ Solo entorno local** - Bloqueado automáticamente fuera del entorno local.

#### **Funcionalidad:**
- 🔍 Identifica automáticamente usuarios creados por seeders
- 🗑️ Elimina usuarios de prueba y sus relaciones (mentores, aprendices, áreas de interés)
- 🌱 Ejecuta seeders completos para regenerar datos
- 🛡️ Protege usuarios reales (no los toca)

#### **Sintaxis:**
```bash
php artisan test:reset-users [--force]
```

#### **Opciones:**
- `--force` - Saltar confirmación (útil para scripts automatizados)

#### **Ejemplos de uso:**
```bash
# Con confirmación (recomendado)
php artisan test:reset-users

# Sin confirmación para scripts
php artisan test:reset-users --force

# En Docker (desarrollo)
docker compose exec app php artisan test:reset-users --force
```

---

### **`profile:send-reminders`** �
Envía recordatorios por email a usuarios con perfiles incompletos.

#### **Sintaxis:**
```bash
php artisan profile:send-reminders [--test]
```

#### **Opciones:**
- `--test` - Incluir usuarios recién creados para pruebas

#### **Ejemplos de uso:**
```bash
# Enviar recordatorios normales
php artisan profile:send-reminders

# Incluir usuarios recientes para testing
php artisan profile:send-reminders --test
```

---

## �🔍 Patrones de Detección de Usuarios de Prueba

El comando `test:reset-users` identifica automáticamente usuarios de prueba usando estos patrones:

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
- ✅ **Solo entorno local**: El comando `test:reset-users` está completamente bloqueado fuera del entorno `local`
- 🔍 **Identificación inteligente**: Solo elimina usuarios que coinciden con patrones de prueba
- 👁️ **Vista previa**: Muestra qué usuarios serán eliminados antes de proceder
- ⚠️ **Confirmación**: Requiere confirmación manual (excepto con `--force`)

---

## 🎯 Casos de Uso Comunes

### **🔄 Development Workflow (Recomendado)**
```bash
# Reset completo de usuarios de prueba
docker compose exec app php artisan test:reset-users --force
```

### **🌱 Solo Seeders (sin eliminar)**
```bash
# Ejecutar seeders nativos de Laravel
docker compose exec app php artisan db:seed --force
```

### **♻️ Fresh Database (⚠️ Elimina TODO)**
```bash
# Reiniciar toda la base de datos
docker compose exec app php artisan migrate:fresh --seed
```

### **📧 Testing Profile Reminders**
```bash
# Probar sistema de recordatorios
docker compose exec app php artisan profile:send-reminders --test
```

---

## 🚨 Advertencias Importantes

### **⚠️ DIFERENCIAS DE COMANDOS:**
- **`test:reset-users`** = Solo usuarios de prueba (SEGURO)
- **`migrate:fresh`** = TODO incluyendo usuarios reales (PELIGROSO)

### **💾 Backup Recomendado:**
```bash
# Crear backup antes de operaciones de base de datos
docker exec webrtcapp-mysql mysqldump -u laravel -psecret laravel > backup_$(date +%Y%m%d_%H%M%S).sql
```

### **🔄 Restaurar Backup:**
```bash
# Solo si es necesario
docker exec -i webrtcapp-mysql mysql -u laravel -psecret laravel < backup_YYYYMMDD_HHMMSS.sql
```

---

## � Comandos Docker

### **Contenedores Disponibles:**
```bash
# Ver contenedores en ejecución
docker ps

# Acceder al contenedor principal
docker compose exec app bash

# Ver logs de la aplicación
docker compose logs app -f
```

### **Comandos Artisan en Docker:**
```bash
# Patrón general
docker compose exec app php artisan [comando]

# Ejemplos específicos
docker compose exec app php artisan migrate:status
docker compose exec app php artisan route:list
docker compose exec app php artisan queue:work
```

---

## 🚀 Deployment y Producción

### **🛡️ Seguridad Automática:**
- El comando `test:reset-users` está **completamente bloqueado** fuera del entorno `local`
- No necesita configuración adicional para ser seguro en producción
- Los usuarios reales nunca se ven afectados en comandos de testing

### **⚡ Para Producción:**
```bash
# Comandos seguros para producción
php artisan db:seed                    # Ejecutar seeders
php artisan profile:send-reminders     # Enviar recordatorios
php artisan migrate --force            # Aplicar migraciones
```

---

## 📝 Resumen de Comandos

| Comando | Entorno | Descripción | Seguridad |
|---------|---------|-------------|-----------|
| `test:reset-users` | 🟢 Solo Local | Elimina usuarios de prueba y ejecuta seeders | 🛡️ Protege usuarios reales |
| `test:reset-users --force` | 🟢 Solo Local | Lo mismo sin confirmación | 🛡️ Protege usuarios reales |
| `profile:send-reminders` | 🌍 Todos | Envía recordatorios de perfil | ✅ Solo notificaciones |
| `profile:send-reminders --test` | 🌍 Todos | Incluye usuarios recientes | ✅ Solo notificaciones |
| `db:seed` | 🌍 Todos | Comando nativo de Laravel | ⚠️ Agrega datos |
| `migrate:fresh --seed` | 🌍 Todos | Reinicia DB completa | 🚨 Elimina TODO |

### **✅ Flujo de Desarrollo Recomendado:**
```bash
# 1. Reset de usuarios de prueba
docker compose exec app php artisan test:reset-users --force

# 2. Verificar estado
docker compose exec app php artisan tinker
>>> User::count()
>>> Mentor::count() 
>>> Aprendiz::count()

# 3. Probar recordatorios (opcional)
docker compose exec app php artisan profile:send-reminders --test
```

### **🎯 Caso de Uso Principal:**
"Actualicé mis seeders y necesito regenerar solo los datos de prueba sin afectar usuarios reales"

```bash
docker compose exec app php artisan test:reset-users --force
```