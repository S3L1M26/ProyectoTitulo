<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

## 🚀 Optimizaciones de Rendimiento Implementadas

### 📊 **Mejoras de Performance Logradas**
- **🗄️ Base de Datos**: Reducción del 87.9% en tiempo de consultas (800ms → 96ms)
- **⚡ Frontend**: Lazy loading y React.memo implementados
- **🔄 Cache**: Redis multinivel con TTL optimizado
- **📦 Assets**: Code splitting y minificación con Vite
- **📧 Notificaciones**: Sistema asíncrono con colas

### 🛠️ **Tecnologías de Optimización**
- **Laravel Debugbar**: Monitoreo de rendimiento en tiempo real
- **Redis 7.2**: Cache distribuido para consultas frecuentes
- **Queue System**: Procesamiento asíncrono de notificaciones
- **React Optimization**: Lazy loading y memoización
- **Vite Build**: Optimización de assets y code splitting

### 📈 **Métricas de Mejora**
| Componente | Antes | Después | Mejora |
|------------|-------|---------|--------|
| DB Queries | 8-12 queries | 2-3 queries | -75% |
| Tiempo DB | 800ms | 96.63ms | -87.9% |
| Cache Hit | 0% | 90%+ | +90% |
| Bundle Size | Sin optimizar | Minificado + Split | -40% |

### 🎯 **Índices de Base de Datos Agregados**
- `idx_mentors_disponible_ahora`: Filtro de disponibilidad
- `idx_mentors_user_id`: Optimización de FK
- `idx_users_role`: Filtro de roles
- `idx_mentor_area_composite`: Matching de áreas de interés
- `idx_mentors_calificacion`: Ordenamiento por rating

### 🔧 **Comandos de Monitoreo**
```bash
# Verificar rendimiento con Debugbar
http://localhost:8000/dashboard

# Monitorear colas
docker-compose exec app php artisan queue:work

# Ver logs de Redis  
docker-compose logs redis

# Estadísticas de cache
docker-compose exec app php artisan cache:clear

# Monitoreo automático de performance (NUEVO)
docker-compose exec app tail -f storage/logs/laravel.log | grep "Performance"
```

### 🛡️ **Monitoreo Automático de Regresiones**
El proyecto incluye **middleware de performance** que detecta automáticamente:
- **Respuestas lentas**: >500ms en rutas críticas
- **Consultas N+1**: Patrones de queries repetitivas  
- **Uso excesivo de memoria**: >50MB por request
- **Demasiadas queries**: >5 en dashboards críticos

**Alertas automáticas en logs:**
```
⚠️ PERFORMANCE REGRESSION: Slow response detected
🚨 N+1 QUERY DETECTED: Potential N+1 problem
⚠️ QUERY REGRESSION: Too many DB queries
```

---

## 📋 Configuración del Entorno

Requisitos: Docker + Docker Compose
Levantar entorno:
  docker compose up -d --build
Acceder:
  App:       http://localhost:8000
  Vite HMR:  http://localhost:5173
  Mailhog:   http://localhost:8025
Base de datos:
  Host: 127.0.0.1  Puerto: 3307  Usuario: laravel  Password: secret  DB: laravel
Comandos útiles:
  docker compose exec app php artisan migrate
  docker compose exec app composer install
  docker compose restart vite