# Njord API

API REST para la gestión de rutas en motocicleta. Permite registrar rutas con archivos GPX, consultar condiciones meteorológicas históricas y obtener predicciones del tiempo para rutas planificadas.

Construida con **Laravel** y autenticación mediante **Laravel Sanctum**. Datos meteorológicos proporcionados por **Open-Meteo** (gratuito, sin API key).

---

## Requisitos

- PHP >= 8.1
- Composer
- MySQL

---

## Instalación

```bash
git clone https://github.com/VictorMCuevas/api_njord.git
cd njord_api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link
```

---

## Autenticación

La API usa tokens Bearer mediante Laravel Sanctum. Todos los endpoints protegidos requieren la cabecera:

```
Authorization: Bearer {token}
```

El token se obtiene al registrarse o iniciar sesión.

---

## Endpoints

### 🌐 Públicos

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `GET` | `/api/salud` | Estado del servidor |
| `POST` | `/api/auth/registrar` | Crear cuenta |
| `POST` | `/api/auth/iniciar-sesion` | Iniciar sesión |

---

### 🔒 Autenticación

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `POST` | `/api/auth/cerrar-sesion` | Cerrar sesión |
| `GET` | `/api/auth/perfil` | Ver perfil del usuario |
| `PUT` | `/api/auth/perfil` | Actualizar perfil |

---

### 🗺️ Rutas

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `GET` | `/api/rutas` | Listar todas las rutas del usuario |
| `POST` | `/api/rutas` | Crear nueva ruta |
| `GET` | `/api/rutas/{id}` | Ver una ruta |
| `PUT` | `/api/rutas/{id}` | Editar una ruta |
| `DELETE` | `/api/rutas/{id}` | Eliminar una ruta |

#### Body — `POST /api/rutas`

```json
{
  "nombre": "string (obligatorio)",
  "fecha": "date YYYY-MM-DD (opcional)",
  "descripcion": "string (opcional)",
  "tipo_moto": "string máx 100 (opcional)",
  "estilo_conduccion": "string máx 100 (opcional)",
  "latitud": "decimal -90 a 90 (opcional)",
  "longitud": "decimal -180 a 180 (opcional)",
  "distancia_km": "decimal >= 0 (opcional)",
  "nivel_dificultad": "integer 1-5 (opcional)",
  "valoracion_personal": "integer 1-5 (opcional)",
  "inicio": {
    "latitud": "decimal -90 a 90 (opcional)",
    "longitud": "decimal -180 a 180 (opcional)",
    "hora": "HH:MM (opcional)"
  },
  "medio": {
    "latitud": "decimal -90 a 90 (opcional)",
    "longitud": "decimal -180 a 180 (opcional)",
    "hora": "HH:MM (opcional)"
  },
  "fin": {
    "latitud": "decimal -90 a 90 (opcional)",
    "longitud": "decimal -180 a 180 (opcional)",
    "hora": "HH:MM (opcional)"
  }
}
```

> Si `fecha` es pasada y se envían coordenadas de `inicio`, `medio` o `fin`, las condiciones meteorológicas históricas se guardan automáticamente desde Open-Meteo.

---

### 📁 Archivos GPX

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `POST` | `/api/rutas/{id}/subir-gpx` | Subir archivo GPX |
| `GET` | `/api/rutas/{id}/descargar-gpx` | Descargar archivo GPX |
| `GET` | `/api/rutas/{id}/gpx` | Ver contenido GPX inline |
| `DELETE` | `/api/rutas/{id}/gpx` | Eliminar archivo GPX |

#### Body — `POST /api/rutas/{id}/subir-gpx`

`multipart/form-data`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `archivo_gpx` | file | Archivo `.gpx` (obligatorio, máx 10MB) |

---

### 🌤️ Clima

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `GET` | `/api/rutas/{id}/clima` | Ver condiciones meteorológicas guardadas de una ruta |
| `GET` | `/api/condiciones-atmosfericas/{id}` | Ver una condición específica |
| `GET` | `/api/clima/prediccion` | Predicción para una fecha futura |
| `POST` | `/api/clima/historico` | Consultar clima histórico puntual sin guardar |

#### Parámetros — `GET /api/clima/prediccion`

| Parámetro | Tipo | Obligatorio | Descripción |
|-----------|------|-------------|-------------|
| `latitud` | decimal | Sí | Entre -90 y 90 |
| `longitud` | decimal | Sí | Entre -180 y 180 |
| `fecha` | date | Sí | Fecha futura `YYYY-MM-DD` |
| `hora` | time | No | `HH:MM` — si se incluye devuelve datos horarios precisos |

#### Body — `POST /api/clima/historico`

```json
{
  "latitud": "decimal -90 a 90 (obligatorio)",
  "longitud": "decimal -180 a 180 (obligatorio)",
  "fecha": "date YYYY-MM-DD pasada (obligatorio)",
  "hora": "HH:MM (opcional)"
}
```

---

## Formato de respuesta

Todas las respuestas siguen el mismo formato:

```json
{
  "estado": "exito | error",
  "mensaje": "Descripción",
  "datos": {}
}
```

Los errores de validación devuelven código `422` con el campo `errores` detallando cada campo inválido.
