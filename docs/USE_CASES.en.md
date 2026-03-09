# Use Cases: Nidya Backend

This document describes practical scenarios of how the system solves common business needs.

## 1. Multi-Store Management (Cross-Store)

### Scenario: Multi-brand branch seller
A physical branch houses two different stores ("Clothing Store" and "Accessories Store"). Only one seller is available.

**Flow:**
1. The seller logs in.
2. A customer buys a shirt (Store A) and a watch (Store B).
3. The seller scans both products in the same sale.
4. **Backend:** Validates that the seller has permission in both stores.
5. **Result:** A single invoice is generated for the customer, but internally the system tracks which store sold which product for inventory and profit reports.

---

## 2. Centralized Administration (Superuser)

### Scenario: The owner expands the business
The franchise owner wants to open a new branch in another city.

**Flow:**
1. The owner (Superuser) enters the panel.
2. Creates a new `Store` and a `Branch`.
3. Creates the initial `Warehouses`.
4. **Backend:** The system allows these structural actions without the owner having to assign roles to themselves in each new entity.
5. **Result:** Fast expansion and total control without administrative friction.

---

## 3. Employee Onboarding

### Scenario: Hiring a new cashier
An administrator registers a new employee in the system.

**Flow:**
1. The administrator uses the `/auth/signup` endpoint.
2. The system creates the account and the employee profile.
3. The administrator assigns the employee to a specific `Store` with the `seller` role.
4. **Backend:** The employee can now log in and see only the products and sales of their assigned store.
5. **Result:** Security by design; the new employee has no access until explicitly assigned.

---

## 4. Inventory and Warehouse Control

### Scenario: Inter-branch transfer
The "Downtown Store" ran out of stock for a product, but the "North Store" has a surplus.

**Flow:**
1. The manager requests a stock transfer.
2. The system generates a `StockTransfer` with a `pending` status.
3. When the product physically arrives, the recipient marks the transfer as `completed`.
4. **Backend:** Automatically updates quantities in the source and destination warehouses.
5. **Result:** Full traceability of merchandise.
