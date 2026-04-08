/**
 * Tickets — CRUD complet + assignation + changement de statut
 */
const TicketsPage = {
    currentPage: 1,
    filters: {},
    technicians: [],

    async load(page = 1) {
        this.currentPage = page;
        const wrap = document.getElementById('tickets-table-wrap');
        wrap.innerHTML = UI.loading();

        const params = new URLSearchParams({ page, per_page: 15, ...this.filters });
        try {
            const res = await API.get(`/tickets?${params}`);
            wrap.innerHTML = this.renderTable(res.data, res.meta);
            UI.bindPagination('tickets-table-wrap', p => this.load(p));
            this.bindTableEvents();
        } catch (e) {
            wrap.innerHTML = `<div class="alert alert-danger m-3">${e.message}</div>`;
        }
    },

    renderTable(tickets, meta) {
        if (!tickets || !tickets.length) {
            return `<div class="table-card">${UI.empty('Aucun ticket trouvé.', 'fa-ticket-alt')}</div>`;
        }
        const isAdmin = API.isAdmin();
        const isTech  = API.isTechnicien();
        return `
        <div class="table-card">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Titre</th>
                            <th>Créateur</th>
                            ${!isTech ? '<th>Technicien</th>' : ''}
                            <th>Priorité</th>
                            <th>Statut</th>
                            <th>Catégorie</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${tickets.map(t => this.renderRow(t, isAdmin, isTech)).join('')}
                    </tbody>
                </table>
            </div>
            ${UI.pagination(meta, p => this.load(p))}
        </div>`;
    },

    renderRow(t, isAdmin, isTech) {
        const canEdit   = isAdmin || (isTech && t.technician_id === API.user()?.id);
        const canDelete = isAdmin;
        const canAssign = isAdmin && t.status === 'open';
        const canStatus = isAdmin || isTech;

        return `
        <tr>
            <td><small class="text-muted fw-semibold">${t.reference || '—'}</small></td>
            <td style="max-width:200px;">
                <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${t.title}">${t.title}</div>
            </td>
            <td>${t.user?.name || '—'}</td>
            ${!isTech ? `<td>${t.technician?.name || '<span class="text-muted">Non assigné</span>'}</td>` : ''}
            <td>${UI.badgePriority(t.priority)}</td>
            <td>${UI.badgeTicketStatus(t.status)}</td>
            <td><small>${t.category || '—'}</small></td>
            <td><small class="text-muted">${UI.fmtDate(t.created_at)}</small></td>
            <td class="actions-cell">
                <button class="btn-action view"   data-id="${t.id}" title="Voir"><i class="fas fa-eye"></i></button>
                ${canEdit   ? `<button class="btn-action edit"   data-id="${t.id}" title="Modifier"><i class="fas fa-pen"></i></button>` : ''}
                ${canAssign ? `<button class="btn-action assign" data-id="${t.id}" title="Assigner"><i class="fas fa-user-tag"></i></button>` : ''}
                ${canStatus ? `<button class="btn-action start"  data-id="${t.id}" data-status="${t.status}" title="Changer statut"><i class="fas fa-exchange-alt"></i></button>` : ''}
                ${canDelete ? `<button class="btn-action delete" data-id="${t.id}" title="Supprimer"><i class="fas fa-trash"></i></button>` : ''}
            </td>
        </tr>`;
    },

    bindTableEvents() {
        const wrap = document.getElementById('tickets-table-wrap');

        wrap.querySelectorAll('.btn-action.view').forEach(b =>
            b.addEventListener('click', () => this.showDetail(+b.dataset.id)));

        wrap.querySelectorAll('.btn-action.edit').forEach(b =>
            b.addEventListener('click', () => this.openForm(+b.dataset.id)));

        wrap.querySelectorAll('.btn-action.assign').forEach(b =>
            b.addEventListener('click', () => this.openAssign(+b.dataset.id)));

        wrap.querySelectorAll('.btn-action.start').forEach(b =>
            b.addEventListener('click', () => this.openStatusChange(+b.dataset.id, b.dataset.status)));

        wrap.querySelectorAll('.btn-action.delete').forEach(b =>
            b.addEventListener('click', () => UI.confirm('Supprimer ce ticket ?', () => this.delete(+b.dataset.id))));
    },

    // ── Créer / Modifier ────────────────────────────────────────────────
    async openForm(id = null) {
        const isEdit = !!id;
        let ticket = null;
        if (isEdit) {
            try { const r = await API.get(`/tickets/${id}`); ticket = r.data; }
            catch (e) { UI.toast(e.message, 'error'); return; }
        }

        const body = `
        <form id="ticket-form">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Titre *</label>
                    <input name="title" class="form-control" placeholder="Titre du ticket" required value="${ticket?.title || ''}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Priorité *</label>
                    <select name="priority" class="form-select" required>
                        <option value="">Sélectionner...</option>
                        ${['low','medium','high','critical'].map(p =>
                            `<option value="${p}" ${ticket?.priority===p?'selected':''}>${{low:'Faible',medium:'Moyenne',high:'Haute',critical:'Critique'}[p]}</option>`
                        ).join('')}
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Catégorie *</label>
                    <select name="category" class="form-select" required>
                        <option value="">Sélectionner...</option>
                        ${['materiel','logiciel','reseau','securite','autre'].map(c =>
                            `<option value="${c}" ${ticket?.category===c?'selected':''}>${{materiel:'Matériel',logiciel:'Logiciel',reseau:'Réseau',securite:'Sécurité',autre:'Autre'}[c]}</option>`
                        ).join('')}
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Description *</label>
                    <textarea name="description" class="form-control" rows="4" placeholder="Décrivez le problème..." required>${ticket?.description || ''}</textarea>
                </div>
            </div>
        </form>`;

        UI.showModal(
            isEdit ? 'Modifier le ticket' : 'Nouveau ticket',
            body,
            isEdit ? 'Enregistrer' : 'Créer',
            async () => {
                const data = UI.formData('ticket-form');
                if (!data.title || !data.priority || !data.category || !data.description) {
                    UI.toast('Veuillez remplir tous les champs obligatoires.', 'warning'); return;
                }
                try {
                    if (isEdit) await API.put(`/tickets/${id}`, data);
                    else        await API.post('/tickets', data);
                    UI.hideModal();
                    UI.toast(isEdit ? 'Ticket mis à jour.' : 'Ticket créé.', 'success');
                    this.load(this.currentPage);
                } catch (e) { UI.toast(e.message, 'error'); }
            }
        );
    },

    // ── Assigner ────────────────────────────────────────────────────────
    async openAssign(id) {
        // Toujours recharger la liste pour inclure les techniciens récemment ajoutés
        try {
            const r = await API.get('/users/technicians');
            this.technicians = r.data || [];
        } catch {
            UI.toast('Impossible de charger les techniciens.', 'error'); return;
        }

        if (!this.technicians.length) {
            UI.toast('Aucun technicien actif disponible. Créez-en un dans la gestion des utilisateurs.', 'warning');
            return;
        }

        const body = `
        <form id="assign-form">
            <label class="form-label">Technicien *</label>
            <select name="technician_id" class="form-select" required>
                <option value="">Sélectionner un technicien...</option>
                ${this.technicians.map(u => `<option value="${u.id}">${u.name} — ${u.department || ''}</option>`).join('')}
            </select>
        </form>`;

        UI.showModal('Assigner le ticket', body, 'Assigner', async () => {
            const data = UI.formData('assign-form');
            if (!data.technician_id) { UI.toast('Veuillez sélectionner un technicien.', 'warning'); return; }
            try {
                await API.post(`/tickets/${id}/assign`, data);
                UI.hideModal();
                UI.toast('Ticket assigné avec succès.', 'success');
                this.load(this.currentPage);
            } catch (e) { UI.toast(e.message, 'error'); }
        });
    },

    // ── Changer statut ──────────────────────────────────────────────────
    openStatusChange(id, currentStatus) {
        const statuses = ['open','assigned','in_progress','resolved','closed'];
        const labels   = { open:'Ouvert', assigned:'Assigné', in_progress:'En cours', resolved:'Résolu', closed:'Clôturé' };

        const body = `
        <form id="status-form">
            <label class="form-label">Nouveau statut *</label>
            <select name="status" class="form-select" required>
                ${statuses.map(s =>
                    `<option value="${s}" ${s===currentStatus?'selected':''}>${labels[s]}</option>`
                ).join('')}
            </select>
        </form>`;

        UI.showModal('Changer le statut', body, 'Mettre à jour', async () => {
            const data = UI.formData('status-form');
            try {
                await API.patch(`/tickets/${id}/status`, data);
                UI.hideModal();
                UI.toast('Statut mis à jour.', 'success');
                this.load(this.currentPage);
            } catch (e) { UI.toast(e.message, 'error'); }
        });
    },

    // ── Détail ──────────────────────────────────────────────────────────
    async showDetail(id) {
        try {
            const res = await API.get(`/tickets/${id}`);
            const t   = res.data;
            const body = `
            <div class="detail-section">
                <h6>Informations générales</h6>
                <div class="detail-row"><span class="detail-label">Référence</span><span class="detail-value">${t.reference || '—'}</span></div>
                <div class="detail-row"><span class="detail-label">Titre</span><span class="detail-value">${t.title}</span></div>
                <div class="detail-row"><span class="detail-label">Statut</span><span class="detail-value">${UI.badgeTicketStatus(t.status)}</span></div>
                <div class="detail-row"><span class="detail-label">Priorité</span><span class="detail-value">${UI.badgePriority(t.priority)}</span></div>
                <div class="detail-row"><span class="detail-label">Catégorie</span><span class="detail-value">${t.category || '—'}</span></div>
                <div class="detail-row"><span class="detail-label">Créateur</span><span class="detail-value">${t.user?.name || '—'}</span></div>
                <div class="detail-row"><span class="detail-label">Technicien</span><span class="detail-value">${t.technician?.name || 'Non assigné'}</span></div>
                <div class="detail-row"><span class="detail-label">Créé le</span><span class="detail-value">${UI.fmtDatetime(t.created_at)}</span></div>
                ${t.resolved_at ? `<div class="detail-row"><span class="detail-label">Résolu le</span><span class="detail-value">${UI.fmtDatetime(t.resolved_at)}</span></div>` : ''}
            </div>
            <div class="detail-section">
                <h6>Description</h6>
                <p style="font-size:0.88rem;white-space:pre-wrap;">${t.description}</p>
            </div>
            ${t.interventions?.length ? `
            <div class="detail-section">
                <h6>Interventions liées (${t.interventions.length})</h6>
                ${t.interventions.map(iv => `
                    <div class="d-flex align-items-center gap-2 mb-2" style="font-size:0.85rem;">
                        ${UI.badgeIntervStatus(iv.status)}
                        <span>${iv.reference || ''}</span>
                        <span class="text-muted">${UI.fmtDate(iv.start_date)}</span>
                    </div>`).join('')}
            </div>` : ''}`;

            UI.showModal(`Ticket — ${t.reference || t.id}`, body);
        } catch (e) { UI.toast(e.message, 'error'); }
    },

    // ── Supprimer ───────────────────────────────────────────────────────
    async delete(id) {
        try {
            await API.delete(`/tickets/${id}`);
            UI.toast('Ticket supprimé.', 'success');
            this.load(this.currentPage);
        } catch (e) { UI.toast(e.message, 'error'); }
    },

    // ── Filtres ─────────────────────────────────────────────────────────
    bindFilters() {
        document.getElementById('ticket-btn-filter')?.addEventListener('click', () => {
            this.filters = {
                search:   document.getElementById('ticket-search').value.trim() || undefined,
                status:   document.getElementById('ticket-filter-status').value || undefined,
                priority: document.getElementById('ticket-filter-priority').value || undefined,
                category: document.getElementById('ticket-filter-category').value || undefined,
            };
            Object.keys(this.filters).forEach(k => this.filters[k] === undefined && delete this.filters[k]);
            this.load(1);
        });

        document.getElementById('btn-new-ticket')?.addEventListener('click', () => this.openForm());
    },
};
