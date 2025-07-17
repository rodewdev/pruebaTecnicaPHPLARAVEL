# Authenticating requests

Esta API utiliza Laravel Sanctum para la autenticación con tokens. Todas las rutas están protegidas excepto:
- `POST /api/users` (registro de usuarios)
- `POST /api/login` (inicio de sesión)

Para acceder a las rutas protegidas, debes incluir un token de autenticación en las solicitudes.

## Autenticación con Laravel Sanctum

En un entorno de producción, la API utilizaría Laravel Sanctum para la autenticación con tokens. El proceso sería el siguiente:

### 1. Obtener un token de autenticación

```bash
POST /api/login
Content-Type: application/json

{
    "email": "juan@example.com",
    "password": "password"
}
```

La respuesta incluiría el token de acceso:

```json
{
    "success": true,
    "message": "Inicio de sesión exitoso",
    "data": {
        "token": "1|abcdefghijklmnopqrstuvwxyz",
        "user": {
            "id": 1,
            "name": "Juan Pérez",
            "email": "juan@example.com"
        }
    }
}
```

### 2. Usar el token en las solicitudes

```bash
GET /api/users
Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz
```

## Usuarios de prueba disponibles

Para probar la API, puedes usar los siguientes usuarios:

- **Juan Pérez**: `juan@example.com`
- **María García**: `maria@example.com`
- **Carlos López**: `carlos@example.com`
