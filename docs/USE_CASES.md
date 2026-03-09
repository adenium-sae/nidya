# Casos de Uso: Nidya Backend

Este documento describe escenarios prácticos de cómo el sistema resuelve necesidades de negocio comunes.

## 1. Gestión Multi-Tienda (Cross-Store)

### Escenario: Vendedor encursal multi-marca
Una sucursal física alberga dos tiendas distintas ("Tienda de Ropa" y "Tienda de Accesorios"). Solo hay un vendedor disponible.

**Flujo:**
1. El vendedor inicia sesión.
2. Un cliente compra una playera (Tienda A) y un reloj (Tienda B).
3. El vendedor escanea ambos productos en la misma venta.
4. **Backend:** Valida que el vendedor tenga permiso en ambas tiendas.
5. **Resultado:** Se genera una sola factura para el cliente, pero internamente el sistema rastrea qué tienda vendió qué producto para reportes de inventario y utilidades.

---

## 2. Administración Centralizada (Superusuario)

### Escenario: El dueño expande el negocio
El dueño de la franquicia quiere abrir una nueva sucursal en otra ciudad.

**Flujo:**
1. El dueño (Superusuario) entra al panel.
2. Crea una nueva `Store` y una `Branch`.
3. Crea los `Warehouses` iniciales.
4. **Backend:** El sistema permite estas acciones estructurales sin necesidad de que el dueño se asigne roles a sí mismo en cada nueva entidad.
5. **Resultado:** Expansión rápida y control total sin fricción administrativa.

---

## 3. Registro y Onboarding de Empleados

### Escenario: Contratación de un nuevo cajero
El administrador registra a un nuevo empleado en el sistema.

**Flujo:**
1. El administrador usa el endpoint `/auth/signup`.
2. El sistema crea la cuenta y el perfil del empleado.
3. El administrador asigna al empleado a una `Store` específica con el rol de `seller`.
4. **Backend:** El empleado ahora puede iniciar sesión y ver solo los productos y ventas de su tienda asignada.
5. **Resultado:** Seguridad por diseño; el nuevo empleado no tiene acceso a nada hasta que se le asigne explícitamente.

---

## 4. Control de Inventario y Almacenes

### Escenario: Transferencia entre sucursales
La "Tienda Centro" se quedó sin stock de un producto, pero la "Tienda Norte" tiene excedente.

**Flujo:**
1. El gerente solicita una transferencia de stock.
2. El sistema genera un `StockTransfer` con estatus `pending`.
3. Cuando el producto llega físicamente, el receptor marca la transferencia como `completed`.
4. **Backend:** Actualiza automáticamente las cantidades en los almacenes de origen y destino.
5. **Resultado:** Trazabilidad total de la mercancía.
