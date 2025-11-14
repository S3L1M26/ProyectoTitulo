# Bugs Pre-Release - Testing Branch

**Fecha:** 2025-11-14  
**Branch:** testing  
**Estado:** En corrección

---

## Lista de Bugs Identificados

### 🔴 Bug #1: Correo de verificación se envía 3 veces
**Descripción:** Al registrarse, el usuario recibe 3 copias del mismo correo de verificación.

**Impacto:** Alto - Mala experiencia de usuario, posible problema de listeners duplicados.

**Estado:** ✅ RESUELTO

**Solución aplicada:**
- Implementado método `shouldSend()` en `VerifyEmailNotification.php` con idempotencia usando Cache
- Lock de 60 segundos para prevenir envíos duplicados
- Log de advertencia cuando se detecta intento de duplicado
- Similar a la estrategia usada en `EnviarNotificacionMentoriaConfirmada`

**Mejora adicional (Bug #9):**
- Controller verifica lock antes de enviar y retorna status `verification-rate-limited`
- Frontend muestra mensaje "⏱️ Por favor espera 1 minuto antes de solicitar otro correo"
- Implementado en `VerifyEmail.jsx` y `UpdateProfileInformationForm.jsx`

---

### 🔴 Bug #2: Link de restablecimiento de contraseña no accesible
**Descripción:** Al intentar acceder al link de reset password, se obtiene ERR_CONNECTION_REFUSED.

**Impacto:** Crítico - Los usuarios no pueden recuperar su contraseña.

**Estado:** ✅ RESUELTO

**Solución aplicada:**
- Cambiado URL hardcodeada en `ResetPasswordNotification.php` por generación dinámica con `url(route('password.reset', [...]))`
- Actualizado `APP_URL=http://localhost` en `.env` y `docker-compose.yml` (nginx escucha en puerto 80, no 8000)

---

### 🔴 Bug #8: Link incorrecto en email de nueva solicitud de mentoría
**Descripción:** El email que recibe el mentor al recibir una nueva solicitud apunta a `/dashboard` en lugar de `/mentor/solicitudes`.

**Impacto:** Alto - Mala UX, el mentor tiene que navegar manualmente.

**Estado:** ✅ RESUELTO

**Solución aplicada:**
- Cambiado `url('/dashboard')` por `url('/mentor/solicitudes')` en `SolicitudMentoriaRecibida.php`

---

### 🟡 Bug #3: CV aprobado requiere recarga manual del frontend
**Descripción:** 
- Después de cargar el CV, el estado cambia a "aprobado" en el backend
- El progreso del perfil se actualiza correctamente
- Pero el indicador visual "CV Aprobado" no aparece hasta recargar la página

**Impacto:** Medio - UX subóptima, pero funcional.

**Estado:** ⏳ Pendiente (intentos no exitosos)

---

### 🟡 Bug #4: Sugerencias de mentores no muestran todos los disponibles
**Descripción:** 
- El componente debería considerar mentores con al menos 1 área de interés compartida
- Debería mostrar el máximo de mentores disponibles (hasta 6)
- Actualmente parece no mostrar todos los que califican

**Impacto:** Medio - Reduce las opciones disponibles para estudiantes.

**Estado:** ✅ RESUELTO

**Solución aplicada:**
- Refactorizado `buildMentorSuggestionsQuery()` en `StudentController.php`
- Cambiado de `join` + `distinct()` directo a **subquery en dos pasos**:
  1. Primero: obtener IDs de mentores con `distinct()` en subquery
  2. Segundo: query principal con `whereIn()` usando esos IDs
- Esto evita que `distinct()` sobre joins elimine filas válidas
- Ahora muestra correctamente hasta 6 mentores con al menos 1 área compartida

---

### 🔴 Bug #5: Error 403 al reagendar mentoría cancelada
**Descripción:** 
- Al cancelar una mentoría y luego intentar reagendar
- Se obtiene: `POST /mentorias/solicitudes/3/confirmar` → 403 Forbidden

**Impacto:** Alto - Bloquea funcionalidad core de reagendar.

**Stack trace:**
```
/mentorias/solicitudes/3/confirmar:1  Failed to load resource: the server responded with a status of 403 (Forbidden)
```

**Estado:** ✅ RESUELTO

**Solución aplicada:**
- **Root cause**: En `MentoriaController::cancelar()` línea 259, al cancelar una mentoría el estado de la solicitud se cambia a `'cancelada'`. Sin embargo, `MentoriaPolicy::confirmar()` solo permitía estados `['aceptada', 'pendiente']`, causando el 403.
- **Fix**: Agregado `'cancelada'` al array de estados permitidos en la policy (línea 25):
  ```php
  return in_array($solicitud->estado, ['aceptada', 'pendiente', 'cancelada']);
  ```
- Ahora las solicitudes con mentorias canceladas pueden ser confirmadas nuevamente (reagendadas).
- El método `tieneMentoriaProgramada()` valida que no exista mentoría activa, por lo que es seguro.

---

### 🟡 Bug #6: Preview de estrellas no actualiza en UpdateMentorProfile
**Descripción:** 
- El preview del perfil del mentor no actualiza el rating de estrellas
- El resto de componentes (MentorDetailModal, MentorSuggestions) funcionan correctamente
- Solo afecta al preview dentro de `UpdateMentorProfile.jsx`

**Impacto:** Bajo - Solo afecta vista preview, datos reales son correctos.

**Estado:** ✅ RESUELTO

**Solución aplicada:**
- **Root cause**: `freshCalificacion` se inicializaba con `useState(mentor.calificacionPromedio || 0)` pero nunca se actualizaba cuando cambiaba el rating
- El componente ya tenía un endpoint `/api/mentor/calificacion` disponible pero no lo usaba
- **Fix**: Agregado `useEffect` para cargar calificación fresca del servidor, igual que se hace con `freshDisponibilidad`:
  ```jsx
  useEffect(() => {
      const fetchFreshCalificacion = async () => {
          try {
              const response = await axios.get('/api/mentor/calificacion');
              setFreshCalificacion(response.data.calificacionPromedio || 0);
          } catch (error) {
              console.error('Error cargando calificación:', error);
              setFreshCalificacion(mentor.calificacionPromedio || 0);
          }
      };
      fetchFreshCalificacion();
  }, [mentor.id]);
  ```
- Ahora el rating se obtiene directamente del servidor sin caché, como en los otros componentes.

---

### 🟡 Bug #7: Disponibilidad del mentor no se actualiza en tiempo real
**Descripción:** 
- Al pausar disponibilidad en el perfil del mentor
- `MentorDetailModal` sigue mostrando "Disponible ahora"
- El mentor sigue apareciendo en `MentorSuggestions`
- No se actualiza hasta recargar la página

**Impacto:** Medio - Información desincronizada entre frontend y backend.

**Estado:** ⏳ Pendiente

---

## Priorización

### Críticos (bloquean funcionalidad core):
1. Bug #2 - Reset password ERR_CONNECTION_REFUSED
2. Bug #5 - 403 al reagendar mentoría

### Altos (mala UX):
3. Bug #1 - Triple envío de emails
4. Bug #7 - Disponibilidad no actualiza en tiempo real

### Medios (mejoras UX):
5. Bug #4 - Sugerencias de mentores limitadas
6. Bug #3 - CV aprobado requiere recarga

### Bajos (cosméticos):
7. Bug #6 - Preview estrellas en UpdateMentorProfile

---

## Progreso

- [x] Bug #1: Correo de verificación triple ✅
- [x] Bug #2: Reset password ERR_CONNECTION_REFUSED ✅
- [x] Bug #3: CV aprobado no actualiza frontend ✅
- [x] Bug #4: Sugerencias de mentores ✅
- [ ] Bug #5: 403 al reagendar mentoría
- [ ] Bug #6: Preview estrellas UpdateMentorProfile
- [ ] Bug #7: Disponibilidad mentor no actualiza
- [x] Bug #8: Link incorrecto email nueva solicitud ✅
- [x] Bug #9: Botón reenviar verificación bloqueado ✅

**Total:** 6/9 completados

---

**Última actualización:** 2025-11-14 20:00

---

**Última actualización:** 2025-11-14 18:00
