# Primer flujo del Motor de Comprensión

> Diseño para el Sprint 08. No implementado todavía — ver `docs/arquitectura/auditoria-repositorio-sprint-08.md` para el contexto del repositorio y `docs/decisiones/D-011-primer-motor-simulado.md` para la decisión de empezar con un motor simulado.

## Recorrido completo de la petición

```
Usuario introduce una necesidad o tarea ambigua (formulario Blade)
   ↓
Laravel valida la entrada (Form Request / validate())
   ↓
Un caso de uso ejecuta el análisis (AnalyzeNeed)
   ↓
El caso de uso invoca el contrato ComprehensionEngine
   ↓
Una implementación concreta (simulada al principio) devuelve un resultado estructurado
   ↓
Laravel muestra el informe (vista Blade)
```

## Responsabilidades por capa

| Capa | Responsabilidad | Qué NO hace |
|------|------------------|-------------|
| **Entrada HTTP** (controlador + validación) | Recibir el texto libre del usuario, validarlo (no vacío, longitud razonable), pasarlo al caso de uso, renderizar la vista con el resultado | No interpreta la necesidad, no conoce el contrato del motor, no decide el estado de preparación |
| **Caso de uso** (`AnalyzeNeed`) | Orquestar: recibir la solicitud original, invocar el `ComprehensionEngine`, devolver el contrato de salida al controlador | No sabe si el motor es simulado o real, no construye HTML, no persiste nada todavía |
| **Contrato del Motor de Comprensión** (interfaz `ComprehensionEngine`) | Definir la forma de entrada y salida del análisis, independiente de cualquier proveedor | No tiene lógica; es solo la frontera estable entre el caso de uso y la implementación |
| **Implementación concreta del motor** | Producir el resultado (simulado con reglas fijas, o real llamando a un proveedor de IA) | No conoce Laravel, ni HTTP, ni el controlador; solo implementa el contrato |
| **Presentación** (vista Blade) | Mostrar el informe estructurado (necesidad detectada, supuestos, preguntas, decisiones, propietarios, riesgos, estado) | No decide el contenido, solo lo presenta |

Esta separación es intencionadamente mínima: 5 responsabilidades, sin DDD ni CQRS, coherente con la arquitectura MVC + Services + Jobs ya existente en el repositorio (ver auditoría).

## Contrato inicial de entrada

```php
final class NeedInput
{
    public function __construct(
        public readonly string $rawText,   // la solicitud original, texto libre
    ) {}
}
```

Deliberadamente mínimo: en este primer experimento la única entrada es el texto original del usuario. Cualquier otro contexto (categorías, equipo, código, tareas similares — como ya hace `AiTaskRefiner`) se añadirá en fases posteriores, cuando el contrato lo justifique.

## Contrato inicial de salida

```php
final class ComprehensionReport
{
    public function __construct(
        public readonly string $detectedNeed,        // necesidad detectada, reformulada
        public readonly string $originalRequest,      // la solicitud original (trazabilidad)
        public readonly array  $assumptions,          // string[] — supuestos hechos por el motor
        public readonly array  $openQuestions,        // string[] — preguntas abiertas
        public readonly array  $pendingDecisions,     // string[] — decisiones pendientes detectadas
        public readonly array  $suggestedOwners,      // string[] — propietarios sugeridos (rol o persona)
        public readonly array  $risks,                // string[] — riesgos identificados
        public readonly string $readinessState,       // p.ej. "ready" | "needs_clarification" | "blocked"
        public readonly string $readinessReason,      // motivo del estado, en lenguaje natural
    ) {}
}
```

Corresponde directamente a los campos pedidos: necesidad detectada, solicitud original, supuestos, preguntas abiertas, decisiones pendientes, propietarios sugeridos, riesgos, estado de preparación y motivo del estado.

## El contrato (interfaz)

```php
interface ComprehensionEngine
{
    public function analyze(NeedInput $input): ComprehensionReport;
}
```

Esta es la única pieza que el caso de uso conoce. El caso de uso (`AnalyzeNeed`) depende de la interfaz, nunca de una implementación concreta — se resuelve vía el contenedor de Laravel (binding en `AppServiceProvider`, siguiendo el principio "contrato antes que proveedor", `docs/second-brain/principios.md`).

## Estrategia para el motor simulado

Una implementación (`SimulatedComprehensionEngine`) que, sin llamar a ningún proveedor externo, aplique reglas fijas y deterministas sobre el texto de entrada para producir un `ComprehensionReport` plausible:

- Si el texto es muy corto o no contiene verbos de acción reconocibles → `readinessState = "needs_clarification"` con preguntas abiertas genéricas ("¿qué comportamiento actual observas?", "¿qué debería ocurrir en su lugar?").
- Si el texto menciona palabras como "urgente", "producción", "cliente" → añadir un riesgo genérico relacionado con impacto.
- Siempre devuelve al menos una decisión pendiente y un propietario sugerido genérico (p. ej. "responsable funcional del área mencionada, si se identifica; si no, sin asignar").

El objetivo no es que sea inteligente, sino que el contrato, la interfaz, el caso de uso, la ruta y la vista funcionen de principio a fin sin ninguna dependencia externa, coste ni latencia — validando la arquitectura antes de invertir en IA real (D-011).

## Estrategia futura para un motor basado en IA

Una segunda implementación (`AiComprehensionEngine`) que implemente la misma interfaz `ComprehensionEngine`, llamando a un proveedor de IA (reutilizando el patrón ya usado en `AiTaskRefiner`: prompt estructurado, `response_format: json_object`, parseo defensivo con fallback). Al depender ambas implementaciones del mismo contrato, el cambio de una a otra es un cambio de binding en `AppServiceProvider`, no un cambio en el controlador, el caso de uso ni la vista.

No se implementa en este sprint.

## Propuesta de pruebas

- **Test del motor simulado en aislamiento:** dado un texto de entrada conocido, verificar que el `ComprehensionReport` resultante tiene la forma esperada y aplica las reglas deterministas (sin HTTP, sin base de datos).
- **Test del caso de uso:** verificar que `AnalyzeNeed` invoca correctamente el contrato inyectado (con un engine simulado/mock) y propaga el resultado sin alterarlo.
- **Test de integración HTTP (Feature test):** `POST` a la nueva ruta con un texto de necesidad, verificar que la respuesta/vista contiene los campos del informe. Usa la infraestructura de tests ya existente (SQLite en memoria, cola síncrona — aunque este flujo no necesita cola en su primera versión).

## Criterios de aceptación del primer experimento

- Un usuario autenticado puede introducir un texto libre describiendo una necesidad ambigua.
- La aplicación devuelve, en la misma sesión de navegación, un informe con los 9 campos del contrato de salida.
- El motor es intercambiable: sustituir el binding en `AppServiceProvider` por una implementación real no requiere tocar el controlador, el caso de uso ni la vista.
- Ninguna funcionalidad existente (`Task`, integraciones, autenticación) se ve alterada.
- El flujo tiene al menos un test automatizado por capa (motor, caso de uso, HTTP).

## Referencias

- `docs/arquitectura/auditoria-repositorio-sprint-08.md`
- `docs/decisiones/D-010-adaptar-repositorio-laravel-existente.md`
- `docs/decisiones/D-011-primer-motor-simulado.md`
- `docs/dominio/necesidad.md`
- `docs/dominio/decision.md`
- `docs/modelo/motor-de-comprension.md`

---
*Fecha: 2026-07-25 (Sprint 08)*
