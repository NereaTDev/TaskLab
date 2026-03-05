# TaskLab UI Agent - Plan de agente autónomo de estilos

## Contexto de la app

TaskLab es una aplicación interna (Laravel 12 + Blade + Tailwind) para:
- Capturar peticiones (bugs, features, mejoras, dudas).
- Refinarlas (actualmente con una IA "fake" mediante `AiTaskRefiner`).
- Gestionarlas como tareas con estados: `new`, `in_refinement`, `ready_for_dev`, `in_progress`, `blocked`, `done`.
- Asignarlas automáticamente a desarrolladores según perfiles y carga (via `TaskAssignmentService`).

Infraestructura:
- Backend Laravel (TaskLab/) desplegado en Render con Docker multi-stage.
- Base de datos: PostgreSQL Supabase con SSL (`DB_CONNECTION=pgsql`, `DB_SSLMODE=require`).
- Assets: Vite + Tailwind, build a `public/build`.

Flujos clave:
- Dashboard: muestra tareas asignadas al usuario actual.
- Tablero (board): Kanban global con todas las tareas.
- Análisis (analysis): vista para métricas y stats.
- Equipo (team): vista admin de usuarios + perfiles de desarrollador.
- Perfil (profile): datos de usuario y perfil de desarrollador.

## Objetivo del agente de UI

Un agente autónomo de UI para TaskLab debe:
- Mantener y evolucionar el diseño visual sin romper layout ni lógica.
- Trabajar exclusivamente sobre **estilos** (Blade + Tailwind), sin tocar:
  - modelos, controladores ni servicios PHP,
  - migraciones o lógica de negocio.
- Respetar estrictamente la estructura actual del header (no mover ni rehacer esa parte).

En concreto, su trabajo se centra en:
- Vistas Blade bajo `resources/views/**`.
- Clases Tailwind en Blade y en `resources/css/app.css`.
- Tokens de diseño en `tailwind.config.js` (`tasklab.*`).

## Design system de TaskLab

### Paleta de colores (tokens Tailwind)

Definidos en `tailwind.config.js` bajo `colors.tasklab`:
- `tasklab.bg`         → fondo principal dark (azul/slate muy oscuro).
- `tasklab.bg-muted`   → fondo de tarjetas / secciones secundarias.
- `tasklab.text`       → texto principal (casi blanco, azul muy claro).
- `tasklab.muted`      → texto secundario / labels (gris medio).
- `tasklab.primary`    → azul para acciones primarias y énfasis.
- `tasklab.primary-soft` → azul algo más oscuro para hover/variantes.
- `tasklab.accent`     → naranja agradable para acentos y CTAs.
- `tasklab.accent-soft` → naranja suave para hovers y estados secundarios.
- `tasklab.danger`     → rojo para estados de error/alto riesgo.
- `tasklab.warning`    → amarillo/naranja para avisos.
- `tasklab.success`    → verde para éxito/completado.

Reglas de uso:
- Fondo general: `bg-tasklab-bg`.
- Tarjetas/paneles: `bg-tasklab-bg-muted` + `border border-slate-800` + `shadow-card`.
- Texto principal: `text-tasklab-text`.
- Texto secundario: `text-tasklab-muted`.
- Acciones primarias (botones principales): `bg-tasklab-primary` + `hover:bg-tasklab-primary-soft`.
- Acciones clave/CTA (ej. "Nueva tarea"): `bg-tasklab-accent` + `hover:bg-tasklab-accent-soft` + texto oscuro.
- Badges/chips: combinaciones `bg-tasklab-bg`, `bg-tasklab-bg-muted`, `bg-tasklab-accent/10`, `bg-tasklab-primary/10`, etc. con borde suave.

### Tipografía (escala semántica)

Definida como utilidades en `resources/css/app.css`:

- `.text-heading` → ~18px, para títulos de secciones.
- `.text-title`   → ~16px, para subtítulos o títulos de cards.
- `.text-body`    → ~14px, texto base (incluye botones).
- `.text-label`   → ~12px, labels, cabeceras de tabla.
- `.text-meta`    → ~11px, metadata muy secundaria.

Regla importante:
- **Todos los botones** deben usar tamaño estándar de 14px:
  - `text-body` o `text-sm` (pero preferible `text-body` para centralizar cambios futuros).
  - Evitar `text-xs` en botones.

## Layout y componentes clave

### Header (NO tocar estructura)

El header actual ya está ajustado al diseño deseado. El agente **no debe**:
- Mover tabs a otro sitio.
- Cambiar el orden logo → tabs → controles derecha.
- Cambiar el comportamiento del dropdown de usuario.

Estructura actual (resumen):
- Izquierda: logo cuadrado dark.
- A su derecha: pastilla con tabs:
  - Dashboard (`/tasks` con `view` por defecto).
  - Tablero (`/tasks?view=board`).
  - Análisis (`/tasks?view=analysis`).
  - Equipo (`/team` para admins).
- Derecha: controles y usuario:
  - Toggle de auto-asignación.
  - Campana de notificaciones.
  - Avatar con dropdown (Perfil, Ajustes, Cerrar sesión).

El agente sólo puede ajustar **estilos menores** del header (colores, tamaño de fuente), pero **no debe** alterar su composición ni mover elementos.

### Barra de filtros y búsqueda (Dashboard/Tablero)

Ubicación: `resources/views/tasks/index.blade.php`.

Elementos:
- Barra bajo el header con:
  - Buscador pastilla:
    - Contenedor exterior dark con icono lupa.
    - Input interior con fondo blanco, texto gris oscuro, placeholder gris claro.
  - Chips de filtro: Tipo, Prioridad, Estado, Asignado, Fecha.
    - Estilo pastilla blanca con borde gris y texto gris (14px).
    - Hover con detalle en naranja (`border-tasklab-accent` / `text-tasklab-accent`).
  - Botón "Nueva tarea" naranja a la derecha (CTA principal de la vista):
    - `bg-tasklab-accent` + `hover:bg-tasklab-accent-soft`.
    - Texto oscuro, 14px.

Objetivo del agente:
- Mantener el look actual (similar a diseño del usuario).
- Afinar spacing/responsividad si se añaden filtros nuevos.

### Kanban (Task board)

Ubicación: `resources/views/components/task-kanban-board.blade.php`.

Columnas (`$columnConfig`):
- `pending` → Pendiente (new, in_refinement, ready_for_dev).
  - Header: dark neutro + badge de conteo con acento naranja.
  - Botón `+` naranja para añadir tarea.
- `in_progress` → En Progreso.
  - Header y badge en azul (`tasklab.primary`).
  - Botón `+` naranja para añadir tarea.
- `in_review` → En Revisión (blocked).
  - Header y badge morado más suave (violet-900/30, etc.).
- `done` → Completada.
  - Header y badge en verde `tasklab.success`.

Badges y chips:
- Prioridad (`$priorityColors`):
  - `critical` → rojo DS (`tasklab.danger`) con borde.
  - `high` → naranja (`tasklab.accent`), borde suave.
  - `medium` → azul (`tasklab.primary`).
  - `low` → fondo dark, texto muted, borde slate.
- Tipo de tarea (`bug`, `feature`, etc.):
  - Chip dark: `bg-tasklab-bg`, `border-slate-800`, texto `tasklab-muted`.
- Tags de ejemplo:
  - `#frontend` → naranja.
  - `#backend` → azul.
  - `#database` → gris muted.

Reglas para el agente:
- Evitar colores Tailwind claros por defecto (`bg-*-100`, `text-*-800`) y sustituirlos por combinaciones basadas en `tasklab.*`.
- Mantener cards como:
  - `border border-slate-800 bg-tasklab-bg-muted shadow-card`.
  - Textos con `text-tasklab-text` / `text-tasklab-muted`.

### Vista Equipo (Team)

Ubicación: `resources/views/team/index.blade.php`.

Estructura:
- Card principal dark con tabla:
  - Columnas: User, Department, Position, Admin, Dev type, Areas, Acciones (solo para admins).
- Contenido:
  - Avatar iniciales del usuario.
  - Nombre/email.
  - Badges Admin/User.
  - Chips de áreas.
  - Botón `Gestionar` para admins.

Reglas de estilo:
- Fondo: `bg-tasklab-bg-muted` + `border-slate-800`.
- Cabecera de tabla: `bg-slate-900/80`, texto `text-tasklab-muted`, tamaño `text-label` (xs).
- Filas: hover `hover:bg-slate-900/60`.
- Botón `Gestionar`:
  - Pastilla con borde y fondo naranja (`border-tasklab-accent/60 bg-tasklab-accent/10`).
  - Texto `text-body text-tasklab-accent`.
  - Icono de papelera o gestión.

## Trabajo del agente de UI (backlog)

### 1. Sweep de tipografías y colores

Tareas:
- Revisar vistas que aún usan `bg-white`, `text-gray-*` o `text-slate-*` directamente y migrar a tokens `tasklab.*`:
  - `resources/views/tasks/show.blade.php`.
  - `resources/views/profile/partials/update-developer-profile-form.blade.php`.
  - `resources/views/profile/partials/update-password-form.blade.php`.
  - Vistas de auth (`resources/views/auth/*.blade.php`) donde queden fondos claros no deseados.
- Reemplazar tamaños directos tipo `text-xs`, `text-sm` por utilidades semánticas cuando sea texto estructural (no iconos/labels muy pequeños):
  - Botones → `text-body`.
  - Títulos de cards → `.text-heading` o `.text-title`.
  - Descripciones → `.text-body`.
  - Labels de formularios → `.text-label`.

Restricción:
- No tocar el layout del header, solo colores/tamaños de texto si hiciera falta para coherencia.

### 2. Refuerzo de acentos naranjas

Tareas:
- Usar naranja (`tasklab.accent`) para acciones clave y puntos de foco:
  - Botones de creación (`Nueva tarea`, posibles CTA secundarios).
  - Iconos de acción primaria (botón `+` en columnas clave del Kanban).
  - Badges de prioridad alta (`high`).
- Mantener equilibrio con azul (`tasklab.primary`) para acciones estándar.

### 3. Vista Análisis (Analysis)

Tareas:
- En `/tasks?view=analysis`, crear cards dark con métricas básicas usando `stats` del controlador:
  - Total de tareas.
  - Tareas en progreso.
  - Tareas completadas.
  - Tasa de éxito (% completadas / total).
- Diseño:
  - Grid de 2x2 o 3x2 cards.
  - Cada card: `rounded-xl border border-slate-800 bg-tasklab-bg-muted p-4 shadow-card`.
  - Icono pequeño + título + número grande.
  - Acentos naranja en tarjetas que representen éxito o CTA.

### 4. Vista Dashboard

Tareas:
- Asegurar que el Dashboard (mis tareas) y Tablero (todas las tareas) comparten el mismo componente `<x-task-kanban-board>` con sólo cambios de dataset.
- Añadir, si procede, stats personales (cards similares a Tablero pero filtradas por usuario actual).

## Límites del agente

El agente de UI **NO debe**:
- Modificar controladores (por ejemplo `TaskController`, `TeamController`).
- Tocar modelos (`Task`, `User`, `DeveloperProfile`).
- Cambiar migraciones o lógica de base de datos.
- Alterar lógica de rutas.
- Reestructurar el header: posición del logo, tabs, auto-asignación, notificaciones y avatar deben mantenerse.

Solo puede:
- Editar clases Tailwind/Blade para estilos.
- Ajustar pequeños fragmentos de HTML si es estrictamente necesario para mejorar semántica o accesibilidad (por ejemplo, añadir `sr-only`, ajustar jerarquía de headings), sin cambiar la estructura general.

## Cómo ejecutarlo en un subproceso OpenClaw (idea)

Supuesto: tienes otra sesión/terminal donde puedes usar `openclaw` con soporte de `sessions_spawn` sin depender de este canal.

Ejemplo de intención (alto nivel, no comando exacto):

- Crear una sesión de subagente con tarea:
  - "Aplicar backlog de TaskLab UI Agent descrito en `memory/projects/TaskLab-ui-agent.md` en el repo `/Users/nereatrebol/.openclaw/workspace/TaskLab`, limitándose a cambios de estilos." 
- Mantener la sesión en modo `session` para poder interactuar con commits y revisiones.

Cuando tengas un canal con `subagent_spawning` habilitado, este documento sirve como contrato para que el agente sepa:
- Qué es TaskLab.
- Qué estilo debe seguir.
- Qué partes puede tocar y cuáles no (especialmente el header).
- Qué tareas concretas tiene en su backlog de UI.
