# Auditoría del repositorio — Sprint 08

> No se ha modificado ningún archivo de código durante esta auditoría. No se han copiado valores de `.env`, solo nombres de variables.

## Resumen tecnológico

- **PHP:** `^8.2` (composer.json)
- **Laravel:** `^12.0`
- **Autenticación:** Laravel Breeze (`laravel/breeze ^2.3`), guard `web` basado en sesión. No hay Sanctum ni guard de API configurado.
- **Frontend:** Alpine.js v3 (`alpinejs ^3.4.2`) + Blade + Tailwind CSS v3/v4 (mezcla de `tailwindcss ^3.1` y `@tailwindcss/vite`/`@tailwindcss/postcss` v4) + Vite 7.
- **Base de datos:** motor configurado vía `DB_*` (Supabase/PostgreSQL en producción); tests usan SQLite en memoria (`phpunit.xml`).
- **Cola:** `QUEUE_CONNECTION=database` en producción, `sync` en tests.
- **IA:** OpenAI vía `Illuminate\Support\Facades\Http` directo a `https://api.openai.com/v1/chat/completions` (sin SDK), configurado en `config/services.php` → `services.openai.api_key` / `services.openai.tasklab_model`.
- **Rutas:** solo `routes/web.php` + `routes/auth.php`. No existe `routes/api.php` ni registro de rutas API en `bootstrap/app.php`.
- **CSRF:** excepciones explícitas para endpoints de integración externa en `bootstrap/app.php`.
- **Dependencias JS de producción:** mínimas (`autoprefixer` solo); todo lo demás (Alpine, Vite, Tailwind) es `devDependencies`, consistente con un frontend Blade + Alpine sin build de SPA.

## Estructura actual relevante

- `app/Http/Controllers/` (13 controladores en la raíz + `Auth/` + `Admin/`): `TaskController` (525 líneas, el más grande, gestiona index/store/update/status/analysis), `DiscordIntegrationController`, `SlackIntegrationController`, `TeamsIntegrationController`, `GithubConnectionController`, `SlackConnectionController`, `TeamController`, `SettingsController`, `OnboardingController`, `NotificationController`, `ProfileController`, `TaskImageController`.
- `app/Services/` (6 servicios, 1468 líneas): `AiTaskRefiner` (286 líneas — llamada a OpenAI + fallback simulado), `TaskAssignmentService`, `GithubContextService` (379 líneas — construye contexto estructural de código desde GitHub), `DiscordNotificationService`, `SlackNotificationService`, `DiscordBatchAnalyzer`.
- `app/Jobs/`: `RefineTaskWithAi` (orquestador principal: contexto → IA → gate de calidad → duplicados → aplicar → asignar), `ProcessDiscordMessageBatch`, `ProcessSlackMessageBatch`, `DownloadTaskAttachments`.
- `app/Models/`: `Task` (modelo central), `TaskImage`, `User`, `Team`, `Department`, `CategoryType`, `CategoryValue`, `UserCategoryAssignment`, `DeveloperProfile`, `DiscordMessageBuffer`, `SlackMessageBuffer`, `GithubConnection`, `SlackConnection`.
- `database/migrations/`: 34 migraciones. El modelo `Task` ha evolucionado con múltiples migraciones incrementales (estados, campos de validación IA, `rejection_reasons`, `co_requester_ids`, índices de rendimiento).
- `resources/views/`: 50 archivos Blade organizados en `tasks/`, `team/`, `admin/teams/`, `settings/`, `onboarding/`, `profile/`, `auth/`, `components/` (incluye `task-modal/`).
- `tests/`: solo el scaffolding de Breeze (`Auth/*Test.php`, `ProfileTest.php`) más dos `ExampleTest.php` vacíos. **No hay tests de `Task`, de los Services, ni del pipeline de IA.**
- `app/Providers/AppServiceProvider.php`: prácticamente vacío, sin bindings de interfaces/contratos. No existe ningún patrón de contrato+implementación en el proyecto todavía.

## Funcionalidades heredadas del TaskLab inicial

- Gestión de tareas (`Task`) con estados (`new`, `ready_for_dev`, `in_progress`, `done`, `blocked`, `archived`, `needs_review`) y flujo de refinamiento por IA ya descrito en `CLAUDE.md`.
- Integraciones de entrada por Discord, Slack y Teams, todas normalizando a `Task` vía buffers + jobs por lotes.
- Asignación automática de desarrolladores por equipo/carga (`TaskAssignmentService`).
- Integración GitHub para contexto de código (`GithubContextService`) — ya construye un contexto estructural similar en espíritu al Motor de Comprensión.
- Sistema de equipos, departamentos y categorías jerárquicas (`Team`, `Department`, `CategoryType`/`CategoryValue`) para clasificar y asignar tareas.
- Onboarding de SuperAdmin, notificaciones internas, conexión OAuth con GitHub y Slack.

## Clasificación de componentes

### Reutilizable sin cambios

- **Autenticación (Breeze, guard `web`)** — no tiene relación con el dominio de necesidades/decisiones; sigue siendo la puerta de entrada de usuarios.
- **`config/services.php` (bloque `openai`)** — el acceso a la API de OpenAI ya está centralizado ahí; el Motor de Comprensión puede leer las mismas credenciales sin duplicar configuración.
- **Infraestructura de cola (`QUEUE_CONNECTION=database`, jobs `ShouldQueue`)** — patrón ya establecido y válido para ejecutar el análisis de forma asíncrona si en el futuro se decide no responder en el mismo request.
- **Stack de tests (SQLite en memoria + cola síncrona en `phpunit.xml`)** — apto tal cual para testear el nuevo flujo sin infraestructura adicional.

### Reutilizable con adaptación

- **`AiTaskRefiner`** — no es el Motor de Comprensión, pero su patrón (prompt estructurado → JSON validado → `fakeRefinement()` como fallback sin API key) es el precedente más cercano al contrato "simulado / real" que pide D-011. Sirve de referencia de diseño, no de código a heredar directamente (su contrato de salida está atado a `Task`, no a `Necesidad`).
- **`RefineTaskWithAi` (el job)** — el patrón de orquestación (construir contexto → llamar al motor → interpretar resultado → actuar) es el mismo que necesitará el nuevo caso de uso, pero está fuertemente acoplado a `Task` y a los efectos secundarios de ese dominio (asignación, notificaciones Discord). No debe reutilizarse tal cual.
- **`GithubContextService`** — ya resuelve "contexto estructural" (dónde vive el código) descrito en `docs/modelo/contextos.md`. Es un candidato natural a inyectarse en el futuro Motor de Comprensión real, pero hoy depende de una conexión GitHub por equipo; su adaptación es posterior a este sprint.
- **`TaskController@store`** — el punto de entrada HTTP más parecido al que necesita el flujo vertical (recibe texto libre del usuario, valida, crea un registro, dispara un job). Sirve de plantilla de implementación, no de código a modificar en esta fase.

### No relacionado, pero no perjudicial

- Integraciones de Discord/Teams/Slack (`*IntegrationController`, `*ConnectionController`, buffers, `Process*MessageBatch`).
- Gestión de equipos/departamentos/categorías (`Team`, `Department`, `CategoryType`, `CategoryValue`, `TeamController`, `SettingsController`).
- Onboarding, notificaciones, perfil de usuario.
- Todo el frontend Blade/Alpine/Tailwind existente para tareas.

Estos módulos siguen dando valor a la aplicación actual y no interfieren con la introducción de un Motor de Comprensión aislado.

### En conflicto con el nuevo dominio

- **Ninguno detectado a nivel de código.** El conflicto es puramente conceptual y ya está documentado en el Second Brain: `Task` sigue siendo el objeto central de la aplicación, mientras que el dominio nuevo define a `Necesidad` como objeto raíz (`docs/dominio/necesidad.md`, D-007). No hace falta resolver este conflicto ahora — el flujo vertical de este sprint puede convivir con `Task` sin tocarlo, y la reconciliación (¿una `Necesidad` genera una `Task`? ¿la sustituye?) queda pendiente para un sprint posterior.

### Candidato a eliminar en el futuro (no ahora)

- La ruta de depuración `POST /integrations/discord/inspect` en `routes/web.php` (devuelve el payload crudo recibido) parece una herramienta de debugging dejada en el código de producción. No se toca en este sprint; se señala como deuda técnica menor.

## Riesgos técnicos detectados

- **Ausencia total de tests automatizados sobre el dominio de negocio** (`Task`, servicios, jobs de IA). Cualquier flujo nuevo debería empezar a corregir esto, no perpetuar el vacío.
- **No hay capa de contratos/interfaces** en el proyecto. Introducir el primer contrato (Motor de Comprensión) es el primer precedente de este patrón en el código; debe hacerse de forma mínima para no imponer una convención que el resto del proyecto no sigue.
- **No hay `routes/api.php`.** Si en el futuro se quiere un cliente no-Blade (móvil, integración externa) para el Motor de Comprensión, habrá que decidir si se registra routing API o se reutiliza `web.php` con JSON. Para este sprint, un formulario Blade + respuesta Blade evita abrir esa decisión antes de tiempo.
- **Mezcla de versiones de Tailwind (v3 y v4 a la vez)** en `package.json` — deuda técnica existente, no relacionada con este sprint, no se toca.

## Deuda técnica relevante (no bloqueante para este sprint)

- Falta de tests de dominio (`Task`, `AiTaskRefiner`, `RefineTaskWithAi`).
- Ruta de debugging (`/integrations/discord/inspect`) sin protección ni utilidad productiva aparente.
- Doble versión de Tailwind en `package.json`.

## Propuesta de adaptación

Introducir el Motor de Comprensión como un módulo nuevo y aislado (`app/Support/Comprehension/` o similar, ver `docs/arquitectura/primer-flujo-motor-comprension.md`), sin tocar `Task`, `AiTaskRefiner` ni `RefineTaskWithAi`. El primer flujo vertical usa su propia ruta, su propia vista y su propio contrato — no reutiliza ni modifica el dominio de `Task`. Esto permite validar el concepto sin arriesgar la funcionalidad existente, en línea con D-010 y con el principio "evolución antes que reconstrucción" (`docs/second-brain/principios.md`).

## Archivos que probablemente cambiarán en la siguiente fase (Sprint 08B)

Ninguno de los siguientes se ha tocado en esta auditoría; se listan como previsión para la fase de implementación:

- `routes/web.php` — nueva ruta para el flujo del Motor de Comprensión.
- `bootstrap/app.php` — solo si la nueva ruta necesita quedar fuera de CSRF o de un middleware existente (previsiblemente no).
- Nuevo controlador HTTP (p. ej. `app/Http/Controllers/ComprehensionController.php`).
- Nuevo caso de uso (p. ej. `app/UseCases/AnalyzeNeed.php` o similar).
- Nueva interfaz/contrato (p. ej. `app/Support/Comprehension/ComprehensionEngine.php`).
- Nueva implementación simulada (p. ej. `app/Support/Comprehension/SimulatedComprehensionEngine.php`).
- Nueva vista Blade para mostrar el informe.
- `app/Providers/AppServiceProvider.php` — binding de la interfaz a la implementación simulada.
- Nuevos tests bajo `tests/Feature/` para el flujo completo.

## Referencias

- `docs/arquitectura/primer-flujo-motor-comprension.md`
- `docs/decisiones/D-010-adaptar-repositorio-laravel-existente.md`
- `docs/decisiones/D-011-primer-motor-simulado.md`
- `docs/dominio/necesidad.md`
- `docs/modelo/motor-de-comprension.md`
- `CLAUDE.md`

---
*Fecha: 2026-07-25 (Sprint 08)*
