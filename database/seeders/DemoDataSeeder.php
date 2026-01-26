<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Profile;
use App\Models\Role;
use App\Models\Stock;
use App\Models\StorageLocation;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Creando datos de demostración...');

        // 1. Crear Tenant
        $tenant = Tenant::create([
            'name' => 'Abarrotes Don Pepe',
            'slug' => 'abarrotes-don-pepe',
            'tax_id' => 'ABCD123456XYZ',
            'business_name' => 'Abarrotes Don Pepe S.A. de C.V.',
            'phone' => '6441234567',
            'email' => 'contacto@abarrotesdonpepe.com',
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addDays(30),
            'subscription_plan' => 'professional',
        ]);
        
        $this->command->info("✅ Tenant creado: {$tenant->name}");

        // 2. Crear Usuario Owner
        $user = User::create([
            'email' => 'admin@abarrotes.com',
            'password' => bcrypt('password'),
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

        // Asignar usuario al tenant como owner
        $tenant->users()->attach($user->id, [
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->command->info("✅ Usuario admin creado: {$user->email} / password");

        // 3. Crear Roles y Permisos
        $allPermissions = Permission::all();
        
        // Rol: Administrador (todos los permisos)
        $adminRole = Role::create([
            'tenant_id' => $tenant->id,
            'key' => 'admin',
            'name' => 'Administrador',
            'description' => 'Acceso total al sistema',
            'is_system' => true,
        ]);
        $adminRole->permissions()->attach($allPermissions->pluck('id'));

        // Rol: Gerente de Sucursal
        $managerRole = Role::create([
            'tenant_id' => $tenant->id,
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
        $managerRole->permissions()->attach($managerPermissions->pluck('id'));

        // Rol: Vendedor
        $sellerRole = Role::create([
            'tenant_id' => $tenant->id,
            'key' => 'seller',
            'name' => 'Vendedor',
            'description' => 'Ventas y caja',
            'is_system' => true,
        ]);
        $sellerPermissions = $allPermissions->whereIn('key', [
            'dashboard.view', 'products.view', 'sales.view', 'sales.create',
            'cash.view', 'cash.open', 'cash.close', 'customers.view', 'customers.create'
        ]);
        $sellerRole->permissions()->attach($sellerPermissions->pluck('id'));

        $this->command->info('✅ Roles creados: Admin, Gerente, Vendedor');

        // 4. Crear Store
        $store = Store::create([
            'tenant_id' => $tenant->id,
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
            'tenant_id' => $tenant->id,
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
            'tenant_id' => $tenant->id,
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

        // 6. Crear Almacenes
        $warehouse1 = Warehouse::create([
            'tenant_id' => $tenant->id,
            'store_id' => $store->id,
            'branch_id' => $branch1->id,
            'name' => 'Almacén Principal Centro',
            'code' => 'ALM-001',
            'type' => 'branch',
            'is_active' => true,
        ]);

        $warehouse2 = Warehouse::create([
            'tenant_id' => $tenant->id,
            'store_id' => $store->id,
            'branch_id' => $branch2->id,
            'name' => 'Almacén Sucursal Norte',
            'code' => 'ALM-002',
            'type' => 'branch',
            'is_active' => true,
        ]);

        // Almacén central (sin sucursal)
        $warehouseCentral = Warehouse::create([
            'tenant_id' => $tenant->id,
            'store_id' => $store->id,
            'name' => 'Almacén Central',
            'code' => 'ALM-CENTRAL',
            'type' => 'central',
            'is_active' => true,
        ]);

        $this->command->info('✅ Almacenes creados');

        // 7. Crear Ubicaciones de Almacenamiento
        foreach ([$warehouse1, $warehouse2, $warehouseCentral] as $wh) {
            StorageLocation::create([
                'tenant_id' => $tenant->id,
                'warehouse_id' => $wh->id,
                'code' => 'ESTANTE-A1',
                'name' => 'Estante A - Nivel 1',
                'type' => 'shelf',
                'aisle' => 'A',
                'section' => '1',
                'is_active' => true,
            ]);

            StorageLocation::create([
                'tenant_id' => $tenant->id,
                'warehouse_id' => $wh->id,
                'code' => 'CAJA-REFRESCOS',
                'name' => 'Caja de Refrescos',
                'type' => 'box',
                'aisle' => 'B',
                'section' => '2',
                'is_active' => true,
            ]);
        }

        $this->command->info('✅ Ubicaciones de almacenamiento creadas');

        // 8. Crear Categorías
        $categories = [
            'Abarrotes' => ['Arroz', 'Frijol', 'Azúcar', 'Harina'],
            'Bebidas' => ['Refrescos', 'Agua', 'Jugos'],
            'Limpieza' => ['Detergente', 'Cloro', 'Jabón'],
            'Botanas' => ['Papas', 'Dulces', 'Galletas'],
        ];

        foreach ($categories as $parentName => $children) {
            $parent = Category::create([
                'tenant_id' => $tenant->id,
                'name' => $parentName,
                'slug' => Str::slug($parentName),
                'is_active' => true,
            ]);

            foreach ($children as $childName) {
                Category::create([
                    'tenant_id' => $tenant->id,
                    'parent_id' => $parent->id,
                    'name' => $childName,
                    'slug' => Str::slug($childName),
                    'is_active' => true,
                ]);
            }
        }

        $this->command->info('✅ Categorías creadas');

        // 9. Crear Productos
        $products = [
            ['name' => 'Coca-Cola 600ml', 'sku' => 'COCA-600', 'barcode' => '7501055308', 'category' => 'Refrescos', 'cost' => 10.00, 'price' => 15.00],
            ['name' => 'Agua Ciel 1L', 'sku' => 'AGUA-1L', 'barcode' => '7501055309', 'category' => 'Agua', 'cost' => 5.00, 'price' => 8.00],
            ['name' => 'Sabritas 45g', 'sku' => 'SAB-45', 'barcode' => '7501055310', 'category' => 'Papas', 'cost' => 8.00, 'price' => 12.00],
            ['name' => 'Arroz Verde Valle 1kg', 'sku' => 'ARROZ-1K', 'barcode' => '7501055311', 'category' => 'Arroz', 'cost' => 18.00, 'price' => 25.00],
            ['name' => 'Frijol Isadora 1kg', 'sku' => 'FRIJ-1K', 'barcode' => '7501055312', 'category' => 'Frijol', 'cost' => 22.00, 'price' => 30.00],
        ];

        foreach ($products as $prodData) {
            $category = Category::where('tenant_id', $tenant->id)
                ->where('name', $prodData['category'])
                ->first();

            $product = Product::create([
                'tenant_id' => $tenant->id,
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
                'tenant_id' => $tenant->id,
                'store_id' => $store->id,
                'product_id' => $product->id,
                'price' => $prodData['price'],
                'currency' => 'MXN',
                'is_active' => true,
            ]);

            // Crear stock inicial en cada almacén
            foreach ([$warehouse1, $warehouse2, $warehouseCentral] as $wh) {
                $location = StorageLocation::where('warehouse_id', $wh->id)->first();
                
                Stock::create([
                    'tenant_id' => $tenant->id,
                    'product_id' => $product->id,
                    'warehouse_id' => $wh->id,
                    'storage_location_id' => $location->id,
                    'quantity' => rand(20, 100),
                    'reserved' => 0,
                    'avg_cost' => $prodData['cost'],
                ]);
            }
        }

        $this->command->info('✅ Productos creados con stock inicial');

        $this->command->info('');
        $this->command->info('🎉 ¡Datos de demostración creados exitosamente!');
        $this->command->info('');
        $this->command->info('📧 Usuario: admin@abarrotes.com');
        $this->command->info('🔑 Password: password');
        $this->command->info('🏪 Tenant: Abarrotes Don Pepe');
        $this->command->info('');
    }
}