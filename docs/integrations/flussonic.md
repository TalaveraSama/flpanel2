# Integración de Flussonic

## Objetivo

XC_VM administrará dos tipos de origen desde el mismo flujo de trabajo:

1. **M3U8 externo**: una URL HLS que no pertenece necesariamente a Flussonic.
2. **Flussonic**: streams descubiertos y administrados mediante la API de uno o varios servidores Flussonic.

La forma de entrega se seleccionará por stream:

- **Proxy XC_VM**: XC_VM consume el origen y entrega el contenido al cliente.
- **Entrega Flussonic**: XC_VM autoriza al cliente y genera una URL temporal para que Flussonic entregue el contenido.

## Alcance inicial

- Registrar múltiples servidores Flussonic.
- Guardar secretos cifrados; nunca devolverlos en formularios ni logs.
- Probar conectividad y compatibilidad de API.
- Descubrir y paginar streams disponibles.
- Importar uno o varios streams a XC_VM.
- Relacionar un stream XC_VM con `server_id` y el identificador estable de Flussonic.
- Crear, editar, iniciar, detener y reiniciar streams según capacidades de la versión detectada.
- Consultar estado, bitrate, sesiones y errores.
- Mantener soporte existente para URL M3U8 externa.
- Permitir modo de entrega por stream (`xcvm_proxy` o `flussonic_direct`).
- Sincronización manual y mediante cron, con registro de auditoría.

## Límites de responsabilidad

### XC_VM

- Usuarios, líneas, bouquets, categorías y vencimientos.
- Autorización y límites de conexiones.
- Selección del origen y modo de entrega.
- Importación y sincronización de metadatos.
- Generación de playlists y URLs públicas.

### Flussonic

- Ingesta, remux, transcodificación y entrega directa.
- DVR/catch-up cuando esté configurado.
- Estado técnico, bitrate y sesiones del motor multimedia.

## Modelo de datos propuesto

### `flussonic_servers`

- `id`
- `name`
- `base_url`
- `api_version`
- `auth_type`
- `encrypted_credentials`
- `verify_tls`
- `connect_timeout`
- `request_timeout`
- `enabled`
- `last_seen_at`
- `last_error`
- timestamps

### `flussonic_stream_links`

- `id`
- `stream_id` (XC_VM)
- `flussonic_server_id`
- `remote_stream_id`
- `delivery_mode`: `xcvm_proxy` o `flussonic_direct`
- `sync_mode`: `manual`, `metadata`, `managed`
- `remote_revision` o hash de configuración
- `last_synced_at`
- `last_status`
- `last_error`
- timestamps

La URL M3U8 externa continúa usando el modelo de fuentes existente y no necesita una fila en `flussonic_stream_links`.

## Servicios

- `FlussonicClientInterface`: contrato independiente de la versión de API.
- `FlussonicApiClient`: HTTP, autenticación, timeout y normalización de errores.
- `FlussonicCapabilityDetector`: detecta versión y operaciones admitidas.
- `FlussonicStreamService`: listar, obtener, crear, actualizar y controlar streams.
- `FlussonicImportService`: convierte streams remotos al modelo XC_VM.
- `FlussonicSyncService`: sincronización idempotente y detección de conflictos.
- `FlussonicPlaybackService`: construye URL directa/token sin exponer secretos.
- `FlussonicHealthService`: estado del servidor y métricas básicas.

## Reglas de sincronización

- El identificador remoto, no el nombre visible, es la clave de asociación.
- Importar dos veces el mismo stream debe actualizar la asociación, no duplicarla.
- `manual`: XC_VM no escribe configuración en Flussonic.
- `metadata`: XC_VM actualiza únicamente metadatos locales.
- `managed`: XC_VM puede crear y modificar el stream remoto.
- Una eliminación local no elimina el stream remoto sin confirmación explícita.
- Una eliminación remota marca la asociación como ausente; no elimina automáticamente líneas o bouquets.
- Toda acción destructiva se registra en auditoría.

## Seguridad

- No guardar usuario/token de Flussonic en texto plano.
- No incluir secretos en query strings, logs, excepciones o HTML.
- Validar `base_url` y bloquear destinos no autorizados para reducir SSRF.
- TLS verificado por defecto; desactivarlo debe producir una advertencia visible.
- URLs directas de reproducción deben ser temporales y restringidas cuando Flussonic lo permita.
- Las acciones administrativas requieren permisos específicos y protección CSRF.
- Aplicar límites de frecuencia y timeouts a operaciones de API.

## Interfaz administrativa

### Servidores → Flussonic

- listar/agregar/editar/deshabilitar servidores;
- probar conexión;
- mostrar versión, latencia, estado y último error;
- abrir explorador de streams.

### Importar streams

- búsqueda y paginación;
- selección múltiple;
- categoría/bouquet destino;
- modo de entrega;
- modo de sincronización;
- vista previa antes de importar.

### Stream

- origen `M3U8 externo` o `Flussonic`;
- servidor e identificador remoto;
- estado, bitrate, clientes y último error;
- iniciar, detener, reiniciar y sincronizar;
- selector de entrega por XC_VM o directa por Flussonic.

## Fases

1. Cliente API, servidores, prueba de conexión y detección de capacidades.
2. Explorador e importación idempotente de streams.
3. Estado, métricas y sincronización periódica.
4. Operaciones de control y edición remota.
5. Entrega directa con autorización/token y control de conexiones.
6. DVR/catch-up y múltiples servidores/balanceo.

## Datos pendientes para implementar la API

- Versión exacta de Flussonic usada en producción.
- Tipo de autenticación disponible.
- Ejemplos anonimizados de las respuestas de versión, listado y estado de streams.
- Formato de autorización/token que se usará para entrega directa.

No se deben copiar credenciales reales al repositorio, issues, logs o conversaciones.
