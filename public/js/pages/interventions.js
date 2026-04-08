/**
 * Interventions — CRUD + démarrer + clôturer
 */
const InterventionsPage = {
    currentPage: 1,
    filters: {},
    tickets: [],
    technicians: [],

    async load(page = 1) {
        this.currentPage = page;
        const wrap = document.getElementById('interventions-table-wrap');
        wrap.innerHTML = UI.loading();

        const params = new URLSearchParams({ page, per_page: 15, ...this.filters });
        try {
            const res = await API.get(`/interventions?${params}`);
            wrap.innerHTML = this.renderTable(res.data, res.meta);
            UI.bindPagination('interventions-table-wrap', p => this.load(p));
            this.bindTableEvents();
        } catch (e) {
            wrap.innerHTML = `<div class="alert alert-danger m-3">${e.message}</div>`;
        }
    },

    async loadDependencies() {
        if (!this.tickets.length) {
            try {
                const r = await API.get('/tickets?per_page=200');
                this.tickets = r.data || [];
            } catch {}
        }
        if (API.isAdmin() && !this.technicians.length) {
            try {
                const r = await API.get('/users/technicians');
                this.technicians = r.data || [];
            } catch {}
        }
    },

    renderTable(items, meta) {
        if (!items || !items.length) {
            return `<div class="table-card">${UI.empty('Aucune intervention trouvée.', 'fa-tools')}</div>`;
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
                            <th>Ticket</th>
                            <th>Technicien</th>
                            <th>Statut</th>
                            <th>Date début</th>
                            <th>Durée</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${items.map(iv => this.renderRow(iv, isAdmin, isTech)).join('')}
                    </tbody>
                </table>
            </div>
            ${UI.pagination(meta, p => this.load(p))}
        </div>`;
    },

    renderRow(iv, isAdmin, isTech) {
        const isOwner   = isTech && iv.technician_id === API.user()?.id;
        const canEdit   = isAdmin || isOwner;
        const canDelete = isAdmin;
        const canStart  = (isAdmin || isOwner) && iv.status === 'planned';
        const canFinish = (isAdmin || isOwner) && iv.status === 'in_progress';

        return `
        <tr>
            <td><small class="text-muted fw-semibold">${iv.reference || '—'}</small></td>
            <td>
                <small>${iv.ticket?.reference || '—'}</small>
                <div style="font-size:0.8rem;color:#64748b;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${iv.ticket?.title || ''}</div>
            </td>
            <td>${iv.technician?.name || '—'}</td>
            <td>${UI.badgeIntervStatus(iv.status)}</td>
            <td><small class="text-muted">${UI.fmtDatetime(iv.start_date)}</small></td>
            <td><small>${iv.duration_minutes ? iv.duration_minutes + ' min' : '—'}</small></td>
            <td class="actions-cell">
                <button class="btn-action view"     data-id="${iv.id}" title="Voir"><i class="fas fa-eye"></i></button>
                ${canEdit   ? `<button class="btn-action edit"    data-id="${iv.id}" title="Modifier"><i class="fas fa-pen"></i></button>` : ''}
                ${canStart  ? `<button class="btn-action start"   data-id="${iv.id}" title="Démarrer"><i class="fas fa-play"></i></button>` : ''}
                ${canFinish ? `<button class="btn-action complete" data-id="${iv.id}" title="Clôturer"><i class="fas fa-flag-checkered"></i></button>` : ''}
                ${canDelete ? `<button class="btn-action delete"  data-id="${iv.id}" title="Supprimer"><i class="fas fa-trash"></i></button>` : ''}
            </td>
        </tr>`;
    },

    bindTableEvents() {
        const wrap = document.getElementById('interventions-table-wrap');

        wrap.querySelectorAll('.btn-action.view').forEach(b =>
            b.addEventListener('click', () => this.showDetail(+b.dataset.id)));

        wrap.querySelectorAll('.btn-action.edit').forEach(b =>
            b.addEventListener('click', () => this.openForm(+b.dataset.id)));

        wrap.querySelectorAll('.btn-action.start').forEach(b =>
            b.addEventListener('click', () => UI.confirm('Démarrer cette intervention ?', () => this.start(+b.dataset.id))));

        wrap.querySelectorAll('.btn-action.complete').forEach(b =>
            b.addEventListener('click', () => this.openComplete(+b.dataset.id)));

        wrap.querySelectorAll('.btn-action.delete').forEach(b =>
            b.addEventListener('click', () => UI.confirm('Supprimer cette intervention ?', () => this.delete(+b.dataset.id))));
    },

    // ── Créer / Modifier ────────────────────────────────────────────────
    async openForm(id = null) {
        await this.loadDependencies();
        const isEdit = !!id;
        let iv = null;
        if (isEdit) {
            try { const r = await API.get(`/interventions/${id}`); iv = r.data; }
            catch (e) { UI.toast(e.message, 'error'); return; }
        }

        const ticketOptions = this.tickets.map(t =>
            `<option value="${t.id}" ${iv?.ticket_id===t.id?'selected':''}>${t.reference || t.id} — ${t.title}</option>`
        ).join('');

        const techOptions = this.technicians.map(u =>
            `<option value="${u.id}" ${iv?.technician_id===u.id?'selected':''}>${u.name}</option>`
        ).join('');

        const toDatetimeLocal = (dt) => {
            if (!dt) return '';
            return new Date(dt).toISOString().slice(0, 16);
        };

        const body = `
        <form id="interv-form">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Ticket associé *</label>
                    <select name="ticket_id" class="form-select" required>
                        <option value="">Sélectionner un ticket...</option>
                        ${ticketOptions}
                    </select>
                </div>
                ${API.isAdmin() ? `
                <div class="col-12">
                    <label class="form-label">Technicien</label>
                    <select name="technician_id" class="form-select">
                        <option value="">Non assigné</option>
                        ${techOptions}
                    </select>
                </div>` : ''}
                <div class="col-md-6">
                    <label class="form-label">Date de début</label>
                    <input type="datetime-local" name="start_date" class="form-control" value="${toDatetimeLocal(iv?.start_date)}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date de fin</label>
                    <input type="datetime-local" name="end_date" class="form-control" value="${toDatetimeLocal(iv?.end_date)}">
                </div>
                <div class="col-12">
                    <label class="form-label">Description *</label>
                    <textarea name="description" class="form-control" rows="4" required placeholder="Décrire l'intervention...">${iv?.description || ''}</textarea>
                </div>
            </div>
        </form>`;

        UI.showModal(
            isEdit ? 'Modifier l\'intervention' : 'Nouvelle intervention',
            body,
            isEdit ? 'Enregistrer' : 'Créer',
            async () => {
                const data = UI.formData('interv-form');
                if (!data.ticket_id || !data.description) {
                    UI.toast('Veuillez remplir les champs obligatoires.', 'warning'); return;
                }
                try {
                    if (isEdit) await API.put(`/interventions/${id}`, data);
                    else        await API.post('/interventions', data);
                    UI.hideModal();
                    UI.toast(isEdit ? 'Intervention mise à jour.' : 'Intervention créée.', 'success');
                    this.load(this.currentPage);
                } catch (e) { UI.toast(e.message, 'error'); }
            }
        );
    },

    // ── Démarrer ────────────────────────────────────────────────────────
    async start(id) {
        try {
            await API.patch(`/interventions/${id}/start`);
            UI.toast('Intervention démarrée.', 'success');
            this.load(this.currentPage);
        } catch (e) { UI.toast(e.message, 'error'); }
    },

    // ── Clôturer ────────────────────────────────────────────────────────
    openComplete(id) {
        const body = `
        <form id="complete-form">
            <label class="form-label">Rapport de clôture *</label>
            <textarea name="report" class="form-control" rows="5" placeholder="Décrivez le travail effectué et les résultats..." required></textarea>
            <small class="text-muted">Minimum 10 caractères.</small>
        </form>`;

        UI.showModal('Clôturer l\'intervention', body, 'Clôturer', async () => {
            const data = UI.formData('complete-form');
            if (!data.report || data.report.length < 10) {
                UI.toast('Le rapport doit contenir au moins 10 caractères.', 'warning'); return;
            }
            try {
                await API.patch(`/interventions/${id}/complete`, { report: data.report });
                UI.hideModal();
                UI.toast('Intervention clôturée.', 'success');
                this.load(this.currentPage);
            } catch (e) { UI.toast(e.message, 'error'); }
        });
    },

    // ── Détail ──────────────────────────────────────────────────────────
    async showDetail(id) {
        try {
            const res = await API.get(`/interventions/${id}`);
            const iv  = res.data;
            const body = `
            <div class="detail-section">
                <h6>Informations générales</h6>
                <div class="detail-row"><span class="detail-label">Référence</span><span class="detail-value">${iv.reference || '—'}</span></div>
                <div class="detail-row"><span class="detail-label">Statut</span><span class="detail-value">${UI.badgeIntervStatus(iv.status)}</span></div>
                <div class="detail-row"><span class="detail-label">Technicien</span><span class="detail-value">${iv.technician?.name || '—'}</span></div>
                <div class="detail-row"><span class="detail-label">Ticket</span><span class="detail-value">${iv.ticket?.reference || ''} — ${iv.ticket?.title || '—'}</span></div>
                <div class="detail-row"><span class="detail-label">Date début</span><span class="detail-value">${UI.fmtDatetime(iv.start_date)}</span></div>
                <div class="detail-row"><span class="detail-label">Date fin</span><span class="detail-value">${UI.fmtDatetime(iv.end_date)}</span></div>
                <div class="detail-row"><span class="detail-label">Durée</span><span class="detail-value">${iv.duration_minutes ? iv.duration_minutes + ' minutes' : '—'}</span></div>
            </div>
            <div class="detail-section">
                <h6>Description</h6>
                <p style="font-size:0.88rem;white-space:pre-wrap;">${iv.description || '—'}</p>
            </div>
            ${iv.report ? `
            <div class="detail-section">
                <h6>Rapport de clôture</h6>
                <p style="font-size:0.88rem;white-space:pre-wrap;">${iv.report}</p>
            </div>` : ''}`;

            UI.showModal(`Intervention — ${iv.reference || iv.id}`, body);
        } catch (e) { UI.toast(e.message, 'error'); }
    },

    // ── Supprimer ───────────────────────────────────────────────────────
    async delete(id) {
        try {
            await API.delete(`/interventions/${id}`);
            UI.toast('Intervention supprimée.', 'success');
            this.load(this.currentPage);
        } catch (e) { UI.toast(e.message, 'error'); }
    },

    // ── Filtres ─────────────────────────────────────────────────────────
    bindFilters() {
        document.getElementById('interv-btn-filter')?.addEventListener('click', () => {
            this.filters = {
                search:    document.getElementById('interv-search').value.trim() || undefined,
                status:    document.getElementById('interv-filter-status').value || undefined,
                date_from: document.getElementById('interv-date-from').value || undefined,
                date_to:   document.getElementById('interv-date-to').value || undefined,
            };
            Object.keys(this.filters).forEach(k => this.filters[k] === undefined && delete this.filters[k]);
            this.load(1);
        });

        document.getElementById('btn-new-intervention')?.addEventListener('click', () => this.openForm());
    },
};
