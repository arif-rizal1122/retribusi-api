# ⚙️ Enhanced Backend Context - POS System

Technical specifications for the POS backend, focusing on role-based access, API standards, and core business logic.

## 🔐 1. Authentication & Authorization

### Role Definitions
| Role | Permissions | Accessible Modules |
| :--- | :--- | :--- |
| **Admin** | Full Access | Dashboard, User Management, Settings, Reports, Inventory |
| **Kasir** | Limited (Retail) | POS, Transactions, Sales Reports |
| **Gudang** | Limited (Stock) | Inventory, Stock Management, Supplier |

### JWT Token Structure
```json
{
  "user_id": 1,
  "email": "admin@pos.com",
  "role": "admin",
  "permissions": ["read", "write", "delete"],
  "exp": 1735689600
}
```

---

## 🌐 2. API Architecture & Routing

### URL Structure (Frontend)
| Role | URL Path | Key Modules |
| :--- | :--- | :--- |
| Admin | `/admin` | Enterprise Management |
| Kasir | `/kasir` | Retail Point of Sale |
| Gudang | `/inventory` | Warehouse & Logistics |

### Backend API Routes
```php
// Role-Based Route Protection
Route::middleware(['auth:api', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', 'AdminDashboardController');
    Route::apiResource('/admin/users', 'UserController');
});

Route::middleware(['auth:api', 'role:kasir'])->group(function () {
    Route::get('/kasir/pos', 'PosController@index');
    Route::post('/kasir/transactions', 'TransactionController@store');
});

Route::middleware(['auth:api', 'role:inventory'])->group(function () {
    Route::apiResource('/inventory/products', 'ProductController');
    Route::post('/inventory/stock/adjust', 'StockController@adjust');
});
```

---

## 📦 3. Core Business Logic

### Transaction Service (Atomic Order)
1. **Validate**: Check stock availability for all items.
2. **Calculate**: Compute subtotal + 12% tax (configurable).
3. **Deduct**: Reduce stock in an atomic database transaction.
4. **Record**: Create transaction and itemized receipt entries.

### Inventory Logic
- **Stock Guard**: Prevents checkout if stock < requested quantity.
- **Auto Alert**: Triggers notification when stock hits `min_stock` threshold.
- **Audit Trail**: Every stock change is logged in `stock_history`.

---

## 🗄️ 4. Data Schema Overview

### Primary Entities
- **Users**: Authentication and role assignment.
- **Products**: SKU-based catalog with price/stock tracking.
- **Transactions**: Sales headers (invoice, total, payment method).
- **Transaction Items**: Line items for each sales record.
- **Stock History**: Traceability for all warehouse movements.

---

## 🔒 5. Security & Standards

### Response Format
```json
{
    "success": true,
    "message": "Action completed",
    "data": { ... },
    "timestamp": "2024-01-15T10:30:00Z"
}
```

### Protection Layers
- **Rate Limiting**: Throttles brute-force attempts on sensitive endpoints.
- **Validation**: Strict server-side validation for all incoming data.
- **Security Headers**: XSS and Frame protection enabled.

---
> [!NOTE]
> This documentation is designed to align with the **Laravel (Backend)** and **React (Frontend)** implementation.
