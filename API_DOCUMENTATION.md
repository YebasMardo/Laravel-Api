# Documentation API - Laravel JWT Authentication

## 📋 Table des Matières
1. [Introduction](#introduction)
2. [Configuration](#configuration)
3. [Endpoints Disponibles](#endpoints-disponibles)
4. [Intégration avec React](#intégration-avec-react)
5. [Exemples de Code React](#exemples-de-code-react)
6. [Gestion des Erreurs](#gestion-des-erreurs)

---

## Introduction

Cette API Laravel utilise JWT (JSON Web Tokens) pour l'authentification. Elle fournit des endpoints pour l'inscription, la connexion, la récupération du profil utilisateur et la déconnexion.

**Base URL**: `http://localhost:8000/api`

---

## Configuration

### 1. Configuration CORS

Vérifiez que le fichier `config/cors.php` autorise votre application React :

```php
'paths' => ['api/*'],
'allowed_origins' => ['http://localhost:3000', 'http://localhost:5173'],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'exposed_headers' => ['Authorization'],
'supports_credentials' => true,
```

### 2. Lancer le serveur Laravel

```bash
php artisan serve
```

L'API sera accessible sur `http://localhost:8000`

---

## Endpoints Disponibles

### 🔓 Endpoints Publics (sans authentification)

#### 1. **Inscription** - `POST /api/register`

Crée un nouveau compte utilisateur.

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123"
}
```

**Response (200):**
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "user",
    "created_at": "2026-04-24T10:30:00Z",
    "updated_at": "2026-04-24T10:30:00Z"
  },
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
}
```

---

#### 2. **Connexion** - `POST /api/login`

Authentifie un utilisateur existant.

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response (200):**
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "user"
  },
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
}
```

**Response (401) - Erreur:**
```json
{
  "error": "Unauthorized"
}
```

---

### 🔒 Endpoints Protégés (authentification requise)

**Header requis:**
```
Authorization: Bearer {token}
```

#### 3. **Profil Utilisateur** - `GET /api/me`

Récupère les informations de l'utilisateur connecté.

**Response (200):**
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "role": "user",
  "created_at": "2026-04-24T10:30:00Z",
  "updated_at": "2026-04-24T10:30:00Z"
}
```

---

#### 4. **Déconnexion** - `GET /api/logout`

Invalide le token JWT actuel.

**Response (200):**
```json
{
  "message": "Logged out"
}
```

---

#### 5. **Dashboard** - `GET /api/dashboard`

Endpoint du tableau de bord utilisateur.

**Response (200):**
```json
{
  "message": "User Dashboard"
}
```

---

#### 6. **Admin Panel** - `GET /api/admin`

Endpoint réservé aux administrateurs (role: admin).

**Response (200):**
```json
{
  "message": "Admin Panel"
}
```

**Response (403) - Accès refusé:**
```json
{
  "error": "Forbidden"
}
```

---

## Intégration avec React

### 1. Installation des dépendances

```bash
npm install axios
```

### 2. Configuration d'Axios

Créez un fichier `src/api/axios.js` :

```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Intercepteur pour ajouter le token à chaque requête
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Intercepteur pour gérer les erreurs d'authentification
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Token expiré ou invalide
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default api;
```

---

## Exemples de Code React

### 1. Service d'Authentification

Créez `src/services/authService.js` :

```javascript
import api from '../api/axios';

const authService = {
  // Inscription
  register: async (name, email, password) => {
    const response = await api.post('/register', { name, email, password });
    if (response.data.token) {
      localStorage.setItem('token', response.data.token);
      localStorage.setItem('user', JSON.stringify(response.data.user));
    }
    return response.data;
  },

  // Connexion
  login: async (email, password) => {
    const response = await api.post('/login', { email, password });
    if (response.data.token) {
      localStorage.setItem('token', response.data.token);
      localStorage.setItem('user', JSON.stringify(response.data.user));
    }
    return response.data;
  },

  // Déconnexion
  logout: async () => {
    try {
      await api.get('/logout');
    } finally {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
    }
  },

  // Récupérer l'utilisateur connecté
  getCurrentUser: () => {
    const user = localStorage.getItem('user');
    return user ? JSON.parse(user) : null;
  },

  // Récupérer le profil
  getProfile: async () => {
    const response = await api.get('/me');
    localStorage.setItem('user', JSON.stringify(response.data));
    return response.data;
  },

  // Vérifier si l'utilisateur est connecté
  isAuthenticated: () => {
    return !!localStorage.getItem('token');
  },
};

export default authService;
```

---

### 2. Context d'Authentification

Créez `src/context/AuthContext.js` :

```javascript
import React, { createContext, useState, useContext, useEffect } from 'react';
import authService from '../services/authService';

const AuthContext = createContext(null);

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const user = authService.getCurrentUser();
    if (user) {
      setUser(user);
    }
    setLoading(false);
  }, []);

  const login = async (email, password) => {
    const data = await authService.login(email, password);
    setUser(data.user);
    return data;
  };

  const register = async (name, email, password) => {
    const data = await authService.register(name, email, password);
    setUser(data.user);
    return data;
  };

  const logout = async () => {
    await authService.logout();
    setUser(null);
  };

  const value = {
    user,
    login,
    register,
    logout,
    isAuthenticated: !!user,
    loading,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};
```

---

### 3. Composant de Connexion

`src/components/Login.jsx` :

```javascript
import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

const Login = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const { login } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      await login(email, password);
      navigate('/dashboard');
    } catch (err) {
      setError(err.response?.data?.error || 'Une erreur est survenue');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="login-container">
      <h2>Connexion</h2>
      <form onSubmit={handleSubmit}>
        <div>
          <label>Email:</label>
          <input
            type="email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            required
          />
        </div>
        <div>
          <label>Mot de passe:</label>
          <input
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            required
          />
        </div>
        {error && <div className="error">{error}</div>}
        <button type="submit" disabled={loading}>
          {loading ? 'Connexion...' : 'Se connecter'}
        </button>
      </form>
    </div>
  );
};

export default Login;
```

---

### 4. Composant d'Inscription

`src/components/Register.jsx` :

```javascript
import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

const Register = () => {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    password: '',
  });
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const { register } = useAuth();
  const navigate = useNavigate();

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value,
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      await register(formData.name, formData.email, formData.password);
      navigate('/dashboard');
    } catch (err) {
      setError(err.response?.data?.error || 'Une erreur est survenue');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="register-container">
      <h2>Inscription</h2>
      <form onSubmit={handleSubmit}>
        <div>
          <label>Nom:</label>
          <input
            type="text"
            name="name"
            value={formData.name}
            onChange={handleChange}
            required
          />
        </div>
        <div>
          <label>Email:</label>
          <input
            type="email"
            name="email"
            value={formData.email}
            onChange={handleChange}
            required
          />
        </div>
        <div>
          <label>Mot de passe:</label>
          <input
            type="password"
            name="password"
            value={formData.password}
            onChange={handleChange}
            required
          />
        </div>
        {error && <div className="error">{error}</div>}
        <button type="submit" disabled={loading}>
          {loading ? 'Inscription...' : "S'inscrire"}
        </button>
      </form>
    </div>
  );
};

export default Register;
```

---

### 5. Route Protégée

`src/components/ProtectedRoute.jsx` :

```javascript
import React from 'react';
import { Navigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

const ProtectedRoute = ({ children, adminOnly = false }) => {
  const { user, loading } = useAuth();

  if (loading) {
    return <div>Chargement...</div>;
  }

  if (!user) {
    return <Navigate to="/login" replace />;
  }

  if (adminOnly && user.role !== 'admin') {
    return <Navigate to="/dashboard" replace />;
  }

  return children;
};

export default ProtectedRoute;
```

---

### 6. Dashboard Utilisateur

`src/components/Dashboard.jsx` :

```javascript
import React, { useEffect, useState } from 'react';
import { useAuth } from '../context/AuthContext';
import api from '../api/axios';

const Dashboard = () => {
  const { user, logout } = useAuth();
  const [dashboardData, setDashboardData] = useState('');

  useEffect(() => {
    const fetchDashboard = async () => {
      try {
        const response = await api.get('/dashboard');
        setDashboardData(response.data.message);
      } catch (error) {
        console.error('Erreur:', error);
      }
    };

    fetchDashboard();
  }, []);

  const handleLogout = async () => {
    await logout();
    window.location.href = '/login';
  };

  return (
    <div className="dashboard">
      <h1>Tableau de bord</h1>
      <div className="user-info">
        <p><strong>Nom:</strong> {user?.name}</p>
        <p><strong>Email:</strong> {user?.email}</p>
        <p><strong>Rôle:</strong> {user?.role}</p>
      </div>
      <div className="dashboard-content">
        <p>{dashboardData}</p>
      </div>
      <button onClick={handleLogout}>Se déconnecter</button>
    </div>
  );
};

export default Dashboard;
```

---

### 7. Configuration des Routes

`src/App.jsx` :

```javascript
import React from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from './context/AuthContext';
import Login from './components/Login';
import Register from './components/Register';
import Dashboard from './components/Dashboard';
import ProtectedRoute from './components/ProtectedRoute';

function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <Routes>
          <Route path="/login" element={<Login />} />
          <Route path="/register" element={<Register />} />
          <Route
            path="/dashboard"
            element={
              <ProtectedRoute>
                <Dashboard />
              </ProtectedRoute>
            }
          />
          <Route path="/" element={<Navigate to="/dashboard" replace />} />
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  );
}

export default App;
```

---

## Gestion des Erreurs

### Codes de Statut HTTP

| Code | Description |
|------|-------------|
| 200  | Succès |
| 401  | Non authentifié (token invalide ou expiré) |
| 403  | Accès refusé (permissions insuffisantes) |
| 404  | Ressource non trouvée |
| 422  | Erreur de validation |
| 500  | Erreur serveur |

### Exemple de Gestion d'Erreurs

```javascript
try {
  const response = await api.post('/login', { email, password });
  // Traiter la réponse
} catch (error) {
  if (error.response) {
    // Le serveur a répondu avec un code d'erreur
    switch (error.response.status) {
      case 401:
        console.error('Identifiants incorrects');
        break;
      case 422:
        console.error('Erreur de validation:', error.response.data);
        break;
      case 500:
        console.error('Erreur serveur');
        break;
      default:
        console.error('Une erreur est survenue');
    }
  } else if (error.request) {
    // La requête a été envoyée mais pas de réponse
    console.error('Pas de réponse du serveur');
  } else {
    // Erreur lors de la configuration de la requête
    console.error('Erreur:', error.message);
  }
}
```

---

## 🔐 Sécurité et Bonnes Pratiques

1. **Ne jamais stocker le token dans les cookies non-sécurisés**
2. **Utiliser HTTPS en production**
3. **Implémenter un système de refresh token pour les sessions longues**
4. **Valider et sanitiser toutes les entrées utilisateur**
5. **Implémenter un rate limiting sur les endpoints sensibles**
6. **Utiliser des variables d'environnement pour les URLs**

### Variables d'Environnement React (.env)

```bash
VITE_API_URL=http://localhost:8000/api
# ou pour Create React App
REACT_APP_API_URL=http://localhost:8000/api
```

Utilisation :

```javascript
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL, // Vite
  // ou
  baseURL: process.env.REACT_APP_API_URL, // Create React App
});
```

---

## 📝 Notes Supplémentaires

- Le token JWT a une durée de validité par défaut (configurée dans `config/jwt.php`)
- Pensez à gérer le rafraîchissement du token pour les sessions longues
- Testez votre API avec Postman ou Insomnia avant l'intégration React
- Activez les logs Laravel pour débugger les problèmes d'authentification

---

## 🚀 Démarrage Rapide

### Backend (Laravel)
```bash
php artisan serve
```

### Frontend (React)
```bash
npm install
npm run dev
```

---

**Créé le 24 avril 2026** | Laravel JWT API Documentation
