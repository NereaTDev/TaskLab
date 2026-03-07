# TaskLab · Bandeja de entrada desde Discord

Este documento define **cómo usar el canal de Discord** conectado a TaskLab para crear tareas de forma consistente, y qué hará TaskLab (y la IA) con esos mensajes.

La idea: cualquier persona puede escribir en un canal de Discord, y TaskLab convertirá ese mensaje en una tarea estructurada, con la mejor información posible para el equipo técnico.

---

## 1. Cómo funciona la integración (visión rápida)

1. Escribes un mensaje en el canal de Discord configurado (por ejemplo `#incidencias`).
2. Un workflow en Pipedream escucha ese canal y envía el mensaje a TaskLab.
3. TaskLab crea una **Task** con:
   - `source = discord`
   - `description_raw` = texto del mensaje (y, en el futuro, más contexto)
   - `type`, `priority`, `area`, etc. con valores por defecto (editable luego).
4. El **refinador de IA** genera (en segundo plano):
   - Título
   - Resumen/descripcion refinada (`description_ai`)
   - Requisitos, comportamiento esperado y casos de prueba
5. El **motor de asignación** decide, según tipo/área/esfuerzo, a qué desarrollador asignar la tarea.

> Nota: en la versión actual el refinador de IA es "fake" para que el flujo funcione sin dependencias externas. Este documento ya está pensado para cuando usemos un modelo real.

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
- `area` → inicialmente `web`, después se leerá de AREA o se inferirá.
- `estimated_effort` → valor por defecto ("medium"), ajustable por el equipo.

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

En el futuro podemos añadir:

- Respuesta automática en Discord con el ID de la Task creada.
- Notificaciones cuando cambie de estado (en progreso, done, etc.).
- Integración con otros canales (Teams, email) usando el mismo modelo de `description_raw` + IA.
