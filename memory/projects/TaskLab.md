# TaskLab

## Visión general

Aplicación interna para capturar peticiones (bugs, features, mejoras, dudas), refinarlas (con IA/fake AI) y gestionarlas como tareas preparadas para desarrollo.

## Objetivos
- Centralizar peticiones en un único sistema.
- Convertir descripciones libres en tareas claras (título, resumen, requisitos, comportamiento, casos de prueba).
- Tener un flujo de estados sencillo: new → in_refinement → ready_for_dev → in_progress → done/blocked.
- Desplegarla y usarla en producción (Render + Supabase).
- Integrarse con Teams para que mensajes en canales creen tickets automáticamente.
- Enriquecer los tickets con IA: criterios de aceptación, contexto, URLs, posibles fragmentos de código, capturas, clasificación (bug/feature/implementación), área (front/back, plataforma, dashboard, etc.).
- Asignar tareas de forma inteligente según tipo (front/back), carga actual del equipo y esfuerzo estimado.
- Notificar automáticamente tanto al desarrollador asignado como a la persona que pidió la tarea (idealmente vía Teams).

## Stack
- Laravel 12
- Blade + Tailwind (via Vite, assets en `public/build`)
- PostgreSQL (Supabase)
- Laravel jobs para refinamiento (`RefineTaskWithAi` + `AiTaskRefiner`).

## Flujo ideal (Teams → TaskLab → Teams)
1. Trabajador escribe en un canal de Teams de la empresa (p.ej. "Hola equipo, tengo un problema en la home, el botón play del vídeo no funciona" + captura).
2. Un conector/bot de Teams envía ese mensaje (texto + metadata + adjuntos) a un endpoint de TaskLab.
3. TaskLab crea un borrador de `Task` y pasa el contenido por IA para:
   - Generar título y resumen.
   - Producir **criterios de aceptación** claros.
   - Extraer contexto, URLs relevantes y posibles pistas de código (si hay acceso al repo en el futuro).
   - Guardar la captura de pantalla asociada a la tarea.
   - Clasificar la tarea: bug / implementación / mejora.
   - Etiquetar por área: front / back / plataforma / dashboard / etc.
   - Estimar esfuerzo aproximado (bajo/medio/alto) para ayudar a la asignación.
4. Un motor de asignación decide quién del equipo la recibe:
   - Detecta si es de front o back.
   - Mira cuántas tareas tiene cada dev.
   - Prioriza al que tenga menos carga y esfuerzo compatible.
5. TaskLab asigna la tarea al dev y actualiza el estado (`new` → `ready_for_dev` / `in_progress`).
6. Notificaciones:
   - Al desarrollador (idealmente vía Teams): resumen de la tarea, criterios de aceptación, quién la pidió.
   - A la persona que la pidió: quién lleva la tarea, estado inicial, y luego cambios clave (aceptada, en progreso, finalizada).
7. El desarrollador ve en su dashboard de TaskLab todas sus tareas, estados y detalles.

## Decisiones clave
- `SESSION_DRIVER=file` en producción para evitar depender de la tabla `sessions`.
- `APP_KEY` siempre generado con `php artisan key:generate --show` y configurado en Render.
- No se commitea `public/hot` (solo se usan assets build de Vite).
- En Dockerfile, evitar `php artisan config:cache` en build para no cachear config sin variables de entorno.

## Estado actual (2026-03-05)
- Proyecto funcional en local (tests pasan salvo el ExampleTest por cambio de `/`).
- Deploy en Render operativo (error 500 resuelto: APP_KEY + sesiones).
- Login y assets de Vite sirviéndose desde `/build/assets/...`.

## Pendiente / siguientes pasos
- Diseñar modelo para integraciones externas (Teams): canal → endpoint → creación de Task.
- Extender `AiTaskRefiner` para que genere criterios de aceptación, clasificación y etiquetas.
- Diseñar y desarrollar el motor de asignación automática de tareas.
- Implementar sistema de notificaciones (Teams primero, email como fallback).
- Mejorar UI de dashboard para que cada dev vea claramente sus tareas y estados.
