# TaskLab · Especificación de la IA de refinamiento de tareas

Este documento define **cómo debe comportarse la IA** que refina las peticiones entrantes (por ejemplo, desde Discord, Teams o formulario web) en una tarea de TaskLab.

La idea: a partir de un texto libre (description_raw) y algo de contexto, la IA debe producir un conjunto de campos estructurados que dejen la tarea lista para que un desarrollador la pueda abordar sin ambigüedades.

---

## 1. Entradas para la IA

Entrada mínima obligatoria:

- `description_raw` (string): texto original enviado por el usuario (por ejemplo, mensaje en Discord o formulario web). Puede incluir un pseudo‑formato como:

  ```text
  TIPO: bug
  AREA: web
  PRIORIDAD: high

  DESCRIPCION:
  ...

  URL:
  ...

  IMPACTO:
  ...

  PASOS:
  ...

  RESULTADO_ESPERADO:
  ...

  RESULTADO_ACTUAL:
  ...
  ```

Entrada opcional (contexto futuro):

- `source` (string): origen de la petición (`web_form`, `discord`, `teams`, etc.).
- `url` (string|null): URL principal asociada, si se ha detectado.
- `reporter_name` (string|null): nombre de quien reporta.
- `area_hint` (string|null): indicación previa del área (`web`, `plataforma`, `frontierz`, `dashboard_empresas`).

La primera versión real de la IA puede trabajar solo con `description_raw` y `source`.

---

## 2. Salidas esperadas (formato JSON)

La IA debe devolver SIEMPRE un objeto JSON con esta forma (aunque algunos campos estén vacíos):

```json
{
  "title": "",
  "summary": "",
  "requirements": [],
  "behavior": "",
  "test_cases": [],
  "type": "",
  "priority": "",
  "area": "",
  "estimated_effort": "",
  "url": "",
  "impact": "",
  "parsed_fields": {
    "raw_tipo": "",
    "raw_area": "",
    "raw_prioridad": "",
    "raw_resultado_esperado": "",
    "raw_resultado_actual": ""
  }
}
```

### Descripción campo a campo

- `title` (string)
  - Título corto, claro y accionable de la tarea.
  - Debe ser entendible sin leer toda la descripción.
  - Incluir, si es posible, pantalla/componente y síntoma.

- `summary` (string)
  - Descripción refinada en 2–6 frases, en español, enfocada a alguien técnico que no ha visto el contexto original.
  - Combina y limpia la información de DESCRIPCION, IMPACTO, RESULTADO_ESPERADO y RESULTADO_ACTUAL.

- `requirements` (string[])
  - Lista de requisitos que deben cumplirse para considerar la tarea terminada.
  - Cada elemento debe ser una frase concreta, comprobable.

- `behavior` (string)
  - Texto con dos bloques, algo como:

    ```text
    Comportamiento actual:
    ...

    Comportamiento esperado:
    ...
    ```

- `test_cases` (string[])
  - Lista de casos de prueba que QA puede seguir para validar la solución.
  - Incluir pasos y resultado esperado, pero de forma breve.

- `type` (string)
  - Uno de: `bug`, `feature`, `improvement`, `question`.
  - Si el usuario ha rellenado `TIPO:` en el mensaje, respétalo salvo que sea incoherente.
  - Si no hay pista clara, usar `bug` por defecto.

- `priority` (string)
  - Uno de: `critical`, `high`, `medium`, `low`.
  - Criterio orientativo:
    - `critical`: bloquea pagos, registros, acceso o un flujo clave para la mayoría de usuarios.
    - `high`: no bloquea totalmente pero tiene alto impacto en negocio o experiencia.
    - `medium`: problema relevante pero con impacto moderado.
    - `low`: mejora, detalle visual o edge case.
  - Si el usuario ha indicado PRIORIDAD, usarla como guía.

- `area` (string)
  - Uno de: `web`, `plataforma`, `frontierz`, `dashboard_empresas`.
  - Intentar inferirla a partir de:
    - URL
    - Nombre de la página/componente
    - Texto del mensaje (por ejemplo “dashboard de empresas” → `dashboard_empresas`).

- `estimated_effort` (string)
  - Uno de: `low`, `medium`, `high`.
  - Estimación muy aproximada basada en la complejidad aparente.

- `url` (string)
  - URL principal relevante, si se menciona claramente en `description_raw`.

- `impact` (string)
  - Explicación compacta del impacto en negocio/usuarios (1–3 frases).

- `parsed_fields` (objeto)
  - Campos auxiliares donde la IA vuelca, si los encuentra explícitos, los valores de las etiquetas del mensaje (`TIPO:`, `AREA:`, `PRIORIDAD:`, etc.).
  - Esto ayuda a debuggear y evolucionar el parser sin perder la info cruda.

---

## 3. Reglas y estilo para la IA

1. **Idioma**: siempre en español.
2. **Claridad**: evitar jerga innecesaria; explicar como si el desarrollador no hubiera visto el mensaje original.
3. **No inventar datos**:
   - Si falta información clave, no inventarla; señalar claramente la ausencia en `requirements` o `summary` (por ejemplo: "El mensaje no indica el navegador.").
4. **Uso de campos TIPO/AREA/PRIORIDAD**:
   - Si el mensaje incluye un bloque estructurado (por ejemplo `TIPO: bug`), úsalo como primera fuente.
   - Si no existe, intenta inferirlo; si la inferencia no es clara, usa el valor por defecto y explica la duda en `impact` o `requirements`.
5. **Coherencia con el mensaje original**:
   - No eliminar detalles importantes aunque parezcan redundantes.

---

## 4. Integración con el servicio `AiTaskRefiner`

El servicio `App\\Services\\AiTaskRefiner` debe:

1. Recibir `description_raw` (y, en el futuro, otros campos de contexto si se añaden).
2. Construir un `prompt` para el modelo de IA que incluya:
   - Instrucciones de esta especificación.
   - El texto original (`description_raw`).
3. Llamar al proveedor de IA (OpenAI, Claude, etc.) para obtener el JSON anterior.
4. Parsear el JSON y devolver un array de PHP con al menos:

   ```php
   return [
       'title'           => $json['title'] ?? $fallbackTitle,
       'summary'         => $json['summary'] ?? null,
       'requirements'    => $json['requirements'] ?? [],
       'behavior'        => $json['behavior'] ?? null,
       'test_cases'      => $json['test_cases'] ?? [],
       'type'            => $json['type'] ?? 'bug',
       'priority'        => $json['priority'] ?? 'medium',
       'area'            => $json['area'] ?? null,
       'estimated_effort'=> $json['estimated_effort'] ?? 'medium',
   ];
   ```

5. En caso de error en la llamada a la IA (timeout, JSON inválido, etc.), no romper el flujo:
   - Mantener el `description_raw`.
   - Rellenar campos con valores seguros por defecto.

---

## 5. Estado actual vs estado objetivo

- **Estado actual** (MVP):
  - `AiTaskRefiner::refine()` devuelve un refinamiento fake, estático.
  - No hay conexión real con un modelo de IA externo.

- **Estado objetivo**:
  - `AiTaskRefiner::refine()` llama a un modelo de IA real siguiendo esta especificación.
  - El flujo Discord → TaskLab → IA deja las tareas listas para desarrollo con el mínimo trabajo manual posible.

Este documento sirve como contrato de comportamiento para cuando se implemente la integración real con IA.
