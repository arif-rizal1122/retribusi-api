# Backend Context - POS System

This document describes the backend architecture implemented using Laravel, adhering to the requirements in the [Core Basic Context](file:///home/arifrizal/Desktop/workspace/bckup_2/point_of_sale/docs/core_basic.md).

## 1. Architecture Patterns
The backend follows standard Laravel best practices:
- **Service Layer:** Houses the business logic (e.g., Tax calculation, Inventory validation).
- **Repository Pattern (Optional):** To decouple database logic if needed.
- **API Controllers:** Handles request/response cycles for the React frontend.
- **Eloquent ORM:** For database interactions and relationships.

## 2. Core Modules

### Authentication Module
- Handles Login, Register, and Logout via JWT.
- Integrated with Laravel's `auth` guards and defined in [Security Context](file:///home/arifrizal/Desktop/workspace/bckup_2/point_of_sale/docs/security.md).

### Transaction Module
- Manages sales records.
- **Automatic Tax Logic:** Intercepts transaction saving events to calculate and record tax deductions.
- **QRIS Integration:** Logic for generating unique QR codes specifically for the store context.

### Inventory Module
- Manages products, categories, and stock history.
- Real-time stock deduction upon successful transactions.

### Reporting Module
- Generates sales aggregations and inventory movements.
- Supports data export formats (PDF/Excel) for store owners.

## 3. API Design Principles
- **RESTful API:** Predictable URLs and standard HTTP methods.
- **JSON Response Format:** Consistent structure for success and error responses.
- **Middleware:** Authorization is enforced via custom middleware checking Roles/Permissions.

## 4. Tax Calculation Implementation
> [!IMPORTANT]
> Tax must be calculated BEFORE the final total is recorded.

```php
// Pseudo-logic for tax calculation
public function processTransaction($data) {
    $taxRate = Config::get('settings.tax_rate'); // e.g., 0.11 for 11%
    $subtotal = $this->calculateSubtotal($data['items']);
    $taxAmount = $subtotal * $taxRate;
    $total = $subtotal + $taxAmount; // Or deducted from subtotal depending on pricing model
    
    // Save to database
}
```

## 5. User Management & Registration Flow
- **Registration:** New users register with email, store name, and password. This automatically generates a store-specific QRIS for payments.
- **Admin Role:** The registering user is automatically assigned the `admin` role and redirected to the dashboard.
- **Staff Credentials:** Admins can create and manage credentials (email, password, role) for **Kasir** (Cashier) staff.

