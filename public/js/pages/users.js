/**
 * Utilisateurs — CRUD + toggle-active (Admin uniquement)
 */
const UsersPage = {
    currentPage: 1,
    filters: {},
    roles: [],

    async load(page = 1) {
        this.currentPage = page;
        const wrap = document.getElementById('users-table-wrap');
        wrap.innerHTML = UI.loading();

        const params = new URLSearchParams({ page, per_page: 15, ...this.filters });
        try {
            const res = await API.get(`/users?${params}`);
            wrap.innerHTML = this.renderTable(res.data, res.meta);
            UI.bindPagination('users-table-wrap', p => this.load(p));
            this.bindTableEvents();
        } catch (e) {
            wrap.innerHTML = `<div class="alert alert-danger m-3">${e.message}</div>`;
        }
    },

    renderTable(users, meta) {
        if (!users || !users.length) {
            return `<div class="table-card">${UI.empty('Aucun utilisateur trouvé.', 'fa-users')}</div>`;
        }
        return `
        <div class="table-card">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Département</th>
                            <th>Téléphone</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${users.map(u => this.renderRow(u)).join('')}
                    </tbody>
                </table>
            </div>
            ${UI.pagination(meta, p => this.load(p))}
        </div>`;
    },

    renderRow(u) {
        const isSelf = u.id === API.user()?.id;
        return `
        <tr>
            <td>
                <div class="d-flex align-items-center gap-2">
                    <div style="width:32px;height:32px;border-radius:50%;background:#eff6ff;color:#2563eb;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.8rem;flex-shrink:0;">
                        ${(u.name || '?').charAt(0).toUpperCase()}
                    </div>
                    <div>
                        <div class="fw-semibold" style="font-size:0.88rem;">${u.name}</div>
                        ${isSelf ? '<small class="text-muted">(vous)</small>' : ''}
                    </div>
                </div>
            </td>
            <td><small>${u.email}</small></td>
            <td>${UI.badgeRole(u.role?.name || u.role)}</td>
            <td><small>${u.department || '—'}</small></td>
            <td><small>${u.phone || '—'}</small></td>
            <td>${UI.badgeUserStatus(u.is_active)}</td>
            <td class="actions-cell">
                <button class="btn-action view"   data-id="${u.id}" title="Voir"><i class="fas fa-eye"></i></button>
                <button class="btn-action edit"   data-id="${u.id}" title="Modifier"><i class="fas fa-pen"></i></button>
                ${!isSelf ? `
                <button class="btn-action ${u.is_active ? 'start' : 'complete'}" data-id="${u.id}" data-active="${u.is_active}" title="${u.is_active ? 'Désactiver' : 'Activer'}">
                    <i class="fas ${u.is_active ? 'fa-ban' : 'fa-check'}"></i>
                </button>
                <button class="btn-action delete" data-id="${u.id}" title="Supprimer"><i class="fas fa-trash"></i></button>` : ''}
            </td>
        </tr>`;
    },

    bindTableEvents() {
        const wrap = document.getElementById('users-table-wrap');

        wrap.querySelectorAll('.btn-action.view').forEach(b =>
            b.addEventListener('click', () => this.showDetail(+b.dataset.id)));

        wrap.querySelectorAll('.btn-action.edit').forEach(b =>
            b.addEventListener('click', () => this.openForm(+b.dataset.id)));

        wrap.querySelectorAll('[data-active]').forEach(b =>
            b.addEventListener('click', () => {
                const active = b.dataset.active === 'true';
                const msg = active ? 'Désactiver ce compte ?' : 'Activer ce compte ?';
                UI.confirm(msg, () => this.toggleActive(+b.dataset.id));
            }));

        wrap.querySelectorAll('.btn-action.delete').forEach(b =>
            b.addEventListener('click', () =>
                UI.confirm('Supprimer définitivement cet utilisateur ?', () => this.delete(+b.dataset.id))));
    },

    // ── Créer / Modifier ────────────────────────────────────────────────
    async openForm(id = null) {
        const isEdit = !!id;
        let u = null;
        if (isEdit) {
            try { const r = await API.get(`/users/${id}`); u = r.data; }
            catch (e) { UI.toast(e.message, 'error'); return; }
        }

        const body = `
        <form id="user-form" autocomplete="off">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nom complet *</label>
                    <input name="name" class="form-control" placeholder="Prénom Nom" required value="${u?.name || ''}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Rôle *</label>
                    <select name="role_id" class="form-select" required>
                        <option value="">Sélectionner...</option>
                        <option value="1" ${(u?.role_id===1||u?.role?.id===1)?'selected':''}>Administrateur</option>
                        <option value="2" ${(u?.role_id===2||u?.role?.id===2)?'selected':''}>Technicien</option>
                        <option value="3" ${(u?.role_id===3||u?.role?.id===3)?'selected':''}>Utilisateur</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email *</label>
                    <input name="email" type="email" class="form-control" placeholder="email@exemple.com" required value="${u?.email || ''}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Département</label>
                    <input name="department" class="form-control" placeholder="DSI, RH, Comptabilité..." value="${u?.department || ''}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Téléphone</label>
                    <input name="phone" class="form-control" placeholder="+225 07 00 00 00" value="${u?.phone || ''}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">${isEdit ? 'Nouveau mot de passe (laisser vide pour ne pas changer)' : 'Mot de passe *'}</label>
                    <input name="password" type="password" class="form-control" autocomplete="new-password" placeholder="••••••••" ${isEdit ? '' : 'required'}>
                </div>
                ${!isEdit ? `
                <div class="col-md-6">
                    <label class="form-label">Confirmer le mot de passe *</label>
                    <input name="password_confirmation" type="password" class="form-control" placeholder="••••••••" required>
                </div>` : ''}
                <div class="col-md-6">
                    <label class="form-label">Statut</label>
                    <select name="is_active" class="form-select">
                        <option value="1" ${u?.is_active!==false?'selected':''}>Actif</option>
                        <option value="0" ${u?.is_active===false?'selected':''}>Inactif</option>
                    </select>
                </div>
            </div>
        </form>`;

        UI.showModal(
            isEdit ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur',
            body, isEdit ? 'Enregistrer' : 'Créer',
            async () => {
                const data = UI.formData('user-form');
                if (!isEdit && data.password !== document.querySelector('[name="password_confirmation"]')?.value) {
                    UI.toast('Les mots de passe ne correspondent pas.', 'warning'); return;
                }
                if (isEdit && !data.password) delete data.password;
                try {
                    if (isEdit) await API.put(`/users/${id}`, data);
                    else        await API.post('/users', data);
                    UI.hideModal();
                    UI.toast(isEdit ? 'Utilisateur mis à jour.' : 'Utilisateur créé.', 'success');
                    this.load(this.currentPage);
                } catch (e) { UI.toast(e.message, 'error'); }
            }
        );
    },

    // ── Détail ──────────────────────────────────────────────────────────
    async showDetail(id) {
        try {
            const res = await API.get(`/users/${id}`);
            const u   = res.data;
            const body = `
            <div class="detail-section">
                <h6>Informations</h6>
                <div class="detail-row"><span class="detail-label">Nom</span><span class="detail-value">${u.name}</span></div>
                <div class="detail-row"><span class="detail-label">Email</span><span class="detail-value">${u.email}</span></div>
                <div class="detail-row"><span class="detail-label">Rôle</span><span class="detail-value">${UI.badgeRole(u.role?.name || u.role)}</span></div>
                <div class="detail-row"><span class="detail-label">Département</span><span class="detail-value">${u.department || '—'}</span></div>
                <div class="detail-row"><span class="detail-label">Téléphone</span><span class="detail-value">${u.phone || '—'}</span></div>
                <div class="detail-row"><span class="detail-label">Statut</span><span class="detail-value">${UI.badgeUserStatus(u.is_active)}</span></div>
                <div class="detail-row"><span class="detail-label">Membre depuis</span><span class="detail-value">${UI.fmtDate(u.created_at)}</span></div>
            </div>`;
            UI.showModal('Profil utilisateur', body);
        } catch (e) { UI.toast(e.message, 'error'); }
    },

    // ── Toggle actif ────────────────────────────────────────────────────
    async toggleActive(id) {
        try {
            const res = await API.patch(`/users/${id}/toggle-active`);
            const active = res.data?.is_active;
            UI.toast(active ? 'Compte activé.' : 'Compte désactivé.', 'success');
            this.load(this.currentPage);
        } catch (e) { UI.toast(e.message, 'error'); }
    },

    // ── Supprimer ───────────────────────────────────────────────────────
    async delete(id) {
        try {
            await API.delete(`/users/${id}`);
            UI.toast('Utilisateur supprimé.', 'success');
            this.load(this.currentPage);
        } catch (e) { UI.toast(e.message, 'error'); }
    },

    // ── Filtres ─────────────────────────────────────────────────────────
    bindFilters() {
        document.getElementById('user-btn-filter')?.addEventListener('click', () => {
            this.filters = {
                search:    document.getElementById('user-search').value.trim() || undefined,
                role_id:   document.getElementById('user-filter-role').value || undefined,
                is_active: document.getElementById('user-filter-active').value !== '' ? document.getElementById('user-filter-active').value : undefined,
            };
            Object.keys(this.filters).forEach(k => this.filters[k] === undefined && delete this.filters[k]);
            this.load(1);
        });

        document.getElementById('btn-new-user')?.addEventListener('click', () => this.openForm());
    },
};
