/**
 * Dashboard — statistiques adaptées au rôle
 */
const DashboardPage = {

    async load() {
        const el = document.getElementById('page-dashboard');
        el.innerHTML = UI.loading();
        try {
            const res = await API.get('/dashboard/stats');
            const stats = res.data;
            if (API.isAdmin())      el.innerHTML = this.renderAdmin(stats);
            else if (API.isTechnicien()) el.innerHTML = this.renderTech(stats);
            else                    el.innerHTML = this.renderUser(stats);
        } catch (e) {
            el.innerHTML = `<div class="alert alert-danger">${e.message}</div>`;
        }
    },

    // ── Admin ─────────────────────────────────────────────────────────────
    renderAdmin(s) {
        const t  = s.tickets       || {};
        const iv = s.interventions || {};
        const st = s.stock         || {};
        const u  = s.users         || {};
        const ch = s.charts        || {};

        const recentTickets = ch.tickets_last_30_days || [];
        const byPriority    = ch.tickets_by_priority  || {};

        return `
        <div class="row g-3 mb-4">
            ${this.card('Tickets totaux',    t.total      || 0, 'fa-ticket-alt', '#eff6ff', '#2563eb')}
            ${this.card('En cours',          (t.assigned||0)+(t.in_progress||0), 'fa-spinner', '#fffbeb', '#d97706')}
            ${this.card('Résolus',           t.resolved   || 0, 'fa-check-circle', '#f0fdf4', '#16a34a')}
            ${this.card('Interventions',     iv.total     || 0, 'fa-tools', '#f5f3ff', '#7c3aed')}
            ${this.card('Stock matériel',    st.total     || 0, 'fa-boxes', '#ecfdf5', '#059669')}
            ${this.card('Stock bas',         st.low_stock || 0, 'fa-exclamation-triangle', '#fef2f2', '#dc2626')}
            ${this.card('Utilisateurs',      u.total      || 0, 'fa-users', '#f0f9ff', '#0369a1')}
            ${this.card('Techniciens actifs',u.technicians|| 0, 'fa-user-cog', '#fff7ed', '#ea580c')}
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="recent-card">
                    <div class="recent-card-header"><i class="fas fa-chart-bar me-2 text-primary"></i>Tickets par statut</div>
                    <div class="p-3">
                        ${this.progressBar('Ouverts',     t.open      || 0, t.total || 1, '#2563eb')}
                        ${this.progressBar('Assignés',    t.assigned  || 0, t.total || 1, '#7c3aed')}
                        ${this.progressBar('En cours',    t.in_progress|| 0, t.total || 1, '#d97706')}
                        ${this.progressBar('Résolus',     t.resolved  || 0, t.total || 1, '#16a34a')}
                        ${this.progressBar('Clôturés',    t.closed    || 0, t.total || 1, '#64748b')}
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="recent-card">
                    <div class="recent-card-header"><i class="fas fa-flag me-2 text-warning"></i>Tickets par priorité</div>
                    <div class="p-3">
                        ${this.progressBar('Critique',  byPriority.critical || 0, t.total || 1, '#dc2626')}
                        ${this.progressBar('Haute',     byPriority.high     || 0, t.total || 1, '#ea580c')}
                        ${this.progressBar('Moyenne',   byPriority.medium   || 0, t.total || 1, '#d97706')}
                        ${this.progressBar('Faible',    byPriority.low      || 0, t.total || 1, '#16a34a')}
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="recent-card">
                    <div class="recent-card-header"><i class="fas fa-wrench me-2 text-purple"></i>Interventions</div>
                    <div class="p-3">
                        ${this.progressBar('Terminées',  iv.completed   || 0, iv.total || 1, '#16a34a')}
                        ${this.progressBar('En cours',   iv.in_progress || 0, iv.total || 1, '#d97706')}
                        <div class="mt-3 text-muted" style="font-size:0.82rem;">
                            Durée moyenne : <strong>${iv.avg_duration || 0} min</strong>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="recent-card">
                    <div class="recent-card-header"><i class="fas fa-boxes me-2 text-success"></i>Stock par catégorie</div>
                    <div class="p-3">
                        ${(st.by_category || []).map(c =>
                            `<div class="d-flex justify-content-between mb-2"><span style="font-size:0.82rem;">${c.category}</span><strong>${c.total}</strong></div>`
                        ).join('') || '<p class="text-muted">Aucune donnée</p>'}
                    </div>
                </div>
            </div>
        </div>`;
    },

    // ── Technicien ────────────────────────────────────────────────────────
    renderTech(s) {
        const t  = s.tickets        || {};
        const iv = s.interventions  || {};
        const rt = s.recent_tickets || [];
        return `
        <div class="row g-3 mb-4">
            ${this.card('Tickets assignés',  t.assigned || 0, 'fa-ticket-alt',   '#eff6ff', '#2563eb')}
            ${this.card('En cours',          t.open     || 0, 'fa-spinner',       '#fffbeb', '#d97706')}
            ${this.card('Résolus',           t.resolved || 0, 'fa-check-circle',  '#f0fdf4', '#16a34a')}
            ${this.card('Interventions',     iv.total   || 0, 'fa-tools',         '#f5f3ff', '#7c3aed')}
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="recent-card">
                    <div class="recent-card-header"><i class="fas fa-clock me-2 text-warning"></i>Performances</div>
                    <div class="p-3">
                        <div class="text-muted" style="font-size:0.85rem;">Durée moyenne intervention : <strong>${iv.avg_duration || 0} min</strong></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                ${this.recentTickets(rt)}
            </div>
        </div>`;
    },

    // ── Utilisateur ───────────────────────────────────────────────────────
    renderUser(s) {
        const t  = s.tickets        || {};
        const rt = s.recent_tickets || [];
        return `
        <div class="row g-3 mb-4">
            ${this.card('Mes tickets',       t.total       || 0, 'fa-ticket-alt',  '#eff6ff', '#2563eb')}
            ${this.card('Ouverts',           t.open        || 0, 'fa-circle-dot',  '#fff7ed', '#ea580c')}
            ${this.card('En cours',          t.in_progress || 0, 'fa-spinner',     '#fffbeb', '#d97706')}
            ${this.card('Résolus',           t.resolved    || 0, 'fa-check-circle','#f0fdf4', '#16a34a')}
        </div>
        <div class="row g-3">
            <div class="col-12">
                ${this.recentTickets(rt)}
            </div>
        </div>`;
    },

    // ── Helpers ──────────────────────────────────────────────────────────
    card(label, value, icon, bg, color) {
        return `
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:${bg};color:${color}">
                    <i class="fas ${icon}"></i>
                </div>
                <div class="stat-value" style="color:${color}">${value}</div>
                <div class="stat-label">${label}</div>
            </div>
        </div>`;
    },

    progressBar(label, value, total, color) {
        const pct = total > 0 ? Math.round((value / total) * 100) : 0;
        return `
        <div class="mb-2">
            <div class="d-flex justify-content-between mb-1" style="font-size:0.8rem;">
                <span>${label}</span><span><strong>${value}</strong> (${pct}%)</span>
            </div>
            <div class="progress" style="height:6px;border-radius:3px;">
                <div class="progress-bar" style="width:${pct}%;background:${color};border-radius:3px;"></div>
            </div>
        </div>`;
    },

    recentTickets(tickets) {
        if (!tickets.length) return `<div class="recent-card"><div class="recent-card-header">Tickets récents</div>${UI.empty()}</div>`;
        return `
        <div class="recent-card">
            <div class="recent-card-header"><i class="fas fa-history me-2 text-primary"></i>Tickets récents</div>
            <table class="table table-sm">
                <thead><tr><th>Référence</th><th>Titre</th><th>Statut</th><th>Priorité</th></tr></thead>
                <tbody>
                    ${tickets.map(t => `
                    <tr>
                        <td><small class="text-muted">${t.reference || '—'}</small></td>
                        <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${t.title}</td>
                        <td>${UI.badgeTicketStatus(t.status)}</td>
                        <td>${UI.badgePriority(t.priority)}</td>
                    </tr>`).join('')}
                </tbody>
            </table>
        </div>`;
    },
};
