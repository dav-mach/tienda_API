# Entrega 4 — Seguridad: autenticación JWT y protección de la tienda

Esta entrega agrega **autenticación con JSON Web Tokens (JWT)** sobre la API
de la Entrega 3: ahora solo usuarios autenticados pueden gestionar su
carrito y confirmar compras, y las rutas sensibles están protegidas por
middleware.

Librería usada: **php-open-source-saver/jwt-auth** (el fork mantenido del
clásico `tymon/jwt-auth`).

## Índice

1. [Qué es un JWT y cómo se usa acá](#1-qué-es-un-jwt-y-cómo-se-usa-acá)
2. [Instalación de la Entrega 4](#2-instalación-de-la-entrega-4)
3. [Endpoints de autenticación](#3-endpoints-de-autenticación)
4. [Rutas protegidas y por qué](#4-rutas-protegidas-y-por-qué)
5. [Buenas prácticas de seguridad aplicadas](#5-buenas-prácticas-de-seguridad-aplicadas)
6. [Protección contra ataques comunes](#6-protección-contra-ataques-comunes)
7. [HTTPS en producción](#7-https-en-producción)
8. [Pruebas con Postman](#8-pruebas-con-postman)

---

## 1. Qué es un JWT y cómo se usa acá

Un **JSON Web Token** es una cadena de texto firmada que el servidor le
entrega al cliente cuando este se loguea, y que el cliente reenvía en cada
petición siguiente para probar quién es — sin que el servidor tenga que
guardar ninguna sesión.

### Estructura (3 partes separadas por puntos)

```
eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9  .  eyJzdWIiOiIxIiwiZXhwIjoxNz...  .  SflKxwRJSMeKKF2QT4f...
        HEADER (base64)                        PAYLOAD (base64)                  SIGNATURE
```

- **Header**: qué algoritmo de firma se usa (acá `HS256`).
- **Payload**: los "claims" — datos del token. En esta app incluye solo el
  `sub` (id del usuario) y datos de control de tiempo (`iat` emitido en,
  `exp` expira en). **No** lleva email, rol ni nada sensible, porque el
  payload viaja *codificado en base64, no cifrado*: cualquiera que
  intercepte el token puede leerlo.
- **Signature**: la firma. Se calcula con el header, el payload y una clave
  secreta (`JWT_SECRET`) que solo conoce el servidor. Si alguien altera el
  payload (por ejemplo, para cambiar el `sub` y hacerse pasar por otro
  usuario), la firma deja de coincidir y el token se rechaza.

### Ciclo de vida

1. **Emisión**: el usuario hace `POST /auth/login` con email + contraseña.
   Si son correctos, el servidor firma un JWT con `JWT_SECRET` y se lo
   devuelve.
2. **Uso**: el cliente manda ese token en cada petición protegida, en el
   header `Authorization: Bearer <token>`.
3. **Verificación**: en cada petición, el middleware `jwt` recalcula la
   firma y la compara con la que trae el token. Si coincide y no expiró,
   deja pasar; si no, responde `401`.
4. **Expiración**: el token vale por `JWT_TTL` minutos (60 = 1 hora por
   defecto). Pasado ese tiempo, hay que volver a loguearse.
5. **Invalidación**: `POST /auth/logout` agrega el token a una *blacklist*
   para que no se pueda reutilizar aunque todavía no haya expirado.

---

## 2. Instalación de la Entrega 4

Partiendo del proyecto de la Entrega 3 ya funcionando:

```powershell
composer require php-open-source-saver/jwt-auth

php artisan jwt:secret
```

`jwt:secret` genera la clave secreta y la escribe como `JWT_SECRET` en tu
`.env` (esa clave es la que firma y verifica todos los tokens — no se
comparte ni se sube al repo).

Luego:

```powershell
php artisan migrate:fresh --seed
php artisan serve
```

> **Por qué `migrate:fresh`**: esta entrega cambia la estructura del
> carrito (ahora pertenece a un usuario, ya no usa token anónimo). Como
> es una base de desarrollo, lo más limpio es recrear las tablas de cero.
> El seeder deja un usuario listo para probar: **ana.perez@mail.com** /
> **password123**.

---

## 3. Endpoints de autenticación

Todos bajo `/api/v1/auth`. Devuelven el formato JSON estándar de la API
(`{ success, message, data }`).

| Verbo | Ruta | Protegida | Body | Descripción |
|---|---|---|---|---|
| POST | `/auth/register` | No | `name, email, password, password_confirmation` | Crea un usuario y devuelve un JWT. |
| POST | `/auth/login` | No | `email, password` | Valida credenciales y devuelve un JWT. |
| GET | `/auth/me` | Sí | — | Devuelve el usuario dueño del token. |
| POST | `/auth/logout` | Sí | — | Invalida el token actual. |

Ejemplo de respuesta de `login`:

```json
{
  "success": true,
  "message": "Sesión iniciada.",
  "data": {
    "usuario": { "id": 1, "name": "Ana Pérez", "email": "ana.perez@mail.com", "rol": "admin" },
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "token_type": "bearer",
    "expira_en": 3600
  }
}
```

Para usar el token en las rutas protegidas, se manda en el header:

```
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

---

## 4. Rutas protegidas y por qué

La protección se aplica con dos middlewares (registrados en
`bootstrap/app.php`):

- **`jwt`** (`App\Http\Middleware\JwtAutenticado`): exige un JWT válido; si
  falta, expiró o es inválido, corta con `401` antes de llegar al
  controlador.
- **`carrito`** (`App\Http\Middleware\IdentificarCarrito`): se aplica
  *después* de `jwt`, y asigna a la petición el carrito del usuario
  autenticado (si no tiene, se lo crea).

| Ruta | Verbo | ¿Protegida? | Por qué |
|---|---|---|---|
| `/auth/register`, `/auth/login` | POST | **No** | Son la puerta de entrada: sin ellas nadie podría conseguir un token. |
| `/auth/me`, `/auth/logout` | GET/POST | **Sí** (`jwt`) | Operan sobre el usuario del token. |
| `/productos`, `/categorias` (GET) | GET | **No** | Mirar el catálogo es público, no requiere cuenta. |
| `/productos`, `/categorias` (POST/PUT/DELETE) | escritura | **Sí** (`jwt`) | Crear/editar/borrar catálogo son acciones de administración. |
| `/carrito/*` | todos | **Sí** (`jwt` + `carrito`) | Cada usuario gestiona **su propio** carrito. |
| `/checkout`, `/checkout/{pedido}/confirmar` | POST | **Sí** (`jwt`) | Confirmar una compra debe hacerlo un usuario autenticado. |

Se eligió aplicar los middlewares **a grupos de rutas específicos** (en
`routes/api.php`) en vez de globalmente, justamente porque hay rutas que
deben quedar públicas (login, register, y la lectura del catálogo). Un
middleware global habría bloqueado también esas.

---

## 5. Buenas prácticas de seguridad aplicadas

- **Contraseñas hasheadas con bcrypt**: el modelo `User`
  tiene el cast `'password' => 'hashed'`, así que Laravel hashea la
  contraseña con bcrypt automáticamente al crear/actualizar el usuario. En
  la base nunca se guarda la contraseña en texto plano, y `UsuarioResource`
  nunca la devuelve en las respuestas (ni siquiera el hash).
- **Payload sin datos sensibles**: `getJWTCustomClaims()` devuelve un array
  vacío a propósito — el token solo lleva el id del usuario, no su email ni
  su rol.
- **Expiración corta**: `JWT_TTL=60` (1 hora). Un token robado deja de
  servir rápido.
- **Secreto fuera del repo**: `JWT_SECRET` vive en `.env`, que está en
  `.gitignore`.
- **Login que no filtra información**: ante credenciales incorrectas se
  responde un genérico "Credenciales inválidas" (401), sin aclarar si lo
  que falló fue el email o la contraseña — así no se le confirma a un
  atacante qué emails existen.

---

## 6. Protección contra ataques comunes

Estas protecciones vienen en buena parte de fábrica con Laravel; la
entrega consiste en entender por qué ya estamos cubiertos y no
desactivarlas.

- **SQL Injection**: todo el acceso a datos pasa por Eloquent y el query
  builder, que usan *prepared statements* (consultas parametrizadas). Los
  valores nunca se concatenan directamente al SQL, así que un input como
  `'; DROP TABLE users; --` se trata como un dato, no como código.
- **XSS (Cross-Site Scripting)**: al ser una API que responde JSON (no
  HTML), no renderizamos contenido en el navegador, así que la superficie
  de XSS es mínima. Además, la validación de los Form Requests limita qué
  datos entran. Si un frontend consume esta API, la responsabilidad de
  escapar el HTML al mostrarlo es de ese frontend.
- **CSRF (Cross-Site Request Forgery)**: los ataques CSRF se apoyan en las
  *cookies de sesión* que el navegador manda solas. Esta API no usa
  sesiones ni cookies: la autenticación es por token JWT en un header, que
  el navegador **no** adjunta automáticamente. Por eso una API stateless
  con JWT no es vulnerable a CSRF de la forma clásica (y por eso las rutas
  de `routes/api.php` no llevan el middleware CSRF, que sí protege los
  formularios web de `routes/web.php`).

---

## 7. HTTPS en producción

En desarrollo local (`php artisan serve`) el tráfico va por HTTP plano, lo
cual es normal. En un despliegue real, el token y los datos de la
transacción **deben** viajar sobre **HTTPS**, para que nadie pueda
interceptar el JWT en la red (si lo roban, pueden hacerse pasar por el
usuario hasta que el token expire).

Cómo se garantizaría en producción (documentado, no implementado en local):

1. **Certificado TLS** en el servidor web (por ejemplo, gratis con Let's
   Encrypt) — es lo que hace que la URL sea `https://`.
2. **Forzar HTTPS a nivel de aplicación**: en `App\Providers\AppServiceProvider`,
   dentro de `boot()`:

   ```php
   if ($this->app->environment('production')) {
       \Illuminate\Support\Facades\URL::forceScheme('https');
   }
   ```

   Esto hace que todas las URLs que genere Laravel usen `https`, y combinado
   con una redirección de HTTP→HTTPS en el servidor web (Nginx/Apache),
   asegura que ninguna petición viaje en texto plano.

---

## 8. Pruebas con Postman

La colección `postman/tienda-negocios-api.postman_collection.json` incluye:

- Carpeta **Autenticación**: register, login, me, logout. Login y register
  guardan el token automáticamente en la variable `{{jwt_token}}`.
- Carpeta **Seguridad (acceso denegado vs permitido)**: las pruebas que
  pide la consigna —
  - *Ver carrito SIN token* → **401** (acceso denegado).
  - *Ver carrito CON token* → **200** (acceso permitido).
  - *Token inválido* → **401**.
  - *Crear producto SIN token* → **401**.

Orden sugerido para probar: **Login** (guarda el token) → cualquier request
de **Carrito/Checkout** (usa el token solo) → carpeta **Seguridad** para ver
el contraste con y sin token.

## Autor

**David Mach**