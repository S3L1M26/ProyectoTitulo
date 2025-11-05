# 🚀 DEPLOYMENT GUIDE - WebRTCApp

Este archivo contiene instrucciones para deployar la aplicación en diferentes entornos de producción.

## 📋 Pre-requisitos

### Preparación del Código
```bash
# 1. Asegúrate de estar en la rama correcta
git checkout main

# 2. Instala dependencias
composer install --no-dev --optimize-autoloader
npm install

# 3. Build de producción
npm run build:prod
```

### Configuración de Entorno
```bash
# 1. Copia el archivo de configuración de producción
cp .env.production .env

# 2. Edita las variables según tu proveedor cloud
nano .env

# 3. Genera una nueva APP_KEY si es necesario
php artisan key:generate --force
```

## 🐳 Deployment con Docker

### Opción 1: Docker Compose (Recomendado para VPS)
```bash
# Build y deploy
docker-compose -f docker-compose.production.yml up -d --build

# Ejecutar migraciones
docker-compose -f docker-compose.production.yml exec app php artisan migrate --force

# Verificar estado
docker-compose -f docker-compose.production.yml ps
```

### Opción 2: Imagen Docker Manual
```bash
# Build de la imagen
docker build -t webrtcapp:latest .

# Run
docker run -d \
  --name webrtcapp \
  -p 80:80 \
  -e APP_ENV=production \
  --env-file .env \
  webrtcapp:latest
```

## ☁️ Deployment en Cloud Providers

### AWS (Elastic Beanstalk / ECS)
```bash
# 1. Configurar AWS CLI
aws configure

# 2. Para Elastic Beanstalk
eb init
eb create production
eb deploy

# 3. Para ECS
# Usar docker-compose.production.yml con AWS Fargate
```

### Azure (Container Apps / App Service)
```bash
# 1. Login a Azure
az login

# 2. Para Container Apps
az containerapp up \
  --name webrtcapp \
  --resource-group your-rg \
  --environment your-env \
  --image your-registry/webrtcapp:latest

# 3. Para App Service
az webapp create \
  --name webrtcapp \
  --resource-group your-rg \
  --plan your-plan \
  --deployment-container-image-name your-registry/webrtcapp:latest
```

### Google Cloud (Cloud Run / GKE)
```bash
# 1. Configurar gcloud
gcloud auth login

# 2. Para Cloud Run
gcloud run deploy webrtcapp \
  --image gcr.io/your-project/webrtcapp:latest \
  --platform managed \
  --region us-central1 \
  --allow-unauthenticated

# 3. Para GKE
# Usar docker-compose.production.yml convertido a k8s manifests
```

### DigitalOcean (App Platform / Droplet)
```bash
# 1. Para App Platform
# Usar el archivo .do/app.yaml (crear si no existe)

# 2. Para Droplet
# Usar docker-compose.production.yml
```

## 🗄️ Configuración de Base de Datos

### Servicios Gestionados Recomendados

#### AWS RDS
```env
DB_HOST=your-rds-endpoint.amazonaws.com
DB_PORT=3306
DB_DATABASE=webrtcapp
DB_USERNAME=admin
DB_PASSWORD=your-secure-password
```

#### Azure Database for MySQL
```env
DB_HOST=your-server.mysql.database.azure.com
DB_PORT=3306
DB_DATABASE=webrtcapp
DB_USERNAME=admin@your-server
DB_PASSWORD=your-secure-password
```

#### Google Cloud SQL
```env
DB_HOST=your-instance-ip
DB_PORT=3306
DB_DATABASE=webrtcapp
DB_USERNAME=root
DB_PASSWORD=your-secure-password
```

## 💾 Configuración de Cache/Redis

### AWS ElastiCache
```env
REDIS_HOST=your-cluster.cache.amazonaws.com
REDIS_PORT=6379
REDIS_PASSWORD=your-auth-token
```

### Azure Cache for Redis
```env
REDIS_HOST=your-cache.redis.cache.windows.net
REDIS_PORT=6380
REDIS_PASSWORD=your-access-key
```

## 📧 Configuración de Email

### AWS SES
```env
MAIL_MAILER=ses
MAIL_HOST=email-smtp.us-east-1.amazonaws.com
MAIL_PORT=587
MAIL_USERNAME=your-smtp-user
MAIL_PASSWORD=your-smtp-password
```

### SendGrid
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-api-key
```

## 🔧 Post-Deployment Tasks

### Comandos Esenciales
```bash
# Ejecutar migraciones
php artisan migrate --force

# Optimizaciones de Laravel
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Limpiar cache si es necesario
php artisan cache:clear
php artisan config:clear

# Generar sitemap (si tienes uno)
php artisan sitemap:generate
```

### Health Checks
```bash
# Verificar que la aplicación responda
curl https://your-domain.com/health

# Verificar logs
tail -f storage/logs/laravel.log

# Verificar workers de queue
php artisan queue:work --daemon
```

## 🔒 Configuración de SSL/HTTPS

### Let's Encrypt (Gratuito)
```bash
# Instalar certbot
sudo apt-get install certbot python3-certbot-nginx

# Obtener certificado
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# Renovación automática
sudo crontab -e
# Agregar: 0 12 * * * /usr/bin/certbot renew --quiet
```

### CloudFlare (Recomendado)
1. Configurar DNS en CloudFlare
2. Activar SSL/TLS Full (strict)
3. Activar HTTP/3, Brotli, Auto Minify

## 📊 Monitoring y Logs

### New Relic
```env
NEW_RELIC_LICENSE_KEY=your-license-key
NEW_RELIC_APPNAME="WebRTCApp Production"
```

### Sentry
```env
SENTRY_LARAVEL_DSN=your-sentry-dsn
```

### Logging
```env
LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=error
```

## 🔄 CI/CD Pipeline

### GitHub Actions Example
```yaml
# .github/workflows/deploy.yml
name: Deploy to Production
on:
  push:
    branches: [ main ]
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Deploy to server
        run: |
          # Your deployment commands here
```

## 🆘 Troubleshooting

### Problemas Comunes

#### Assets no cargan
```bash
# Verificar permisos
sudo chown -R www-data:www-data public/build
sudo chmod -R 755 public/build
```

#### Base de datos no conecta
```bash
# Verificar configuración
php artisan tinker
>>> DB::connection()->getPdo();
```

#### Cache issues
```bash
# Limpiar todo
php artisan optimize:clear
```

### Logs Importantes
- `storage/logs/laravel.log` - Errores de aplicación
- `/var/log/nginx/error.log` - Errores de Nginx
- `docker-compose logs app` - Logs de contenedor

## 🔧 Maintenance Mode

```bash
# Activar modo mantenimiento
php artisan down --refresh=15 --retry=60 --secret="your-secret"

# Desactivar modo mantenimiento  
php artisan up
```

---

## 📞 Support

Si tienes problemas durante el deployment:
1. Revisa los logs de la aplicación
2. Verifica la configuración de variables de entorno
3. Asegúrate de que todos los servicios externos estén configurados
4. Consulta la documentación de tu proveedor cloud

¡Buena suerte con tu deployment! 🚀