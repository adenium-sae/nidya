# Nidya Backend — Architecture

> Point of sale (POS) and inventory management system for grocery stores. Open source project.

🌐 *[Leer en español](ARCHITECTURE.md)*

## Technology Stack

| Layer | Technology | Version |
|-------|-----------|---------|
| Framework | Laravel | 12.x |
| Language | PHP | ≥ 8.2 |
| Database | PostgreSQL | — |
| Authentication | Laravel Sanctum | 4.x |
| Testing | Pest | 4.x |
| Server (prod) | Laravel Octane | 2.x |
| i18n | Laravel Localization | `es`, `en` |

## Directory Structure

```
app/
├── Actions/                    # Mutation logic (create, update, delete)
│   ├── Access/Auth/            # Login, Logout, OTP, Register
│   ├── Catalog/
│   │   ├── Categories/         # Category CRUD
│   │   └── Products/           # Product CRUD
│   ├── Inventory/
│   │   ├── StorageLocations/   # Create locations
│   │   └── Warehouses/         # Warehouse CRUD
│   ├── Organization/
│   │   ├── Branches/           # Create/update branches
│   │   └── Stores/             # Create/update stores
│   ├── Sales/                  # Create/cancel sales
│   └── Stock/                  # Stock adjustment, transfer, correction
├── Exceptions/                 # Domain exceptions (ClientException base)
├── Http/
│   ├── Controllers/Api/
│   │   ├── Management/         # API for admin panel
│   │   └── Operations/         # API for branch operations (POS)
│   ├── Middleware/              # EnsureRoleMiddleware
│   └── Requests/               # Form Requests (validation)
├── Models/                     # 27 Eloquent models (UUIDs)
├── Providers/
└── Services/                   # Read logic + orchestration
    ├── Catalog/                # CategoryService, ProductService
    ├── Inventory/              # WarehouseService, StorageLocationService
    ├── Organization/           # StoreService, BranchService
    ├── Sales/                  # SaleService
    └── Stock/                  # StockService

database/
├── migrations/                 # 8 migration files
└── seeders/                    # PermissionSeeder, DemoDataSeeder

routes/
└── api.php                     # Grouped routes: auth, admin, operations

lang/
├── en/                         # English messages
└── es/                         # Spanish messages
```

## Architecture Pattern

### Request Flow

```
Request → Controller → Service → Action (if mutation)
                          ↓
                      Result → JSON Response
```

### Design Rules

| Layer | Responsibility | Can Mutate DB |
|-------|---------------|:-------------:|
| **Controller** | HTTP validation, routing, JSON response | ❌ |
| **Service** | Queries, filters, orchestration | ❌ |
| **Action** | Mutations (create, update, delete) | ✅ |
| **Model** | Relationships, casts, scopes, domain helpers | Only in own helpers |

### Controllers

Controllers **only inject Services**, never Actions directly. Each controller is responsible for:

1. Receiving and validating the request (via Form Requests or `$request->validate()`)
2. Delegating to the corresponding Service
3. Returning the JSON response in the appropriate format

```php
// ✅ Correct
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

Services contain **read logic** (queries with filters, eager loading) and delegate mutations to Actions:

```php
class SaleService
{
    public function __construct(
        protected CreateSaleAction $createSaleAction,
        protected CancelSaleAction $cancelSaleAction,
    ) {}

    // Direct read
    public function list(array $filters, int $perPage) { /* query */ }

    // Mutation delegated to Action
    public function create(array $data, int $userId): Sale
    {
        return ($this->createSaleAction)($data, $userId);
    }
}
```

### Actions

Actions are invocable classes (`__invoke`) that encapsulate a single write operation. They can use `DB::transaction()` for complex operations:

```php
class CreateSaleAction
{
    public function __invoke(array $data, int $userId): Sale
    {
        return DB::transaction(function () use ($data, $userId) {
            // Create sale, items, deduct stock, record movements...
        });
    }
}
```

## Data Model

All models use **UUIDs** as primary keys (`HasUuids` trait).

### Entity Diagram

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
│    Store     │◄──►│   Branch     │◄──┤   Warehouse     │
│──────────────│ N:M│──────────────│ 1:1│─────────────────│
│ name         │    │ address_id   │    │ code            │
│ slug         │    │ code         │    │ type            │
│ primary_color│    │ allow_sales  │    │                 │
│ is_active    │    │ is_active    │    │                 │
└──────────────┘    └──────────────┘    └────────┬────────┘
       ▲                                         │
       └─────────────────────────────────────────┘
                      N:M (pivot)
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

### Additional Models

| Model | Description |
|-------|------------|
| `Address` | Physical address (street, neighborhood, city, zip) |
| `Customer` | Customers with name, email, phone |
| `CashRegister` | Cash registers per branch |
| `CashRegisterSession` | Cash register open/close sessions |
| `CashMovement` | Movements within a cash session |
| `ProductAttribute` | Key-value product attributes |
| `ProductImage` | Product images |
| `StoreLocation` | Locations within a store |

## REST API

### Authentication (`/api/auth`)

| Method | Route | Description |
|--------|------|-------------|
| `POST` | `/auth/signup` | Register user + store + branch + warehouse |
| `POST` | `/auth/signin` | Login with email and password → Sanctum token |
| `POST` | `/auth/signin/otp` | Login with OTP code |
| `POST` | `/auth/signin/otp/generate` | Generate OTP code |
| `POST` | `/auth/signout` | 🔒 Sign out |

### Admin Panel (`/api/admin`) — 🔒 `auth:sanctum`

#### Catalog
| Method | Route | Controller |
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

#### Organization
| Method | Route | Controller |
|--------|------|-----------|
| `GET` | `/admin/stores` | `StoresController@index` |
| `POST` | `/admin/stores` | `StoresController@store` |
| `GET` | `/admin/stores/{id}` | `StoresController@show` |
| `PUT` | `/admin/stores/{id}` | `StoresController@update` |
| `GET` | `/admin/branches` | `BranchesController@index` |
| `GET` | `/admin/branches/{id}` | `BranchesController@show` |
| `PUT` | `/admin/branches/{id}` | `BranchesController@update` |

#### Inventory
| Method | Route | Controller |
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

#### Sales and Dashboard
| Method | Route | Controller |
|--------|------|-----------|
| `GET` | `/admin/dashboard` | `DashboardController@index` |
| `GET` | `/admin/sales` | `SaleController@index` |
| `POST` | `/admin/sales` | `SaleController@store` |
| `GET` | `/admin/sales/{sale}` | `SaleController@show` |
| `POST` | `/admin/sales/{sale}/cancel` | `SaleController@cancel` |

## Authentication & Security

### Authentication Flow

```
1. POST /auth/signup
   → RegisterUserAction: creates User + Profile + Store + Branch + Warehouse
   → CompletesLogin: generates Sanctum token
   → Response: { user, token }

2. POST /auth/signin
   → LoginAction: validates credentials
   → CompletesLogin: generates token
   → Response: { user, token }

3. Authenticated requests
   → Header: Authorization: Bearer {token}
   → Middleware: auth:sanctum

4. POST /auth/signout
   → LogoutAction: deletes user tokens
```

### OTP Login

1. `POST /auth/signin/otp/generate` — generates 6-digit code, valid for 2 minutes
2. `POST /auth/signin/otp` — validates OTP and generates token

### Middleware

| Alias | Class | Description |
|-------|-------|-------------|
| `auth:sanctum` | Sanctum built-in | Verifies Bearer token |
| `profile.type` | `EnsureRoleMiddleware` | Verifies user role (TODO: implement logic) |

## Error Handling

All domain exceptions extend `ClientException`:

```php
class ClientException extends Exception
{
    public string $keyCode;
    public int $status;
}
```

The global handler in `bootstrap/app.php` automatically converts them to JSON:

```json
{
    "status": false,
    "error": {
        "code": "PRODUCT_NOT_FOUND",
        "message": "Product not found"
    }
}
```

### Exception Catalog

| Exception | Code | HTTP |
|-----------|------|------|
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

## Permissions

The system uses a role-based permission model with **assignable roles** and **granular permissions**. Permissions are organized by module:

| Module | Permissions |
|--------|------------|
| `dashboard` | `view` |
| `products` | `view`, `create`, `edit`, `delete` |
| `inventory` | `view`, `adjust`, `transfer`, `receive` |
| `sales` | `view`, `create`, `cancel`, `refund` |
| `cash` | `view`, `open`, `close`, `withdraw`, `deposit` |
| `customers` | `view`, `create`, `edit`, `delete` |
| `reports` | `sales`, `inventory`, `cash`, `products` |
| `settings` | `view`, `edit`, `users`, `roles`, `stores`, `branches` |

### Default Roles (Seeder)

| Role | Type | Permissions |
|------|------|------------|
| **Administrator** | System | All |
| **Branch Manager** | System | Dashboard, products (view), inventory, sales, cash, customers, reports |
| **Seller** | System | Dashboard, products (view), sales, cash, customers (view/create) |

## Internationalization (i18n)

The system supports `es` (Spanish) and `en` (English). Messages are defined in:

```
lang/
├── en/
│   ├── exceptions.php    # Error messages
│   ├── messages.php      # Success messages
│   └── validation.php    # Validation messages
└── es/
    ├── exceptions.php
    ├── messages.php
    └── validation.php
```

Usage in code:
```php
return response()->json([
    'message' => __('messages.product_created_successfully')
]);
```

## Local Setup

```bash
# 1. Clone and install dependencies
git clone <repo>
cd nidya-backend
composer install
npm install

# 2. Configure environment
cp .env.example .env
php artisan key:generate
# Configure DB_* in .env (PostgreSQL)

# 3. Database
php artisan migrate:fresh --seed

# 4. Start development server
composer run dev
# Runs php artisan serve + npm run dev (Vite) concurrently
```

### Seeders

| Seeder | Description |
|--------|------------|
| `PermissionSeeder` | Creates the 31 system permissions |
| `DemoDataSeeder` | Creates admin user, roles, store, branches, warehouses, categories, products with stock |

## Conventions

- **IDs**: UUID v7 on all models (`HasUuids`)
- **Soft Deletes**: on `User`, `Product`, `Store`
- **Timestamps**: automatic on all models
- **API Responses**: consistent format `{ status, message, data }` or `{ error: { code, message } }`
- **Validation**: via Form Requests in `app/Http/Requests/`
- **Action naming**: `VerbNounAction` (e.g., `CreateProductAction`, `CancelSaleAction`)
- **Service naming**: `DomainService` (e.g., `ProductService`, `StockService`)
