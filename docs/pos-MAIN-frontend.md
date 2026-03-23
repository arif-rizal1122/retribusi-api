# Frontend Context - POS System

This document outlines the frontend architecture implemented using React, focusing on a dynamic and responsive user experience as defined in the [Core Basic Context](file:///home/arifrizal/Desktop/workspace/bckup_2/point_of_sale/docs/core_basic.md).

## 1. Tech Stack & State Management
- **Framework:** React.
- **Styling:** Vanilla CSS / Modern UI Libraries (ensuring high visual appeal).
- **Authentication:** JWT stored in `localStorage` or `HttpOnly` Cookies.
- **State Management:** React Context API or Redux for managing global state (User, Cart, Settings).

## 2. Component Architecture
- **Atoms/Molecules:** Reusable UI components (Buttons, Inputs, Modals).
- **Organisms/Pages:** Specific feature pages (Login, Dashboard, Stock Management, POS Checkout).
- **Hooks:** Custom hooks for API interactions (e.g., `useAuth`, `useTransactions`).

## 3. Responsive Strategy (Web & Mobile)
The application is designed to be fully responsive for both browser usage and Google Play Store deployment.

- **Mobile View:** Optimized for touch interactions, simplified layouts for smaller screens, and barcode scanning support.
- **Desktop/Web View:** Comprehensive dashboard layouts, detailed reports, and efficient multi-item transaction management.

## 4. Feature Flow

### POS Transaction Flow
1. **Selection:** Kasir selects/scans items.
2. **Review:** Subtotal is calculated automatically with tax included.
3. **Payment:** Selection of Cash or QRIS.
4. **QRIS Generation:** If QRIS is selected, a unique store QR is displayed.
5. **Completion:** Transaction is finalized and stock is updated.

### Inventory Management
- Petugas Gudang can search, add, or update stock records.
- Real-time alerts for low-stock items.

## 5. UI/UX Principles
- **Vibrant & Professional:** Using a premium design palette that feels modern.
- **Micro-animations:** Subtle feedback on interactions (e.g., successful scan, login error).
- **Fast & Intuitive:** Designed for real-world retail speed.
