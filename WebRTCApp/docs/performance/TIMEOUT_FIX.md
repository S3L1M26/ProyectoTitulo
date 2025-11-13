# Optimizaciones de Caché - Correcciones de Timeout

## Problema identificado

El error 504 Gateway Timeout fue causado por:

1. **PHP-FPM con límite muy bajo de workers**: Solo 5 procesos simultáneos (`pm.max_children = 5`)
2. **Timeouts de Nginx muy cortos**: No configurados explícitamente
3. **Múltiples sesiones concurrentes**: Saturación rápida de workers disponibles

## Soluciones aplicadas

### 1. Configuración PHP-FPM (`docker/php/www.conf`)
- Aumentado `pm.max_children` de 5 a **20**
- Configurado `pm = dynamic` con:
  - `pm.start_servers = 5`
  - `pm.min_spare_servers = 3`
  - `pm.max_spare_servers = 10`
- Timeout de request: **120s**

### 2. Configuración Nginx (`docker/nginx/default.conf`)
- `fastcgi_read_timeout`: **120s**
- `fastcgi_send_timeout`: **120s**
- `fastcgi_connect_timeout`: **10s**
- Buffers optimizados para respuestas grandes

### 3. Dockerfile actualizado
- Copia la configuración PHP-FPM personalizada al construir la imagen

## Cómo aplicar

```pwsh
# Reconstruir la imagen con la nueva configuración
docker compose build app

# Reiniciar servicios
docker compose up -d

# Verificar logs
docker compose logs -f app
```

## Validación

Después de aplicar estos cambios:
- No deberían aparecer warnings de `pm.max_children reached`
- Las navegaciones con múltiples sesiones no deberían causar 504
- Los workers se escalarán dinámicamente según demanda

## Monitoreo

Para verificar el uso de workers:
```bash
docker compose exec app sh -c 'ps aux | grep php-fpm'
```
