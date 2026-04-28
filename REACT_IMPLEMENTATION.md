# 📘 Guide d'Implémentation React - API Laravel JWT + CRUD Tasks

Guide complet pour intégrer l'API Laravel JWT avec gestion des tâches dans une application React utilisant Axios.

---

## 📋 Table des Matières

1. [Installation & Configuration](#installation--configuration)
2. [Configuration Axios](#configuration-axios)
3. [Services API](#services-api)
4. [Composants React](#composants-react)
5. [Exemples d'Utilisation](#exemples-dutilisation)
6. [Gestion des Erreurs](#gestion-des-erreurs)

---

## 🚀 Installation & Configuration

### 1. Installation des Dépendances

```bash
npm install axios react-router-dom
```

### 2. Structure du Projet React

```
src/
├── api/
│   └── axios.js              # Configuration Axios
├── services/
│   ├── authService.js        # Service d'authentification
│   └── taskService.js        # Service de gestion des tâches
├── context/
│   └── AuthContext.js        # Context d'authentification
├── components/
│   ├── auth/
│   │   ├── Login.jsx
│   │   └── Register.jsx
│   ├── tasks/
│   │   ├── TaskList.jsx      # Liste des tâches
│   │   ├── TaskForm.jsx      # Formulaire création/édition
│   │   ├── TaskItem.jsx      # Item de tâche
│   │   └── TaskDetails.jsx   # Détails d'une tâche
│   └── ProtectedRoute.jsx
└── App.jsx
```

---

## ⚙️ Configuration Axios

### `src/api/axios.js`

```javascript
import axios from 'axios';

// Configuration de base
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

## 🔐 Services API

### 1. Service d'Authentification - `src/services/authService.js`

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

### 2. Service des Tâches - `src/services/taskService.js`

```javascript
import api from '../api/axios';

const taskService = {
  // Récupérer toutes les tâches de l'utilisateur connecté
  getAllTasks: async () => {
    const response = await api.get('/tasks');
    return response.data;
  },

  // Récupérer une tâche spécifique par ID
  getTaskById: async (id) => {
    const response = await api.get(`/tasks/${id}`);
    return response.data;
  },

  // Créer une nouvelle tâche
  createTask: async (taskData) => {
    const response = await api.post('/tasks', taskData);
    return response.data;
  },

  // Mettre à jour une tâche existante
  updateTask: async (id, taskData) => {
    const response = await api.put(`/tasks/${id}`, taskData);
    return response.data;
  },

  // Supprimer une tâche
  deleteTask: async (id) => {
    const response = await api.delete(`/tasks/${id}`);
    return response.data;
  },
};

export default taskService;
```

---

## 🎨 Composants React

### 1. Context d'Authentification - `src/context/AuthContext.js`

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

### 2. Composant de Connexion - `src/components/auth/Login.jsx`

```javascript
import React, { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';

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
      navigate('/tasks');
    } catch (err) {
      setError(err.response?.data?.error || 'Identifiants incorrects');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="auth-container">
      <div className="auth-card">
        <h2>Connexion</h2>
        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <label>Email</label>
            <input
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="votre@email.com"
              required
            />
          </div>
          <div className="form-group">
            <label>Mot de passe</label>
            <input
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="••••••••"
              required
            />
          </div>
          {error && <div className="error-message">{error}</div>}
          <button type="submit" disabled={loading} className="btn-primary">
            {loading ? 'Connexion...' : 'Se connecter'}
          </button>
        </form>
        <p className="auth-link">
          Pas encore de compte ? <Link to="/register">S'inscrire</Link>
        </p>
      </div>
    </div>
  );
};

export default Login;
```

### 3. Liste des Tâches - `src/components/tasks/TaskList.jsx`

```javascript
import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import taskService from '../../services/taskService';
import TaskItem from './TaskItem';
import { useAuth } from '../../context/AuthContext';

const TaskList = () => {
  const [tasks, setTasks] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [filter, setFilter] = useState('all'); // all, pending, in_progress, done
  const { user, logout } = useAuth();

  useEffect(() => {
    fetchTasks();
  }, []);

  const fetchTasks = async () => {
    try {
      setLoading(true);
      const data = await taskService.getAllTasks();
      setTasks(data);
      setError('');
    } catch (err) {
      setError('Erreur lors du chargement des tâches');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (id) => {
    if (!window.confirm('Êtes-vous sûr de vouloir supprimer cette tâche ?')) {
      return;
    }

    try {
      await taskService.deleteTask(id);
      setTasks(tasks.filter((task) => task.id !== id));
    } catch (err) {
      alert('Erreur lors de la suppression de la tâche');
      console.error(err);
    }
  };

  const filteredTasks = tasks.filter((task) => {
    if (filter === 'all') return true;
    return task.statut === filter;
  });

  const handleLogout = async () => {
    await logout();
  };

  if (loading) {
    return <div className="loading">Chargement des tâches...</div>;
  }

  return (
    <div className="task-list-container">
      <header className="task-header">
        <div className="header-content">
          <h1>Mes Tâches</h1>
          <div className="user-info">
            <span>Bonjour, {user?.name}</span>
            <button onClick={handleLogout} className="btn-secondary">
              Déconnexion
            </button>
          </div>
        </div>
        <div className="actions">
          <Link to="/tasks/new" className="btn-primary">
            + Nouvelle Tâche
          </Link>
        </div>
      </header>

      <div className="filter-tabs">
        <button
          className={filter === 'all' ? 'active' : ''}
          onClick={() => setFilter('all')}
        >
          Toutes ({tasks.length})
        </button>
        <button
          className={filter === 'pending' ? 'active' : ''}
          onClick={() => setFilter('pending')}
        >
          En attente ({tasks.filter((t) => t.statut === 'pending').length})
        </button>
        <button
          className={filter === 'in_progress' ? 'active' : ''}
          onClick={() => setFilter('in_progress')}
        >
          En cours ({tasks.filter((t) => t.statut === 'in_progress').length})
        </button>
        <button
          className={filter === 'done' ? 'active' : ''}
          onClick={() => setFilter('done')}
        >
          Terminées ({tasks.filter((t) => t.statut === 'done').length})
        </button>
      </div>

      {error && <div className="error-message">{error}</div>}

      <div className="tasks-grid">
        {filteredTasks.length === 0 ? (
          <div className="empty-state">
            <p>Aucune tâche trouvée</p>
            <Link to="/tasks/new" className="btn-primary">
              Créer votre première tâche
            </Link>
          </div>
        ) : (
          filteredTasks.map((task) => (
            <TaskItem
              key={task.id}
              task={task}
              onDelete={handleDelete}
              onRefresh={fetchTasks}
            />
          ))
        )}
      </div>
    </div>
  );
};

export default TaskList;
```

### 4. Item de Tâche - `src/components/tasks/TaskItem.jsx`

```javascript
import React from 'react';
import { Link } from 'react-router-dom';

const TaskItem = ({ task, onDelete, onRefresh }) => {
  const getStatusBadge = (status) => {
    const badges = {
      pending: { label: 'En attente', className: 'badge-pending' },
      in_progress: { label: 'En cours', className: 'badge-in-progress' },
      done: { label: 'Terminée', className: 'badge-done' },
    };
    return badges[status] || badges.pending;
  };

  const statusBadge = getStatusBadge(task.statut);

  return (
    <div className="task-card">
      <div className="task-header">
        <h3>{task.title}</h3>
        <span className={`badge ${statusBadge.className}`}>
          {statusBadge.label}
        </span>
      </div>
      <p className="task-description">{task.description}</p>
      <div className="task-footer">
        <div className="task-actions">
          <Link to={`/tasks/${task.id}`} className="btn-view">
            Voir
          </Link>
          <Link to={`/tasks/${task.id}/edit`} className="btn-edit">
            Modifier
          </Link>
          <button
            onClick={() => onDelete(task.id)}
            className="btn-delete"
          >
            Supprimer
          </button>
        </div>
      </div>
    </div>
  );
};

export default TaskItem;
```

### 5. Formulaire de Tâche - `src/components/tasks/TaskForm.jsx`

```javascript
import React, { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import taskService from '../../services/taskService';

const TaskForm = () => {
  const [formData, setFormData] = useState({
    title: '',
    description: '',
    statut: 'pending',
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [isEdit, setIsEdit] = useState(false);
  const navigate = useNavigate();
  const { id } = useParams();

  useEffect(() => {
    if (id) {
      setIsEdit(true);
      fetchTask();
    }
  }, [id]);

  const fetchTask = async () => {
    try {
      const task = await taskService.getTaskById(id);
      setFormData({
        title: task.title,
        description: task.description,
        statut: task.statut,
      });
    } catch (err) {
      setError('Erreur lors du chargement de la tâche');
      console.error(err);
    }
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({
      ...prev,
      [name]: value,
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      if (isEdit) {
        await taskService.updateTask(id, formData);
      } else {
        await taskService.createTask(formData);
      }
      navigate('/tasks');
    } catch (err) {
      setError(err.response?.data?.message || 'Une erreur est survenue');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="form-container">
      <div className="form-card">
        <h2>{isEdit ? 'Modifier la tâche' : 'Nouvelle tâche'}</h2>
        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <label htmlFor="title">Titre *</label>
            <input
              type="text"
              id="title"
              name="title"
              value={formData.title}
              onChange={handleChange}
              placeholder="Titre de la tâche"
              required
            />
          </div>

          <div className="form-group">
            <label htmlFor="description">Description *</label>
            <textarea
              id="description"
              name="description"
              value={formData.description}
              onChange={handleChange}
              placeholder="Description détaillée de la tâche"
              rows="5"
              required
            />
          </div>

          <div className="form-group">
            <label htmlFor="statut">Statut *</label>
            <select
              id="statut"
              name="statut"
              value={formData.statut}
              onChange={handleChange}
              required
            >
              <option value="pending">En attente</option>
              <option value="in_progress">En cours</option>
              <option value="done">Terminée</option>
            </select>
          </div>

          {error && <div className="error-message">{error}</div>}

          <div className="form-actions">
            <button
              type="button"
              onClick={() => navigate('/tasks')}
              className="btn-secondary"
            >
              Annuler
            </button>
            <button type="submit" disabled={loading} className="btn-primary">
              {loading
                ? 'Enregistrement...'
                : isEdit
                ? 'Mettre à jour'
                : 'Créer'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default TaskForm;
```

### 6. Détails d'une Tâche - `src/components/tasks/TaskDetails.jsx`

```javascript
import React, { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import taskService from '../../services/taskService';

const TaskDetails = () => {
  const [task, setTask] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const { id } = useParams();
  const navigate = useNavigate();

  useEffect(() => {
    fetchTask();
  }, [id]);

  const fetchTask = async () => {
    try {
      setLoading(true);
      const data = await taskService.getTaskById(id);
      setTask(data);
      setError('');
    } catch (err) {
      setError('Tâche introuvable');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async () => {
    if (!window.confirm('Êtes-vous sûr de vouloir supprimer cette tâche ?')) {
      return;
    }

    try {
      await taskService.deleteTask(id);
      navigate('/tasks');
    } catch (err) {
      alert('Erreur lors de la suppression');
      console.error(err);
    }
  };

  const getStatusBadge = (status) => {
    const badges = {
      pending: { label: 'En attente', className: 'badge-pending' },
      in_progress: { label: 'En cours', className: 'badge-in-progress' },
      done: { label: 'Terminée', className: 'badge-done' },
    };
    return badges[status] || badges.pending;
  };

  if (loading) {
    return <div className="loading">Chargement...</div>;
  }

  if (error || !task) {
    return (
      <div className="error-container">
        <h2>{error}</h2>
        <Link to="/tasks" className="btn-primary">
          Retour à la liste
        </Link>
      </div>
    );
  }

  const statusBadge = getStatusBadge(task.statut);

  return (
    <div className="details-container">
      <div className="details-card">
        <div className="details-header">
          <Link to="/tasks" className="back-link">
            ← Retour
          </Link>
          <div className="details-actions">
            <Link to={`/tasks/${task.id}/edit`} className="btn-edit">
              Modifier
            </Link>
            <button onClick={handleDelete} className="btn-delete">
              Supprimer
            </button>
          </div>
        </div>

        <div className="details-content">
          <div className="details-title-row">
            <h1>{task.title}</h1>
            <span className={`badge ${statusBadge.className}`}>
              {statusBadge.label}
            </span>
          </div>

          <div className="details-section">
            <h3>Description</h3>
            <p>{task.description}</p>
          </div>

          <div className="details-meta">
            <div className="meta-item">
              <span className="meta-label">Créée le :</span>
              <span className="meta-value">
                {new Date(task.created_at).toLocaleDateString('fr-FR', {
                  year: 'numeric',
                  month: 'long',
                  day: 'numeric',
                  hour: '2-digit',
                  minute: '2-digit',
                })}
              </span>
            </div>
            <div className="meta-item">
              <span className="meta-label">Dernière modification :</span>
              <span className="meta-value">
                {new Date(task.updated_at).toLocaleDateString('fr-FR', {
                  year: 'numeric',
                  month: 'long',
                  day: 'numeric',
                  hour: '2-digit',
                  minute: '2-digit',
                })}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default TaskDetails;
```

### 7. Route Protégée - `src/components/ProtectedRoute.jsx`

```javascript
import React from 'react';
import { Navigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

const ProtectedRoute = ({ children }) => {
  const { user, loading } = useAuth();

  if (loading) {
    return <div className="loading">Chargement...</div>;
  }

  if (!user) {
    return <Navigate to="/login" replace />;
  }

  return children;
};

export default ProtectedRoute;
```

### 8. Application Principale - `src/App.jsx`

```javascript
import React from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from './context/AuthContext';
import Login from './components/auth/Login';
import Register from './components/auth/Register';
import TaskList from './components/tasks/TaskList';
import TaskForm from './components/tasks/TaskForm';
import TaskDetails from './components/tasks/TaskDetails';
import ProtectedRoute from './components/ProtectedRoute';
import './App.css';

function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <Routes>
          <Route path="/login" element={<Login />} />
          <Route path="/register" element={<Register />} />
          
          <Route
            path="/tasks"
            element={
              <ProtectedRoute>
                <TaskList />
              </ProtectedRoute>
            }
          />
          
          <Route
            path="/tasks/new"
            element={
              <ProtectedRoute>
                <TaskForm />
              </ProtectedRoute>
            }
          />
          
          <Route
            path="/tasks/:id"
            element={
              <ProtectedRoute>
                <TaskDetails />
              </ProtectedRoute>
            }
          />
          
          <Route
            path="/tasks/:id/edit"
            element={
              <ProtectedRoute>
                <TaskForm />
              </ProtectedRoute>
            }
          />
          
          <Route path="/" element={<Navigate to="/tasks" replace />} />
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  );
}

export default App;
```

---

## 🎨 Styles CSS de Base - `src/App.css`

```css
/* Variables CSS */
:root {
  --primary-color: #3b82f6;
  --secondary-color: #6b7280;
  --success-color: #10b981;
  --warning-color: #f59e0b;
  --danger-color: #ef4444;
  --background: #f9fafb;
  --card-background: #ffffff;
  --text-primary: #111827;
  --text-secondary: #6b7280;
  --border-color: #e5e7eb;
  --radius: 8px;
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  background-color: var(--background);
  color: var(--text-primary);
  line-height: 1.6;
}

/* Conteneurs */
.auth-container,
.form-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.auth-card,
.form-card {
  background: var(--card-background);
  padding: 40px;
  border-radius: var(--radius);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 450px;
}

.details-container {
  max-width: 800px;
  margin: 40px auto;
  padding: 20px;
}

.details-card {
  background: var(--card-background);
  border-radius: var(--radius);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  padding: 30px;
}

/* Formulaires */
.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: var(--text-primary);
}

.form-group input,
.form-group textarea,
.form-group select {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
  font-size: 14px;
  transition: border-color 0.2s;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
  outline: none;
  border-color: var(--primary-color);
}

.form-group textarea {
  resize: vertical;
  font-family: inherit;
}

/* Boutons */
.btn-primary,
.btn-secondary,
.btn-edit,
.btn-delete,
.btn-view {
  padding: 10px 20px;
  border: none;
  border-radius: var(--radius);
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  text-decoration: none;
  display: inline-block;
}

.btn-primary {
  background-color: var(--primary-color);
  color: white;
}

.btn-primary:hover {
  background-color: #2563eb;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background-color: var(--secondary-color);
  color: white;
}

.btn-secondary:hover {
  background-color: #4b5563;
}

.btn-edit {
  background-color: var(--warning-color);
  color: white;
}

.btn-edit:hover {
  background-color: #d97706;
}

.btn-delete {
  background-color: var(--danger-color);
  color: white;
}

.btn-delete:hover {
  background-color: #dc2626;
}

.btn-view {
  background-color: var(--primary-color);
  color: white;
}

/* Messages d'erreur */
.error-message {
  background-color: #fee2e2;
  color: var(--danger-color);
  padding: 12px;
  border-radius: var(--radius);
  margin-bottom: 20px;
  font-size: 14px;
}

/* Liste des tâches */
.task-list-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.task-header {
  margin-bottom: 30px;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.header-content h1 {
  font-size: 32px;
  color: var(--text-primary);
}

.user-info {
  display: flex;
  align-items: center;
  gap: 15px;
}

/* Filtres */
.filter-tabs {
  display: flex;
  gap: 10px;
  margin-bottom: 30px;
  flex-wrap: wrap;
}

.filter-tabs button {
  padding: 8px 16px;
  border: 1px solid var(--border-color);
  background: white;
  border-radius: var(--radius);
  cursor: pointer;
  transition: all 0.2s;
}

.filter-tabs button.active {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

/* Grille de tâches */
.tasks-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
}

.task-card {
  background: var(--card-background);
  border-radius: var(--radius);
  padding: 20px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s, box-shadow 0.2s;
}

.task-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.task-header {
  display: flex;
  justify-content: space-between;
  align-items: start;
  margin-bottom: 12px;
}

.task-card h3 {
  font-size: 18px;
  color: var(--text-primary);
  margin: 0;
}

.task-description {
  color: var(--text-secondary);
  margin-bottom: 20px;
  line-height: 1.5;
}

.task-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.task-actions button,
.task-actions a {
  flex: 1;
  min-width: 80px;
  text-align: center;
}

/* Badges de statut */
.badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

.badge-pending {
  background-color: #fef3c7;
  color: #92400e;
}

.badge-in-progress {
  background-color: #dbeafe;
  color: #1e40af;
}

.badge-done {
  background-color: #d1fae5;
  color: #065f46;
}

/* État vide */
.empty-state {
  text-align: center;
  padding: 60px 20px;
}

.empty-state p {
  color: var(--text-secondary);
  margin-bottom: 20px;
  font-size: 18px;
}

/* Loading */
.loading {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 200px;
  font-size: 18px;
  color: var(--text-secondary);
}

/* Détails de tâche */
.details-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
}

.back-link {
  color: var(--primary-color);
  text-decoration: none;
  font-weight: 500;
}

.back-link:hover {
  text-decoration: underline;
}

.details-actions {
  display: flex;
  gap: 10px;
}

.details-title-row {
  display: flex;
  justify-content: space-between;
  align-items: start;
  margin-bottom: 30px;
}

.details-title-row h1 {
  font-size: 32px;
  margin: 0;
}

.details-section {
  margin-bottom: 30px;
}

.details-section h3 {
  margin-bottom: 12px;
  color: var(--text-primary);
}

.details-section p {
  color: var(--text-secondary);
  line-height: 1.6;
}

.details-meta {
  display: flex;
  gap: 30px;
  padding-top: 20px;
  border-top: 1px solid var(--border-color);
}

.meta-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.meta-label {
  font-size: 12px;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.meta-value {
  font-size: 14px;
  color: var(--text-primary);
}

/* Form actions */
.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 30px;
}

/* Auth link */
.auth-link {
  text-align: center;
  margin-top: 20px;
  color: var(--text-secondary);
}

.auth-link a {
  color: var(--primary-color);
  text-decoration: none;
}

.auth-link a:hover {
  text-decoration: underline;
}

/* Responsive */
@media (max-width: 768px) {
  .tasks-grid {
    grid-template-columns: 1fr;
  }

  .header-content {
    flex-direction: column;
    align-items: flex-start;
    gap: 15px;
  }

  .details-meta {
    flex-direction: column;
    gap: 15px;
  }

  .task-actions {
    flex-direction: column;
  }

  .task-actions button,
  .task-actions a {
    width: 100%;
  }
}
```

---

## 💡 Exemples d'Utilisation

### 1. Créer une Tâche

```javascript
// Exemple simple
const createNewTask = async () => {
  try {
    const newTask = await taskService.createTask({
      title: 'Apprendre React',
      description: 'Suivre le tutoriel complet sur React',
      statut: 'pending'
    });
    console.log('Tâche créée:', newTask);
  } catch (error) {
    console.error('Erreur:', error);
  }
};
```

### 2. Mettre à Jour une Tâche

```javascript
const updateTaskStatus = async (taskId) => {
  try {
    const updatedTask = await taskService.updateTask(taskId, {
      statut: 'done'
    });
    console.log('Tâche mise à jour:', updatedTask);
  } catch (error) {
    console.error('Erreur:', error);
  }
};
```

### 3. Charger les Tâches avec Pagination (Future amélioration)

```javascript
// Si vous ajoutez la pagination côté backend
const loadTasksWithPagination = async (page = 1) => {
  try {
    const response = await api.get(`/tasks?page=${page}`);
    return response.data;
  } catch (error) {
    console.error('Erreur:', error);
  }
};
```

---

## 🔧 Gestion des Erreurs

### Wrapper de Gestion d'Erreurs

```javascript
// src/utils/errorHandler.js
export const handleApiError = (error) => {
  if (error.response) {
    // Erreur de réponse du serveur
    switch (error.response.status) {
      case 400:
        return 'Requête invalide';
      case 401:
        return 'Non authentifié. Veuillez vous reconnecter.';
      case 403:
        return 'Accès refusé';
      case 404:
        return 'Ressource non trouvée';
      case 422:
        return error.response.data.message || 'Erreur de validation';
      case 500:
        return 'Erreur du serveur. Veuillez réessayer plus tard.';
      default:
        return 'Une erreur est survenue';
    }
  } else if (error.request) {
    return 'Aucune réponse du serveur. Vérifiez votre connexion.';
  } else {
    return error.message || 'Une erreur inattendue est survenue';
  }
};

// Utilisation dans les composants
import { handleApiError } from '../utils/errorHandler';

try {
  // Requête API
} catch (err) {
  const errorMessage = handleApiError(err);
  setError(errorMessage);
}
```

---

## 🚀 Configuration de Production

### Variables d'Environnement

Créez un fichier `.env` :

```bash
# Pour Vite
VITE_API_URL=https://votre-api.com/api

# Pour Create React App
REACT_APP_API_URL=https://votre-api.com/api
```

Mise à jour de `src/api/axios.js` :

```javascript
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  // ou
  baseURL: process.env.REACT_APP_API_URL || 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});
```

---

## 📊 Résumé des Endpoints

| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| POST | `/register` | Inscription | Non |
| POST | `/login` | Connexion | Non |
| GET | `/me` | Profil utilisateur | Oui |
| GET | `/logout` | Déconnexion | Oui |
| GET | `/tasks` | Liste des tâches | Oui |
| POST | `/tasks` | Créer une tâche | Oui |
| GET | `/tasks/{id}` | Détails d'une tâche | Oui |
| PUT | `/tasks/{id}` | Modifier une tâche | Oui |
| DELETE | `/tasks/{id}` | Supprimer une tâche | Oui |

---

## 🧪 Tests avec Axios

```javascript
// Test rapide dans la console du navigateur
// ou dans un composant de test

// 1. Test de connexion
const testLogin = async () => {
  try {
    const response = await api.post('/login', {
      email: 'test@example.com',
      password: 'password'
    });
    console.log('Login successful:', response.data);
  } catch (error) {
    console.error('Login failed:', error.response?.data);
  }
};

// 2. Test de création de tâche
const testCreateTask = async () => {
  try {
    const response = await api.post('/tasks', {
      title: 'Test Task',
      description: 'This is a test task',
      statut: 'pending'
    });
    console.log('Task created:', response.data);
  } catch (error) {
    console.error('Task creation failed:', error.response?.data);
  }
};
```

---

## 🎯 Checklist de Démarrage

- [ ] Installer les dépendances : `npm install axios react-router-dom`
- [ ] Créer la structure de dossiers
- [ ] Configurer Axios avec intercepteurs
- [ ] Créer les services (auth & tasks)
- [ ] Implémenter le Context d'authentification
- [ ] Créer les composants d'authentification
- [ ] Créer les composants CRUD des tâches
- [ ] Configurer les routes dans App.jsx
- [ ] Ajouter les styles CSS
- [ ] Tester l'authentification
- [ ] Tester le CRUD complet
- [ ] Gérer les erreurs
- [ ] Préparer pour la production

---

## 📞 API Endpoints Détaillés

### Tâches

#### GET /tasks
Récupère toutes les tâches de l'utilisateur connecté.

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
[
  {
    "id": 1,
    "title": "Formation Laravel",
    "description": "Faire une formation approfondie sur le framework Laravel",
    "statut": "pending",
    "user_id": 3,
    "created_at": "2026-04-28T09:56:15.000000Z",
    "updated_at": "2026-04-28T09:56:15.000000Z"
  }
]
```

#### POST /tasks
Crée une nouvelle tâche.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
  "title": "Nouvelle tâche",
  "description": "Description de la tâche",
  "statut": "pending"
}
```

**Response (201):**
```json
{
  "id": 2,
  "title": "Nouvelle tâche",
  "description": "Description de la tâche",
  "statut": "pending",
  "user_id": 3,
  "created_at": "2026-04-28T10:30:00.000000Z",
  "updated_at": "2026-04-28T10:30:00.000000Z"
}
```

#### GET /tasks/{id}
Récupère une tâche spécifique.

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "id": 1,
  "title": "Formation Laravel",
  "description": "Faire une formation approfondie sur le framework Laravel",
  "statut": "pending",
  "user_id": 3,
  "created_at": "2026-04-28T09:56:15.000000Z",
  "updated_at": "2026-04-28T09:56:15.000000Z"
}
```

#### PUT /tasks/{id}
Met à jour une tâche existante.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
  "title": "Titre modifié",
  "description": "Description modifiée",
  "statut": "in_progress"
}
```

**Response (200):**
```json
{
  "id": 1,
  "title": "Titre modifié",
  "description": "Description modifiée",
  "statut": "in_progress",
  "user_id": 3,
  "created_at": "2026-04-28T09:56:15.000000Z",
  "updated_at": "2026-04-28T10:45:00.000000Z"
}
```

#### DELETE /tasks/{id}
Supprime une tâche.

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "message": "Deleted"
}
```

---

## 🔒 Bonnes Pratiques de Sécurité

1. **Stockage du Token**
   - Utilisez `localStorage` pour le développement
   - En production, considérez `httpOnly cookies` pour plus de sécurité

2. **Validation des Entrées**
   - Validez toujours les données côté client ET serveur
   - Utilisez des bibliothèques comme Yup ou Zod pour la validation

3. **Gestion des Tokens Expirés**
   - Implémentez un système de refresh token
   - Redirigez automatiquement vers la page de login

4. **HTTPS**
   - Toujours utiliser HTTPS en production
   - Ne jamais envoyer de tokens via HTTP non sécurisé

---

**Dernière mise à jour : 28 avril 2026**

Cette documentation vous permet d'implémenter rapidement et efficacement votre API Laravel JWT dans une application React avec toutes les fonctionnalités CRUD pour la gestion des tâches.
