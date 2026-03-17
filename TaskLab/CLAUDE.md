# TaskLab — Contexto para Claude Code

## Qué es este proyecto

TaskLab es una aplicación de gestión de tareas construida en **Laravel 12** con frontend **Alpine.js v3 + Blade**. Permite crear y gestionar tareas desde múltiples fuentes (formulario web, Discord, Teams) con refinamiento automático por IA y asignación automática de desarrolladores.

## Stack técnico

- **Backend:** Laravel 12, PHP 8.3
- **Frontend:** Alpine.js v3, Blade components, Tailwind CSS
- **BD:** Supabase (PostgreSQL), conexión via `DB_*` env vars
- **Cola:** `QUEUE_CONNECTION=database` (jobs en tabla `jobs`)
- **Storage:** Cloudinary (imágenes desde Discord), disco local (uploads manuales en dev)
- **Deploy:** Render — dos servicios Docker: `web` (PHP-FPM + Nginx) y `worker` (queue:work)
- **IA:** OpenAI (gpt-4.1-mini por defecto), configurado en `config/services.php`

## Arquitectura de integraciones

### Discord → TaskLab
```
Discord canal → Pipedream trigger → code step (JS) → POST /integrations/discord/messages
  → DiscordIntegrationController → discord_message_buffer
  → ProcessDiscordMessageBatch (job 30s delay)
  → [RefineTaskWithAi + DownloadTaskAttachments + TaskAssignmentService]
  → DiscordNotificationService (DM al requester)
```

El endpoint está protegido por `X-Teams-Token` (mismo token para Discord y Teams, `SERVICES_TEAMS_TOKEN`).

### Pipedream code step
Extrae `image_urls` de tres fuentes:
1. URLs en `event.content` (regex)
2. Attachments reales via `GET https://discord.com/api/v10/channels/{id}/messages/{id}` (requiere `DISCORD_BOT_TOKEN` en env vars de Pipedream)
3. Embeds de Discord

### Imágenes: Discord → Cloudinary → modal
- `DownloadTaskAttachments` sube a Cloudinary via REST API firmada (sin SDK)
- `TaskImage.storage_path` = URL completa de Cloudinary (`https://res.cloudinary.com/...`)
- `TaskImage::getUrlAttribute()` detecta URLs absolutas y las devuelve directamente
- El modal eager-load `taskImages` y las renderiza

## Archivos clave

| Archivo | Responsabilidad |
|---------|----------------|
| `app/Http/Controllers/DiscordIntegrationController.php` | Recibe webhook de Pipedream, normaliza payload, guarda en buffer |
| `app/Jobs/ProcessDiscordMessageBatch.php` | Agrupa mensajes por usuario, llama a IA, crea/modifica tareas |
| `app/Jobs/DownloadTaskAttachments.php` | Sube imágenes a Cloudinary o disco local como fallback |
| `app/Jobs/RefineTaskWithAi.php` | Llama a OpenAI para refinar título, descripción, requisitos, etc. |
| `app/Services/AiTaskRefiner.php` | Lógica de llamada a OpenAI (soporta visión con imágenes) |
| `app/Services/TaskAssignmentService.php` | Asigna tarea al developer con menos carga y tipo compatible |
| `app/Services/DiscordNotificationService.php` | Envía DM al requester de Discord cuando la tarea es asignada |
| `app/Models/TaskImage.php` | Modelo de imagen con `url` calculado (Cloudinary o Storage) |
| `app/Models/DiscordMessageBuffer.php` | Buffer de mensajes antes de procesar |
| `app/Models/Task.php` | Modelo principal. Campos relevantes: `source`, `external_user_id`, `external_channel`, `assignee_id`, `reporter_id` |
| `config/services.php` | Credenciales: openai, cloudinary, discord, teams |
| `routes/web.php` | Rutas públicas: `/integrations/discord/messages`, `/integrations/teams/messages` |
| `bootstrap/app.php` | CSRF exceptions para rutas de integración |

## Variables de entorno críticas

### Render — web service y worker
```
APP_KEY=...
DB_HOST=...  DB_DATABASE=...  DB_USERNAME=...  DB_PASSWORD=...
QUEUE_CONNECTION=database
OPENAI_API_KEY=...
OPENAI_TASKLAB_MODEL=gpt-4.1-mini
CLOUDINARY_CLOUD_NAME=...
CLOUDINARY_API_KEY=...
CLOUDINARY_API_SECRET=...
DISCORD_BOT_TOKEN=...
SERVICES_TEAMS_TOKEN=...
FILESYSTEM_DISK=local
APP_URL=https://tu-app.onrender.com
```

## Documentación adicional

- `docs/WORK_LOG.md` — historial de cambios y decisiones técnicas
- `docs/discord-inbox.md` — guía de uso del canal de Discord + configuración técnica
- `docs/ai-task-refiner-spec.md` — especificación del refinador de IA
- `docs/founderz-tasklab-flow.md` — flujo general de la aplicación

## Próximas features planificadas

1. **Notificaciones in-app** — campana en el nav, tabla `notifications`, Alpine.js polling. Disparar desde `TaskAssignmentService` para cualquier fuente (webform, Discord, Teams).
2. **Notificaciones de estado** — DM de Discord cuando la tarea cambia a "in progress" o "done".

## Convenciones del proyecto

- Los jobs de cola usan `QUEUE_CONNECTION=database`. En Render el worker corre como servicio separado.
- El token de autenticación de webhooks se llama `X-Teams-Token` (legado del nombre de la integración de Teams, se usa también para Discord).
- `source` en Task puede ser: `discord`, `teams`, `web`, `manual`.
- Los usuarios de Discord se crean como `user_type = requester` con email sintético `discord+{discord_user_id}@tasklab.local`.
- No hay SDK de Cloudinary — todo se hace via REST API con firma SHA1.
