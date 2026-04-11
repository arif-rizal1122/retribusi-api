# 📍 Core Foundations - POS System

Core business requirements and functional foundations for the grocery store Point of Sale application.

## 1. Application Overview
A specialized POS system for **Toko Kelontong**, bridging the gap between desktop management and mobile retail through a cross-platform React/Laravel architecture.

### Mission
- **Speed**: Rapid checkout for high-traffic retail.
- **Accuracy**: Automated stock deduction and tax calculation.
- **Portability**: Seamless deployment to the Google Play Store.

---

## 2. User Roles & Access Control

| Role | Responsibility | Module Access |
| :--- | :--- | :--- |
| **Admin** | General Management | Full System Control + Reports |
| **Kasir** | Frontend Sales | POS, Transactions, Receipts |
| **Gudang** | Inventory Control | Stock, Products, Suppliers |

---

## 3. Core Business Rules

### 💰 Payments
- **Cash**: Standard manual entry for physical currency.
- **QRIS**: Unique store-based digital payment integration.

### 📝 Tax Logic
> [!IMPORTANT]
> All transactions are subject to a **12% tax rate** (standardized across frontend/backend).
- Tax is calculated **automatically** upon item addition or checkout.
- Calculation logic is **method-agnostic** (applies to both Cash and QRIS).

---

## 🎨 UI/UX Philosophy
- **Dynamic**: Fluid responsiveness between 4K monitors and mobile screens.
- **Premium**: A high-end visual aesthetic that builds trust with professional users.
- **Proactive**: Micro-interactions that guide the user through the sales funnel.
