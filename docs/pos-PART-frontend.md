# 🛠️ Enhanced Frontend Context - POS System

Detailed frontend architecture using React, focusing on a premium user experience and atomic component design.

## 1. Tech Stack
- **Framework**: React 18+ (Vite)
- **Styling**: Tailwind CSS + Framer Motion (Animations)
- **State Management**: Zustand (App State) + TanStack Query (Server State)
- **Icons**: Lucide React

---

## 🧩 2. Atomic Design Structure

| Level | Examples | Purpose |
| :--- | :--- | :--- |
| **Atoms** | Button, Input, Badge | Basic UI primitives |
| **Molecules** | ProductCard, FormInputGroup | Combined functional units |
| **Organisms** | Sidebar, POSGrid, CartSidebar | Complex feature components |
| **Templates** | DashboardLayout, AuthLayout | Page-level structural layouts |

---

## 📱 3. Responsive Strategy

### Breakpoints
- **Mobile (< 768px)**: Single column, bottom navigation, drawer menu.
- **Tablet (768px - 1024px)**: Collapsible sidebar, 2-column dashboard.
- **Desktop (> 1024px)**: Permanent fixed sidebar, full-width grids.

### Key Mobile Features
- **Touch-Optimized**: Targets > 44px, swipe gestures for cart actions.
- **Mobile Header**: Fixed header with hamburger menu for drawer access.
- **POS View**: Toggleable cart summary for seamless mobile checkout.

---

## ⚡ 4. Dynamic Interactions

### Real-Time & Micro-Animations
- **Optimistic UI**: Instant cart updates before server confirmation.
- **Feedback**: Skeleton screens for loading; Toast notifications for errors.
- **Motion**: Page transitions and button hover/click effects via Framer Motion.

---

## 🔄 5. Core Feature Flows

### POS Workflow
1. **Catalog**: Search or browse from a responsive product grid.
2. **Cart**: Side-drawer review (desktop) or summary bar (mobile).
3. **Payment**: Choose method (Cash/QRIS) with automatic change calculation.
4. **Receipt**: Visual confirmation with print/save options.

### Inventory Workflow
- **Search**: Debounced real-time filtering by Name or SKU.
- **Modals**: Slide-up sheets (mobile) or centered modals (desktop) for editing.
- **Badges**: Visual indicators (Green/Red) for stock status levels.

---

## 🎨 6. Visual Design System

- **Colors**: Blue (Primary), Emerald (Success), Rose (Danger).
- **Grid**: Consistent 8px spacing system for padding/margin.
- **Typography**: Inter/Inter-UI for maximum readability across devices.

---
> [!TIP]
> Use the reusable `cn` utility for combining Tailwind classes dynamically and avoiding class conflicts.