# Permission Hierarchy and Security Architecture

This document details how the permission system works in **Nidya**, from the database structure to code validation.

## 1. Access Levels

The system operates under a three-level hierarchy:

### Level 1: Global Superuser (`is_superuser`)
The highest level. A user with `is_superuser = true` has total access to **all** stores, branches, and system resources, regardless of whether they have an assigned role or not.
- **Implementation:** Boolean column in the `users` table.
- **Usage:** Ideal for technical administrators or franchise owners.

### Level 2: Store Admin
Users linked to one or more specific stores with the `admin` role. They have full control over the resources of **that** store (products, sales, reports).
- **Implementation:** Record in `store_user_roles` linking `user_id`, `store_id`, and `role_id` (admin).

### Level 3: Employee with Partial Permissions (Seller/Cashier)
Users with limited roles (e.g., `seller`) in one or more stores. Their permissions are restricted to operational actions.
- **Implementation:** Similar to Level 2, but with a `role_id` that has fewer associated permissions.

---

## 2. Database Structure

The system uses an RBAC (Role-Based Access Control) model distributed by store:

- **Users:** Stores the global `is_superuser` flag.
- **Roles:** Defines permission sets (admin, seller, etc.).
- **Permissions:** Granular actions (sales.create, products.edit).
- **Store User Roles:** Pivot table linking the user to a store and a specific role.

---

## 3. Code Validation

### The Core Method: `hasPermissionInStore`
All security logic resides in the `User.php` model.

```php
public function hasPermissionInStore(string $permission, string $storeId): bool
{
    // 1. Bypass for Superusers
    if ($this->is_superuser) {
        return true;
    }

    // 2. Check Roles in the Store
    return $this->rolesInStore($storeId)
        ->flatMap->permissions
        ->pluck('key')
        ->contains($permission);
}
```

### Common Use Cases

#### Case A: Multi-Store Sale
When a seller tries to sell a product from **Store B** while logged into **Store A**:
1. The system identifies the product's `store_id`.
2. Calls `user->hasPermissionInStore('sales.create', 'STORE_B_ID')`.
3. If the seller is a superuser or has an explicitly assigned sales role in Store B, the sale proceeds.

#### Case B: Store Management (Global Level)
To create or delete stores, the system directly checks the superuser flag:
```php
if (!Auth::user()->is_superuser) {
    throw new AccessDeniedException();
}
```

---

## 4. Permission Matrix (Example)

| Action | Superuser | Store Admin | Seller |
| :--- | :---: | :---: | :---: |
| Create Store | ✅ | ❌ | ❌ |
| Create Sale | ✅ | ✅ (In their store) | ✅ (In their store) |
| View Global Reports | ✅ | ❌ | ❌ |
| Edit Products | ✅ | ✅ (In their store) | ❌ |
