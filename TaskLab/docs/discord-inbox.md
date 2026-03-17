# TaskLab · Bandeja de entrada desde Discord

Este documento define **cómo usar el canal de Discord** conectado a TaskLab para crear tareas de forma consistente, y qué hará TaskLab (y la IA) con esos mensajes.

La idea: cualquier persona puede escribir en un canal de Discord, y TaskLab convertirá ese mensaje en una tarea estructurada, con la mejor información posible para el equipo técnico.

---

## 1. Cómo funciona la integración (estado actual — marzo 2026)

1. Escribes un mensaje (o mandas una imagen) en el canal de Discord configurado.
2. **Pipedream** escucha ese canal y ejecuta un code step que:
   - Extrae URLs de imagen del texto del mensaje (regex)
   - Llama a la Discord API (`GET /channels/{id}/messages/{id}`) para obtener attachments reales con URL
   - Extrae imágenes de embeds (previews de URLs pegadas)
   - Devuelve `{ message_id, message_text, channel_id, from_name, from_teams_id, attachments, image_urls }`
3. Pipedream hace `POST /integrations/discord/messages` con ese payload + header `X-Teams-Token`.
4. `DiscordIntegrationController` guarda el mensaje en `discord_message_buffer` (incluyendo `image_urls`).
5. `ProcessDiscordMessageBatch` (job con 30s de delay, agrupa mensajes del mismo usuario):
   - Analiza los mensajes con IA (`DiscordBatchAnalyzer`)
   - Crea/modifica/ignora tareas según el análisis
   - Lanza `RefineTaskWithAi` (IA real via OpenAI, con visión si hay imágenes)
   - Lanza `DownloadTaskAttachments` si hay `image_urls`
6. `DownloadTaskAttachments` sube las imágenes a **Cloudinary** (sin SDK, firmado con SHA1). Si falla, descarga y guarda en disco local.
7. `TaskAssignmentService` asigna la tarea al developer más adecuado (por tipo y carga de trabajo).
8. `DiscordNotificationService` envía un **DM al requester de Discord** con el nombre del assignee y la prioridad.
9. Las imágenes aparecen en el modal de la tarea en la app.

---

## 2. Qué tengo que escribir en Discord

### 2.1. Versión mínima (funciona siempre)

Si no quieres complicarte, basta con que expliques el problema o petición en texto libre:

> "En la home, el botón de reproducir vídeo no funciona en Safari móvil. Antes funcionaba, ahora al pulsar no pasa nada."

Con eso TaskLab ya creará una tarea, y luego tú o el equipo podréis completar tipo, prioridad, etc. en la ficha.

### 2.2. Versión recomendada (para que la IA y TaskLab brillen)

Para sacar más partido, usa este pequeño **formato recomendado**. No es obligatorio, pero ayuda muchísimo a la IA y al motor de asignación.

```text
TIPO: bug | feature | improvement | question
AREA: web | plataforma | frontierz | dashboard_empresas
PRIORIDAD: critical | high | medium | low

DESCRIPCION:
Explica qué quieres que cambie o qué problema ves, con detalles.

URL:
Pega la URL relevante (si aplica).

IMPACTO:
Explica a quién afecta y qué pasa si no se arregla (por ejemplo: "no pueden pagar", "solo afecta a admin", etc.).

PASOS:
1. Paso 1
2. Paso 2
3. Paso 3

RESULTADO_ESPERADO:
Qué debería ocurrir.

RESULTADO_ACTUAL:
Qué está ocurriendo ahora.
```

Ejemplo realista:

```text
TIPO: bug
AREA: web
PRIORIDAD: high

DESCRIPCION:
En la home, el botón de reproducir vídeo del hero no hace nada en Safari móvil. Antes lanzaba el reproductor embebido.

URL:
https://miapp.com/

IMPACTO:
Afecta a todos los usuarios que entran en la home desde iPhone. No pueden ver el vídeo principal.

PASOS:
1. Abrir la home en Safari en iOS.
2. Hacer scroll hasta el hero.
3. Pulsar el botón de play.

RESULTADO_ESPERADO:
Se abre el reproductor y empieza a reproducirse el vídeo.

RESULTADO_ACTUAL:
No ocurre nada, no hay feedback ni errores visibles.
```

TaskLab usará esa información para:

- **Inferir el tipo** (bug/feature/…) si no lo has rellenado.
- **Entender el área** (web/plataforma/…) y derivar la tarea al equipo adecuado.
- **Ajustar prioridad** según impacto.
- Generar una ficha de tarea limpia, con criterios de aceptación y casos de prueba.

---

## 3. Qué campos se rellenan en TaskLab

A partir del mensaje de Discord, TaskLab alimenta los siguientes campos de la Task:

- `description_raw` → el mensaje completo de Discord, tal cual.
- `source` → `discord`.
- `reporter` → en el futuro se podrá mapear el usuario de Discord a un usuario de TaskLab.
- `type` → inicialmente `bug` (MVP), más adelante se inferirá por IA o leyendo el campo TIPO.
- `priority` → inicialmente `medium`, después la IA podrá sugerir cambios.

En la UI, verás:

- El texto original en "Descripción original" (editable).
- La descripción refinada y requisitos en la columna de IA.
- Estado, tipo, prioridad, requester y asignado en el panel derecho del modal.

---

## 4. Qué hace la IA (especificación futura)

Cuando el refinador de IA esté conectado a un modelo real, su objetivo será:

1. **Título**
   - Generar un título corto, claro y accionable.
   - Evitar títulos genéricos tipo "Error"; incluir página/componente y síntoma.

2. **Descripción refinada (`description_ai`)**
   - Reescribir la descripción combinando DESCRIPCION, IMPACTO, RESULTADO_ESPERADO y RESULTADO_ACTUAL.
   - Quitar ruido, duplicados y lenguaje informal.

3. **Requisitos (`requirements`)**
   - Lista de puntos claros que el desarrollador debe cumplir para considerar la tarea hecha.
   - Ej.: "El botón de play debe abrir el reproductor de vídeo en todos los navegadores soportados".

4. **Comportamiento (`behavior`)**
   - Dos bloques: comportamiento actual vs comportamiento esperado.

5. **Casos de prueba (`test_cases`)**
   - Lista de escenarios que QA puede seguir para validar la solución.

6. **Clasificación**
   - Tipo de tarea: bug / feature / improvement / question.
   - Área: web / plataforma / frontierz / dashboard_empresas.
   - Prioridad sugerida: critical / high / medium / low.
   - Esfuerzo estimado: low / medium / high.

7. **Asignación automática**
   - El motor de asignación usará tipo, área y esfuerzo estimado para elegir el mejor desarrollador disponible.

---

## 5. Buenas prácticas al escribir incidencias en Discord

1. **Una petición por mensaje**
   - No mezcles varios bugs/features distintos en el mismo mensaje; mejor uno por mensaje.

2. **Incluye URL y contexto**
   - Siempre que puedas, indica la URL exacta y el rol del usuario ("admin", "empresa", "cliente final", etc.).

3. **Impacto claro**
   - Indica si bloquea ventas, solo molesta, es un edge case raro, etc.

4. **Adjunta capturas o vídeos**
   - Aunque el backend todavía no procese adjuntos automáticamente, son oro para entender el problema.

5. **Evita mensajes tipo "no va" sin contexto**
   - Cuanto más claro seas, menos vueltas tendrá que dar el equipo técnico.

---

## 6. Qué pasa después de enviar el mensaje

1. El mensaje se convierte en una Task en TaskLab.
2. La IA (fake por ahora, real en el futuro) enriquece la tarea.
3. El motor de asignación puede asignar la tarea directamente al desarrollador adecuado.
4. Podrás ver y gestionar la tarea en el tablero (drag & drop, editar detalles, cambiar estado, etc.).
5. El bot de Discord te enviará un DM confirmando quién ha sido asignado a tu tarea.

---

## 7. Configuración técnica requerida

### Pipedream
- Trigger: Discord trigger en el canal `task_lab`
- Code step: extrae imágenes (ver `docs/WORK_LOG.md` 2026-03-17 para el código completo)
- HTTP step: `POST https://tu-app.render.com/integrations/discord/messages` con header `X-Teams-Token`
- Variable de entorno en Pipedream: `DISCORD_BOT_TOKEN`

### Render — variables de entorno (web service y worker)
```
DISCORD_BOT_TOKEN=...
CLOUDINARY_CLOUD_NAME=...
CLOUDINARY_API_KEY=...
CLOUDINARY_API_SECRET=...
FILESYSTEM_DISK=local
SERVICES_TEAMS_TOKEN=...   ← token compartido con Pipedream para autenticar webhooks
```

### Bot de Discord (Developer Portal)
- Permisos necesarios: `Read Messages`, `Read Message History`, `Send Messages` (para DMs)
- El bot debe estar en el servidor de Discord

---

## 8. Próximas mejoras previstas

- Notificaciones in-app (campana en el frontend) para cualquier fuente de tarea
- Notificaciones de cambio de estado (en progreso, done) de vuelta al canal de Discord
