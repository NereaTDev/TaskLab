# 02 · Problema

> Second Brain de TaskLab. Completar y revisar a medida que el producto evoluciona.

## Problema principal (Sprint 00 — 2026-07-22)

Actualmente creemos que el problema principal es:

- La información cambia continuamente durante el ciclo de vida de una petición.
- El contexto se pierde entre departamentos.
- Las tareas llegan con información insuficiente.
- Nadie tiene una única fuente de verdad.
- Los desarrolladores trabajan con incertidumbre.

> **Nota:** esta definición todavía es una hipótesis que deberá validarse con más empresas. Ver [[../validaciones/README]] para el proceso de validación y [[../investigacion/problemas-observados]] para la evidencia recogida.

## Problema principal (formulación previa)

La información entre negocio, soporte, producto y desarrollo **llega incompleta, desordenada o perdida**.

Esto provoca:

- Tareas mal definidas
- Pérdida de tiempo
- Reuniones innecesarias
- Trabajo rehecho
- Frustración en los equipos

## Síntomas observables

- [ ] Recopilar ejemplos reales de tareas mal definidas (capturas, hilos de Discord, tickets)
- [ ] Cuantificar tiempo perdido por retrabajo o falta de contexto
- [ ] Identificar qué roles sufren más este problema (soporte, PM, dev...)

## Causas raíz (hipótesis a validar)

- La comunicación ocurre en canales informales (chat, voz, mensajes sueltos) no diseñados para estructurar trabajo.
- No existe un paso de "traducción" entre la conversación y la tarea formal.
- Falta de contexto se detecta tarde, normalmente ya en desarrollo.

## Evidencias actuales (Sprint 01 — 2026-07-23)

- Caso real de desarrollo del sistema de descuentos.
- Caso real de cambios continuos de prioridades durante un sprint.

Ver los casos completos en `docs/casos/2026-07-descuentos.md` y `docs/casos/2026-07-cambios-prioridades.md`, y los patrones repetitivos extraídos en `docs/investigacion/patrones.md` (P-001 a P-005).

## Conclusión (Sprint 01)

Actualmente la principal hipótesis del proyecto es que el mayor problema no es la gestión de tareas sino **la pérdida de contexto, responsabilidad y trazabilidad durante el ciclo de vida de una petición**.

Ver decisión formal: `docs/decisiones/D-002-el-problema-es-la-perdida-de-contexto.md`. Ver hipótesis de solución en validación: `docs/validaciones/H-001-expediente-completo.md`.

## Referencias

- [[01-vision]]
- [[03-cliente-ideal]]
- Ver `docs/validaciones/README.md` para evidencia recogida sobre este problema.
- `docs/casos/2026-07-descuentos.md`
- `docs/casos/2026-07-cambios-prioridades.md`
- `docs/investigacion/patrones.md`
- `docs/decisiones/D-002-el-problema-es-la-perdida-de-contexto.md`

---
*Última actualización: 2026-07-23 (Sprint 01)*
