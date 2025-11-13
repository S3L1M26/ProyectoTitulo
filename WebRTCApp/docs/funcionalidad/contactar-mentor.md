# Funcionalidad: Contactar Mentor

## Descripción General

Esta funcionalidad permite a los estudiantes enviar mensajes directos a mentores con los que han tenido una relación previa de mentoría. El sistema garantiza que solo los estudiantes que han completado al menos una mentoría con un mentor específico puedan contactarlo.

## Flujo de Uso

### Para el Estudiante

1. **Verificación de Elegibilidad**
   - El estudiante debe haber completado al menos una mentoría con el mentor
   - El sistema verifica automáticamente esta relación antes de permitir el contacto

2. **Envío de Mensaje**
   - El estudiante accede al perfil del mentor
   - Si es elegible, verá un botón "Contactar Mentor"
   - Escribe su mensaje en el formulario
   - El mensaje se envía al mentor vía email y notificación en el sistema

3. **Limitaciones**
   - Rate limiting: 5 mensajes por minuto para prevenir spam
   - Solo se puede contactar a mentores con relación previa

### Para el Mentor

1. **Recepción de Mensaje**
   - Recibe notificación por email
   - El mensaje incluye información del estudiante
   - Puede responder directamente al email del estudiante

## Endpoints API

### Obtener Mentores Contactables

```http
GET /api/student/mentores-contactables
```

**Autenticación:** Requerida (estudiante)

**Respuesta:**
```json
{
  "mentores": [
    {
      "id": 1,
      "nombre": "Juan Pérez",
      "email": "juan@example.com",
      "mentorias_completadas": 3
    }
  ]
}
```

### Verificar Si Puede Contactar

```http
GET /api/student/mentores/{mentor_id}/can-contact
```

**Autenticación:** Requerida (estudiante)

**Parámetros:**
- `mentor_id`: ID del mentor

**Respuesta:**
```json
{
  "can_contact": true,
  "reason": null
}
```

O si no puede contactar:
```json
{
  "can_contact": false,
  "reason": "No tienes mentorías previas con este mentor"
}
```

### Enviar Mensaje

```http
POST /student/mentores/{mentor_id}/contactar
```

**Autenticación:** Requerida (estudiante)

**Rate Limit:** 5 peticiones por minuto

**Parámetros:**
```json
{
  "mensaje": "Hola, tengo una duda sobre..."
}
```

**Validaciones:**
- `mensaje`: requerido, string, máximo 1000 caracteres

**Respuesta Exitosa (200):**
```json
{
  "message": "Mensaje enviado exitosamente al mentor"
}
```

**Errores Comunes:**

- **403 Forbidden:** No tiene relación previa con el mentor
- **422 Validation Error:** Mensaje inválido
- **429 Too Many Requests:** Excedió el límite de 5 mensajes por minuto

## Componentes Frontend

### `ContactMentorButton.jsx`

Botón para iniciar el contacto con el mentor.

**Props:**
- `mentorId`: ID del mentor
- `canContact`: Boolean que indica si puede contactar
- `reason`: Razón por la cual no puede contactar (si aplica)

**Ejemplo de uso:**
```jsx
<ContactMentorButton 
  mentorId={mentor.id}
  canContact={true}
  reason={null}
/>
```

### `ContactMentorModal.jsx`

Modal con formulario para enviar mensaje al mentor.

**Props:**
- `mentor`: Objeto con datos del mentor
- `isOpen`: Estado del modal
- `onClose`: Función para cerrar el modal

**Ejemplo de uso:**
```jsx
<ContactMentorModal
  mentor={mentor}
  isOpen={showModal}
  onClose={() => setShowModal(false)}
/>
```

## Validaciones Implementadas

### Backend

1. **Autenticación:** Usuario debe estar autenticado como estudiante
2. **Relación Previa:** 
   - Verifica que existe al menos una mentoría completada entre estudiante y mentor
   - Estado de mentoría debe ser "completada"
3. **Rate Limiting:** Máximo 5 mensajes por minuto
4. **Validación de Mensaje:**
   - Requerido
   - Tipo string
   - Máximo 1000 caracteres

### Frontend

1. **Validación de Formulario:**
   - Mensaje no puede estar vacío
   - Mensaje no puede exceder 1000 caracteres
2. **Estado de Carga:**
   - Deshabilita botón mientras se envía
   - Muestra indicador de carga
3. **Manejo de Errores:**
   - Muestra mensajes de error apropiados
   - Maneja errores de red

## Notificaciones

### Email al Mentor

**Plantilla:** `MensajeMentorMail.php`

**Contenido:**
- Asunto: "Mensaje de [Nombre del Estudiante]"
- Nombre y email del estudiante
- Mensaje completo
- Enlace al perfil del estudiante

**Variables:**
```php
[
    'student_name' => 'Nombre del estudiante',
    'student_email' => 'email@estudiante.com',
    'mensaje' => 'Contenido del mensaje',
    'mentor_name' => 'Nombre del mentor'
]
```

## Seguridad

1. **Rate Limiting:** Previene spam limitando a 5 mensajes por minuto
2. **Autorización:** Solo estudiantes con relación previa pueden contactar
3. **Sanitización:** El mensaje se escapa automáticamente en el email
4. **CSRF Protection:** Todas las peticiones requieren token CSRF válido

## Casos de Uso

### Ejemplo 1: Estudiante Contacta Mentor

```javascript
// Frontend
const handleContact = async () => {
  try {
    const response = await axios.post(
      `/student/mentores/${mentorId}/contactar`,
      { mensaje: mensajeTexto }
    );
    
    toast.success('Mensaje enviado exitosamente');
    setShowModal(false);
  } catch (error) {
    if (error.response?.status === 429) {
      toast.error('Has enviado demasiados mensajes. Espera un momento.');
    } else {
      toast.error('Error al enviar mensaje');
    }
  }
};
```

### Ejemplo 2: Verificar Elegibilidad

```javascript
// Frontend
useEffect(() => {
  const checkEligibility = async () => {
    const { data } = await axios.get(
      `/api/student/mentores/${mentorId}/can-contact`
    );
    
    setCanContact(data.can_contact);
    setContactReason(data.reason);
  };
  
  checkEligibility();
}, [mentorId]);
```

## Capturas de Pantalla

### Vista del Estudiante - Botón de Contacto
![Botón Contactar Mentor](../images/contactar-mentor-button.png)

### Modal de Envío de Mensaje
![Modal de Contacto](../images/contactar-mentor-modal.png)

### Email Recibido por el Mentor
![Email Mentor](../images/contactar-mentor-email.png)

## Troubleshooting

### Problema: "No puedes contactar a este mentor"

**Causa:** No existe relación previa de mentoría completada

**Solución:**
1. Verificar que tiene al menos una mentoría completada con el mentor
2. Revisar el estado de las mentorías en el dashboard

### Problema: "Too Many Requests"

**Causa:** Excedió el límite de 5 mensajes por minuto

**Solución:**
1. Esperar 60 segundos antes de enviar otro mensaje
2. Verificar que no hay múltiples ventanas enviando mensajes

### Problema: El mensaje no se envía

**Causa:** Error de red o validación

**Solución:**
1. Verificar conexión a internet
2. Revisar que el mensaje no excede 1000 caracteres
3. Verificar que el mensaje no está vacío
4. Revisar logs del servidor para errores específicos

## Próximas Mejoras

- [ ] Sistema de mensajería bidireccional dentro de la plataforma
- [ ] Historial de mensajes enviados
- [ ] Notificaciones en tiempo real usando WebSockets
- [ ] Plantillas de mensajes predefinidos
- [ ] Archivos adjuntos en mensajes
