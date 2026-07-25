# Arquitectura

> Plantilla inicial. Espacio para documentación de arquitectura técnica de alto nivel (estratégica, no de implementación detallada).

## Propósito de esta carpeta

Documentar decisiones de arquitectura a nivel estratégico: cómo se integran los sistemas, qué principios técnicos guían las decisiones, y cómo evoluciona la infraestructura a medida que el producto crece.

> Nota: la documentación técnica detallada de implementación ya existe en `CLAUDE.md` y en `docs/ai-task-refiner-spec.md`, `docs/discord-inbox.md`, `docs/founderz-tasklab-flow.md`. Esta carpeta complementa esos documentos con una vista más estratégica/evolutiva.

## Contenido esperado

- Diagramas de arquitectura de alto nivel
- Decisiones sobre escalabilidad y multi-tenancy
- Evolución prevista de integraciones (nuevos canales de comunicación, nuevos destinos de tareas)
- [auditoria-repositorio-sprint-08.md](auditoria-repositorio-sprint-08.md) — inspección completa del repositorio Laravel existente y clasificación de sus componentes frente al nuevo dominio (Sprint 08)
- [primer-flujo-motor-comprension.md](primer-flujo-motor-comprension.md) — diseño del primer flujo vertical del Motor de Comprensión, contratos de entrada/salida y arquitectura en capas (Sprint 08)

## Pendiente

- [ ] Diagrama de arquitectura actual (Discord → Pipedream → TaskLab → notificación)
- [ ] Plan de evolución hacia soporte multi-canal y multi-destino
- [ ] Implementar el primer flujo vertical del Motor de Comprensión (Sprint 08B, ver `docs/arquitectura/primer-flujo-motor-comprension.md`)

## Referencias cruzadas

- `CLAUDE.md`
- `docs/founderz-tasklab-flow.md`
- `docs/dominio/necesidad.md`
- `docs/modelo/motor-de-comprension.md`

---
*Última actualización: 2026-07-25 (Sprint 08)*
