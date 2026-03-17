# TaskLab · Especificación de la IA de refinamiento de tareas

Este documento define **cómo debe comportarse la IA** que refina las peticiones entrantes (por ejemplo, desde Discord, Teams o formulario web) en una tarea de TaskLab.

La idea: a partir de un texto libre (`description_raw`) y algo de contexto, la IA produce un conjunto de campos estructurados que dejan la tarea lista para que un desarrollador pueda abordarla sin ambigüedades.

---

## 1. Entradas para la IA

Entrada mínima obligatoria:

- `description_raw` (string): texto original enviado por el usuario. Puede incluir un pseudo‑formato como:

  ```text
  TIPO: bug
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

Entrada opcional (contexto):

- `source` (string): origen de la petición (`web_form`, `discord`, `teams`, etc.).
- `url` (string|null): URL principal asociada, si se ha detectado.
- `reporter_name` (string|null): nombre de quien reporta.
- `image_urls` (string[]): URLs de imágenes adjuntas (la IA las analiza con visión si están presentes).

---

## 2. Salidas esperadas (formato JSON)

La IA devuelve SIEMPRE un objeto JSON con esta forma:

```json
{
  "title": "",
  "summary": "",
  "requirements": [],
  "behavior": "",
  "test_cases": [],
  "type": "",
  "priority": "",
  "points": null,
  "primary_url": "",
  "additional_urls": [],
  "impact": "",
  "parsed_fields": {
    "raw_tipo": "",
    "raw_prioridad": "",
    "raw_resultado_esperado": "",
    "raw_resultado_actual": ""
  },
  "categories": [
    { "path": ["Tipo", "Categoria", "Subcategoria"] }
  ]
}
```

### Descripción campo a campo

- `title` (string) — Título corto, claro, accionable. Debe mencionar módulo/página + síntoma.

- `summary` (string) — Descripción refinada en 2–6 frases en español, para alguien técnico sin contexto previo.

- `requirements` (string[]) — Lista de criterios de aceptación concretos y comprobables.

- `behavior` (string) — Dos bloques: comportamiento actual vs comportamiento esperado.

- `test_cases` (string[]) — Escenarios de QA para validar la solución.

- `type` (string) — Uno de: `bug`, `feature`, `improvement`, `question`.

- `priority` (string) — Uno de: `critical`, `high`, `medium`, `low`.
  - `critical`: bloquea pagos, registros, acceso o un flujo clave para la mayoría de usuarios.
  - `high`: alto impacto en negocio o experiencia, pero no bloquea totalmente.
  - `medium`: problema relevante con impacto moderado.
  - `low`: mejora, detalle visual o edge case.

- `points` (number|null) — Estimación de esfuerzo en horas (1 punto ≈ 1 hora). Valores permitidos: 0.5, 1, 2, 4, 6, 8, 10, 12, 16.

- `primary_url` (string) — URL principal donde ocurre el problema.

- `additional_urls` (string[]) — URLs adicionales relevantes.

- `impact` (string) — Frase corta explicando el impacto en negocio/usuarios.

- `parsed_fields` — Campos auxiliares con los valores explícitos encontrados en el mensaje (para debug y trazabilidad).

- `categories` — Propuesta de categorización dinámica. Cada item es un path de tipo → categoría → subcategoría, mapeado contra los `CategoryType`/`CategoryValue` existentes en la BD.

---

## 3. Reglas y estilo para la IA

1. **Idioma**: siempre en español.
2. **No inventar datos**: si falta información clave, señalarlo en `requirements` o `summary`.
3. **Respetar TIPO/PRIORIDAD explícitos**: si el mensaje los incluye, usarlos como primera fuente.
4. **Coherencia**: no eliminar detalles importantes aunque parezcan redundantes.
5. **Imágenes**: si se pasan `image_urls`, analizarlas con visión y usarlas para enriquecer la descripción.

---

## 4. Integración con `AiTaskRefiner`

El servicio `App\Services\AiTaskRefiner` recibe `description_raw` + `imageUrls[]`, construye el prompt, llama a OpenAI (modelo configurado en `OPENAI_TASKLAB_MODEL`), parsea el JSON y actualiza la tarea.

En caso de error (timeout, JSON inválido), no rompe el flujo: mantiene `description_raw` y rellena campos con valores seguros por defecto.

---

## 5. Estado actual

- **Implementado**: `AiTaskRefiner::refine()` llama a OpenAI con visión. El job `RefineTaskWithAi` lo lanza en background tras crear la tarea.
- **Modelo**: `gpt-4.1-mini` por defecto, configurable con `OPENAI_TASKLAB_MODEL`.
- **Categorías dinámicas**: la IA propone paths de categorías que se mapean contra los `CategoryType`/`CategoryValue` existentes con fuzzy matching.
