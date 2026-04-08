/**
 * App — point d'entrée, routeur, navigation
 */
(function () {
    'use strict';

    // ── Guard : si non connecté, rediriger ──────────────────────────────
    if (!API.isLoggedIn()) {
        window.location.href = '/login';
        return;
    }

    // ── Pages disponibles ───────────────────────────────────────────────
    const PAGES = {
        dashboard:     { id: 'page-dashboard',     title: 'Tableau de bord',   load: () => DashboardPage.load() },
        tickets:       { id: 'page-tickets',        title: 'Tickets',           load: () => TicketsPage.load() },
        interventions: { id: 'page-interventions',  title: 'Interventions',     load: () => InterventionsPage.load() },
        stock:         { id: 'page-stock',          title: 'Stock Matériel',    load: () => StockPage.load() },
        users:         { id: 'page-users',          title: 'Utilisateurs',      load: () => UsersPage.load(), adminOnly: true },
    };

    let currentPage = null;

    // ── Navigation ──────────────────────────────────────────────────────
    function navigate(name) {
        const page = PAGES[name];
        if (!page) return navigateTo('dashboard');
        if (page.adminOnly && !API.isAdmin()) return navigateTo('dashboard');

        // Hide all pages
        document.querySelectorAll('.page').forEach(el => el.classList.add('d-none'));

        // Show target page
        const el = document.getElementById(page.id);
        if (el) el.classList.remove('d-none');

        // Update header title
        document.getElementById('page-title').textContent = page.title;

        // Update nav active state
        document.querySelectorAll('.nav-link-item').forEach(a => {
            a.classList.toggle('active', a.dataset.page === name);
        });

        // Load page content
        if (currentPage !== name) {
            currentPage = name;
            page.load();
        }

        // Close sidebar on mobile
        closeSidebarMobile();
    }

    window.navigateTo = navigate;

    // ── Init ────────────────────────────────────────────────────────────
    function init() {
        const user = API.user();
        if (!user) { window.location.href = '/login'; return; }

        // Set user info in sidebar & header
        const initial = (user.name || '?').charAt(0).toUpperCase();
        document.getElementById('nav-avatar').textContent     = initial;
        document.getElementById('nav-username').textContent   = user.name || '—';
        document.getElementById('nav-role').textContent       = user.role_label || user.role || '—';
        document.getElementById('header-username').textContent = user.name || '—';
        document.getElementById('header-role-badge').textContent = user.role_label || user.role || '';

        // Show/hide admin-only elements
        if (API.isAdmin()) {
            document.querySelectorAll('.admin-only').forEach(el => el.classList.remove('d-none'));
        }

        // Show admin+tech elements
        if (API.isAdmin() || API.isTechnicien()) {
            document.querySelectorAll('.admin-tech-only').forEach(el => el.classList.remove('d-none'));
        }

        // Navigation clicks
        document.querySelectorAll('.nav-link-item').forEach(a => {
            a.addEventListener('click', e => {
                e.preventDefault();
                navigate(a.dataset.page);
            });
        });

        // Logout
        document.getElementById('btn-logout').addEventListener('click', () => {
            UI.confirm('Voulez-vous vous déconnecter ?', async () => {
                try { await API.post('/auth/logout'); } catch {}
                localStorage.clear();
                window.location.href = '/login';
            });
        });

        // Sidebar toggle (mobile)
        document.getElementById('sidebar-toggle')?.addEventListener('click', toggleSidebar);
        document.getElementById('sidebar-overlay')?.addEventListener('click', closeSidebarMobile);

        // Bind all page filters
        TicketsPage.bindFilters();
        InterventionsPage.bindFilters();
        StockPage.bindFilters();
        UsersPage.bindFilters();

        // Démarrer le système de notifications
        Notifications.init();

        // Start on dashboard
        navigate('dashboard');
    }

    // ── Sidebar mobile ──────────────────────────────────────────────────
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('open');
        document.getElementById('sidebar-overlay').classList.toggle('show');
    }

    function closeSidebarMobile() {
        document.getElementById('sidebar').classList.remove('open');
        document.getElementById('sidebar-overlay').classList.remove('show');
    }

    // ── Run ─────────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', init);

})();
