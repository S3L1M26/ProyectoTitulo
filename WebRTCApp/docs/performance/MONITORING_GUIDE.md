# Quick Performance Monitoring Guide

## 🚀 Start Monitoring

### 1. Restart Octane (Apply Changes)
```bash
cd c:\My_Web_Sites\Pruebas_Practica\webrtc\WebRTCApp
php artisan octane:reload
```

### 2. Watch Logs in Real-Time
```bash
# PowerShell
Get-Content storage\logs\laravel.log -Wait -Tail 50 | Select-String "🐌|⚠️"

# Git Bash / WSL
tail -f storage/logs/laravel.log | grep -E "🐌|⚠️"
```

### 3. Load Dashboard Pages
- Student: http://localhost/student/dashboard
- Mentor: http://localhost/mentor/dashboard

---

## 📊 What to Look For

### Slow Query Log Format
```json
{
  "message": "🐌 SLOW QUERY DETECTED",
  "time_ms": 450.23,
  "sql": "select * from users where ...",
  "bindings": [1, "mentor"],
  "connection": "mysql",
  "caller": "/app/Http/Controllers/Student/StudentController.php:75"
}
```

### Performance Degradation Log
```json
{
  "message": "⚠️ PERFORMANCE DEGRADATION DETECTED",
  "route": "student.dashboard",
  "execution_time_ms": 2345.67,
  "query_count": 15,
  "memory_usage_mb": 12.5,
  "issues": ["Slow execution: 2345ms", "Too many queries: 15"],
  "slowest_queries": [...]
}
```

---

## 🎯 Performance Thresholds

### Critical Routes (student/mentor dashboards)
- ✅ **Execution time:** <500ms
- ✅ **Query count:** <5 queries
- ✅ **Memory:** <50MB

### Individual Queries
- ⚠️ **Slow:** >300ms
- 🚨 **Critical:** >1000ms

---

## 🔍 Common Issues to Identify

### 1. N+1 Query Pattern
**Symptom:**
```
Query count: 25
Slowest: SELECT * FROM areas_interes WHERE mentor_id = ? (repeated 20x)
```
**Fix:** Eager loading missing or incorrect

### 2. Missing Index
**Symptom:**
```
time_ms: 1500
sql: SELECT * FROM solicitud_mentorias WHERE mentor_id = ? AND estado = ?
```
**Fix:** Add compound index `(mentor_id, estado)`

### 3. Inefficient Join
**Symptom:**
```
time_ms: 800
sql: SELECT * FROM users INNER JOIN mentors ... WHERE users.role = 'mentor'
```
**Fix:** Index on `users.role` or filter earlier

### 4. Cache Miss
**Symptom:**
- First load: 15 queries, 2000ms
- Reload: 15 queries, 1800ms (still slow)

**Fix:** Cache not working; check Redis connection or key generation

---

## 🛠️ Quick Fixes

### Add Index (example)
```php
// database/migrations/XXXX_add_performance_indices.php
Schema::table('mentors', function (Blueprint $table) {
    $table->index(['disponible_ahora', 'calificacionPromedio']);
});
```

### Fix N+1 (example)
```php
// Before
$mentors = User::all();
foreach ($mentors as $m) { $m->areasInteres; } // N+1!

// After
$mentors = User::with('mentor.areasInteres')->get(); // 2 queries total
```

### Clear Cache (testing)
```bash
php artisan cache:clear
redis-cli FLUSHDB  # Or: redis-cli KEYS "mentor_*" | xargs redis-cli DEL
```

---

## 📈 Check Performance Headers

```bash
# PowerShell
$response = Invoke-WebRequest -Uri "http://localhost/student/dashboard" `
  -Headers @{"Cookie"="your_session_cookie"}
$response.Headers["X-Performance-Time"]
$response.Headers["X-Performance-Queries"]
$response.Headers["X-Performance-Memory"]

# Curl (Git Bash)
curl -I http://localhost/student/dashboard \
  -H "Cookie: laravel_session=..." \
  | grep X-Performance
```

---

## 🎨 Log Emoji Legend

| Emoji | Meaning | Severity |
|-------|---------|----------|
| 🐌 | Slow query (>300ms) | Warning |
| ⚠️ | Performance degradation | Warning |
| 🔍 | Event listener count | Info |
| 🔔 | Listener executed | Info |
| ⛔ | Duplicate skip (idempotency) | Info |
| 📨 | Job enqueued | Info |
| 🚀 | Job start | Info |
| ✅ | Job completed | Success |

---

## 📝 Next Steps After Data Collection

1. **Identify patterns:** Group slow queries by type
2. **Prioritize:** Focus on queries >1000ms first
3. **Test fixes:** Apply one optimization at a time
4. **Validate:** Compare before/after metrics
5. **Document:** Update this file with successful fixes

---

## 🔗 Related Files

- Performance Plan: `docs/performance/DASHBOARD_OPTIMIZATION_PLAN.md`
- Middleware: `app/Http/Middleware/PerformanceMonitoringMiddleware.php`
- Provider: `app/Providers/DatabaseQueryServiceProvider.php`
- Controllers: `app/Http/Controllers/{Student,Mentor}/*Controller.php`
