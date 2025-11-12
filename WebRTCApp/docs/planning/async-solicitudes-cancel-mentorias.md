# Plan de Ejecución: Actualización Asíncrona de Solicitudes, UI Expandible y Cancelación de Mentorías

Fecha: 2025-11-08
Estado: Planning
Owner: Equipo Plataforma Mentorías

## Objetivos
1. Actualizar automáticamente (sin recargar página) las solicitudes de mentoría del estudiante.
2. Hacer cada solicitud (en vista de estudiante) expandible: mostrar detalles, estado y, si está aceptada/confirmada, link de Zoom + horario.
3. Permitir que el mentor cancele una mentoría confirmada: eliminar reunión de Zoom, invalidar caché y notificar.
4. Mantener performance (mínimo overhead) y consistencia de datos.

## Alcance
Incluye frontend (Inertia + React), backend (controladores, jobs, eventos, notificaciones), integración Zoom y capa de caché. No incluye todavía reprogramación ni recordatorios por correo (scope futuro).

## Resumen de Estrategia
- Real-time ligero con Polling inteligente (fase 1) → Opcional migración a Broadcasting (fase 2) usando Laravel Echo + Redis + Pusher/socket interno.
- Normalización de payload de solicitudes reutilizable para listado y expansión.
- Evento de Dominio: `SolicitudMentoriaActualizada` emitido en create/accept/reject + al generar mentoría confirmada.
- Para cancelación: nuevo endpoint `mentor/mentorias/{id}/cancel` que:
  - Verifica ownership y estado `confirmada`.
  - Llama a `ZoomService::cancelarReunion()` si hay `zoom_meeting_id`.
  - Marca mentoría `cancelada`, limpia campos `enlace_reunion`, `zoom_meeting_id`, `zoom_password`.
  - Emite evento y notificación.
  - Invalida caché dependiente.

## Componentes a Modificar / Crear
### Backend
| Componente | Acción | Detalle |
|------------|--------|---------|
| `SolicitudMentoriaController` | Nuevo método API `student.solicitudes.poll` | Retorna hash `etag` y lista si hay cambios. |
| `Mentoria` (modelo) | Método `cancelarConZoom()` | Envoltorio transaccional para cancelar y limpiar. |
| `MentoriaController` (nuevo o existente) | Endpoint `cancel` | Ruta protegida (mentor). |
| `Events/` | `SolicitudMentoriaActualizada`, `MentoriaCancelada` | Disparados tras cambios. |
| `Listeners/` | Opcional broadcasting futuro | Preparar stub. |
| `Notifications/` | `MentoriaCanceladaNotification` | A estudiante. |
| `ZoomService` | Reutilización | Ya expone `cancelarReunion`. |
| `routes/web.php` | Nuevas rutas | Cancelar mentoría, polling. |

### Frontend (React + Inertia)
| Archivo | Cambio |
|---------|--------|
| `resources/js/Pages/Student/Solicitudes/Index.jsx` | Añadir estado local + efecto polling + expansión por ítem. |
| `resources/js/Components/SolicitudRow.jsx` (nuevo) | Componente colapsable. |
| `resources/js/Components/MentoriaInfo.jsx` (nuevo) | Muestra link + horario si confirmada. |
| `resources/js/Pages/Mentor/Dashboard/Index.jsx` | Botón "Cancelar" en mentorías confirmadas. |
| `resources/js/lib/api.js` (nuevo) | Helper fetch/poll con abort + ETag. |

## Flujo de Polling (Fase 1)
1. Al montar página de solicitudes del estudiante:
   - Guardar `lastEtag` (hash de contenidos, p.ej. sha1 de concat ids+updated_ats).
   - Ejecutar `GET /api/student/solicitudes?etag=XYZ`.
2. Backend:
   - Calcula etag actual.
   - Si coincide con `?etag=` → responde `304 { changed: false }`.
   - Si no: responde `200 { changed: true, etag, items:[...] }`.
3. Frontend:
   - Si `changed` → actualiza estado + re-render solo diferencias (key por id).
4. Intervalo incremental: 10s → si 3 ciclos sin cambios, subir a 20s (máx 30s); si cambio, reset a 10s.

## Expansión de Solicitudes
Campos base (lista): `id, estado, fecha_solicitud, fecha_respuesta, mentor{name, id}, mensaje_truncado`.
Campos extra (expandido): `mensaje_completo, mentor.bio, mentor.areas_interes[], mentor.años_experiencia, mentoria{fecha_formateada, hora_formateada, enlace_reunion}`.
Optimización: backend siempre puede enviar completo; frontend decide qué renderizar.

## Cancelación de Mentorías
Secuencia:
1. Mentor hace click en "Cancelar".
2. Modal de confirmación (motivo opcional – futuro).
3. `DELETE /mentor/mentorias/{id}` (o `POST /mentor/mentorias/{id}/cancel`).
4. Backend:
   - Verifica estado `confirmada`.
   - Transaction: cancelar Zoom (best-effort; si 404 → continuar), actualizar DB.
   - Emitir evento + notificación.
   - Invalidar cache: `student_solicitudes_*`, `mentor_*`, `mentorias_*` relacionados.
5. Frontend:
   - Refrescar listado (forzado: ignorar etag) + toast.

## Modelo de Estados (Solicitudes vs Mentorías)
| Solicitud | Transición | Mentoría | Acción |
|-----------|-----------|----------|--------|
| pendiente | → aceptada | se crea mentoría (programada) | Generar reunión Zoom (futuro si no se hace aún) |
| pendiente | → rechazada | — | Notificación y cierre |
| aceptada | → confirmada (futuro) | estado mentoría: confirmada | Mostrar enlace |
| confirmada | → cancelada | mentoría cancelada | Eliminar Zoom |

(Adaptar si ya se genera reunión en `accepted`).

## Métricas a Loggear
- `poll_solicitudes.hit` (304 vs 200)
- `poll_solicitudes.latency_ms`
- `cancel_mentoria.success` / `.failure` (con causa)
- `zoom.cancel.status`

## Seguridad y Consistencia
- Rate limiting para endpoint polling (`throttle:60,1`).
- Validar ownership en cancelación (`mentor_id == auth()->id()`).
- Manejar fallos de Zoom: continuar cancelación local si 404.
- Evitar condición de carrera: `SELECT ... FOR UPDATE` opcional al cancelar.

## Riesgos
| Riesgo | Mitigación |
|--------|------------|
| Polling excesivo | ETag + backoff adaptativo |
| Latencia en Zoom API | Cola asíncrona (futuro) para cancelación pesada |
| Estado inconsistente (cancel OK, Zoom fail) | Guardar bandera `zoom_cancel_error` opcional |
| Fugas de memoria en frontend | Limpiar intervals en unmount |

## Roadmap Fases
| Fase | Entrega |
|------|---------|
| 1 | Polling + expansión + cancel básico |
| 2 | Broadcasting (Echo) |
| 3 | Reprogramar mentoría |
| 4 | Recordatorios (jobs) |

## Checklist Implementación (Fase 1)
- [ ] Ruta API GET `/api/student/solicitudes` con soporte `etag`
- [ ] Helper `SolicitudMentoria::collectionForStudent($id)` centralizado
- [ ] SHA1 etag builder (ids + updated_at)
- [ ] Componente React `SolicitudRow` expandible
- [ ] Hook `usePollingSolicitudes()`
- [ ] UI estados: cargando / sin cambios / error / vacía
- [ ] Endpoint cancelar mentoría
- [ ] Método `Mentoria::cancelarConZoom()`
- [ ] Notificación `MentoriaCanceladaNotification`
- [ ] Invalidación de caché consistente
- [ ] Logs estructurados
- [ ] Tests: unit (etag), feature (poll 304/200), cancellation (estado + zoom mock)

## Testing Plan Resumido
| Tipo | Caso |
|------|------|
| Unit | Generación de etag determinista |
| Unit | `Mentoria::cancelarConZoom()` limpia campos |
| Feature | Poll sin cambios → 304 |
| Feature | Poll con nueva solicitud → 200 incluye nueva |
| Feature | Cancel mentoría → estado cancelada + notificación |
| Mock Zoom | 404 en cancel → sigue flujo |

## Ejemplo de Respuesta Poll (200)
```json
{
  "etag": "9d4c7e...",
  "changed": true,
  "items": [
    {
      "id": 17,
      "estado": "aceptada",
      "fecha_solicitud": "2025-11-08T14:12:00Z",
      "fecha_respuesta": "2025-11-08T15:00:00Z",
      "mentor": { "id": 5, "name": "Laura" },
      "mentoria": null
    }
  ]
}
```

## Ejemplo de Respuesta Poll (304)
```json
{
  "etag": "9d4c7e...",
  "changed": false
}
```

## Siguientes Pasos Inmediatos
1. Implementar endpoint polling (solo lectura) + etag.
2. Crear hook React y componente expandible.
3. Añadir endpoint cancelación mentor.
4. Integrar con ZoomService para cancelar.
5. Escribir tests base.

---
Fin del documento.
