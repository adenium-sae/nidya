# Jerarquía de Permisos y Arquitectura de Seguridad

Este documento detalla cómo funciona el sistema de permisos en **Nidya**, desde la estructura de la base de datos hasta la validación en el código.

## 1. Niveles de Acceso

El sistema opera bajo una jerarquía de tres niveles:

### Nivel 1: Superusuario Global (`is_superuser`)
Es el nivel más alto. Un usuario con `is_superuser = true` tiene acceso total a **todas** las tiendas, sucursales y recursos del sistema, sin importar si tiene un rol asignado o no.
- **Implementación:** Columna booleana en la tabla `users`.
- **Uso:** Ideal para administradores técnicos o dueños de la franquicia.

### Nivel 2: Administrador de Tienda (Store Admin)
Usuarios vinculados a una o más tiendas específicas con el rol de `admin`. Tienen control total sobre los recursos de **esa** tienda (productos, ventas, reportes).
- **Implementación:** Registro en `store_user_roles` vinculando `user_id`, `store_id` y `role_id` (admin).

### Nivel 3: Empleado con Permisos Parciales (Vendedor/Cajero)
Usuarios con roles limitados (ej. `seller`) en una o más tiendas. Sus permisos están restringidos a acciones operativas.
- **Implementación:** Similar al Nivel 2, pero con un `role_id` con menos permisos asociados.

---

## 2. Estructura de Base de Datos

El sistema utiliza un modelo RBAC (Role-Based Access Control) distribuido por tienda:

- **Users:** Almacena el flag global `is_superuser`.
- **Roles:** Define los conjuntos de permisos (admin, seller, etc.).
- **Permissions:** Acciones granulares (sales.create, products.edit).
- **Store User Roles:** Tabla pivote que vincula al usuario con una tienda y un rol específico.

---

## 3. Validación en el Código

### El Método Central: `hasPermissionInStore`
Toda la lógica de seguridad reside en el modelo `User.php`.

```php
public function hasPermissionInStore(string $permission, string $storeId): bool
{
    // 1. Bypass para Superusuarios
    if ($this->is_superuser) {
        return true;
    }

    // 2. Verificación de Roles en la Tienda
    return $this->rolesInStore($storeId)
        ->flatMap->permissions
        ->pluck('key')
        ->contains($permission);
}
```

### Casos de Uso Comunes

#### Caso A: Venta Multi-Tienda
Cuando un vendedor intenta vender un producto de la **Tienda B** estando logueado bajo la **Tienda A**:
1. El sistema identifica el `store_id` del producto.
2. Llama a `user->hasPermissionInStore('sales.create', 'ID_TIENDA_B')`.
3. Si el vendedor es superusuario o tiene un rol de ventas asignado explícitamente en la Tienda B, la venta procede.

#### Caso B: Gestión de Tiendas (Nivel Global)
Para crear o eliminar tiendas, el sistema verifica directamente el flag de superusuario:
```php
if (!Auth::user()->is_superuser) {
    throw new AccessDeniedException();
}
```

---

## 4. Matriz de Permisos (Ejemplo)

| Acción | Superusuario | Admin de Tienda | Vendedor |
| :--- | :---: | :---: | :---: |
| Crear Tienda | ✅ | ❌ | ❌ |
| Crear Venta | ✅ | ✅ (En su tienda) | ✅ (En su tienda) |
| Ver Reportes Globales | ✅ | ❌ | ❌ |
| Editar Productos | ✅ | ✅ (En su tienda) | ❌ |
