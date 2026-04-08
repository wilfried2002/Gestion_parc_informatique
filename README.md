# Gestion de Parc Informatique — Laravel API

Application complète de gestion IT : tickets, interventions, stock, utilisateurs.

## Stack technique
- **Backend** : Laravel 10 (PHP 8.1+)
- **Base de données** : MySQL 8.0+
- **Authentification** : JWT (tymon/jwt-auth v2)
- **Architecture** : Controller → Service → Repository → Model

---

## Installation

### 1. Créer le projet Laravel (dans le dossier parent)

```bash
composer create-project laravel/laravel gestion_intervention
cd gestion_intervention
```

### 2. Copier tous les fichiers générés
Remplacez les fichiers existants par ceux fournis dans ce projet.

### 3. Installer les dépendances

```bash
composer install
composer require tymon/jwt-auth:^2.0
```

### 4. Configuration de l'environnement

```bash
cp .env.example .env
php artisan key:generate
```

Éditez `.env` :
```env
DB_DATABASE=gestion_intervention
DB_USERNAME=root
DB_PASSWORD=votre_mot_de_passe
```

### 5. Générer la clé JWT

```bash
php artisan jwt:secret
```

### 6. Publier la configuration JWT

```bash
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
```

### 7. Créer la base de données MySQL

```sql
CREATE DATABASE gestion_intervention CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 8. Exécuter les migrations et seeders

```bash
php artisan migrate
php artisan db:seed
```

### 9. Créer le lien symbolique pour le stockage

```bash
php artisan storage:link
```

### 10. Démarrer le serveur

```bash
php artisan serve
```

L'API est accessible sur : `http://localhost:8000/api`

---

## Comptes par défaut

| Rôle          | Email                            | Mot de passe |
|---------------|----------------------------------|--------------|
| Administrateur | admin@gestion-it.local          | Admin@123!   |
| Technicien    | jean.dupont@gestion-it.local     | Tech@123!    |
| Utilisateur   | alice.durand@gestion-it.local    | User@123!    |

---

## Architecture des dossiers

```
app/
├── Http/
│   ├── Controllers/Api/
│   │   ├── AuthController.php
│   │   ├── TicketController.php
│   │   ├── InterventionController.php
│   │   ├── StockController.php
│   │   ├── UserController.php
│   │   ├── DashboardController.php
│   │   └── AttachmentController.php
│   ├── Middleware/
│   │   └── RoleMiddleware.php          ← Contrôle d'accès par rôle
│   └── Requests/
│       ├── Auth/
│       ├── Ticket/
│       ├── Intervention/
│       └── Stock/
├── Models/
│   ├── User.php          ← implements JWTSubject
│   ├── Role.php
│   ├── Ticket.php
│   ├── Intervention.php
│   ├── Stock.php
│   ├── Affectation.php
│   ├── Attachment.php    ← MorphTo (ticket/intervention)
│   └── ActivityLog.php
├── Services/
│   ├── AuthService.php
│   ├── TicketService.php
│   ├── InterventionService.php
│   ├── StockService.php
│   ├── UserService.php
│   ├── DashboardService.php
│   ├── AttachmentService.php
│   └── ActivityLogService.php
├── Repositories/
│   ├── Interfaces/
│   │   └── RepositoryInterface.php
│   ├── BaseRepository.php
│   ├── UserRepository.php
│   ├── TicketRepository.php
│   ├── InterventionRepository.php
│   └── StockRepository.php
└── Providers/
    └── AppServiceProvider.php          ← IoC Container bindings
```

---

## Routes API

### Authentification
| Méthode | Endpoint            | Auth | Description              |
|---------|---------------------|------|--------------------------|
| POST    | /api/auth/login     | Non  | Connexion JWT            |
| POST    | /api/auth/register  | Non  | Inscription              |
| GET     | /api/auth/me        | Oui  | Profil utilisateur       |
| POST    | /api/auth/refresh   | Oui  | Rafraîchir le token      |
| POST    | /api/auth/logout    | Oui  | Déconnexion              |

### Tickets
| Méthode | Endpoint                    | Rôles         | Description           |
|---------|-----------------------------|---------------|-----------------------|
| GET     | /api/tickets                | Tous          | Liste paginée         |
| POST    | /api/tickets                | Tous          | Créer un ticket       |
| GET     | /api/tickets/{id}           | Tous*         | Détail                |
| PUT     | /api/tickets/{id}           | Tous*         | Modifier              |
| DELETE  | /api/tickets/{id}           | Admin         | Supprimer             |
| POST    | /api/tickets/{id}/assign    | Admin         | Assigner technicien   |
| PATCH   | /api/tickets/{id}/status    | Admin/Tech    | Changer statut        |
| POST    | /api/tickets/{id}/attachments| Tous*        | Upload fichier        |

*Accès restreint à son propre ticket pour l'utilisateur simple.

### Interventions
| Méthode | Endpoint                        | Rôles      | Description          |
|---------|---------------------------------|------------|----------------------|
| GET     | /api/interventions              | Admin/Tech | Liste               |
| POST    | /api/interventions              | Admin/Tech | Créer               |
| GET     | /api/interventions/{id}         | Admin/Tech | Détail              |
| PUT     | /api/interventions/{id}         | Admin/Tech | Modifier            |
| DELETE  | /api/interventions/{id}         | Admin      | Supprimer           |
| PATCH   | /api/interventions/{id}/start   | Tech       | Démarrer            |
| PATCH   | /api/interventions/{id}/complete| Tech       | Clôturer + rapport  |

### Stock
| Méthode | Endpoint                                  | Rôles | Description        |
|---------|-------------------------------------------|-------|--------------------|
| GET     | /api/stocks                               | Tous  | Liste stock        |
| POST    | /api/stocks                               | Admin | Ajouter matériel   |
| PUT     | /api/stocks/{id}                          | Admin | Modifier           |
| DELETE  | /api/stocks/{id}                          | Admin | Supprimer          |
| POST    | /api/stocks/{id}/assign                   | Admin | Affecter           |
| PATCH   | /api/stocks/affectations/{id}/return      | Admin | Retour stock       |
| GET     | /api/stocks/low-stock                     | Admin | Alertes stock      |

### Dashboard
| Méthode | Endpoint               | Rôles | Description               |
|---------|------------------------|-------|---------------------------|
| GET     | /api/dashboard/stats   | Tous  | Statistiques adaptées au rôle |

---

## Format de réponse API

### Succès
```json
{
    "success": true,
    "message": "Ticket créé avec succès.",
    "data": { ... }
}
```

### Liste paginée
```json
{
    "success": true,
    "message": "Succès",
    "data": [ ... ],
    "meta": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 72
    },
    "links": {
        "first": "...",
        "last": "...",
        "prev": null,
        "next": "..."
    }
}
```

### Erreur
```json
{
    "success": false,
    "message": "Identifiants invalides ou compte désactivé.",
    "errors": { ... }
}
```

---

## Sécurité

- **JWT** : Tous les endpoints protégés nécessitent `Authorization: Bearer <token>`
- **Middleware `role`** : Contrôle fin par rôle (admin, technicien, utilisateur)
- **FormRequest** : Validation stricte sur toutes les entrées
- **SoftDelete** : Les suppressions sont logiques (soft delete)
- **Logs d'activité** : Toutes les actions critiques sont tracées dans `activity_logs`

---

## Intégration Chart.js (Dashboard)

L'endpoint `GET /api/dashboard/stats` retourne dans `data.charts` :
- `tickets_last_30_days` : Tableau `[{date, count}]` pour courbe temporelle
- `interventions_by_tech` : Tableau `[{technician, total, avg_duration}]` pour barres
- `tickets_by_priority` : Objet `{low, medium, high, critical}` pour camembert
