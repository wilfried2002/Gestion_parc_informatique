/**
 * UI — helpers : toast, badges, modal, loading, pagination
 */
const UI = {

    // ─── Toast ───────────────────────────────────────────────────────────
    toast(message, type = 'success', duration = 4000) {
        const icons = { success: 'fa-check-circle', error: 'fa-times-circle', info: 'fa-info-circle', warning: 'fa-exclamation-triangle' };
        const el = document.createElement('div');
        el.className = `toast-msg ${type}`;
        el.innerHTML = `<i class="fas ${icons[type] || icons.info}"></i><span>${message}</span>`;
        document.getElementById('toast-container').appendChild(el);
        setTimeout(() => el.remove(), duration);
    },

    // ─── Loading ─────────────────────────────────────────────────────────
    loading() {
        return `<div class="loading-wrap"><div class="spinner-border text-primary" style="width:2rem;height:2rem;"></div><span>Chargement...</span></div>`;
    },

    empty(msg = 'Aucun élément trouvé.', icon = 'fa-inbox') {
        return `<div class="empty-state"><i class="fas ${icon}"></i><p>${msg}</p></div>`;
    },

    // ─── Badges ──────────────────────────────────────────────────────────
    badgeTicketStatus(status) {
        const labels = { open: 'Ouvert', assigned: 'Assigné', in_progress: 'En cours', resolved: 'Résolu', closed: 'Clôturé' };
        return `<span class="badge badge-${status}">${labels[status] || status}</span>`;
    },

    badgePriority(priority) {
        const labels = { low: 'Faible', medium: 'Moyenne', high: 'Haute', critical: 'Critique' };
        return `<span class="badge badge-${priority}">${labels[priority] || priority}</span>`;
    },

    badgeIntervStatus(status) {
        const labels = { planned: 'Planifiée', in_progress: 'En cours', completed: 'Terminée', cancelled: 'Annulée' };
        return `<span class="badge badge-${status}">${labels[status] || status}</span>`;
    },

    badgeStockStatus(status) {
        const labels = { disponible: 'Disponible', affecte: 'Affecté', maintenance: 'Maintenance', hors_service: 'Hors service' };
        return `<span class="badge badge-${status}">${labels[status] || status}</span>`;
    },

    badgeUserStatus(isActive) {
        return isActive
            ? `<span class="badge badge-active">Actif</span>`
            : `<span class="badge badge-inactive">Inactif</span>`;
    },

    badgeRole(role) {
        // Accepte une string ou un objet {name, label}
        const name = (typeof role === 'object' && role !== null) ? role.name : role;
        const map = {
            admin:       ['#eff6ff', '#2563eb', 'Administrateur'],
            technicien:  ['#fffbeb', '#d97706', 'Technicien'],
            utilisateur: ['#f1f5f9', '#475569', 'Utilisateur'],
        };
        const [bg, color, label] = map[name] || ['#f1f5f9', '#475569', name || '—'];
        return `<span class="badge" style="background:${bg};color:${color}">${label}</span>`;
    },

    // ─── Date formatters ─────────────────────────────────────────────────
    fmtDate(dateStr) {
        if (!dateStr) return '—';
        return new Date(dateStr).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
    },

    fmtDatetime(dateStr) {
        if (!dateStr) return '—';
        return new Date(dateStr).toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    },

    // ─── Global Modal ────────────────────────────────────────────────────
    _modal: null,

    getModal() {
        if (!this._modal) this._modal = new bootstrap.Modal(document.getElementById('globalModal'));
        return this._modal;
    },

    showModal(title, bodyHtml, confirmLabel = 'Enregistrer', onConfirm = null) {
        document.getElementById('globalModalTitle').textContent = title;
        document.getElementById('globalModalBody').innerHTML = bodyHtml;
        const footer = document.getElementById('globalModalFooter');

        if (onConfirm) {
            footer.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="globalModalConfirm">${confirmLabel}</button>`;
            document.getElementById('globalModalConfirm').onclick = onConfirm;
        } else {
            footer.innerHTML = `<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>`;
        }
        this.getModal().show();
    },

    hideModal() { this.getModal().hide(); },

    // ─── Confirm Modal ───────────────────────────────────────────────────
    _confirm: null,

    getConfirm() {
        if (!this._confirm) this._confirm = new bootstrap.Modal(document.getElementById('confirmModal'));
        return this._confirm;
    },

    confirm(message, onOk) {
        document.getElementById('confirmModalBody').textContent = message;
        document.getElementById('confirmModalOk').onclick = () => { onOk(); this.getConfirm().hide(); };
        this.getConfirm().show();
    },

    // ─── Pagination ──────────────────────────────────────────────────────
    pagination(meta, onPage) {
        if (!meta || meta.last_page <= 1) return '';
        const { current_page, last_page, from, to, total } = meta;
        let pages = '';
        const start = Math.max(1, current_page - 2);
        const end   = Math.min(last_page, current_page + 2);
        if (start > 1) pages += `<li class="page-item"><a class="page-link" href="#" data-p="1">1</a></li>`;
        if (start > 2) pages += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
        for (let p = start; p <= end; p++) {
            pages += `<li class="page-item ${p === current_page ? 'active' : ''}"><a class="page-link" href="#" data-p="${p}">${p}</a></li>`;
        }
        if (end < last_page - 1) pages += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
        if (end < last_page) pages += `<li class="page-item"><a class="page-link" href="#" data-p="${last_page}">${last_page}</a></li>`;

        return `
        <div class="pagination-bar">
            <span class="pagination-info">Affichage ${from}–${to} sur ${total} résultats</span>
            <ul class="pagination pagination-sm mb-0">${pages}</ul>
        </div>`;
    },

    bindPagination(containerId, onPage) {
        const el = document.getElementById(containerId);
        if (!el) return;
        el.querySelectorAll('.page-link[data-p]').forEach(a => {
            a.addEventListener('click', e => { e.preventDefault(); onPage(+a.dataset.p); });
        });
    },

    // ─── Form helpers ────────────────────────────────────────────────────
    formData(formId) {
        const form = document.getElementById(formId);
        const data = {};
        new FormData(form).forEach((v, k) => { if (v !== '') data[k] = v; });
        return data;
    },

    setFormValues(formId, values) {
        const form = document.getElementById(formId);
        if (!form) return;
        Object.entries(values).forEach(([k, v]) => {
            const el = form.querySelector(`[name="${k}"]`);
            if (el && v