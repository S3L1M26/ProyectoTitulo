# WebRTCApp - Documentación de Despliegue en Producción

## 📖 Tabla de Contenidos

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Arquitectura de Producción](#arquitectura-de-producción)
3. [Pre-requisitos](#pre-requisitos)
4. [Guía Rápida de Deployment (5 pasos)](#guía-rápida-de-deployment-5-pasos)
5. [Deployment Detallado](#deployment-detallado)
6. [Configuración Post-Deployment](#configuración-post-deployment)
7. [Monitoreo](#monitoreo)
8. [Troubleshooting](#troubleshooting)
9. [Referencias](#referencias)

---

## 🎯 Resumen Ejecutivo

**WebRTCApp** es una aplicación Laravel 12 con React/Inertia para gestionar mentorías con video y documentación.

**Stack de Producción:**
- **Backend:** PHP 8.4 + Laravel 12 + Octane/RoadRunner
- **Frontend:** React 18 + Inertia.js + Vite
- **Base de Datos:** MySQL 8.0
- **Cache/Queue:** Redis 7.0
- **Storage:** S3 (DigitalOcean Spaces)
- **Email:** SMTP (SendGrid)
- **Plataforma:** DigitalOcean App Platform

**Costo Estimado:** ~$48/mes (GRATIS con GitHub Student Pack)

**Tiempo de Deployment:** ~2-3 horas (incluye setup de servicios)

---

## 🏗️ Arquitectura de Producción

```
┌─────────────────────────────────────────┐
│        DigitalOcean App Platform        │
├─────────────────────────────────────────┤
│                                         │
│  ┌──────────────┐  ┌──────────────┐    │
│  │   Nginx      │  │   Nginx      │    │
│  │  (Load Bal.) │──│  (Load Bal.) │    │
│  └──────┬───────┘  └──────┬───────┘    │
│         │                  │             │
│  ┌──────▼────────┬─────────▼────────┐  │
│  │   PHP/Octane  │  PHP/Octane      │  │
│  │   (Web App)   │  (Web App)       │  │
│  └──────┬────────┴─────────┬────────┘  │
│         │                  │             │
│  ┌──────▼────────┐  ┌──────▼────────┐  │
│  │ Queue Worker  │  │  Scheduler    │  │
│  │ (Background)  │  │  (Cron)       │  │
│  └───────────────┘  └───────────────┘  │
│                                         │
└─────────────────────────────────────────┘
         ▲                    ▲
         │                    │
    ┌────┴────┐          ┌────┴────┐
    │          │          │          │
┌───▼──┐  ┌────▼──┐  ┌───▼──┐  ┌──▼──────┐
│MySQL │  │Redis  │  │ S3   │  │SendGrid│
│  DB  │  │Cache  │  │Storage│  │ Email  │
└──────┘  └───────┘  └──────┘  └────────┘

DigitalOcean Managed Services (Externo)
```

### Componentes

**1. Nginx (Web Server)**
- Reverse proxy a RoadRunner
- Compresión gzip
- Rate limiting
- Assets estáticos

**2. PHP/Octane (App Server)**
- RoadRunner worker pool
- 4 workers por defecto
- Max 500 requests por worker
- Health checks cada 30s

**3. Queue Worker**
- Procesa jobs: emails, notificaciones
- Redis driver
- Reintento automático (3 intentos)

**4. Scheduler**
- Tareas programadas (cron)
- Correr cada minuto
- Incluye backups, reportes, etc.

**5. Managed Services**
- **MySQL:** Base de datos relacional (High Availability)
- **Redis:** Cache y Queue (SSL)
- **S3 (Spaces):** Almacenamiento de archivos
- **SendGrid:** Email transaccional
- **Sentry:** Error tracking

---

## 📋 Pre-requisitos

### Cuentas Online
- [ ] GitHub con acceso al repositorio
- [ ] DigitalOcean con GitHub Student Pack (recomendado)
- [ ] SendGrid (gratis con GitHub Student Pack)
- [ ] Sentry (free plan)

### Software Local
- [ ] Git (`git --version`)
- [ ] Docker & Docker Compose (para testing local)
- [ ] PHP 8.4+ CLI (`php --version`)
- [ ] Node.js 20+ (`node --version`)
- [ ] Composer (`composer --version`)

### Verificación Local

```bash
# En el root del proyecto
./scripts/verify-do-deployment.sh

# Output esperado:
# ✓ LISTO PARA DESPLEGAR
```

---

## 🚀 Guía Rápida de Deployment (5 pasos)

### Paso 1: Generar Secretos (5 min)

**En Linux/macOS:**
```bash
chmod +x scripts/generate-do-secrets.sh
./scripts/generate-do-secrets.sh
# Seguir el asistente interactivo
```

**En Windows (PowerShell):**
```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope Process
.\scripts\generate-do-secrets.ps1
```

Output:
- ✅ `secrets.env` (NO COMETER)
- ✅ `secrets.json` (referencia)

### Paso 2: Crear Servicios Gestionados en DigitalOcean (10 min)

1. **MySQL Cluster**
   - Nombre: `webrtcapp-db`
   - Version: 8.0.38+
   - Nodos: 2 (HA)
   - Storage: 100GB
   - SSL: ✅ Requerido

2. **Redis Cluster**
   - Nombre: `webrtcapp-redis`
   - Version: 7.0+
   - Nodos: 2 (HA)
   - Memory: 250MB
   - SSL: ✅ Requerido

3. **Spaces Bucket**
   - Nombre: `webrtcapp-storage`
   - Region: NYC3
   - CDN: Opcional

### Paso 3: Crear App en DigitalOcean (5 min)

1. DigitalOcean Dashboard → Apps → Create App
2. Source: GitHub → tu repo → rama main
3. Loaded spec: Editar y pegar contenido de `app.yaml`
4. Verificar servicios: app, web, queue, scheduler, db-migrate

### Paso 4: Agregar Variables de Entorno (10 min)

En DigitalOcean App Panel:
- Settings → Environment
- Copiar **todas** las variables de `secrets.env`
- Marcar como "Secret" si es sensible

### Paso 5: Deploy y Verificar (15 min)

1. Click "Create App" en DigitalOcean
2. Esperar 10-15 minutos
3. Verificar en Deployments tab
4. Testear health endpoint:
   ```bash
   curl https://yourdomain.com/health
   # Respuesta: {"status":"healthy"}
   ```

---

## 📚 Deployment Detallado

Ver: [`DIGITALOCEAN_DEPLOYMENT_GUIDE.md`](./DIGITALOCEAN_DEPLOYMENT_GUIDE.md)

Contiene:
- ✅ Requerimientos en detalle
- ✅ Screenshots paso a paso
- ✅ Configuración de DNS
- ✅ Primeros comandos post-deploy
- ✅ Testing manual

---

## ⚙️ Configuración Post-Deployment

### 1. Ejecutar Migraciones

```bash
# DigitalOcean App Panel → App → Console → app service

php artisan migrate --force --seed
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan storage:link
```

### 2. Verificar Logs

```bash
# En DigitalOcean App Panel → Logs

# Buscar errores:
ERROR
Exception
Timeout
Connection refused
```

### 3. Verificar Servicios

```bash
# En DigitalOcean App Panel → Metrics

# Monitorear:
- CPU Usage (debería estar < 30%)
- Memory Usage (debería estar < 60%)
- Request Count (debería ser > 0)
- Error Rate (debería ser 0%)
```

### 4. Configurar Dominio

```bash
# Si es dominio en DigitalOcean:
# 1. Networks → Domains → Select domain
# 2. Create A record:
#    Host: @ (root)
#    Type: A
#    Data: [IP provided by App Platform]

# Si es dominio externo:
# 1. En registrador (GoDaddy, Namecheap, etc)
# 2. Crear CNAME: yourdomain.com → cname.ondigitalocean.app
```

### 5. Verificar SSL

```bash
# Certificado Let's Encrypt se genera automáticamente
curl -I https://yourdomain.com
# HTTP/2 200
# Certificate válido
```

---

## 📊 Monitoreo

### DigitalOcean Dashboard

1. **App Panel → Metrics**
   - CPU, RAM, Network
   - Request count
   - Error rate

2. **App Panel → Logs**
   - Stdout/stderr en tiempo real
   - Filtrar por servicio
   - Búsqueda por keyword

3. **Deployments**
   - Estado de deploy
   - Build logs
   - Rollback si es necesario

### Sentry Error Tracking

1. https://sentry.io/dashboard
2. Proyecto: webrtcapp
3. Configurar alertas:
   - New issue (todos)
   - Critical errors (solo críticos)

### Métricas Recomendadas

| Métrica | Umbral | Acción |
|---------|--------|--------|
| CPU | > 70% | Aumentar instance size |
| RAM | > 80% | Aumentar instance size |
| Error Rate | > 1% | Revisar logs en Sentry |
| Response Time | > 5s | Optimizar queries |
| Queue Size | > 1000 | Aumentar workers |

---

## 🔧 Troubleshooting

### 502 Bad Gateway

**Causas:**
- APP no inicia
- Database connection failed
- Redis connection failed

**Solución:**
```bash
# Ver logs
DigitalOcean App Panel → Logs → filter "app"

# Verificar:
1. DB_HOST, DB_PORT, credenciales
2. REDIS_HOST, REDIS_PASSWORD
3. Todos los ENV vars requeridos
```

### Health Check Failing

**Causas:**
- Endpoint /health no existe
- APP no responde

**Solución:**
```bash
# Verificar en routes/web.php:
Route::get('/health', function () {
    return response()->json(['status' => 'healthy']);
});

# Re-deploy:
Deployments → Latest Deployment → Redeploy
```

### Archivos no se suben a Spaces

**Causas:**
- Credenciales AWS incorrectas
- Bucket no existe o no está accesible

**Solución:**
```bash
# Verificar en Sentry logs
# Revisar:
- AWS_ACCESS_KEY_ID
- AWS_SECRET_ACCESS_KEY
- AWS_ENDPOINT (debe ser S3-compatible)

# Test local:
php artisan tinker
Storage::put('test.txt', 'test');
```

### Emails no se envían

**Causas:**
- SendGrid API key incorrecta
- Sender no verificado en SendGrid
- Rate limit excedido

**Solución:**
```bash
# Verificar:
1. MAIL_PASSWORD = SG.xxxxx (not user password)
2. MAIL_FROM_ADDRESS verificado en SendGrid
3. SendGrid dashboard → Logs (para ver errores)

# Test:
php artisan tinker
Mail::raw('test', fn ($msg) => $msg->to('test@example.com')->send());
```

### App lenta (> 5s respuesta)

**Causas:**
- Queries lentas
- N+1 queries
- Cache no configurado

**Solución:**
```bash
# Verificar logs MySQL slow query log
# Optimizar:
- Agregar índices
- Usar SELECT específicos
- Implementar caching

# En Sentry:
- Ver Performance → Transactions > 5000ms
- Identificar función lenta
```

### OOM (Out of Memory)

**Causas:**
- Memoria insuficiente
- Memory leak en PHP

**Solución:**
```bash
# Aumentar instance size
DigitalOcean App Panel → Settings → Instance Size
# Cambiar a: basic-s (1 CPU, 1GB RAM)

# Monitorear:
# En Sentry → Performance
# Buscar: memory_usage > 900MB
```

---

## 📞 Contacto y Soporte

### Documentación Oficial

- [DigitalOcean App Platform](https://docs.digitalocean.com/products/app-platform/)
- [Laravel Octane](https://laravel.com/docs/octane)
- [RoadRunner](https://roadrunner.dev/)
- [GitHub Student Pack](https://education.github.com/pack)

### Recursos Útiles

- [DigitalOcean Community](https://www.digitalocean.com/community)
- [Laravel Discord](https://discord.gg/laravel)
- [Sentry Documentation](https://docs.sentry.io/platforms/php/guides/laravel/)

---

## 📋 Checklist Final

Antes de ir a producción:

### Seguridad
- [ ] APP_KEY rotado (nuevo para produción)
- [ ] APP_DEBUG=false
- [ ] HTTPS/SSL habilitado
- [ ] DB SSL requerido
- [ ] Redis SSL habilitado
- [ ] .env.* en .gitignore
- [ ] Credenciales NO commiteadas

### Rendimiento
- [ ] Assets compilados (npm run build)
- [ ] OPcache habilitado en PHP
- [ ] Config cacheado (config:cache)
- [ ] Routes cacheado (route:cache)
- [ ] Health checks configurados

### Monitoreo
- [ ] Sentry DSN configurado
- [ ] Logs en stdout (para DigitalOcean)
- [ ] Métricas en DigitalOcean dashboard
- [ ] Alertas en Sentry (critical)

### Backups
- [ ] Backups automáticos MySQL (enabled)
- [ ] Backups en Spaces (cronjob)
- [ ] Plan de recovery documentado

### Testing
- [ ] Health endpoint: ✅ https://yourdomain.com/health
- [ ] Login: ✅ Acceder con usuario seed
- [ ] Upload archivos: ✅ Verificar en Spaces
- [ ] Emails: ✅ Verificar en SendGrid logs
- [ ] Queue: ✅ Verificar en worker logs

---

## 🎯 Próximos Pasos

1. **Immediato (hoy):**
   - [ ] Generar secretos locales
   - [ ] Crear servicios gestionados en DigitalOcean
   - [ ] Desplegar app.yaml

2. **Corto plazo (esta semana):**
   - [ ] Configurar dominio personalizado
   - [ ] Verificar logs y monitoreo
   - [ ] Test end-to-end
   - [ ] Actualizar documentación

3. **Mediano plazo (este mes):**
   - [ ] Implementar CI/CD con GitHub Actions
   - [ ] Configurar backups automáticos
   - [ ] Optimizar performance
   - [ ] Capacitación de equipo

---

**Última actualización:** 2024
**Versión:** 1.0
**Mantenedor:** [tu nombre]
**Licencia:** MIT
