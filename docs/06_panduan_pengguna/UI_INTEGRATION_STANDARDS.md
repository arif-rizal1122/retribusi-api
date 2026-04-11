# UI Integration & Styling Standards

## Core Principles

> [!IMPORTANT]
> **Synchronized Evolution**: Any change to the UI integration model (e.g., auth logic, API structure, global styling) **MUST** be implemented across all relevant repositories simultaneously to maintain system integrity.
>
> **Architectural Awareness**: When creating new files or components that interact with multiple systems, you **MUST** refer to this schema to ensure consistency in state management, storage keys, and visual branding.

## 1. API Integration Model

All applications must use a consistent `api.ts` utility located in `src/lib/api.ts`.

### Standard Implementation
```typescript
const API_URL = import.meta.env.VITE_API_URL || 'https://api.sipanda.online';

export async function apiFetch(endpoint: string, options: RequestInit & { params?: Record<string, any> } = {}) {
    const token = localStorage.getItem('token'); // standard key: 'token'

    let url = `${API_URL}${endpoint}`;
    if (options.params) {
        const searchParams = new URLSearchParams();
        Object.entries(options.params).forEach(([key, value]) => {
            if (value !== undefined && value !== null) {
                searchParams.append(key, String(value));
            }
        });
        const queryString = searchParams.toString();
        if (queryString) {
            url += (url.includes('?') ? '&' : '?') + queryString;
        }
    }

    const headers = {
        'Accept': 'application/json',
        ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
        ...((options.headers as any) || {}),
    };

    const response = await fetch(url, {
        ...options,
        headers,
    });

    if (response.status === 401) {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        if (window.location.pathname !== '/login') {
            window.location.href = '/login';
        }
        throw new Error('Unauthorized');
    }

    const data = await response.json();
    if (!response.ok) {
        throw new Error(data.message || 'Terjadi kesalahan pada server');
    }
    return data;
}

export const api = {
    get: (endpoint: string, options: any = {}) => apiFetch(endpoint, { method: 'GET', ...options }),
    post: (endpoint: string, body: any, options: any = {}) => api_call_with_body('POST', endpoint, body, options),
    put: (endpoint: string, body: any, options: any = {}) => api_call_with_body('PUT', endpoint, body, options),
    delete: (endpoint: string, options: any = {}) => apiFetch(endpoint, { method: 'DELETE', ...options }),
};

async function api_call_with_body(method: string, endpoint: string, body: any, options: any) {
    const isFormData = body instanceof FormData;
    const headers = { ...((options.headers as any) || {}) };
    if (!isFormData && !headers['Content-Type']) {
        headers['Content-Type'] = 'application/json';
    }
    return apiFetch(endpoint, {
        method,
        body: isFormData ? body : JSON.stringify(body),
        ...options,
        headers
    });
}
```

## 2. Visual Standards (Tailwind CSS)

### Color Palette
The following colors should be extended in `tailwind.config.js`:

```javascript
theme: {
  extend: {
    colors: {
      baubau: {
        yellow: '#FFD700',
        'yellow-light': '#FFED4E',
        'yellow-dark': '#E6C200',
        blue: '#0066B3',
        'blue-light': '#1E88E5',
        'blue-dark': '#004D8C',
        green: '#2E7D32',
      },
      dark: {
        bg: '#0f172a',
        card: '#1e293b',
        border: '#334155',
      },
    },
  },
}
```

### Typography
- Primary Font: **Inter** (Google Fonts)

## 3. Storage Keys
To ensure compatibility across tools, use these standard keys:
- Authentication: `token`
- User Data: `user`
- Theme Mode: `theme` (values: `light`, `dark`)
