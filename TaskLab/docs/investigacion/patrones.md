# Patrones observados

Este documento recopila patrones repetitivos detectados a partir de los casos reales documentados en `docs/casos/`.

## P-001

Nombre

El solicitante desaparece.

Descripción

Cuando la funcionalidad llega a desarrollo ya nadie sabe exactamente quién realizó la petición original.

## P-002

Nombre

Teléfono escacharrado.

Descripción

La información pasa por Teams, reuniones y Shortcut siendo reinterpretada varias veces.

## P-003

Nombre

Validación tardía.

Descripción

La mayoría de problemas aparecen cuando la funcionalidad ya está desarrollada.

## P-004

Nombre

Deriva de decisiones.

Descripción

Las prioridades y requisitos cambian continuamente durante el desarrollo.

## P-005

Nombre

Desarrollo con incertidumbre.

Descripción

Los desarrolladores comienzan tareas sin saber si la información es suficiente o si cambiará al día siguiente.

## P-006

Nombre

Comprensión antes que implementación.

Descripción

La mayor parte del tiempo inicial no se dedica a programar sino a comprender el sistema existente.

## P-007

Nombre

Git como fuente de contexto.

Descripción

Los desarrolladores utilizan git blame y el historial de commits para intentar reconstruir el contexto que no existe en la tarea.

## P-008

Nombre

Estado actual desconocido.

Descripción

Muchas tareas indican qué debe cambiar pero no describen claramente cuál es el comportamiento actual.

## P-009

Nombre

Estado esperado ambiguo.

Descripción

Muchas tareas indican que algo está roto sin explicar cuál debería ser el comportamiento correcto.

## P-010

Nombre

Reconstrucción manual del contexto.

Descripción

Cuando la información necesaria no aparece en la tarea, los desarrolladores reconstruyen el contexto utilizando historial de Git, conversaciones y reuniones.

## P-011

Nombre

Comprender antes de modificar.

Descripción

La implementación comienza únicamente cuando el desarrollador entiende el funcionamiento actual del sistema.

## P-012

Nombre

Reducción progresiva de incertidumbre.

Descripción

Los desarrolladores no pasan directamente de recibir una tarea a implementar una solución.

Antes reducen progresivamente la incertidumbre hasta sentirse capaces de actuar.

## P-013

Nombre

Las preguntas determinan la preparación.

Descripción

Una tarea puede contener mucha información y seguir generando preguntas importantes.

Otra puede contener menos información y permitir comenzar inmediatamente.

## P-014

Nombre

El razonamiento comienza por la localización.

Descripción

Antes de comprender una petición, el desarrollador necesita localizar la funcionalidad dentro del sistema.

## P-015

Nombre

El conocimiento tiene propietarios.

Descripción

En los equipos de desarrollo el conocimiento suele estar distribuido entre distintas personas.

Conocer quién posee ese conocimiento reduce la incertidumbre.

## P-016

Nombre

Toda ejecución contiene decisiones.

Descripción

Incluso los cambios aparentemente pequeños implican decisiones sobre qué modificar, cómo hacerlo, quién debe aprobarlo o cómo comprobar el resultado.

## P-017

Nombre

Las decisiones tienen propietarios diferentes.

Descripción

Una necesidad genera decisiones funcionales, técnicas, visuales, legales o estratégicas que pueden depender de distintas personas o departamentos.

## P-018

Nombre

El contexto se reconstruye buscando decisiones anteriores.

Descripción

Las personas consultan código, historial, conversaciones y documentación para entender por qué se eligió una actuación concreta.

## P-019

Nombre

La ejecución no implica autoridad.

Descripción

La persona responsable de aplicar un cambio no siempre posee autoridad para decidir todos sus detalles.

## P-020

Nombre

Las herramientas registran decisiones explícitas.

Descripción

Las herramientas actuales permiten documentar decisiones ya identificadas.

No ayudan necesariamente a descubrir cuáles faltan.

## P-021

Nombre

Toda decisión ausente genera una consecuencia observable.

Descripción

Las decisiones implícitas producen bloqueos, retrasos, implementaciones erróneas o abandono.

## P-022

Nombre

Los errores de decisión son diferentes de los errores de implementación.

Descripción

Un sistema puede estar correctamente implementado y, aun así, responder a una decisión equivocada.

## Referencias

- `docs/casos/2026-07-descuentos.md`
- `docs/casos/2026-07-cambios-prioridades.md`
- `docs/validaciones/H-001-expediente-completo.md`
- `docs/investigacion/flujo-del-desarrollador.md`
- `docs/validaciones/H-002-tiempo-de-comprension.md`
- `docs/investigacion/validacion-externa.md`
- `docs/producto/ready-to-understand.md`
- `docs/investigacion/incertidumbre.md`
- `docs/producto/incertidumbre.md`
- `docs/validaciones/H-004-incertidumbre.md`
- `docs/modelo/motor-de-comprension.md`
- `docs/modelo/contextos.md`
- `docs/modelo/cadena-de-confianza.md`
- `docs/validaciones/H-005-motor-de-comprension.md`
- `docs/dominio/necesidad.md`
- `docs/dominio/decision.md`
- `docs/dominio/cadena-de-decisiones.md`
- `docs/dominio/propiedad-de-la-decision.md`
- `docs/modelo/transformacion-de-tasklab.md`
- `docs/modelo/contexto-como-decisiones.md`
- `docs/validaciones/H-006-necesidad-como-objeto-raiz.md`
- `docs/validaciones/H-007-decisiones-implicitas.md`
- `docs/investigacion/mercado-decisiones.md`
- `docs/investigacion/errores-de-decision.md`
- `docs/investigacion/consecuencias-de-decisiones-ausentes.md`
- `docs/validaciones/H-008-descubrimiento-de-decisiones.md`
- `docs/decisiones/D-009-posicionamiento.md`

---
*Última actualización: 2026-07-25 (Sprint 07)*
