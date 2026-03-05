<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Branch;
use App\Models\LandingPageSetting;
use App\Models\Permission;
use App\Models\Profile;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Configurando datos de producción...');

        // ─── 1. Permisos ───────────────────────────────────────────
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

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['key' => $p['key']], $p);
        }

        $this->command->info('✅ Permisos creados (' . count($permissions) . ')');

        // ─── 2. Roles del sistema ──────────────────────────────────
        $allPermissions = Permission::all();

        $attachPermissions = function (string $roleId, array $permissionIds) {
            $existing = DB::table('role_permissions')
                ->where('role_id', $roleId)
                ->whereIn('permission_id', $permissionIds)
                ->pluck('permission_id')
                ->toArray();

            $toInsert = array_values(array_diff($permissionIds, $existing));
            $now = now();
            $records = [];
            foreach ($toInsert as $permissionId) {
                $records[] = [
                    'id'            => Str::uuid(),
                    'role_id'       => $roleId,
                    'permission_id' => $permissionId,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }
            if (!empty($records)) {
                DB::table('role_permissions')->insert($records);
            }
        };

        // Administrador — todos los permisos
        $adminRole = Role::firstOrCreate(
            ['key' => 'admin'],
            ['name' => 'Administrador', 'description' => 'Acceso total al sistema', 'is_system' => true]
        );
        $attachPermissions($adminRole->id, $allPermissions->pluck('id')->toArray());

        // Gerente de Sucursal
        $managerRole = Role::firstOrCreate(
            ['key' => 'branch_manager'],
            ['name' => 'Gerente de Sucursal', 'description' => 'Gestión completa de una sucursal', 'is_system' => true]
        );
        $managerPerms = $allPermissions->whereIn('key', [
            'dashboard.view', 'products.view', 'inventory.view', 'inventory.adjust',
            'sales.view', 'sales.create', 'cash.view', 'cash.open', 'cash.close',
            'customers.view', 'customers.create', 'reports.sales', 'reports.inventory',
        ]);
        $attachPermissions($managerRole->id, $managerPerms->pluck('id')->toArray());

        // Vendedor
        $sellerRole = Role::firstOrCreate(
            ['key' => 'seller'],
            ['name' => 'Vendedor', 'description' => 'Ventas y caja', 'is_system' => true]
        );
        $sellerPerms = $allPermissions->whereIn('key', [
            'dashboard.view', 'products.view', 'sales.view', 'sales.create',
            'cash.view', 'cash.open', 'cash.close', 'customers.view', 'customers.create',
        ]);
        $attachPermissions($sellerRole->id, $sellerPerms->pluck('id')->toArray());

        $this->command->info('✅ Roles creados: Administrador, Gerente, Vendedor');

        // ─── 3. Super Admin ────────────────────────────────────────
        $email    = env('ADMIN_EMAIL', 'super@admin.app');
        $password = env('ADMIN_PASSWORD', 'password');

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'password'          => bcrypt($password),
                'email_verified_at' => now(),
                'is_active'         => true,
            ]
        );

        Profile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'first_name'      => 'Super',
                'last_name'       => 'Admin',
                'second_last_name' => null,
                'phone'           => null,
            ]
        );

        $this->command->info("✅ Super admin creado: {$email}");

        // ─── 4. Tienda principal ───────────────────────────────────
        $storeName = env('STORE_NAME', 'Mi Tienda');
        $storeSlug = Str::slug($storeName);

        $store = Store::firstOrCreate(
            ['slug' => $storeSlug],
            [
                'name'         => $storeName,
                'display_name' => $storeName,
                'description'  => 'Tienda principal',
                'is_active'    => true,
            ]
        );

        $this->command->info("✅ Tienda creada: {$store->name}");

        // ─── 5. Sucursal principal ─────────────────────────────────
        $address = Address::create([
            'street'       => 'Calle Principal',
            'ext_number'   => '1',
            'neighborhood' => 'Centro',
            'city'         => 'Ciudad',
            'state'        => 'Estado',
            'postal_code'  => '00000',
            'country'      => 'México',
        ]);

        $branch = Branch::firstOrCreate(
            ['code' => 'SUC-MAIN'],
            [
                'address_id'      => $address->id,
                'name'            => 'Sucursal Principal',
                'phone'           => null,
                'is_active'       => true,
                'allow_sales'     => true,
                'allow_inventory' => true,
            ]
        );
        $branch->stores()->syncWithoutDetaching([$store->id]);

        $this->command->info("✅ Sucursal creada: {$branch->name}");

        // ─── 6. Almacén principal ──────────────────────────────────
        $warehouse = Warehouse::firstOrCreate(
            ['code' => 'ALM-MAIN'],
            [
                'branch_id' => $branch->id,
                'name'      => 'Almacén Principal',
                'type'      => 'central',
                'is_active'  => true,
            ]
        );
        $warehouse->stores()->syncWithoutDetaching([$store->id]);

        $this->command->info("✅ Almacén creado: {$warehouse->name}");

        // ─── 7. Branding (Landing Page Settings) ───────────────────
        LandingPageSetting::firstOrCreate(
            [],
            [
                'display_name'    => $storeName,
                'logo_url'        => null,
                'icon_url'        => null,
                'primary_color'   => '#171717',
                'secondary_color' => '#F5F5F5',
                'accent_color'    => '#F5F5F5',
            ]
        );

        $this->command->info('✅ Branding configurado (colores neutros por defecto)');

        // ─── Resumen ───────────────────────────────────────────────
        $this->command->newLine();
        $this->command->info('╔══════════════════════════════════════════╗');
        $this->command->info('║   🎉 Configuración inicial completada   ║');
        $this->command->info('╠══════════════════════════════════════════╣');
        $this->command->info("║  📧 Email:     {$email}");
        $this->command->info("║  🔑 Password:  {$password}");
        $this->command->info("║  🏪 Tienda:    {$store->name}");
        $this->command->info("║  🏢 Sucursal:  {$branch->name}");
        $this->command->info("║  📦 Almacén:   {$warehouse->name}");
        $this->command->info('╚══════════════════════════════════════════╝');
        $this->command->newLine();
    }
}
