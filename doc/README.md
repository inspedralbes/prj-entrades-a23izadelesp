# QueueLy — Documentació tècnica

Aquesta carpeta conté la documentació tècnica exhaustiva del projecte, amb navegació entre fitxers Markdown.

## Índex

1. [Visió general](./01-overview.md)
2. [Arquitectura del sistema](./02-architecture.md)
3. [Backend i API](./03-backend-api.md)
4. [Serveis de domini (locks, pagaments, QR)](./04-services-domain.md)
5. [Cues i temps real (nucli del projecte)](./05-queues-realtime.md)
6. [Frontend i flux d'usuari](./06-frontend-flow.md)
7. [Model de dades](./07-data-model.md)
8. [Infraestructura i desplegament](./08-infra-deploy.md)
9. [CI/CD (GitHub Actions)](./09-ci-cd.md)

## Ordre de lectura recomanat

- **Per entendre el projecte ràpid**: `01 -> 02 -> 05`.
- **Per treballar a backend**: `03 -> 04 -> 07`.
- **Per treballar en operació i deploy**: `08 -> 09`.

## Focus del projecte

El component més important és la coordinació de **cua + locks + temps real** per evitar sobreventa i oferir feedback instantani durant alta concurrència.

Document clau: [05-queues-realtime.md](./05-queues-realtime.md).
