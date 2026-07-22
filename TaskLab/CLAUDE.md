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

## Second Brain — documentación estratégica

Además de la documentación técnica anterior, `docs/` incluye un espacio de documentación estratégica ("Second Brain") separado del código, pensado para mantener contexto de negocio, producto y estrategia a lo largo del tiempo:

| Carpeta | Contenido |
|---------|-----------|
| `docs/second-brain/` | Núcleo estratégico: visión, problema, cliente ideal, propuesta de valor, principios (`01-vision.md` a `05-principios.md`) |
| `docs/producto/` | Definiciones funcionales de producto |
| `docs/investigacion/` | Investigación de mercado, competencia y usuarios |
| `docs/decisiones/` | Registro de decisiones estratégicas (ADR de negocio/producto), p.ej. `001-no-competir-con-jira.md` |
| `docs/roadmap/` | Planificación temporal de features |
| `docs/marketing/` | Mensajes, posicionamiento y estrategia de comunicación |
| `docs/ventas/` | Pitch de ventas, objeciones, pricing |
| `docs/arquitectura/` | Arquitectura técnica estratégica (complementa este archivo) |
| `docs/validaciones/` | Experimentos y validación de hipótesis de negocio (formato: Hipótesis / Evidencias / Cómo validarla / Resultado / Estado) |
| `docs/feedback-clientes/` | Feedback cualitativo recogido de clientes/usuarios |
| `docs/metricas/` | Métricas de producto y negocio |
| `docs/casos/` | Casos reales ocurridos durante el desarrollo de software (solo hechos: qué ocurrió, qué información faltó/cambió, consecuencias, aprendizaje) |
| `docs/sprints/` | Bitácora de sprints del Second Brain (objetivo, resultados, pendientes): `Sprint-00.md`, `Sprint-01.md`, `Sprint-02.md` |

**Idea clave a recordar en todo momento:** TaskLab no debe tratarse como "un gestor de tareas más". A partir del Sprint 01 se define como **un sistema para preservar el contexto y la trazabilidad de las decisiones que originan el desarrollo de software** (ver `docs/decisiones/D-002-el-problema-es-la-perdida-de-contexto.md`), y a partir del Sprint 02 aspira a ser la **memoria operativa del desarrollo de software**: no solo conserva decisiones de negocio, sino que ayuda a cualquier desarrollador a comprender rápidamente cómo funciona una funcionalidad, por qué existe y qué debe cambiar (ver `docs/decisiones/D-003-el-tiempo-mas-caro-es-el-de-comprension.md`). No compite inicialmente con Jira/Linear/Shortcut/Trello/ClickUp — se integra con ellos (ver `docs/decisiones/001-no-competir-con-jira.md` y `docs/decisiones/D-001-no-competir-con-jira.md`). Cualquier sugerencia de producto o arquitectura debe respetar este posicionamiento salvo indicación expresa en contra.

**Estado del Second Brain:** Sprints 00, 01 y 02 completados (ver `docs/sprints/`). El problema documentado en `docs/second-brain/02-problema.md` sigue siendo una **hipótesis**, respaldada por los primeros casos reales (`docs/casos/`), los patrones detectados (`docs/investigacion/patrones.md`, P-001 a P-009) y el flujo mental del desarrollador (`docs/investigacion/flujo-del-desarrollador.md`), pero aún pendiente de validar con más empresas (ver `docs/validaciones/H-001-expediente-completo.md` y `docs/validaciones/H-002-tiempo-de-comprension.md`). Existe un primer modelo de dominio provisional en `docs/producto/modelo-del-dominio.md`. Próximo sprint: definir el modelo completo de una petición de desarrollo.

## Pipeline de IA (RefineTaskWithAi)

El job hace una sola llamada a OpenAI con contexto completo y luego orquesta:

```
1. buildCategoryTree()   → árbol de CategoryTypes/Values de la BD
2. findSimilarTasks()    → hasta 8 tareas activas pre-filtradas por similitud
3. AiTaskRefiner::refine() → 1 llamada OpenAI con todo el contexto
4. acceptance_check
   └─ needs_review → status='needs_review' + rejection_reasons + DM → STOP
   └─ approved → continuar
5. duplicate_check
   └─ conflict → DM al requester → la tarea sigue
   └─ related  → merge en tarea existente + archivar nueva + DM → STOP
   └─ unique   → continuar
6. applyRefinement() + mapCategoriesToValues() + TaskAssignmentService::assign()
```

**Estado nuevo de tarea:** `needs_review` — tarea bloqueada por falta de calidad/contexto.

**Campos nuevos en tasks:** `rejection_reasons` (json), `co_requester_ids` (json).

**Notificaciones Discord (DiscordNotificationService):**
- `notifyTaskAssigned()` — tarea asignada
- `notifyTaskNeedsReview()` — tarea bloqueada por gate de calidad
- `notifyTaskConflict()` — tarea conflictiva con otra existente
- `notifyTaskMerged()` — tarea fusionada en otra existente

## Próximas features planificadas

- Notificaciones de estado — DM de Discord cuando la tarea cambia a "in progress" o "done".

## Convenciones del proyecto

- Los jobs de cola usan `QUEUE_CONNECTION=database`. En Render el worker corre como servicio separado.
- El token de autenticación de webhooks se llama `X-Teams-Token` (legado del nombre de la integración de Teams, se usa también para Discord).
- `source` en Task puede ser: `discord`, `teams`, `web`, `manual`.
- Los usuarios de Discord se crean como `user_type = requester` con email sintético `discord+{discord_user_id}@tasklab.local`.
- No hay SDK de Cloudinary — todo se hace via REST API con firma SHA1.
