/**
 * Notifications — polling, son, badge, dropdown
 */
const Notifications = {
    pollInterval: null,
    lastUnreadCount: 0,
    audioCtx: null,

    // ── Initialisation ───────────────────────────────────────────────────
    init() {
        this.bindEvents();
        this.fetchAndRender();
        // Polling toutes les 30 secondes
        this.pollInterval = setInterval(() => this.fetchAndRender(), 30000);
    },

    // ── Récupération API ─────────────────────────────────────────────────
    async fetchAndRender() {
        try {
            const res  = await API.get('/notifications?limit=20');
            const data = res.data;
            this.renderBadge(data.unread_count);
            this.renderList(data.notifications);

            // Son si nouvelles notifications non lues
            if (data.unread_count > this.lastUnreadCount && this.lastUnreadCount !== null) {
                this.playSound();
            }
            this.lastUnreadCount = data.unread_count;
        } catch {}
    },

    // ── Badge ────────────────────────────────────────────────────────────
    renderBadge(count) {
        const badge = document.getElementById('notif-badge');
        if (!badge) return;
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.classList.remove('d-none');
            document.getElementById('notif-bell-btn')?.classList.add('has-notif');
        } else {
            badge.classList.add('d-none');
            document.getElementById('notif-bell-btn')?.classList.remove('has-notif');
        }
    },

    // ── Liste dropdown ───────────────────────────────────────────────────
    renderList(notifications) {
        const list = document.getElementById('notif-list');
        if (!list) return;

        if (!notifications || notifications.length === 0) {
            list.innerHTML = `
            <div class="notif-empty">
                <i class="fas fa-bell-slash"></i>
                <p>Aucune notification</p>
            </div>`;
            return;
        }

        list.innerHTML = notifications.map(n => this.renderItem(n)).join('');

        // Bind clics sur chaque item
        list.querySelectorAll('.notif-item').forEach(el => {
            el.addEventListener('click', () => {
                const id = el.dataset.id;
                if (!el.classList.contains('read')) this.markRead(id, el);
                // Naviguer vers les tickets si applicable
                if (el.dataset.ticketId) navigateTo('tickets');
                this.closeDropdown();
            });
        });

        // Boutons de suppression
        list.querySelectorAll('.notif-delete-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.deleteNotif(btn.dataset.id, btn.closest('.notif-item'));
            });
        });
    },

    renderItem(n) {
        const icons = {
            new_ticket:      { icon: 'fa-ticket-alt',  color: '#2563eb', bg: '#eff6ff' },
            ticket_assigned: { icon: 'fa-user-tag',    color: '#7c3aed', bg: '#f5f3ff' },
        };
        const cfg     = icons[n.type] || { icon: 'fa-bell', color: '#64748b', bg: '#f8fafc' };
        const timeAgo = this.timeAgo(n.created_at);
        const isRead  = n.read;

        const priorityBadge = n.priority ? `
        <span class="notif-priority notif-priority-${n.priority}">
            ${({ low:'Faible', medium:'Moyenne', high:'Haute', critical:'Critique' })[n.priority] || n.priority}
        </span>` : '';

        return `
        <div class="notif-item ${isRead ? 'read' : 'unread'}" data-id="${n.id}" data-ticket-id="${n.ticket_id || ''}">
            <div class="notif-icon" style="background:${cfg.bg};color:${cfg.color}">
                <i class="fas ${cfg.icon}"></i>
            </div>
            <div class="notif-content">
                <div class="notif-message">${n.message}</div>
                <div class="notif-meta">
                    ${priorityBadge}
                    <span class="notif-time"><i class="fas fa-clock me-1"></i>${timeAgo}</span>
                </div>
            </div>
            <div class="notif-actions">
                ${!isRead ? `<div class="notif-dot" title="Non lue"></div>` : ''}
                <button class="notif-delete-btn" data-id="${n.id}" title="Supprimer">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>`;
    },

    // ── Marquer comme lue ────────────────────────────────────────────────
    async markRead(id, el) {
        try {
            const res = await API.patch(`/notifications/${id}/read`);
            el?.classList.replace('unread', 'read');
            el?.querySelector('.notif-dot')?.remove();
            this.renderBadge(res.data?.unread_count ?? 0);
            this.lastUnreadCount = res.data?.unread_count ?? 0;
        } catch {}
    },

    // ── Tout marquer comme lu ────────────────────────────────────────────
    async markAllRead() {
        try {
            await API.patch('/notifications/read-all');
            document.querySelectorAll('.notif-item.unread').forEach(el => {
                el.classList.replace('unread', 'read');
                el.querySelector('.notif-dot')?.remove();
            });
            this.renderBadge(0);
            this.lastUnreadCount = 0;
        } catch {}
    },

    // ── Supprimer ────────────────────────────────────────────────────────
    async deleteNotif(id, el) {
        try {
            await API.delete(`/notifications/${id}`);
            el?.remove();
            if (!document.querySelector('.notif-item')) {
                document.getElementById('notif-list').innerHTML = `
                <div class="notif-empty">
                    <i class="fas fa-bell-slash"></i>
                    <p>Aucune notification</p>
                </div>`;
            }
            await this.fetchAndRender();
        } catch {}
    },

    // ── Son de notification (Web Audio API) ──────────────────────────────
    playSound() {
        try {
            if (!this.audioCtx) {
                this.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
            const ctx = this.audioCtx;

            // Mélodie 3 notes : do-mi-sol
            const notes = [523.25, 659.25, 783.99];
            notes.forEach((freq, i) => {
                const osc  = ctx.createOscillator();
                const gain = ctx.createGain();

                osc.connect(gain);
                gain.connect(ctx.destination);

                osc.type      = 'sine';
                osc.frequency.setValueAtTime(freq, ctx.currentTime + i * 0.15);

                gain.gain.setValueAtTime(0, ctx.currentTime + i * 0.15);
                gain.gain.linearRampToValueAtTime(0.3, ctx.currentTime + i * 0.15 + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + i * 0.15 + 0.3);

                osc.start(ctx.currentTime + i * 0.15);
                osc.stop(ctx.currentTime + i * 0.15 + 0.35);
            });
        } catch {}
    },

    // ── Dropdown ─────────────────────────────────────────────────────────
    bindEvents() {
        document.getElementById('notif-bell-btn')?.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleDropdown();
        });

        document.getElementById('notif-read-all')?.addEventListener('click', (e) => {
            e.stopPropagation();
            this.markAllRead();
        });

        // Fermer en cliquant ailleurs
        document.addEventListener('click', (e) => {
            if (!document.getElementById('notif-bell-wrap')?.contains(e.target)) {
                this.closeDropdown();
            }
        });
    },

    toggleDropdown() {
        const dd = document.getElementById('notif-dropdown');
        dd?.classList.toggle('d-none');
        if (!dd?.classList.contains('d-none')) this.fetchAndRender();
    },

    closeDropdown() {
        document.getElementById('notif-dropdown')?.classList.add('d-none');
    },

    // ── Helpers ──────────────────────────────────────────────────────────
    timeAgo(dateStr) {
        if (!dateStr) return '';
        const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
        if (diff < 60)      return "à l'instant";
        if (diff < 3600)    return `il y a ${Math.floor(diff / 60)} min`;
        if (diff < 86400)   return `il y a ${Math.floor(diff / 3600)} h`;
        if (diff < 604800)  return `il y a ${Math.floor(diff / 86400)} j`;
        return new Date(dateStr).toLocaleDateString('fr-FR');
    },

    stop() {
        if (this.pollInterval) clearInterval(this.pollInterval);
    },
};
