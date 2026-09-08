# Tienda de Negocios — API REST (Proyecto integrador)

API REST de una tienda, construida con Laravel a lo largo de 5 entregas:
dominio con POO, persistencia con Eloquent, API REST versionada,
autenticación con JWT, y una suite de tests automatizados. Es **solo API**:
todo lo que devuelve es JSON, pensada para Postman, una app o un frontend
separado.

## Índice

1. [Instalación](#1-instalación)
2. [Arquitectura](#2-arquitectura)
3. [Uso de la API](#3-uso-de-la-api)
4. [Seguridad (JWT)](#4-seguridad-jwt)
5. [Testing](#5-testing)

---

## 1. Instalación

Requisitos: PHP 8.3+, Composer, XAMPP (MySQL), Postman.

### 1.1. Base de datos (MySQL con XAMPP)

1. Panel de XAMPP → **Start** en MySQL.
2. `http://localhost/phpmyadmin` → crear la base `tienda_api`
   (cotejamiento `utf8mb4_unicode_ci`).

### 1.2. Proyecto

```powershell
git clone https://github.com/dav-mach/tienda_API.git tienda-api
cd tienda-api

composer install
copy .env.example .env
php artisan key:generate
php artisan jwt:secret

php artisan migrate --seed
php artisan serve
```

La API queda en `http://127.0.0.1:8000/api/v1`. Usuario de prueba del
seeder: **ana.perez@mail.com** / **password123**.

> `jwt:secret` genera la clave que firma los tokens y la escribe en `.env`.
> En macOS/Linux, usá `cp` en vez de `copy`.

---

## 2. Arquitectura

Patrón por capas, sobre Laravel:

```
Cliente (Postman / app / frontend)
        │  HTTP + JSON, header Authorization: Bearer <JWT>
        ▼
routes/api.php  ──▶  Middleware (jwt, carrito)  ──▶  Controllers/Api/V1/*
                                                          │
                                                          ▼
                                    Services/*  ──▶  Models (Eloquent)
                                        │
                                        ▼
                              DTOs (entrada/salida)     Http/Resources/* (salida JSON)
```

- **Controladores** (`app/Http/Controllers/Api/V1/`): reciben la petición,
  llaman al Service, devuelven la respuesta. Sin lógica de negocio.
- **Services** (`app/Services/`): `CarritoService` y `CheckoutService`
  concentran las reglas (stock, subtotales, armar/confirmar pedidos).
- **Modelos** (`app/Models/`): Producto, Categoria, Usuario (`User`),
  Carrito, CarritoItem, Pedido, PedidoItem.
- **DTOs** (`app/DTOs/`): estructuran datos de entrada/salida que no son
  un modelo 1 a 1 (resumen de compra, datos de checkout).
- **Resources** (`app/Http/Resources/`): serializan los modelos a JSON.
- **Excepciones** (`app/Exceptions/`): errores de negocio que se
  convierten en JSON con su código HTTP (definido en `bootstrap/app.php`).
- **Middlewares** (`app/Http/Middleware/`): `JwtAutenticado` (exige token)
  e `IdentificarCarrito` (asigna el carrito del usuario).

Toda la API vive bajo `/api/v1` — el prefijo de versión permite sacar una
`v2` en el futuro sin romper clientes que ya usen `v1`.

---

## 3. Uso de la API

Formato de respuesta uniforme en toda la API:

```json
{ "success": true, "message": "...", "data": { } }
```

Endpoints principales (todos bajo `/api/v1`):

| Área | Verbo + ruta | Protegida | Descripción |
|---|---|---|---|
| Auth | POST `/auth/register` | No | Registro, devuelve JWT. |
| Auth | POST `/auth/login` | No | Login, devuelve JWT. |
| Auth | GET `/auth/me` | Sí | Usuario del token. |
| Auth | POST `/auth/logout` | Sí | Invalida el token. |
| Catálogo | GET `/productos`, `/categorias` | No | Listado y detalle (público). |
| Catálogo | POST/PUT/DELETE `/productos`, `/categorias` | Sí | Administración. |
| Carrito | GET/POST/PUT/DELETE `/carrito*` | Sí | Ver, agregar, actualizar, quitar, vaciar. |
| Carrito | GET `/carrito/resumen` | Sí | Subtotal, impuestos, envío, total. |
| Checkout | POST `/checkout` | Sí | Registrar datos de envío/pago. |
| Checkout | POST `/checkout/{pedido}/confirmar` | Sí | Confirmar compra. |

La colección de Postman
(`postman/tienda-negocios-api.postman_collection.json`) tiene todos los
endpoints con ejemplos, y guarda el token automáticamente al hacer login.

---

## 4. Seguridad (JWT)

- **Autenticación por JWT** (`php-open-source-saver/jwt-auth`): el login
  devuelve un token firmado que el cliente manda en cada petición como
  `Authorization: Bearer <token>`. El servidor no guarda sesión.
- **Rutas protegidas**: el middleware `jwt` bloquea con `401` cualquier
  ruta sensible (carrito, checkout, administración de catálogo) sin un
  token válido. Login, registro y lectura de catálogo quedan públicos.
- **Contraseñas con bcrypt**: el modelo `User` usa el cast `'hashed'`; la
  contraseña nunca se guarda ni se devuelve en texto plano.
- **Payload sin datos sensibles**: el token solo lleva el id del usuario.
- **Protección contra ataques comunes**: SQL Injection (Eloquent usa
  consultas parametrizadas), XSS (API JSON, sin render de HTML), CSRF (al
  ser stateless con token en header, no aplica el vector clásico de
  cookies).

En producción, el tráfico debe ir sobre **HTTPS** para que el token no
viaje en texto plano (se fuerza con `URL::forceScheme('https')` en el
`AppServiceProvider` + certificado TLS en el servidor).

---

## 5. Testing

Suite de tests automatizados escritos como **clases PHPUnit**, siguiendo el
patrón **Triple A (Arrange, Act, Assert)** en cada test, y corriendo sobre
**SQLite en memoria** (no toca MySQL de desarrollo).

> El proyecto tiene Pest instalado, pero los tests están escritos como
> clases tradicionales; Pest las ejecuta igual, así que `php artisan test`
> corre toda la suite sin configuración extra.

### Cómo correr los tests

```powershell
php artisan test
```

O con más detalle por test:

```powershell
php artisan test --testdox
```

No hace falta configurar nada: `phpunit.xml` ya define la base SQLite en
memoria, el mailer en modo `array` (los emails no se mandan de verdad) y
un `JWT_SECRET` de testing.

### Qué se prueba

**Unit tests** (`tests/Unit/`) — lógica de negocio aislada:
- `CarritoServiceTest`: cálculo de subtotal, IVA 21%, envío gratis desde
  $100.000, y total.
- `ProductoStockTest`: reglas de `hayStockDisponible()`.
- `ModelosReglasTest`: `CarritoItem::subtotal()` y `Carrito::estaVacio()`.
- `CheckoutMockeryTest`: **mocking con Mockery** — reemplaza
  `CarritoService` por un mock dentro de `CheckoutService`.

**Feature tests** (`tests/Feature/`) — endpoints de punta a punta:
- `AuthTest`: registro, login OK, login inválido (401), `/auth/me` con y
  sin token.
- `ProductoTest`: alta de producto (con token), listado público, creación
  bloqueada sin token, validación 422.
- `CarritoTest`: agregar/quitar del carrito, validación de stock (409),
  resumen.
- `CheckoutTest`: flujo completo de compra + **mock del email de
  confirmación con `Mail::fake()`**.
- `SeguridadMiddlewareTest`: recorre todas las rutas protegidas (con un
  data provider) y verifica que devuelvan `401` sin token o con token
  inválido.

Cada test se divide en tres fases comentadas: **Arrange** (preparar los
datos), **Act** (ejecutar la acción bajo prueba) y **Assert** (verificar el
resultado).

### Datos de prueba

Se generan con **Factories** (`database/factories/`): `UserFactory`
(de Laravel), `CategoriaFactory`, `ProductoFactory` (con estado
`sinStock()`) y `CarritoFactory`. Cada test arranca con la base recién
migrada (`RefreshDatabase`), así no dependen unos de otros.

### Mocking

Dos técnicas, según el caso:
- **`Mail::fake()`** (en `CheckoutTest`): intercepta el email de
  confirmación de compra para verificar que "se habría enviado", sin
  depender de un servidor de correo real.
- **`Mockery`** (en `CheckoutMockeryTest`): reemplaza una dependencia
  interna (`CarritoService`) por un objeto falso con respuestas
  predefinidas, para aislar la unidad bajo prueba.

### Reporte de ejecución

La salida de `php artisan test` está guardada en
[`docs/test-output.txt`](docs/test-output.txt) como evidencia y captura de pantalla.
