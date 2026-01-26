<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
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

        $this->command->info('Permisos creados correctamente');
    }
}