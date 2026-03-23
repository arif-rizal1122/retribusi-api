# 🔒 Security Architecture - POS System

Security protocols and authorization standards for the POS ecosystem.

## 1. Authentication (Stateless JWT)

The system utilizes **JSON Web Tokens** for secure communication between React and Laravel.

### The Security Loop
1. **Challenge**: User submits credentials (SSL encrypted).
2. **Issue**: Backend validates and issues a signed JWT.
3. **Persistence**: Tokens are stored in **Secure Cookies** or LocalStorage.
4. **Authorize**: Every API call includes the Bearer token in the header.
5. **Renewal**: Silent refresh logic ensures a smooth user session.

---

## 2. Authorization (RBAC)

Granular control over specific system features based on user role.

| Module | Admin | Kasir | Gudang |
| :--- | :---: | :---: | :---: |
| **Login / Logout** | ✅ | ✅ | ✅ |
| **Create Users** | ✅ | ❌ | ❌ |
| **Process Sales** | ✅ | ✅ | ❌ |
| **Void/Delete** | ✅ | ❌ | ❌ |
| **Edit Products** | ✅ | ❌ | ✅ |
| **View Reports** | ✅ | ✅* | ❌ |
| **Store Settings** | ✅ | ❌ | ❌ |

*\*Kasir can only view their own shift/daily reports.*

---

## 3. Data Integrity & Privacy
- **Hashing**: Passwords protected by Bcrypt (Rounds: 12).
- **Protection**: Middleware-level guards on all sensitive API routes.
- **Prevention**: Native XSS and CSRF protection layers enabled.
- **Sanitization**: Input filtering on all transaction-critical data points.
