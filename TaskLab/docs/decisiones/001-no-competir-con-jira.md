# 001 · No competir con Jira

- **Estado:** Aceptada
- **Fecha:** 2026-07-22

## Contexto

TaskLab podría diseñarse como un gestor de tareas completo, compitiendo directamente con herramientas ya consolidadas como Jira, Linear, Shortcut, Trello o ClickUp. Sin embargo, entrar a competir directamente contra estas herramientas desde el primer día implicaría un esfuerzo de producto enorme (funcionalidades de gestión, reporting, permisos, integraciones, etc.) sin garantía de diferenciación real a corto plazo.

## Decisión

TaskLab **no intentará reemplazar** herramientas de gestión de tareas en su primera versión.

Se posicionará como **una capa inteligente que mejora la calidad de la información antes de que una tarea llegue a Jira, Shortcut, Linear u otra herramienta**.

## Consecuencias

- El roadmap inicial prioriza integraciones de salida hacia herramientas externas, no un sistema de gestión propio completo.
- El panel propio de TaskLab se mantiene simple, centrado en revisión y refinamiento de tareas, no en reemplazar los tableros de gestión existentes.
- Las decisiones de producto deben evaluarse contra el principio: *integrar antes de sustituir* (ver `docs/second-brain/05-principios.md`).
- Esta decisión puede revisarse en el futuro si la validación de mercado sugiere que un producto de gestión propio es más viable (ver `docs/validaciones/README.md`).

## Referencias

- `docs/second-brain/01-vision.md`
- `docs/second-brain/04-propuesta-valor.md`
- `docs/second-brain/05-principios.md`
