<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Parc Informatique</title>
    <link href="/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="/vendor/fontawesome/css/all.min.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
</head>
<body>

<!-- ═══════════════════════════════════════════════════════════
     SIDEBAR
════════════════════════════════════════════════════════════ -->
<aside id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="fas fa-server"></i></div>
        <span class="brand-name">Parc IT</span>
    </div>

    <nav class="sidebar-nav">
        <a href="#" class="nav-link-item" data-page="dashboard">
            <i class="fas fa-tachometer-alt"></i><span>Tableau de bord</span>
        </a>
        <a href="#" class="nav-link-item" data-page="tickets">
            <i class="fas fa-ticket-alt"></i><span>Tickets</span>
        </a>
        <a href="#" class="nav-link-item" data-page="interventions">
            <i class="fas fa-tools"></i><span>Interventions</span>
        </a>
        <a href="#" class="nav-link-item" data-page="stock">
            <i class="fas fa-boxes"></i><span>Stock Matériel</span>
        </a>
        <a href="#" class="nav-link-item admin-only d-none" data-page="users">
            <i class="fas fa-users-cog"></i><span>Utilisateurs</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar" id="nav-avatar">A</div>
            <div class="user-details">
                <div class="user-name" id="nav-username">—</div>
                <div class="user-role" id="nav-role">—</div>
            </div>
        </div>
        <button class="btn-logout" id="btn-logout" title="Déconnexion">
            <i class="fas fa-sign-out-alt"></i>
        </button>
    </div>
</aside>

<!-- ═══════════════════════════════════════════════════════════
     MAIN
════════════════════════════════════════════════════════════ -->
<div id="main-wrapper">

    <!-- TOP HEADER -->
    <header id="top-header">
        <button id="sidebar-toggle" class="btn-icon d-md-none">
            <i class="fas fa-bars"></i>
        </button>
        <div id="page-title" class="header-title">Tableau de bord</div>
        <div class="header-right">
            <span class="header-user-badge" id="header-role-badge"></span>
            <span class="header-username" id="header-username"></span>

            <!-- ── Cloche notifications ── -->
            <div class="notif-bell-wrap" id="notif-bell-wrap">
                <button class="notif-bell-btn" id="notif-bell-btn" title="Notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notif-badge d-none" id="notif-badge">0</span>
                </button>

                <!-- Dropdown -->
                <div class="notif-dropdown d-none" id="notif-dropdown">
                    <div class="notif-dropdown-header">
                        <span class="notif-dropdown-title">
                            <i class="fas fa-bell me-2"></i>Notifications
                        </span>
                        <button class="notif-read-all-btn" id="notif-read-all" title="Tout marquer comme lu">
                            <i class="fas fa-check-double"></i>
                        </button>
                    </div>
                    <div class="notif-list" id="notif-list">
                        <div class="notif-empty">
                            <i class="fas fa-bell-slash"></i>
                            <p>Aucune notification</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- CONTENT -->
    <main id="content">

        <!-- ── DASHBOARD ───────────────────────────────────────────────── -->
        <div id="page-dashboard" class="page d-none"></div>

        <!-- ── TICKETS ─────────────────────────────────────────────────── -->
        <div id="page-tickets" class="page d-none">
            <div class="page-header">
                <h2 class="page-heading"><i class="fas fa-ticket-alt me-2 text-primary"></i>Tickets</h2>
                <button class="btn btn-primary btn-sm" id="btn-new-ticket">
                    <i class="fas fa-plus me-1"></i>Nouveau ticket
                </button>
            </div>
            <!-- Filters -->
            <div class="filter-bar">
                <input type="text" class="form-control form-control-sm" id="ticket-search" placeholder="Rechercher...">
                <select class="form-select form-select-sm" id="ticket-filter-status">
                    <option value="">Tous les statuts</option>
                    <option value="open">Ouvert</option>
                    <option value="assigned">Assigné</option>
                    <option value="in_progress">En cours</option>
                    <option value="resolved">Résolu</option>
                    <option value="closed">Clôturé</option>
                </select>
                <select class="form-select form-select-sm" id="ticket-filter-priority">
                    <option value="">Toutes priorités</option>
                    <option value="low">Faible</option>
                    <option value="medium">Moyenne</option>
                    <option value="high">Haute</option>
                    <option value="critical">Critique</option>
                </select>
                <select class="form-select form-select-sm" id="ticket-filter-category">
                    <option value="">Toutes catégories</option>
                    <option value="materiel">Matériel</option>
                    <option value="logiciel">Logiciel</option>
                    <option value="reseau">Réseau</option>
                    <option value="securite">Sécurité</option>
                    <option value="autre">Autre</option>
                </select>
                <button class="btn btn-outline-secondary btn-sm" id="ticket-btn-filter">
                    <i class="fas fa-filter me-1"></i>Filtrer
                </button>
            </div>
            <div id="tickets-table-wrap"></div>
        </div>

        <!-- ── INTERVENTIONS ───────────────────────────────────────────── -->
        <div id="page-interventions" class="page d-none">
            <div class="page-header">
                <h2 class="page-heading"><i class="fas fa-tools me-2 text-warning"></i>Interventions</h2>
                <button class="btn btn-primary btn-sm admin-tech-only" id="btn-new-intervention">
                    <i class="fas fa-plus me-1"></i>Nouvelle intervention
                </button>
            </div>
            <div class="filter-bar">
                <input type="text" class="form-control form-control-sm" id="interv-search" placeholder="Rechercher...">
                <select class="form-select form-select-sm" id="interv-filter-status">
                    <option value="">Tous les statuts</option>
                    <option value="planned">Planifiée</option>
                    <option value="in_progress">En cours</option>
                    <option value="completed">Terminée</option>
                    <option value="cancelled">Annulée</option>
                </select>
                <input type="date" class="form-control form-control-sm" id="interv-date-from" title="Date début">
                <input type="date" class="form-control form-control-sm" id="interv-date-to" title="Date fin">
                <button class="btn btn-outline-secondary btn-sm" id="interv-btn-filter">
                    <i class="fas fa-filter me-1"></i>Filtrer
                </button>
            </div>
            <div id="interventions-table-wrap"></div>
        </div>

        <!-- ── STOCK ───────────────────────────────────────────────────── -->
        <div id="page-stock" class="page d-none">
            <div class="page-header">
                <h2 class="page-heading"><i class="fas fa-boxes me-2 text-success"></i>Stock Matériel</h2>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-danger btn-sm admin-only d-none" id="btn-low-stock">
                        <i class="fas fa-exclamation-triangle me-1"></i>Stock bas
                    </button>
                    <button class="btn btn-primary btn-sm admin-only d-none" id="btn-new-stock">
                        <i class="fas fa-plus me-1"></i>Ajouter matériel
                    </button>
                </div>
            </div>
            <div class="filter-bar">
                <input type="text" class="form-control form-control-sm" id="stock-search" placeholder="Rechercher...">
                <select class="form-select form-select-sm" id="stock-filter-category">
                    <option value="">Toutes catégories</option>
                    <option value="ordinateur">Ordinateur</option>
                    <option value="imprimante">Imprimante</option>
                    <option value="serveur">Serveur</option>
                    <option value="reseau">Réseau</option>
                    <option value="peripherique">Périphérique</option>
                    <option value="consommable">Consommable</option>
                    <option value="autre">Autre</option>
                </select>
                <select class="form-select form-select-sm" id="stock-filter-status">
                    <option value="">Tous statuts</option>
                    <option value="disponible">Disponible</option>
                    <option value="affecte">Affecté</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="hors_service">Hors service</option>
                </select>
                <button class="btn btn-outline-secondary btn-sm" id="stock-btn-filter">
                    <i class="fas fa-filter me-1"></i>Filtrer
                </button>
            </div>
            <div id="stock-table-wrap"></div>
        </div>

        <!-- ── USERS ───────────────────────────────────────────────────── -->
        <div id="page-users" class="page d-none">
            <div class="page-header">
                <h2 class="page-heading"><i class="fas fa-users-cog me-2 text-info"></i>Gestion Utilisateurs</h2>
                <button class="btn btn-primary btn-sm" id="btn-new-user">
                    <i class="fas fa-user-plus me-1"></i>Nouvel utilisateur
                </button>
            </div>
            <div class="filter-bar">
                <input type="text" class="form-control form-control-sm" id="user-search" placeholder="Rechercher...">
                <select class="form-select form-select-sm" id="user-filter-role">
                    <option value="">Tous les rôles</option>
                    <option value="1">Administrateur</option>
                    <option value="2">Technicien</option>
                    <option value="3">Utilisateur</option>
                </select>
                <select class="form-select form-select-sm" id="user-filter-active">
                    <option value="">Tous</option>
                    <option value="1">Actifs</option>
                    <option value="0">Inactifs</option>
                </select>
                <button class="btn btn-outline-secondary btn-sm" id="user-btn-filter">
                    <i class="fas fa-filter me-1"></i>Filtrer
                </button>
            </div>
            <div id="users-table-wrap"></div>
        </div>

    </main>
</div>

<!-- ═══════════════════════════════════════════════════════════
     TOAST NOTIFICATIONS
════════════════════════════════════════════════════════════ -->
<div id="toast-container" aria-live="polite" aria-atomic="true"></div>

<!-- ═══════════════════════════════════════════════════════════
     GLOBAL MODAL (réutilisable)
════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="globalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="globalModalTitle">Titre</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="globalModalBody"></div>
            <div class="modal-footer" id="globalModalFooter">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="globalModalConfirm">Confirmer</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     CONFIRM MODAL
════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Confirmation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="confirmModalBody">Êtes-vous sûr de vouloir effectuer cette action ?</div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger btn-sm" id="confirmModalOk">Confirmer</button>
            </div>
        </div>
    </div>
</div>

<!-- Overlay sidebar mobile -->
<div id="sidebar-overlay"></div>

<!-- Scripts -->
<script src="/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/js/api.js"></script>
<script src="/js/ui.js"></script>
<script src="/js/pages/dashboard.js"></script>
<script src="/js/pages/tickets.js"></script>
<script src="/js/pages/interventions.js"></script>
<script src="/js/pages/stock.js"></script>
<script src="/js/pages/users.js"></script>
<script src="/js/notifications.js"></script>
<script src="/js/app.js"></script>
</body>
</html>
