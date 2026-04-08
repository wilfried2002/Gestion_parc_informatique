/**
 * Stock — CRUD + affectation + retour matériel
 */
const StockPage = {
    currentPage: 1,
    filters: {},
    users: [],

    async load(page = 1) {
        this.currentPage = page;
        const wrap = document.getElementById('stock-table-wrap');
        wrap.innerHTML = UI.loading();

        const params = new URLSearchParams({ page, per_page: 15, ...this.filters });
        try {
            const res = await API.get(`/stocks?${params}`);
            wrap.innerHTML = this.renderTable(res.data, res.meta);
            UI.bindPagination('stock-table-wrap', p => this.load(p));
            this.bindTableEvents();
        } catch (e) {
            wrap.innerHTML = `<div class="alert alert-danger m-3">${e.message}</div>`;
        }
    },

    async loadUsers() {
        if (!this.users.length) {
            try {
                const r = await API.get('/users?per_page=200');
                this.users = r.data || [];
            } catch {}
        }
    },

    renderTable(items, meta) {
        if (!items || !items.length) {
            return `<div class="table-card">${UI.empty('Aucun matériel en stock.', 'fa-boxes')}</div>`;
        }
        const isAdmin = API.isAdmin();
        return `
        <div class="table-card">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Référence</th>
                            <th>Catégorie</th>
                            <th>Quantité</th>
                            <th>Statut</th>
                            <th>Localisation</th>
                            <th>Marque / Modèle</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${items.map(s => this.renderRow(s, isAdmin)).join('')}
                    </tbody>
                </table>
            </div>
            ${UI.pagination(meta, p => this.load(p))}
        </div>`;
    },

    renderRow(s, isAdmin) {
        const isLow = s.quantity <= s.quantity_min;
        return `
        <tr>
            <td>
                <div class="fw-semibold">${s.name}</div>
                ${isLow ? `<small class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>Stock bas</small>` : ''}
            </td>
            <td><small class="text-muted">${s.reference || '—'}</small></td>
            <td><span class="badge" style="background:#f1f5f9;color:#475569">${this.categoryLabel(s.category)}</span></td>
            <td>
                <span class="${isLow ? 'text-danger fw-bold' : ''}">${s.quantity}</span>
                ${s.quantity_min ? `<small class="text-muted"> / min: ${s.quantity_min}</small>` : ''}
            </td>
            <td>${UI.badgeStockStatus(s.status)}</td>
            <td><small>${s.location || '—'}</small></td>
            <td><small>${[s.brand, s.model].filter(Boolean).join(' / ') || '—'}</small></td>
            <td class="actions-cell">
                <button class="btn-action view"   data-id="${s.id}" title="Voir"><i class="fas fa-eye"></i></button>
                ${isAdmin ? `
                <button class="btn-action edit"   data-id="${s.id}" title="Modifier"><i class="fas fa-pen"></i></button>
                <button class="btn-action assign" data-id="${s.id}" title="Affecter"><i class="fas fa-share-square"></i></button>
                <button class="btn-action delete" data-id="${s.id}" title="Supprimer"><i class="fas fa-trash"></i></button>` : ''}
            </td>
        </tr>`;
    },

    categoryLabel(cat) {
        const map = { ordinateur:'Ordinateur', imprimante:'Imprimante', serveur:'Serveur', reseau:'Réseau', peripherique:'Périphérique', consommable:'Consommable', autre:'Autre' };
        return map[cat] || cat;
    },

    bindTableEvents() {
        const wrap = document.getElementById('stock-table-wrap');

        wrap.querySelectorAll('.btn-action.view').forEach(b =>
            b.addEventListener('click', () => this.showDetail(+b.dataset.id)));

        wrap.querySelectorAll('.btn-action.edit').forEach(b =>
            b.addEventListener('click', () => this.openForm(+b.dataset.id)));

        wrap.querySelectorAll('.btn-action.assign').forEach(b =>
            b.addEventListener('click', () => this.openAssign(+b.dataset.id)));

        wrap.querySelectorAll('.btn-action.delete').forEach(b =>
            b.addEventListener('click', () => UI.confirm('Supprimer ce matériel ?', () => this.delete(+b.dataset.id))));
    },

    // ── Créer / Modifier ────────────────────────────────────────────────
    async openForm(id = null) {
        const isEdit = !!id;
        let s = null;
        if (isEdit) {
            try { const r = await API.get(`/stocks/${id}`); s = r.data; }
            catch (e) { UI.toast(e.message, 'error'); return; }
        }

        // Récupère la prochaine référence disponible pour une création
        let nextRef = '';
        if (!isEdit) {
            try {
                const r = await API.get('/stocks/next-reference');
                nextRef = r.data?.reference || '';
            } catch {}
        }

        const categories = ['ordinateur','imprimante','serveur','reseau','peripherique','consommable','autre'];
        const statuses   = ['disponible','affecte','maintenance','hors_service'];
        const catLabels  = { ordinateur:'Ordinateur', imprimante:'Imprimante', serveur:'Serveur', reseau:'Réseau', peripherique:'Périphérique', consommable:'Consommable', autre:'Autre' };
        const stLabels   = { disponible:'Disponible', affecte:'Affecté', maintenance:'Maintenance', hors_service:'Hors service' };

        const body = `
        <form id="stock-form">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Nom *</label>
                    <input name="name" class="form-control" placeholder="Nom du matériel" required value="${s?.name || ''}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Quantité *</label>
                    <input name="quantity" type="number" min="0" class="form-control" required value="${s?.quantity ?? ''}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Catégorie *</label>
                    <select name="category" class="form-select" required>
                        <option value="">Sélectionner...</option>
                        ${categories.map(c => `<option value="${c}" ${s?.category===c?'selected':''}>${catLabels[c]}</option>`).join('')}
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select">
                        ${statuses.map(st => `<option value="${st}" ${s?.status===st?'selected':''}>${stLabels[st]}</option>`).join('')}
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">
                        Référence
                        ${!isEdit ? '<span class="text-muted" style="font-size:0.75rem;font-weight:400;"> — générée automatiquement</span>' : ''}
                    </label>
                    <div class="input-group">
                        <input name="reference" class="form-control"
                            value="${isEdit ? (s?.reference || '') : nextRef}"
                            ${!isEdit ? 'readonly style="background:#f8fafc;font-weight:600;color:#2563eb;"' : 'placeholder="MAT-2026-0001"'}>
                        ${!isEdit ? `<span class="input-group-text" style="background:#f0f9ff;color:#2563eb;border-color:#bae6fd;" title="Référence générée automatiquement"><i class="fas fa-magic"></i></span>` : ''}
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">N° de série</label>
                    <input name="serial_number" class="form-control" placeholder="SN-XXXXX" value="${s?.serial_number || ''}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Marque</label>
                    <input name="brand" class="form-control" placeholder="Dell, HP..." value="${s?.brand || ''}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Modèle</label>
                    <input name="model" class="form-control" placeholder="OptiPlex 3090..." value="${s?.model || ''}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Localisation</label>
                    <input name="location" class="form-control" placeholder="Salle serveur, Bureau 3..." value="${s?.location || ''}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Quantité minimale</label>
                    <input name="quantity_min" type="number" min="0" class="form-control" value="${s?.quantity_min ?? ''}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Prix d'achat (FCFA)</label>
                    <input name="purchase_price" type="number" step="0.01" min="0" class="form-control" value="${s?.purchase_price || ''}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date d'achat</label>
                    <input name="purchase_date" type="date" class="form-control" value="${s?.purchase_date || ''}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fin garantie</label>
                    <input name="warranty_end" type="date" class="form-control" value="${s?.warranty_end || ''}">
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Description optionnelle...">${s?.description || ''}</textarea>
                </div>
            </div>
        </form>`;

        UI.showModal(
            isEdit ? 'Modifier le matériel' : 'Ajouter du matériel',
            body, isEdit ? 'Enregistrer' : 'Ajouter',
            async () => {
                const data = UI.formData('stock-form');
                if (!data.name || !data.category || data.quantity === undefined) {
                    UI.toast('Veuillez remplir les champs obligatoires.', 'warning'); return;
                }
                try {
                    if (isEdit) await API.put(`/stocks/${id}`, data);
                    else        await API.post('/stocks', data);
                    UI.hideModal();
                    UI.toast(isEdit ? 'Matériel mis à jour.' : 'Matériel ajouté.', 'success');
                    this.load(this.currentPage);
                } catch (e) { UI.toast(e.message, 'error'); }
            }
        );
    },

    // ── Affecter ────────────────────────────────────────────────────────
    async openAssign(id) {
        await this.loadUsers();
        const body = `
        <form id="assign-stock-form">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Utilisateur *</label>
                    <select name="user_id" class="form-select" required>
                        <option value="">Sélectionner un utilisateur...</option>
                        ${this.users.map(u => `<option value="${u.id}">${u.name} (${u.role || '—'})</option>`).join('')}
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Quantité *</label>
                    <input name="quantity" type="number" min="1" value="1" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Notes optionnelles..."></textarea>
                </div>
            </div>
        </form>`;

        UI.showModal('Affecter le matériel', body, 'Affecter', async () => {
            const data = UI.formData('assign-stock-form');
            if (!data.user_id || !data.quantity) {
                UI.toast('Veuillez remplir les champs obligatoires.', 'warning'); return;
            }
            try {
                await API.post(`/stocks/${id}/assign`, data);
                UI.hideModal();
                UI.toast('Matériel affecté.', 'success');
                this.load(this.currentPage);
            } catch (e) { UI.toast(e.message, 'error'); }
        });
    },

    // ── Détail + retour matériel ─────────────────────────────────────────
    async showDetail(id) {
        try {
            const res = await API.get(`/stocks/${id}`);
            const s   = res.data;
            const affectations = s.active_affectations || s.affectations || [];

            const affectTable = affectations.length ? `
            <div class="detail-section">
                <h6>Affectations actives</h6>
                <table class="table table-sm">
                    <thead><tr><th>Utilisateur</th><th>Quantité</th><th>Date</th><th></th></tr></thead>
                    <tbody>
                        ${affectations.map(a => `
                        <tr>
                            <td>${a.user?.name || '—'}</td>
                            <td>${a.quantity}</td>
                            <td><small>${UI.fmtDate(a.created_at)}</small></td>
                            <td>${API.isAdmin() && a.status === 'active'
                                ? `<button class="btn btn-sm btn-outline-warning py-0 px-2 btn-return-aff" data-aff="${a.id}"><i class="fas fa-undo me-1"></i>Retour</button>`
                                : ''
                            }</td>
                        </tr>`).join('')}
                    </tbody>
                </table>
            </div>` : '';

            const body = `
            <div class="detail-section">
                <h6>Informations générales</h6>
                <div class="detail-row"><span class="detail-label">Nom</span><span class="detail-value">${s.name}</span></div>
                <div class="detail-row"><span class="detail-label">Référence</span><span class="detail-value">${s.reference || '—'}</span></div>
                <div class="detail-row"><span class="detail-label">N° série</span><span class="detail-value">${s.serial_number || '—'}</span></div>
                <div class="detail-row"><span class="detail-label">Catégorie</span><span class="detail-value">${this.categoryLabel(s.category)}</span></div>
                <div class="detail-row"><span class="detail-label">Statut</span><span class="detail-value">${UI.badgeStockStatus(s.status)}</span></div>
                <div class="detail-row"><span class="detail-label">Quantité</span><span class="detail-value">${s.quantity} (min: ${s.quantity_min || 0})</span></div>
                <div class="detail-row"><span class="detail-label">Localisation</span><span class="detail-value">${s.location || '—'}</span></div>
                <div class="detail-row"><span class="detail-label">Marque / Modèle</span><span class="detail-value">${[s.brand, s.model].filter(Boolean).join(' / ') || '—'}</span></div>
                <div class="detail-row"><span class="detail-label">Prix d'achat</span><span class="detail-value">${s.purchase_price ? Number(s.purchase_price).toLocaleString('fr-FR') + ' FCFA' : '—'}</span></div>
                <div class="detail-row"><span class="detail-label">Date achat</span><span class="detail-value">${UI.fmtDate(s.purchase_date)}</span></div>
                <div class="detail-row"><span class="detail-label">Fin garantie</span><span class="detail-value">${UI.fmtDate(s.warranty_end)}</span></div>
            </div>
            ${affectTable}`;

            UI.showModal(`Matériel — ${s.name}`, body);

            // Bind return buttons
            document.querySelectorAll('.btn-return-aff').forEach(b => {
                b.addEventListener('click', async () => {
                    if (!confirm('Confirmer le retour de ce matériel ?')) return;
                    try {
                        await API.patch(`/stocks/affectations/${b.dataset.aff}/return`);
                        UI.hideModal();
                        UI.toast('Matériel retourné au stock.', 'success');
                        this.load(this.currentPage);
                    } catch (e) { UI.toast(e.message, 'error'); }
                });
            });
        } catch (e) { UI.toast(e.message, 'error'); }
    },

    // ── Supprimer ───────────────────────────────────────────────────────
    async delete(id) {
        try {
            await API.delete(`/stocks/${id}`);
            UI.toast('Matériel supprimé.', 'success');
            this.load(this.currentPage);
        } catch (e) { UI.toast(e.message, 'error'); }
    },

    // ── Stock bas ────────────────────────────────────────────────────────
    async showLowStock() {
        try {
            const res = await API.get('/stocks/low-stock');
            const items = res.data || [];
            if (!items.length) { UI.toast('Aucun article en stock bas.', 'info'); return; }
            const body = `
            <table class="table table-sm">
                <thead><tr><th>Matériel</th><th>Quantité</th><th>Min</th><th>Statut</th></tr></thead>
                <tbody>
                    ${items.map(s => `
                    <tr>
                        <td>${s.name}</td>
                        <td class="text-danger fw-bold">${s.quantity}</td>
                        <td>${s.quantity_min}</td>
                        <td>${UI.badgeStockStatus(s.status)}</td>
                    </tr>`).join('')}
                </tbody>
            </table>`;
            UI.showModal(`Alertes stock bas (${items.length})`, body);
        } catch (e) { UI.toast(e.message, 'error'); }
    },

    // ── Filtres ─────────────────────────────────────────────────────────
    bindFilters() {
        document.getElementById('stock-btn-filter')?.addEventListener('click', () => {
            this.filters = {
                search:   document.getElementById('stock-search').value.trim() || undefined,
                category: document.getElementById('stock-filter-category').value || undefined,
                status:   document.getElementById('stock-filter-status').value || undefined,
            };
            Object.keys(this.filters).forEach(k => this.filters[k] === undefined && delete this.filters[k]);
            this.load(1);
        });

        document.getElementById('btn-new-stock')?.addEventListener('click', () => this.openForm());
        document.getElementById('btn-low-stock')?.addEventListener('click', () => this.showLowStock());
    },
};
