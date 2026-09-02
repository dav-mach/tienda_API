# Tienda de Negocios — API REST (Entrega 3)

API REST de una tienda: Productos, Categorías, Carrito y Checkout. Es
**solo API**: no hay vistas, todo lo que el proyecto devuelve es JSON.
Pensada para ser consumida por Postman, una app o cualquier frontend
separado.

## Índice

1. [Instalación](#1-instalación)
2. [Ver la base de datos en phpMyAdmin](#2-ver-la-base-de-datos-en-phpmyadmin)
3. [Principios REST aplicados](#3-principios-rest-aplicados)
4. [Formato de respuesta](#4-formato-de-respuesta)
5. [Cómo se identifica el carrito](#5-cómo-se-identifica-el-carrito)
6. [Endpoints](#6-endpoints)
7. [Resumen de compra](#7-resumen-de-compra)
8. [DTOs](#8-dtos-data-transfer-objects)
9. [Manejo de inventario](#9-manejo-de-inventario)
10. [Colección de Postman](#10-colección-de-postman)
11. [Estructura de carpetas](#11-estructura-de-carpetas)

---

## 1. Instalación

Requisitos: PHP 8.3+, Composer, [XAMPP](https://www.apachefriends.org/)
(para MySQL), Postman (para probar los endpoints).

### 1.1. Levantar MySQL desde XAMPP

1. Abrí el **Panel de control de XAMPP**.
2. Iniciá el módulo **MySQL** (botón *Start*). No hace falta iniciar
   Apache: la API se sirve con `php artisan serve`, no con Apache/XAMPP.
3. Entrá a **phpMyAdmin**: botón *Admin* al lado de MySQL, o directo
   `http://localhost/phpmyadmin`.
4. Creá la base de datos: pestaña **Bases de datos** → nombre
   `tienda_api` → cotejamiento `utf8mb4_unicode_ci` → **Crear**.
   (Todavía va a aparecer vacía, sin tablas — eso lo resuelve
   `php artisan migrate` en el paso siguiente.)

### 1.2. Clonar y configurar el proyecto

```powershell
git clone <URL-de-este-repo> tienda-api
cd tienda-api

composer install
copy .env.example .env
php artisan key:generate
```

El `.env.example` ya viene apuntando a `tienda_api` por MySQL en
`127.0.0.1:3306`, usuario `root` sin contraseña — la configuración por
defecto de XAMPP. Si tu XAMPP usa otro usuario/contraseña, o la base la
llamaste distinto, editá estas líneas de tu `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tienda_api
DB_USERNAME=root
DB_PASSWORD=
```

### 1.3. Crear las tablas y levantar el servidor

```powershell
php artisan migrate
php artisan db:seed

php artisan serve
```

La API queda en `http://127.0.0.1:8000/api/v1`. Importá la colección de
Postman (sección 10) y empezá probando `GET /api/v1/productos`.

> **Si `php artisan migrate` tira "could not find driver"**: el PHP que
> estás corriendo desde la terminal no tiene la extensión `pdo_mysql`
> habilitada. Usá el PHP que trae XAMPP (`C:\xampp\php\php.exe`, o
> agregalo a tu PATH) o habilitá `extension=pdo_mysql` en tu `php.ini`.

### Alternativa sin XAMPP: SQLite (cero configuración)

Si no querés instalar XAMPP, la API funciona igual con SQLite:

```powershell
copy .env.example .env
```

y en el `.env`, reemplazá las líneas de `DB_*` por una sola:

```
DB_CONNECTION=sqlite
```

```powershell
New-Item -ItemType File -Path database\database.sqlite -Force
php artisan migrate
php artisan db:seed
php artisan serve
```

(En macOS/Linux/Git Bash: `cp` en vez de `copy`, `touch database/database.sqlite` en vez del `New-Item`.)

---

## 2. Ver la base de datos en phpMyAdmin

Con MySQL/XAMPP levantado y las migraciones ya corridas:

1. `http://localhost/phpmyadmin`.
2. Panel izquierdo → hacé clic en **`tienda_api`**.
3. Vas a ver las tablas del proyecto: `categorias`, `productos`, `users`,
   `carritos`, `carrito_items`, `pedidos`, `pedido_items` (más `cache`,
   `jobs`, `sessions`, que son internas de Laravel).
4. Hacé clic en cualquier tabla → pestaña **Examinar** para ver sus
   filas. `categorias` y `productos` van a tener los datos del seeder;
   `carritos`/`pedidos` se van a ir llenando a medida que uses la API
   (por ejemplo, desde Postman).
5. Pestaña **SQL** si en algún momento querés correr una consulta
   manual para revisar algo puntual.

---

## 3. Principios REST aplicados

| Principio | Cómo se aplica acá |
|---|---|
| **Cliente-servidor** | La API no sabe nada de quién la consume (Postman, una app, un frontend); solo expone datos y acciones por HTTP. |
| **Comunicación por HTTP, con verbos** | **GET** para leer, **POST** para crear, **PUT** para actualizar, **DELETE** para borrar — sobre URLs que nombran recursos (`/productos`, `/carrito/items`), nunca acciones (nunca `/obtenerProductos`). |
| **Sin estado en el servidor** | Cada petición se identifica sola (el carrito viaja con un token, no con una sesión atada a un proceso); el estado real vive en la base de datos. |
| **Respuestas uniformes** | Toda respuesta es JSON, con la misma forma, sin importar el endpoint (sección 4). |
| **Códigos de estado con significado** | 200/201 éxito, 404 no existe, 409 conflicto (stock, pedido ya confirmado), 422 datos inválidos — el cliente puede reaccionar al código sin "leer" el texto del mensaje. |

---

## 4. Formato de respuesta

**Toda respuesta exitosa** tiene siempre las mismas 3 claves, aunque
`message` a veces valga `null` (ej. un GET no manda mensaje) y `data` a
veces valga `null` (ej. un DELETE no tiene nada que mostrar):

```json
{
  "success": true,
  "message": "Producto creado.",
  "data": { "...": "..." }
}
```

**Toda respuesta de error** tiene la misma forma:

```json
{
  "success": false,
  "message": "Los datos enviados no son validos.",
  "errors": { "nombre": ["El nombre del producto es obligatorio."] }
}
```

| Código | Cuándo |
|---|---|
| `200` | Lectura/actualización OK |
| `201` | Se creó un recurso |
| `404` | El recurso no existe |
| `409` | Conflicto con el estado actual (sin stock, pedido ya confirmado) |
| `422` | Datos inválidos, o una regla de negocio no se cumple (carrito vacío) |
| `500` | Error inesperado |

Toda esta lógica vive en un solo lugar: los 3 métodos del controlador
base (`app/Http/Controllers/Controller.php`) arman siempre la misma
forma de respuesta exitosa, y `bootstrap/app.php` centraliza cómo cada
tipo de error se convierte en JSON.

---

## 5. Cómo se identifica el carrito

El carrito se persiste en la base de datos (tabla `carritos`),
identificado por un **token**, no por sesión de PHP — así un cliente sin
cookies (Postman, una app) también puede mantener su carrito entre
peticiones.

1. Mandás el header `X-Cart-Token: <token>` con el token que ya tenías.
2. Si no lo mandás (primera vez), se crea un carrito nuevo.
3. Toda respuesta de `/carrito` o `/checkout` devuelve el header
   `X-Cart-Token` con el token vigente — guardalo para la próxima
   petición.

Lo resuelve `App\Http\Middleware\IdentificarCarrito`, aplicado a todas
las rutas de carrito y checkout.

---

## 6. Endpoints

### Productos — CRUD completo

| Verbo | Ruta | Body | Descripción |
|---|---|---|---|
| GET | `/api/v1/productos` | — | Listado paginado. Filtros: `?categoria_id=`, `?por_pagina=`. |
| GET | `/api/v1/productos/{id}` | — | Detalle de un producto. |
| POST | `/api/v1/productos` | `nombre, precio, stock, categoria_id` | Crea un producto. |
| PUT | `/api/v1/productos/{id}` | `nombre, precio, stock, categoria_id` | Actualiza. |
| DELETE | `/api/v1/productos/{id}` | — | Elimina. |

### Categorías — CRUD completo

| Verbo | Ruta | Body | Descripción |
|---|---|---|---|
| GET | `/api/v1/categorias` | — | Listado, con `cantidad_productos`. |
| GET | `/api/v1/categorias/{id}` | — | Detalle. |
| POST | `/api/v1/categorias` | `nombre, descripcion?` | Crea. |
| PUT | `/api/v1/categorias/{id}` | `nombre, descripcion?` | Actualiza. |
| DELETE | `/api/v1/categorias/{id}` | — | Elimina (409 si tiene productos asociados). |

### Carrito (todas requieren el header `X-Cart-Token`)

| Verbo | Ruta | Body | Descripción |
|---|---|---|---|
| GET | `/api/v1/carrito` | — | Ver el carrito actual con sus items. |
| GET | `/api/v1/carrito/resumen` | — | Subtotal, impuestos, envío y total (sección 7). |
| POST | `/api/v1/carrito/items` | `producto_id, cantidad` | Agrega un producto (si ya estaba, suma la cantidad). |
| PUT | `/api/v1/carrito/items/{item}` | `cantidad` | Cambia la cantidad de una línea. |
| DELETE | `/api/v1/carrito/items/{item}` | — | Quita un producto del carrito. |
| DELETE | `/api/v1/carrito` | — | Vacía el carrito completo. |

### Checkout — flujo de 3 pasos

| Paso | Verbo | Ruta | Body | Descripción |
|---|---|---|---|---|
| 1. Revisar carrito | GET | `/api/v1/carrito/resumen` | — | Ver qué se va a pagar antes de mandar los datos. |
| 2. Registrar datos | POST | `/api/v1/checkout` | `nombre_cliente, email, direccion_envio, ciudad, codigo_postal, metodo_pago` | Crea un **Pedido** en estado `pendiente_confirmacion`, con una foto del carrito. Todavía NO descuenta stock. |
| 3. Confirmar | POST | `/api/v1/checkout/{pedido}/confirmar` | — | Revalida stock, lo descuenta, marca el pedido `confirmado` y vacía el carrito. |
| — | GET | `/api/v1/checkout/{pedido}` | — | Consultar el estado de un pedido. |

---

## 7. Resumen de compra

`GET /api/v1/carrito/resumen` devuelve:

```json
{
  "success": true,
  "message": null,
  "data": {
    "cantidad_items": 3,
    "subtotal": 1730000.00,
    "impuestos": 363300.00,
    "costo_envio": 0,
    "total": 2093300.00
  }
}
```

Reglas (`App\Services\CarritoService::calcularResumen()`):
- `impuestos` = 21% del subtotal.
- `costo_envio` = $5.000 fijo, **gratis** si el subtotal supera $100.000.
- `total` = subtotal + impuestos + costo_envio.

---

## 8. DTOs (Data Transfer Objects)

Los DTOs (`app/DTOs/`) son clases simples que estructuran los datos de
entrada/salida en los flujos más relevantes (carrito, checkout), sin
depender de `Request` ni de Eloquent:

| DTO | Uso |
|---|---|
| `AgregarAlCarritoDTO` | Entrada de `POST /carrito/items`. |
| `ActualizarCantidadDTO` | Entrada de `PUT /carrito/items/{item}`. |
| `DatosCheckoutDTO` | Entrada de `POST /checkout`. |
| `ResumenCompraDTO` | Salida de `GET /carrito/resumen`. |
| `PedidoConfirmadoDTO` | Salida de `POST /checkout/{pedido}/confirmar`. |

Para Producto y Categoría se usan **API Resources** de Laravel
(`app/Http/Resources/`) en vez de DTOs, porque son serializaciones 1 a 1
de un modelo Eloquent — que es para lo que están pensadas. Los DTOs se
reservan para datos que no son "un modelo": un resumen calculado, o los
datos de checkout que todavía no son un Pedido guardado.

---

## 9. Manejo de inventario

El stock se valida con `Producto::hayStockDisponible()` en dos momentos:

1. **Al agregar/actualizar el carrito** (`CarritoService`).
2. **Al confirmar el checkout** (`CheckoutService::confirmar()`): se
   revalida (pudo cambiar desde que se agregó al carrito) dentro de una
   transacción — si algún producto no tiene stock, no se descuenta nada
   de ningún producto del pedido.

Si no alcanza, se lanza `StockInsuficienteException` (409). Ver
`bootstrap/app.php` para cómo esa excepción se convierte en respuesta
JSON.

---

## 10. Colección de Postman

Archivo: [`postman/tienda-negocios-api.postman_collection.json`](postman/tienda-negocios-api.postman_collection.json).

1. Postman → **Import** → elegir el archivo.
2. La variable de colección `base_url` ya viene en
   `http://127.0.0.1:8000/api/v1`.
3. Correr las carpetas en orden: **Productos/Categorías** (para tener
   datos) → **Carrito** → **Checkout**. Las de Carrito/Checkout tienen
   scripts que guardan `cart_token`, `item_id` y `pedido_id` solos, así
   que ejecutadas en orden no hace falta copiar nada a mano.
4. La carpeta **Casos de error** prueba a propósito un 422, un 404 y un
   409.

---

## 11. Estructura de carpetas

```
tienda-api/
├── app/
│   ├── DTOs/
│   ├── Exceptions/
│   ├── Services/              # lógica de negocio (carrito, checkout)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Controller.php     # controlador base: métodos de respuesta JSON
│   │   │   └── Api/V1/            # todos los controladores de la API viven acá
│   │   ├── Middleware/IdentificarCarrito.php
│   │   ├── Requests/           # Form Requests (validación)
│   │   └── Resources/          # serializan los modelos a JSON
│   ├── Models/
│   └── Rules/PrecioConDosDecimales.php
├── database/
│   ├── migrations/
│   └── seeders/DatabaseSeeder.php
├── routes/api.php
└── postman/tienda-negocios-api.postman_collection.json
```

**Por qué `Controllers/Api/V1/`**: versionar la API desde el arranque
(`/api/v1/...`) deja preparado el terreno para el día que haga falta un
cambio que rompa compatibilidad (por ejemplo, cambiar la forma de un
campo en la respuesta) — se agregaría un `Api/V2/` nuevo sin tocar ni
romper nada de lo que ya esté consumiendo `v1`.

**Buenas prácticas de Git**: un commit por sesión de trabajo, mensajes
descriptivos (`feat: ...`, `fix: ...`, `docs: ...`). `vendor/`, `.env` y
`database/database.sqlite` quedan fuera del control de versiones.

## Autor

**David Mach**