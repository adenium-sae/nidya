# Nidya Backend — Arquitectura

> Sistema de gestión para puntos de venta (POS) y administración de inventario. Proyecto de código libre.

🌐 *[Read in English](ARCHITECTURE.en.md)*

## Stack Tecnológico

| Capa | Tecnología | Versión |
|------|-----------|---------|
| Framework | Laravel | 12.x |
| Lenguaje | PHP | ≥ 8.2 |
| Base de datos | PostgreSQL | — |
| Autenticación | Laravel Sanctum | 4.x |
| Testing | Pest | 4.x |
| Server (prod) | Laravel Octane | 2.x |
| i18n | Laravel Localization | `es`, `en` |

## Estructura de Directorios

```
app/
├── Actions/                    # Lógica de mutación (create, update, delete)
│   ├── Access/Auth/            # Login, Logout, OTP, Register
│   ├── Catalog/
│   │   ├── Categories/         # CRUD de categorías
│   │   └── Products/           # CRUD de productos
│   ├── Inventory/
│   │   ├── StorageLocations/   # Crear ubicaciones
│   │   └── Warehouses/         # CRUD de almacenes
│   ├── Organization/
│   │   ├── Branches/           # Crear/actualizar sucursales
│   │   └── Stores/             # Crear/actualizar tiendas
│   ├── Sales/                  # Crear/cancelar ventas
│   └── Stock/                  # Ajuste, transferencia, corrección de stock
├── Exceptions/                 # Excepciones de dominio (ClientException base)
├── Http/
│   ├── Controllers/Api/
│   │   ├── Management/         # API para panel administrativo
│   │   └── Operations/         # API para operaciones en sucursal (POS)
│   ├── Middleware/              # EnsureRoleMiddleware
│   └── Requests/               # Form Requests (validación)
├── Models/                     # 27 modelos Eloquent (UUIDs)
├── Providers/
└── Services/                   # Lógica de lectura + orquestación
    ├── Catalog/                # CategoryService, ProductService
    ├── Inventory/              # WarehouseService, StorageLocationService
    ├── Organization/           # StoreService, BranchService
    ├── Sales/                  # SaleService
    └── Stock/                  # StockService

database/
├── migrations/                 # 8 archivos de migración
└── seeders/                    # PermissionSeeder, DemoDataSeeder

routes/
└── api.php                     # Rutas agrupadas: auth, admin, operations

lang/
├── en/                         # Mensajes en inglés
└── es/                         # Mensajes en español
```

## Patrón de Arquitectura

### Flujo de una petición

```
Request → Controller → Service → Action (si hay mutación)
                          ↓
                      Resultado → Response JSON
```

### Reglas de diseño

| Capa | Responsabilidad | Puede mutar DB |
|------|----------------|:--------------:|
| **Controller** | Validación HTTP, enrutamiento, respuesta JSON | ❌ |
| **Service** | Consultas, filtros, orquestación | ❌ |
| **Action** | Mutaciones (create, update, delete) | ✅ |
| **Model** | Relaciones, casts, scopes, helpers de dominio | Solo en helpers propios |

### Controllers

Los controllers **solo inyectan Services**, nunca Actions directamente. Cada controller es responsable de:

1. Recibir y validar la petición (vía Form Requests o `$request->validate()`)
2. Delegar al Service correspondiente
3. Retornar la respuesta JSON con el formato adecuado

```php
// ✅ Correcto
class SaleController extends Controller
{
    public function __construct(protected SaleService $saleService) {}

    public function store(StoreSaleRequest $request): JsonResponse
    {
        $sale = $this->saleService->create($request->validated(), Auth::user()->id);
        return response()->json(['data' => $sale], 201);
    }
}
```

### Services

Los Services contienen la **lógica de lectura** (queries con filtros, eager loading) y delegan las mutaciones a Actions:

```php
class SaleService
{
    public function __construct(
        protected CreateSaleAction $createSaleAction,
        protected CancelSaleAction $cancelSaleAction,
    ) {}

    // Lectura directa
    public function list(array $filters, int $perPage) { /* query */ }

    // Mutación delegada a Action
    public function create(array $data, int $userId): Sale
    {
        return ($this->createSaleAction)($data, $userId);
    }
}
```

### Actions

Las Actions son clases invocables (`__invoke`) que encapsulan una única operación de escritura. Pueden usar `DB::transaction()` para operaciones complejas:

```php
class CreateSaleAction
{
    public function __invoke(array $data, int $userId): Sale
    {
        return DB::transaction(function () use ($data, $userId) {
            // Crear venta, items, descontar stock, registrar movimientos...
        });
    }
}
```

## Modelo de Datos

Todos los modelos usan **UUIDs** como primary key (`HasUuids` trait).

### Diagrama de Entidades

```
┌─────────────┐    ┌──────────────┐    ┌──────────────┐
│    User      │    │   Profile    │    │   Permission │
│─────────────│    │──────────────│    │──────────────│
│ id (uuid)   │◄──┤ user_id      │    │ id (uuid)    │
│ email       │    │ first_name   │    │ key          │
│ password    │    │ last_name    │    │ name         │
│ otp_code    │    │ phone        │    │ module       │
│ is_active   │    │ type (admin) │    └──────┬───────┘
└──────┬──────┘    └──────────────┘           │
       │                                      │ role_permissions
       │                              ┌───────┴───────┐
       │                              │     Role      │
       │                              │───────────────│
       │                              │ id (uuid)     │
       │                              │ key           │
       │                              │ name          │
       │                              │ is_system     │
       │                              └───────────────┘
       │
       │ user_id
┌──────┴──────┐    ┌──────────────┐    ┌──────────────┐
│    Sale     │◄──┤  SaleItem    │    │ SalePayment  │
│─────────────│    │──────────────│    │──────────────│
│ folio       │    │ product_id   │    │ sale_id      │
│ store_id    │    │ quantity     │    │ method       │
│ branch_id   │    │ unit_price   │    │ amount       │
│ warehouse_id│    │ subtotal     │    └──────────────┘
│ customer_id │    │ tax          │
│ total       │    └──────────────┘
│ status      │
└─────────────┘

┌──────────────┐    ┌──────────────┐    ┌─────────────────┐
│    Store     │◄──┤   Branch     │◄──┤   Warehouse     │
│──────────────│    │──────────────│    │─────────────────│
│ name         │    │ store_id     │    │ store_id        │
│ slug         │    │ address_id   │    │ branch_id (opt) │
│ primary_color│    │ code         │    │ code            │
│ is_active    │    │ allow_sales  │    │ type            │
└──────────────┘    └──────────────┘    └────────┬────────┘
                                                 │
                                        ┌────────┴────────────┐
                                        │  StorageLocation    │
                                        │─────────────────────│
                                        │ warehouse_id        │
                                        │ code, name, type    │
                                        │ aisle, section      │
                                        └─────────────────────┘

┌──────────────┐    ┌──────────────┐    ┌──────────────────┐
│  Category    │◄──┤   Product    │◄──┤   StoreProduct   │
│──────────────│    │──────────────│    │──────────────────│
│ name         │    │ category_id  │    │ store_id         │
│ slug         │    │ name, sku    │    │ product_id       │
│ parent_id    │    │ barcode      │    │ price, currency  │
│ is_active    │    │ type         │    │ is_active        │
└──────────────┘    │ cost         │    └──────────────────┘
                    │ track_inv    │
                    │ min_stock    │    ┌──────────────────┐
                    │ is_active    │◄──┤     Stock        │
                    └──────────────┘    │──────────────────│
                                       │ product_id       │
                                       │ warehouse_id     │
                                       │ storage_loc_id   │
                                       │ quantity         │
                                       │ reserved         │
                                       │ avg_cost         │
                                       └────────┬─────────┘
                                                │
                              ┌─────────────────┼────────────────┐
                              │                 │                │
                    ┌─────────┴──────┐ ┌────────┴───────┐ ┌─────┴───────────┐
                    │ StockMovement  │ │ StockAdjustment│ │ StockTransfer   │
                    │────────────────│ │────────────────│ │─────────────────│
                    │ type           │ │ type (entry/   │ │ source_wh_id    │
                    │ quantity       │ │   exit)        │ │ dest_wh_id      │
                    │ qty_before     │ │ reason         │ │ status          │
                    │ qty_after      │ │ user_id        │ │ items[]         │
                    │ reason         │ │ items[]        │ └─────────────────┘
                    │ user_id        │ └────────────────┘
                    └────────────────┘
```

### Modelos adicionales

| Modelo | Descripción |
|--------|------------|
| `Address` | Dirección física (calle, colonia, ciudad, CP) |
| `Customer` | Clientes con nombre, email, teléfono |
| `CashRegister` | Cajas registradoras por sucursal |
| `CashRegisterSession` | Sesiones de apertura/cierre de caja |
| `CashMovement` | Movimientos dentro de una sesión de caja |
| `ProductAttribute` | Atributos clave-valor de un producto |
| `ProductImage` | Imágenes de producto |
| `StoreLocation` | Ubicaciones dentro de una tienda |

## API REST

### Autenticación (`/api/auth`)

| Método | Ruta | Descripción |
|--------|------|-------------|
| `POST` | `/auth/signup` | Registro de usuario + tienda + sucursal + almacén |
| `POST` | `/auth/signin` | Login con email y password → token Sanctum |
| `POST` | `/auth/signin/otp` | Login con código OTP |
| `POST` | `/auth/signin/otp/generate` | Generar código OTP |
| `POST` | `/auth/signout` | 🔒 Cerrar sesión |

### Panel Administrativo (`/api/admin`) — 🔒 `auth:sanctum`

#### Catálogo
| Método | Ruta | Controller |
|--------|------|-----------|
| `GET` | `/admin/categories` | `CategoryController@index` |
| `POST` | `/admin/categories` | `CategoryController@store` |
| `GET` | `/admin/categories/{id}` | `CategoryController@show` |
| `PUT` | `/admin/categories/{id}` | `CategoryController@update` |
| `DELETE` | `/admin/categories/{id}` | `CategoryController@destroy` |
| `GET` | `/admin/products` | `ProductController@index` |
| `GET` | `/admin/products/{id}` | `ProductController@show` |
| `PUT` | `/admin/products/{id}` | `ProductController@update` |
| `DELETE` | `/admin/products/{id}` | `ProductController@destroy` |
| `POST` | `/admin/products/single` | `ProductController@storeSingle` |
| `POST` | `/admin/products/multiple` | `ProductController@storeMultiple` |
| `POST` | `/admin/products/all` | `ProductController@storeAll` |

#### Organización
| Método | Ruta | Controller |
|--------|------|-----------|
| `GET` | `/admin/stores` | `StoresController@index` |
| `POST` | `/admin/stores` | `StoresController@store` |
| `GET` | `/admin/stores/{id}` | `StoresController@show` |
| `PUT` | `/admin/stores/{id}` | `StoresController@update` |
| `GET` | `/admin/branches` | `BranchesController@index` |
| `GET` | `/admin/branches/{id}` | `BranchesController@show` |
| `PUT` | `/admin/branches/{id}` | `BranchesController@update` |

#### Inventario
| Método | Ruta | Controller |
|--------|------|-----------|
| `GET` | `/admin/warehouses` | `WarehousesController@index` |
| `POST` | `/admin/warehouses` | `WarehousesController@store` |
| `GET` | `/admin/warehouses/types` | `WarehousesController@getTypes` |
| `GET` | `/admin/warehouses/{id}` | `WarehousesController@show` |
| `PUT` | `/admin/warehouses/{id}` | `WarehousesController@update` |
| `DELETE` | `/admin/warehouses/{id}` | `WarehousesController@destroy` |
| `GET` | `/admin/inventory/locations` | `StorageLocationController@index` |
| `POST` | `/admin/inventory/locations` | `StorageLocationController@store` |
| `GET` | `/admin/inventory/stock` | `StockController@index` |
| `PATCH` | `/admin/inventory/stock/{id}/quantity` | `StockController@updateQuantity` |
| `POST` | `/admin/inventory/stock/adjust` | `StockController@adjust` |
| `POST` | `/admin/inventory/stock/transfer` | `StockController@transfer` |
| `GET` | `/admin/inventory/stock/movements` | `StockController@movements` |
| `GET` | `/admin/inventory/stock/adjustments` | `StockController@adjustments` |

#### Ventas y Dashboard
| Método | Ruta | Controller |
|--------|------|-----------|
| `GET` | `/admin/dashboard` | `DashboardController@index` |
| `GET` | `/admin/sales` | `SaleController@index` |
| `POST` | `/admin/sales` | `SaleController@store` |
| `GET` | `/admin/sales/{sale}` | `SaleController@show` |
| `POST` | `/admin/sales/{sale}/cancel` | `SaleController@cancel` |

## Autenticación y Seguridad

### Flujo de autenticación

```
1. POST /auth/signup
   → RegisterUserAction: crea User + Profile + Store + Branch + Warehouse
   → CompletesLogin: genera token Sanctum
   → Respuesta: { user, token }

2. POST /auth/signin
   → LoginAction: valida credenciales
   → CompletesLogin: genera token
   → Respuesta: { user, token }

3. Peticiones autenticadas
   → Header: Authorization: Bearer {token}
   → Middleware: auth:sanctum

4. POST /auth/signout
   → LogoutAction: elimina tokens del usuario
```

### Login con OTP

1. `POST /auth/signin/otp/generate` — genera código de 6 dígitos, válido 2 minutos
2. `POST /auth/signin/otp` — valida el OTP y genera token

### Middleware

| Alias | Clase | Descripción |
|-------|-------|-------------|
| `auth:sanctum` | Sanctum built-in | Verifica token Bearer |
| `profile.type` | `EnsureRoleMiddleware` | Verifica rol del usuario (TODO: implementar lógica) |

## Manejo de Errores

Todas las excepciones de dominio extienden `ClientException`:

```php
class ClientException extends Exception
{
    public string $keyCode;
    public int $status;
}
```

El handler global en `bootstrap/app.php` las convierte automáticamente a JSON:

```json
{
    "status": false,
    "error": {
        "code": "PRODUCT_NOT_FOUND",
        "message": "Producto no encontrado"
    }
}
```

### Catálogo de excepciones

| Excepción | Código | HTTP |
|-----------|--------|------|
| `CategoryNotFoundException` | `CATEGORY_NOT_FOUND` | 404 |
| `ProductNotFoundException` | `PRODUCT_NOT_FOUND` | 404 |
| `ProductNotAvailableException` | `PRODUCT_NOT_AVAILABLE` | 422 |
| `ProductHasStockException` | `PRODUCT_HAS_STOCK` | 422 |
| `WarehouseNotFoundException` | `WAREHOUSE_NOT_FOUND` | 404 |
| `BranchNotFoundException` | `BRANCH_NOT_FOUND` | 404 |
| `StoreNotFoundException` | `STORE_NOT_FOUND` | 404 |
| `InsufficientStockException` | `INSUFFICIENT_STOCK` | 422 |
| `SaleCancellationException` | `SALE_CANCELLATION_ERROR` | 422 |
| `InvalidCredentialsException` | `INVALID_CREDENTIALS` | 401 |
| `UserInactiveException` | `USER_INACTIVE` | 403 |
| `OtpExpiredException` | `OTP_EXPIRED` | 422 |
| `OtpInvalidException` | `OTP_INVALID` | 422 |

## Permisos

El sistema usa un modelo de permisos basado en **roles asignables** con **permisos granulares**. Los permisos están organizados por módulo:

| Módulo | Permisos |
|--------|----------|
| `dashboard` | `view` |
| `products` | `view`, `create`, `edit`, `delete` |
| `inventory` | `view`, `adjust`, `transfer`, `receive` |
| `sales` | `view`, `create`, `cancel`, `refund` |
| `cash` | `view`, `open`, `close`, `withdraw`, `deposit` |
| `customers` | `view`, `create`, `edit`, `delete` |
| `reports` | `sales`, `inventory`, `cash`, `products` |
| `settings` | `view`, `edit`, `users`, `roles`, `stores`, `branches` |

### Roles predefinidos (Seeder)

| Rol | Tipo | Permisos |
|-----|------|----------|
| **Administrador** | Sistema | Todos |
| **Gerente de Sucursal** | Sistema | Dashboard, productos (ver), inventario, ventas, caja, clientes, reportes |
| **Vendedor** | Sistema | Dashboard, productos (ver), ventas, caja, clientes (ver/crear) |

## Internacionalización (i18n)

El sistema soporta `es` (español) y `en` (inglés). Los mensajes se definen en:

```
lang/
├── en/
│   ├── exceptions.php    # Mensajes de error
│   ├── messages.php      # Mensajes de éxito
│   └── validation.php    # Mensajes de validación
└── es/
    ├── exceptions.php
    ├── messages.php
    └── validation.php
```

Uso en código:
```php
return response()->json([
    'message' => __('messages.product_created_successfully')
]);
```

## Setup local

```bash
# 1. Clonar e instalar dependencias
git clone <repo>
cd nidya-backend
composer install
npm install

# 2. Configurar entorno
cp .env.example .env
php artisan key:generate
# Configurar DB_* en .env (PostgreSQL)

# 3. Base de datos
php artisan migrate:fresh --seed

# 4. Iniciar servidor de desarrollo
composer run dev
# Esto ejecuta php artisan serve + npm run dev (Vite) en paralelo
```

### Seeders

| Seeder | Descripción |
|--------|------------|
| `PermissionSeeder` | Crea los 31 permisos del sistema |
| `DemoDataSeeder` | Crea usuario admin, roles, tienda, sucursales, almacenes, categorías, productos con stock |

## Convenciones

- **IDs**: UUID v7 en todos los modelos (`HasUuids`)
- **Soft Deletes**: en `User`, `Product`, `Store`
- **Timestamps**: automáticos en todos los modelos
- **Respuestas API**: formato consistente `{ status, message, data }` o `{ error: { code, message } }`
- **Validación**: mediante Form Requests en `app/Http/Requests/`
- **Nomenclatura de Actions**: `VerbNounAction` (ej: `CreateProductAction`, `CancelSaleAction`)
- **Nomenclatura de Services**: `DomainService` (ej: `ProductService`, `StockService`)
