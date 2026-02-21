<?php

namespace Database\Seeders;

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

class DemoDataSeeder extends Seeder
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

        // 2. Crear Roles y Permisos
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

        // 3. Crear Store
        $store = Store::create([
            'name' => 'Abarrotes Don Pepe',
            'slug' => 'abarrotes-don-pepe',
            'description' => 'Tu abarrotera de confianza',
            'primary_color' => '#10B981',
            'is_active' => true,
        ]);

        $this->command->info("✅ Tienda creada: {$store->name}");

        // 4. Crear Direcciones y Sucursales
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
            'store_id' => $store->id,
            'address_id' => $address1->id,
            'name' => 'Sucursal Centro',
            'code' => 'SUC-001',
            'phone' => '6441234567',
            'is_active' => true,
            'allow_sales' => true,
            'allow_inventory' => true,
        ]);

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
            'store_id' => $store->id,
            'address_id' => $address2->id,
            'name' => 'Sucursal Norte',
            'code' => 'SUC-002',
            'phone' => '6441234568',
            'is_active' => true,
            'allow_sales' => true,
            'allow_inventory' => true,
        ]);

        $this->command->info('✅ Sucursales creadas: Centro y Norte');

        // 5. Crear Almacenes
        $warehouse1 = Warehouse::create([
            'store_id' => $store->id,
            'branch_id' => $branch1->id,
            'name' => 'Almacén Principal Centro',
            'code' => 'ALM-001',
            'type' => 'branch',
            'is_active' => true,
        ]);

        $warehouse2 = Warehouse::create([
            'store_id' => $store->id,
            'branch_id' => $branch2->id,
            'name' => 'Almacén Sucursal Norte',
            'code' => 'ALM-002',
            'type' => 'branch',
            'is_active' => true,
        ]);

        // Almacén central (sin sucursal)
        $warehouseCentral = Warehouse::create([
            'store_id' => $store->id,
            'name' => 'Almacén Central',
            'code' => 'ALM-CENTRAL',
            'type' => 'central',
            'is_active' => true,
        ]);

        $this->command->info('✅ Almacenes creados');

        // 6. Crear Categorías (flat structure)
        $categoryNames = ['Refrescos', 'Agua', 'Papas', 'Arroz', 'Frijol', 'Galletas', 'Abarrotes', 'Bebidas', 'Botanas', 'Limpieza'];
        
        foreach ($categoryNames as $catName) {
            Category::create([
                'name' => $catName,
                'slug' => Str::slug($catName),
                'is_active' => true,
            ]);
        }

        $this->command->info('✅ Categorías creadas');

        // 7. Crear Productos
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

        $this->command->info('');
        $this->command->info('🎉 ¡Datos de demostración creados exitosamente!');
        $this->command->info('');
        $this->command->info('📧 Usuario: admin@abarrotes.com');
        $this->command->info('🔑 Password: password');
        $this->command->info('🏪 Tienda: Abarrotes Don Pepe');
        $this->command->info('');
    }
}