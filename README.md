# API de Transacciones Financieras

API RESTful para la gestión de usuarios y transacciones financieras desarrollada con Laravel.

## Requisitos

- PHP 8.2+
- Composer
- MySQL o SQLite

## Instalación

1. Clonar el repositorio
2. Instalar dependencias: `composer install`
3. Configurar variables de entorno: `cp .env.example .env`
4. Generar clave: `php artisan key:generate`
5. Configurar base de datos en `.env`
6. Ejecutar migraciones: `php artisan migrate`
7. Crear datos de prueba: `php artisan db:seed`
8. Iniciar servidor: `php artisan serve`

## Documentación

La documentación de la API está disponible en `/docs` una vez iniciado el servidor.

## Endpoints principales

### Usuarios
- `POST /api/users` - Crear usuario
- `GET /api/users` - Listar usuarios
- `GET /api/users/{id}` - Ver usuario
- `PUT /api/users/{id}` - Actualizar usuario
- `DELETE /api/users/{id}` - Eliminar usuario

### Transacciones
- `POST /api/transactions/transfer` - Realizar transferencia

### Reportes
- `GET /api/reports/transactions/export` - Exportar CSV
- `GET /api/reports/transfers/totals` - Totales por usuario
- `GET /api/reports/transfers/averages` - Promedios por usuario

## Validaciones implementadas

- No permitir transferencias por encima del saldo del emisor
- Límite diario de transferencia: 5.000 USD
- Evitar transacciones duplicadas
- Personalización de mensajes de error

## Testing

Ejecutar tests: `php artisan test`

## Caso 2: Análisis del problema de límite diario

### Problema
Los usuarios reportan que el sistema permite hacer más de 5.000 USD en transferencias diarias.

### Identificación del origen
El problema se origina por condiciones de carrera (race conditions) cuando múltiples transacciones simultáneas consultan el total diario antes de que cada una se complete. Esto permite que varias transacciones pasen la validación del límite diario al mismo tiempo.

### Solución implementada
Se implementó una solución con locks pesimistas para evitar que los usuarios puedan exceder el límite diario de 5.000 USD en transferencias:

1. Se utiliza `lockForUpdate()` en la consulta del usuario para bloquear la fila durante la transacción
2. Se recalcula el límite diario con el lock activo
3. Se invalida la caché después de cada transacción exitosa
4. Se implementa logging detallado para monitoreo y debugging

Esta solución garantiza que las validaciones de límite diario se realicen de forma atómica, evitando que múltiples transacciones simultáneas puedan exceder el límite establecido.