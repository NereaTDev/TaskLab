# TaskLab · Especificación de la IA de refinamiento y validación de tareas

Este documento define **cómo funciona la IA** que procesa las peticiones entrantes (Discord, Teams, formulario web) en TaskLab. La IA hace tres cosas en una sola llamada:

1. **Refina** la descripción bruta en una tarea estructurada para desarrolladores
2. **Valida** la calidad de la tarea (acceptance gate)
3. **Detecta** duplicados o conflictos con tareas existentes

---

## 1. Entradas para la IA

El job `RefineTaskWithAi` construye el contexto completo antes de llamar a `AiTaskRefiner::refine()`:

| Parámetro | Tipo | Fuente |
|-----------|------|--------|
| `description_raw` | string | Texto original del usuario |
| `imageUrls` | string[] | URLs de imágenes adjuntas (Discord/manual) |
| `categoryTree` | string | Árbol de CategoryTypes/Values de la BD, formateado como texto |
| `similarTasks` | array | Hasta 8 tareas activas pre-filtradas por similitud de texto |

**Árbol de categorías** — ejemplo del formato enviado a la IA:
```
- Departamento
  - Ventas
  - Producto
    - Campus
    - Web
  - Atención al Cliente
- Tipo de trabajo
  - Bug
  - Feature
```

**Tareas similares** — ejemplo:
```
- ID:42 | "Botón inicio de clase no visible" | Estado:in_progress | Categorías: Departamento > Producto > Campus
- ID:38 | "Cambiar color botón de verde a azul" | Estado:done
```

---

## 2. Salidas (JSON completo)

```json
{
  "title": string,
  "summary": string,
  "requirements": string[],
  "behavior": string,
  "test_cases": string[],
  "type": "bug|feature|improvement|question",
  "priority": "critical|high|medium|low",
  "points": number,
  "primary_url": string,
  "additional_urls": string[],
  "impact": string,
  "parsed_fields": {
    "raw_tipo": string,
    "raw_prioridad": string,
    "raw_resultado_esperado": string,
    "raw_resultado_actual": string
  },
  "categories": [
    { "path": ["NombreExactoTipo", "NombreExactoCategoría", "NombreExactoSubcategoría"] }
  ],
  "acceptance_check": {
    "status": "approved|needs_review",
    "score": 0-10,
    "issues": ["razón 1", "razón 2"]
  },
  "duplicate_check": {
    "status": "unique|conflict|related",
    "related_task_id": null | number,
    "confidence": 0.0-1.0,
    "explanation": string
  }
}
```

---

## 3. Categorización inteligente

La IA recibe el árbol completo de categorías tal como las tiene configuradas el Super Admin. Instrucciones que sigue:

- Usar **únicamente** los nombres exactos del árbol proporcionado
- Analizar qué departamento/área/módulo es responsable de la tarea
- Recorrer el árbol de lo general a lo específico: tipo → categoría → subcategoría
- Si hay subcategoría coherente, incluirla; si no, quedarse en categoría
- Si no hay ninguna correspondencia clara, devolver `categories: []`
- Puede proponer hasta 2 rutas si la tarea toca múltiples áreas

El job luego mapea las rutas propuestas contra los IDs reales de `CategoryValue` usando fuzzy matching (umbral 60% de similitud), nunca inventa categorías.

---

## 4. Gate de aceptación (acceptance_check)

Cuatro criterios evaluados por la IA:

| Criterio | Obligatorio | Descripción |
|----------|-------------|-------------|
| **Claridad** | ✅ Sí | La petición describe claramente QUÉ ocurre o QUÉ se pide. No vale "arregla el botón" sin más contexto. |
| **Relevancia** | ✅ Sí | La petición es relevante para la plataforma/producto. Spam, mensajes casuales o solicitudes ajenas al producto son inválidos. |
| **Accionabilidad** | ✅ Sí | Se puede convertir en trabajo concreto para un desarrollador. Opiniones o comentarios sin acción no son válidos. |
| **Contexto mínimo** | ⚠️ No | Tiene suficiente contexto para empezar sin preguntar. Si falta pero la tarea es válida, se indica en `issues` pero no bloquea. |

**Regla:** si algún criterio obligatorio falla (puntuación < 4/10) → `status: "needs_review"`.

**Consecuencia:**
- `approved` → la tarea sigue al flujo normal (assignment)
- `needs_review` → tarea queda en estado `needs_review`, se guardan los `rejection_reasons`, se notifica al requester por Discord DM explicando qué falta

---

## 5. Detección de duplicados (duplicate_check)

| Estado | Significado | Acción del job |
|--------|-------------|----------------|
| `unique` | Sin duplicados ni conflictos | Flujo normal |
| `conflict` | Existe una tarea que contradice/revierte la nueva | Se notifica al requester del conflicto. La tarea sigue adelante (puede ser un revert intencional) |
| `related` | Misma funcionalidad/componente que tarea activa (`new/ready_for_dev/in_progress`) | La tarea nueva se fusiona en la existente: se añade contexto al `description_raw` de la existente y el reporter como `co_requester_ids`. La tarea nueva se archiva. Se notifica al requester. |

**Condición para fusión:** la tarea existente debe estar en estado activo (`new`, `ready_for_dev`, `in_progress`). Si está en `done` o `archived`, no se fusiona.

---

## 6. Flujo completo del job `RefineTaskWithAi`

```
1. buildCategoryTree()      → formato texto del árbol de categorías de la BD
2. findSimilarTasks()        → hasta 8 tareas pre-filtradas por similar_text()
3. AiTaskRefiner::refine()   → llamada única a OpenAI con todo el contexto
4. acceptance_check
   └─ needs_review → update status + rejection_reasons + DM al requester → STOP
   └─ approved → continuar
5. duplicate_check
   └─ conflict  → DM al requester informando del conflicto → continuar
   └─ related   → mergeIntoExisting() + DM + archivar nueva tarea → STOP
   └─ unique    → continuar
6. applyRefinement()         → update task con todos los campos refinados
7. mapCategoriesToValues()    → sync de categorías con fuzzy matching
8. TaskAssignmentService::assign() → asignación automática
```

---

## 7. Campos de BD relacionados

| Campo | Tabla | Descripción |
|-------|-------|-------------|
| `rejection_reasons` | tasks | JSON: array de strings con los motivos de bloqueo |
| `co_requester_ids` | tasks | JSON: array de user_ids que también han solicitado la tarea |
| `status = 'needs_review'` | tasks | Tarea bloqueada pendiente de más contexto del requester |

---

## 8. Modelo de IA y configuración

- **Proveedor:** OpenAI
- **Modelo por defecto:** `gpt-4.1-mini` (configurable con `OPENAI_TASKLAB_MODEL`)
- **Temperatura:** 0.2 (respuestas consistentes y deterministas)
- **Response format:** `json_object` (garantiza JSON válido)
- **Timeout:** 45 segundos
- **Reintentos del job:** 3

Si no hay API key o falla la llamada, se usa el modo `fakeRefinement` que aprueba la tarea con campos por defecto, sin bloquear el flujo.
