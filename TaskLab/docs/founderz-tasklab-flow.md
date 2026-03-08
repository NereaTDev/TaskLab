# TaskLab en Founderz · Flujo ideal con IA

Este documento describe el flujo objetivo de TaskLab como herramienta interna de gestión de trabajo para Founderz (empresa de e‑learning), y cómo debe interactuar la IA con los mensajes entrantes desde canales como Discord o Teams.

## 1. Contexto

- Founderz es una escuela de negocio y programas online (e‑learning) enfocada en skills digitales, IA, etc.
- El producto principal es una **plataforma de campus online** (web y servicios backend) donde:
  - Los alumnos acceden a contenidos, clases, actividades, etc.
  - Existen flujos como inicio de sesión, activación de cámara/micrófono, navegación por cursos, etc.
- TaskLab se usará como **sistema interno** para capturar:
  - Bugs
  - Mejoras / evolutivos
  - Implementaciones nuevas
  - Dudas/soporte

Fuentes de entrada previstas:

- Canal de Discord interno (ya integrado vía Pipedream → `/integrations/discord/messages`).
- Futuro canal de Teams (o email / otros formularios) usando el mismo modelo.

---

## 2. Flujo alto nivel (de mensaje a tarea asignada)

1. **Usuario interno** escribe un mensaje en un canal configurado (p. ej. "#incidencias" en Discord):
   - Ejemplo: "En el campus no se puede activar la cámara".
2. Conector (Pipedream/Teams) envía el mensaje a TaskLab → endpoint de integración.
3. TaskLab crea una `Task` con la `description_raw` (= mensaje original + contexto) y `source` (`discord` / `teams`).
4. Se lanza el job `RefineTaskWithAi`, que llama a **OpenAI** (`AiTaskRefiner`) con un prompt especializado.
5. La IA devuelve un JSON con:
   - Título, resumen, requisitos, comportamiento actual/esperado, casos de prueba.
   - Tipo (`bug/feature/improvement/question`).
   - Prioridad (`critical/high/medium/low`).
   - Área (`web/plataforma/frontierz/dashboard_empresas`).
   - Esfuerzo aproximado (`low/medium/high`).
6. TaskLab actualiza la `Task` con esos campos.
7. El motor de asignación (`TaskAssignmentService` ampliado) decide a qué persona del equipo va la tarea, según:
   - Área y tipo (front/back/fullstack).
   - Departamento/equipo (p. ej. Producto / Tech).
   - Carga actual medida en **puntos de esfuerzo** (ver §4).
8. TaskLab asigna la tarea a ese usuario y deja el estado en `ready_for_dev` / `in_progress`.
9. Se envían notificaciones:
   - Al **requester** (quien pidió la tarea) indicando quién la va a llevar.
   - Al **asignado** indicando que tiene una nueva tarea.

---

## 3. Qué debe producir la IA para cada tarea

A partir de `description_raw` (mensaje original) + contexto, la IA debe generar:

1. **Título (`title`)**
   - Corto, claro, accionable.
   - Debe mencionar el módulo/página + síntoma.
   - Ejemplo: "Campus: no se activa la cámara en clases en vivo".

2. **Resumen (`summary` / `description_ai`)**
   - Descripción refinada en español, 2–6 frases.
   - Combina contexto, impacto y resultado actual/esperado.

3. **Requisitos (`requirements[]`)**
   - Lista de criterios de aceptación bien definidos.
   - Ejemplo: "La cámara debe poder activarse correctamente en el navegador X, versión Y, en el campus de alumnos".

4. **Comportamiento (`behavior`)**
   - Dos bloques: comportamiento actual vs comportamiento esperado.

5. **Casos de prueba (`test_cases[]`)**
   - Escenarios que QA puede seguir para validar que el bug está resuelto o la feature implementada.

6. **Clasificación**
   - `type`: bug/feature/improvement/question.
   - `priority`: critical/high/medium/low.
   - `area`: web/plataforma/frontierz/dashboard_empresas.
   - `estimated_effort`: low/medium/high (estimación aproximada).

7. **Metadatos adicionales**
   - `url`: URL principal donde ocurre (si la puede inferir del mensaje o de un patrón conocido).
   - `impact`: frase corta explicando el impacto en negocio/usuarios.

Más adelante se definirá con detalle la plantilla de **criterios de aceptación** (muy importante) en otro documento, pero la IA ya debe ser capaz de generar un primer borrador razonable.

---

## 4. Modelo de asignación por áreas / equipos / puntos

### 4.1. Estructura de equipos

- Founderz tiene áreas/departamentos internos (ejemplos):
  - Tech / Producto
  - Marketing
  - Operaciones, etc.
- Dentro de Tech/Producto, distinguimos:
  - Frontend
  - Backend
  - Fullstack

TaskLab debe poder mapear esto a:

- `DeveloperProfile::type` → `front`, `back`, `fullstack`.
- `DeveloperProfile::areas` → donde esa persona puede trabajar (`web`, `plataforma`, etc.).

### 4.2. Sistema de puntos

- Cada tarea tiene un campo `points` (estimación de esfuerzo).
- Regla de negocio: **1 punto ≈ 1 hora de trabajo** de un desarrollador estándar.
- La IA, a partir de la descripción, puede sugerir un `estimated_effort` (`low/medium/high`) que luego se traduce en puntos aproximados (p. ej. low=1–2, medium=3–5, high=8+), pero la decisión final de puntos puede ser humana.

### 4.3. Criterios para elegir la persona asignada

Cuando la IA ha clasificado la tarea, el asignador debe:

1. Determinar si es tarea de `front`, `back` o `fullstack` (por contenido y área).
2. Buscar en `DeveloperProfile`:
   - `active = true`.
   - `type` compatible con la tarea (mismo tipo o fullstack).
   - Área incluida en `areas` del developer.
3. Calcular la carga actual:
   - Suma de puntos de todas las tareas activas (`new`, `in_refinement`, `ready_for_dev`, `in_progress`) asignadas a cada desarrollador.
4. Escoger al desarrollador con **menos carga de puntos** (y por debajo de su capacidad máxima si la hemos definido).
5. Asignar la tarea a esa persona:
   - `assignee_id = user_id` elegido.
   - Estado: `ready_for_dev` o `in_progress` según la política.

---

## 5. Notificaciones

Cuando la tarea queda asignada:

- **Al requester (quien ha escrito en Discord/Teams)**:
  - Recibe una notificación con:
    - ID de la tarea en TaskLab.
    - Título.
    - Quién la tiene asignada.
- **Al desarrollador asignado**:
  - Recibe una notificación con:
    - Título.
    - Resumen.
    - Criterios de aceptación principales.
    - Enlace a la tarea en TaskLab.

El canal de notificación puede ser Discord, Teams o email, según el contexto (a definir más adelante).

---

## 6. Entrenamiento práctico de la IA con el contexto de Founderz

Para que la IA entienda bien el entorno Founderz:

- Se tendrá en cuenta el dominio `founderz.com` y el campus (URLs, nombres de módulos, procesos típicos, etc.).
- Ejemplos típicos de mensajes:
  - "En el campus no se puede activar la cámara" → bug, área `web`, tipo `front`, alta prioridad si bloquea clases.
  - "Queremos añadir badges en el dashboard de empresas" → feature, área `dashboard_empresas`, probablemente front+back.

La especificación detallada del prompt que se envía a OpenAI está en `docs/ai-task-refiner-spec.md` y se irá ajustando con ejemplos reales.
