<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Address;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Profile;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Creando datos de demostración...');

        // 1. Crear Usuario Owner
        $user = User::create([
            'email' => 'oscar@erus.mx',
            'password' => bcrypt('12345678'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        Profile::create([
            'user_id' => $user->id,
            'first_name' => 'José',
            'last_name' => 'Pérez',
            'second_last_name' => 'López',
            'phone' => '6441234567',
        ]);

        $this->command->info("✅ Usuario admin creado: {$user->email} / password");

        // 2. Crear Permisos
        $permissions = [
            // Dashboard
            ['key' => 'dashboard.view', 'name' => 'Ver dashboard', 'module' => 'dashboard'],
            // Productos
            ['key' => 'products.view', 'name' => 'Ver productos', 'module' => 'products'],
            ['key' => 'products.create', 'name' => 'Crear productos', 'module' => 'products'],
            ['key' => 'products.edit', 'name' => 'Editar productos', 'module' => 'products'],
            ['key' => 'products.delete', 'name' => 'Eliminar productos', 'module' => 'products'],
            // Inventario
            ['key' => 'inventory.view', 'name' => 'Ver inventario', 'module' => 'inventory'],
            ['key' => 'inventory.adjust', 'name' => 'Ajustar inventario', 'module' => 'inventory'],
            ['key' => 'inventory.transfer', 'name' => 'Transferir inventario', 'module' => 'inventory'],
            ['key' => 'inventory.receive', 'name' => 'Recibir inventario', 'module' => 'inventory'],
            // Ventas
            ['key' => 'sales.view', 'name' => 'Ver ventas', 'module' => 'sales'],
            ['key' => 'sales.create', 'name' => 'Crear ventas', 'module' => 'sales'],
            ['key' => 'sales.cancel', 'name' => 'Cancelar ventas', 'module' => 'sales'],
            ['key' => 'sales.refund', 'name' => 'Reembolsar ventas', 'module' => 'sales'],
            // Caja
            ['key' => 'cash.view', 'name' => 'Ver movimientos de caja', 'module' => 'cash'],
            ['key' => 'cash.open', 'name' => 'Abrir caja', 'module' => 'cash'],
            ['key' => 'cash.close', 'name' => 'Cerrar caja', 'module' => 'cash'],
            ['key' => 'cash.withdraw', 'name' => 'Retirar efectivo', 'module' => 'cash'],
            ['key' => 'cash.deposit', 'name' => 'Depositar efectivo', 'module' => 'cash'],
            // Clientes
            ['key' => 'customers.view', 'name' => 'Ver clientes', 'module' => 'customers'],
            ['key' => 'customers.create', 'name' => 'Crear clientes', 'module' => 'customers'],
            ['key' => 'customers.edit', 'name' => 'Editar clientes', 'module' => 'customers'],
            ['key' => 'customers.delete', 'name' => 'Eliminar clientes', 'module' => 'customers'],
            // Reportes
            ['key' => 'reports.sales', 'name' => 'Ver reporte de ventas', 'module' => 'reports'],
            ['key' => 'reports.inventory', 'name' => 'Ver reporte de inventario', 'module' => 'reports'],
            ['key' => 'reports.cash', 'name' => 'Ver reporte de caja', 'module' => 'reports'],
            ['key' => 'reports.products', 'name' => 'Ver reporte de productos', 'module' => 'reports'],
            // Configuración
            ['key' => 'settings.view', 'name' => 'Ver configuración', 'module' => 'settings'],
            ['key' => 'settings.edit', 'name' => 'Editar configuración', 'module' => 'settings'],
            ['key' => 'settings.users', 'name' => 'Gestionar usuarios', 'module' => 'settings'],
            ['key' => 'settings.roles', 'name' => 'Gestionar roles', 'module' => 'settings'],
            ['key' => 'settings.stores', 'name' => 'Gestionar tiendas', 'module' => 'settings'],
            ['key' => 'settings.branches', 'name' => 'Gestionar sucursales', 'module' => 'settings'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['key' => $permission['key']],
                $permission
            );
        }

        $this->command->info('✅ Permisos creados');

        // 3. Crear Roles
        $allPermissions = Permission::all();
        
        // Helper function to attach permissions with UUIDs
        $attachPermissions = function ($roleId, $permissionIds) {
            $now = now();
            $records = [];
            foreach ($permissionIds as $permissionId) {
                $records[] = [
                    'id' => Str::uuid(),
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            DB::table('role_permissions')->insert($records);
        };
        
        // Rol: Administrador (todos los permisos)
        $adminRole = Role::create([
            'key' => 'admin',
            'name' => 'Administrador',
            'description' => 'Acceso total al sistema',
            'is_system' => true,
        ]);
        $attachPermissions($adminRole->id, $allPermissions->pluck('id')->toArray());

        // Rol: Gerente de Sucursal
        $managerRole = Role::create([
            'key' => 'branch_manager',
            'name' => 'Gerente de Sucursal',
            'description' => 'Gestión completa de una sucursal',
            'is_system' => true,
        ]);
        $managerPermissions = $allPermissions->whereIn('key', [
            'dashboard.view', 'products.view', 'inventory.view', 'inventory.adjust',
            'sales.view', 'sales.create', 'cash.view', 'cash.open', 'cash.close',
            'customers.view', 'customers.create', 'reports.sales', 'reports.inventory'
        ]);
        $attachPermissions($managerRole->id, $managerPermissions->pluck('id')->toArray());

        // Rol: Vendedor
        $sellerRole = Role::create([
            'key' => 'seller',
            'name' => 'Vendedor',
            'description' => 'Ventas y caja',
            'is_system' => true,
        ]);
        $sellerPermissions = $allPermissions->whereIn('key', [
            'dashboard.view', 'products.view', 'sales.view', 'sales.create',
            'cash.view', 'cash.open', 'cash.close', 'customers.view', 'customers.create'
        ]);
        $attachPermissions($sellerRole->id, $sellerPermissions->pluck('id')->toArray());

        $this->command->info('✅ Roles creados: Admin, Gerente, Vendedor');

        // 4. Crear Store
        $store = Store::create([
            'name' => 'Abarrotes Don Pepe',
            'slug' => 'abarrotes-don-pepe',
            'description' => 'Tu abarrotera de confianza',
            'primary_color' => '#10B981',
            'is_active' => true,
        ]);

        $this->command->info("✅ Tienda creada: {$store->name}");

        // 5. Crear Direcciones y Sucursales
        $address1 = Address::create([
            'street' => 'Av. Miguel Alemán',
            'ext_number' => '123',
            'neighborhood' => 'Centro',
            'city' => 'Ciudad Obregón',
            'state' => 'Sonora',
            'postal_code' => '85000',
            'country' => 'México',
        ]);

        $branch1 = Branch::create([
            'address_id' => $address1->id,
            'name' => 'Sucursal Centro',
            'code' => 'SUC-001',
            'phone' => '6441234567',
            'is_active' => true,
            'allow_sales' => true,
            'allow_inventory' => true,
        ]);
        $branch1->stores()->attach($store->id);

        $address2 = Address::create([
            'street' => 'Blvd. Colosio',
            'ext_number' => '456',
            'neighborhood' => 'Villa Bonita',
            'city' => 'Ciudad Obregón',
            'state' => 'Sonora',
            'postal_code' => '85010',
            'country' => 'México',
        ]);

        $branch2 = Branch::create([
            'address_id' => $address2->id,
            'name' => 'Sucursal Norte',
            'code' => 'SUC-002',
            'phone' => '6441234568',
            'is_active' => true,
            'allow_sales' => true,
            'allow_inventory' => true,
        ]);
        $branch2->stores()->attach($store->id);

        $this->command->info('✅ Sucursales creadas: Centro y Norte');

        // 6. Crear Almacenes
        $warehouse1 = Warehouse::create([
            'branch_id' => $branch1->id,
            'name' => 'Almacén Principal Centro',
            'code' => 'ALM-001',
            'type' => 'branch',
            'is_active' => true,
        ]);
        $warehouse1->stores()->attach($store->id);

        $warehouse2 = Warehouse::create([
            'branch_id' => $branch2->id,
            'name' => 'Almacén Sucursal Norte',
            'code' => 'ALM-002',
            'type' => 'branch',
            'is_active' => true,
        ]);
        $warehouse2->stores()->attach($store->id);

        // Almacén central (sin sucursal)
        $warehouseCentral = Warehouse::create([
            'name' => 'Almacén Central',
            'code' => 'ALM-CENTRAL',
            'type' => 'central',
            'is_active' => true,
        ]);
        $warehouseCentral->stores()->attach($store->id);

        $this->command->info('✅ Almacenes creados');

        // 7. Crear Categorías (flat structure)
        $categoryNames = ['Refrescos', 'Agua', 'Papas', 'Arroz', 'Frijol', 'Galletas', 'Abarrotes', 'Bebidas', 'Botanas', 'Limpieza'];
        
        foreach ($categoryNames as $catName) {
            Category::create([
                'name' => $catName,
                'slug' => Str::slug($catName),
                'is_active' => true,
            ]);
        }

        $this->command->info('✅ Categorías creadas');

        // 8. Crear Productos
        $products = [
            ['name' => 'Coca-Cola 600ml', 'sku' => 'COCA-600', 'barcode' => '7501055308', 'category' => 'Refrescos', 'cost' => 10.00, 'price' => 15.00],
            ['name' => 'Agua Ciel 1L', 'sku' => 'AGUA-1L', 'barcode' => '7501055309', 'category' => 'Agua', 'cost' => 5.00, 'price' => 8.00],
            ['name' => 'Sabritas 45g', 'sku' => 'SAB-45', 'barcode' => '7501055310', 'category' => 'Papas', 'cost' => 8.00, 'price' => 12.00],
            ['name' => 'Arroz Verde Valle 1kg', 'sku' => 'ARROZ-1K', 'barcode' => '7501055311', 'category' => 'Arroz', 'cost' => 18.00, 'price' => 25.00],
            ['name' => 'Frijol Isadora 1kg', 'sku' => 'FRIJ-1K', 'barcode' => '7501055312', 'category' => 'Frijol', 'cost' => 22.00, 'price' => 30.00],
            ['name' => 'Pepsi 600ml', 'sku' => 'PEPSI-600', 'barcode' => '7501055313', 'category' => 'Refrescos', 'cost' => 9.00, 'price' => 14.00],
            ['name' => 'Doritos 62g', 'sku' => 'DOR-62', 'barcode' => '7501055314', 'category' => 'Papas', 'cost' => 10.00, 'price' => 16.00],
            ['name' => 'Galletas Marías 170g', 'sku' => 'GAL-170', 'barcode' => '7501055315', 'category' => 'Galletas', 'cost' => 12.00, 'price' => 18.00],
        ];

        $createdProducts = [];
        foreach ($products as $prodData) {
            $category = Category::where('name', $prodData['category'])->first();

            $product = Product::create([
                'category_id' => $category->id,
                'name' => $prodData['name'],
                'sku' => $prodData['sku'],
                'barcode' => $prodData['barcode'],
                'type' => 'product',
                'track_inventory' => true,
                'min_stock' => 10,
                'cost' => $prodData['cost'],
                'is_active' => true,
            ]);

            // Asignar producto a la tienda con precio
            StoreProduct::create([
                'store_id' => $store->id,
                'product_id' => $product->id,
                'price' => $prodData['price'],
                'currency' => 'MXN',
                'is_active' => true,
            ]);

            // Crear stock inicial en cada almacén
            foreach ([$warehouse1, $warehouse2, $warehouseCentral] as $wh) {
                Stock::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $wh->id,
                    'quantity' => rand(20, 100),
                    'reserved' => 0,
                    'avg_cost' => $prodData['cost'],
                ]);
            }

            $createdProducts[] = ['product' => $product, 'price' => $prodData['price']];
        }

        $this->command->info('✅ Productos creados con stock inicial');

        // Log de creación de catálogo
        ActivityLog::create([
            'user_id' => $user->id,
            'store_id' => $store->id,
            'type' => ActivityLog::TYPE_CATALOG,
            'event' => 'catalog.seeded',
            'description' => 'Carga inicial de productos y categorías completada.',
            'level' => ActivityLog::LEVEL_INFO,
        ]);

        // 9. Crear Clientes
        $customers = [
            ['first_name' => 'Juan', 'last_name' => 'García', 'email' => 'juan@gmail.com', 'phone' => '6441010101'],
            ['first_name' => 'María', 'last_name' => 'Rodríguez', 'email' => 'maria@gmail.com', 'phone' => '6442020202'],
            ['first_name' => 'Pedro', 'last_name' => 'Sánchez', 'email' => 'pedro@gmail.com', 'phone' => '6443030303'],
            ['first_name' => 'Ana', 'last_name' => 'Martínez', 'email' => 'ana@gmail.com', 'phone' => '6444040404'],
            ['first_name' => 'Luis', 'last_name' => 'Hernández', 'email' => 'luis@gmail.com', 'phone' => '6445050505'],
        ];

        $createdCustomers = [];
        foreach ($customers as $c) {
            $createdCustomers[] = Customer::create(array_merge($c, [
                'code' => 'CLI-' . rand(100, 999),
                'is_active' => true,
            ]));
        }
        $this->command->info('✅ Clientes creados');

        // 10. Crear Ventas de los últimos 7 días (para el Chart)
        $this->command->info('📈 Generando historial de ventas...');
        
        $folioCounter = 1;
        $year = Carbon::now()->year;

        for ($i = 0; $i < 7; $i++) {
            $date = Carbon::now()->subDays($i);
            $salesCount = rand(5, 15);
            
            for ($j = 0; $j < $salesCount; $j++) {
                $customer = $createdCustomers[array_rand($createdCustomers)];
                $folio = 'VENTA-' . $year . '-' . str_pad($folioCounter++, 5, '0', STR_PAD_LEFT);

                $sale = Sale::create([
                    'folio' => $folio,
                    'store_id' => $store->id,
                    'branch_id' => $branch1->id,
                    'warehouse_id' => $warehouse1->id,
                    'customer_id' => $customer->id,
                    'user_id' => $user->id,
                    'subtotal' => 0,
                    'tax' => 0,
                    'discount' => 0,
                    'total' => 0,
                    'status' => 'completed',
                    'payment_method' => 'cash',
                    'completed_at' => $date->copy()->addHours(rand(8, 20)),
                    'created_at' => $date->copy()->addHours(rand(8, 20)),
                ]);

                // Agregar de 1 a 3 productos por venta
                $itemsCount = rand(1, 3);
                $saleTotal = 0;
                
                $randomKeys = array_rand($createdProducts, $itemsCount);
                if (!is_array($randomKeys)) $randomKeys = [$randomKeys];
                
                foreach ($randomKeys as $key) {
                    $prodItem = $createdProducts[$key];
                    $qty = rand(1, 5);
                    $itemTotal = $prodItem['price'] * $qty;
                    
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $prodItem['product']->id,
                        'quantity' => $qty,
                        'unit_price' => $prodItem['price'],
                        'discount' => 0,
                        'tax' => 0,
                        'subtotal' => $itemTotal,
                        'total' => $itemTotal,
                    ]);
                    
                    $saleTotal += $itemTotal;
                }
                
                $sale->update([
                    'subtotal' => $saleTotal,
                    'total' => $saleTotal,
                ]);

                // Log a few recent sales for the dashboard activity feed
                if ($i === 0 && $j < 3) {
                    ActivityLog::create([
                        'user_id' => $user->id,
                        'store_id' => $store->id,
                        'type' => ActivityLog::TYPE_SALES,
                        'event' => 'sale.created',
                        'description' => "Venta {$sale->folio} registrada por $" . number_format($sale->total, 2),
                        'level' => ActivityLog::LEVEL_INFO,
                        'created_at' => $sale->created_at,
                    ]);
                }
            }
        }
        $this->command->info('✅ Historial de ventas generado');

        $this->command->info('');
        $this->command->info('🎉 ¡Datos de demostración creados exitosamente!');
        $this->command->info('');
        $this->command->info("📧 Usuario: {$user->email}");
        $this->command->info('🔑 Password: password');
        $this->command->info("🏪 Tienda: {$store->name}");
        $this->command->info('');
    }
}