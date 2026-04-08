/**
 * API — wrapper Fetch avec gestion JWT
 */
const API = {
    base: '/api',

    token() { return localStorage.getItem('token'); },

    user() {
        try { return JSON.parse(localStorage.getItem('user') || 'null'); }
        catch { return null; }
    },

    async request(method, endpoint, data = null, isFormData = false) {
        const headers = { 'Accept': 'application/json' };
        if (this.token()) headers['Authorization'] = `Bearer ${this.token()}`;
        if (!isFormData) headers['Content-Type'] = 'application/json';

        const opts = { method, headers };
        if (data) opts.body = isFormData ? data : JSON.stringify(data);

        let res;
        try {
            res = await fetch(`${this.base}${endpoint}`, opts);
        } catch (e) {
            throw new Error('Erreur réseau — le serveur est inaccessible.');
        }

        // Token expiré / non authentifié
        if (res.status === 401) {
            localStorage.clear();
            window.location.href = '/login';
            return;
        }

        const json = await res.json().catch(() => ({}));

        if (!res.ok) {
            // Laravel validation errors (422)
            if (res.status === 422 && json.errors) {
                const msgs = Object.values(json.errors).flat().join('\n');
                throw new Error(msgs);
            }
            throw new Error(json.message || `Erreur ${res.status}`);
        }
        return json;
    },

    get(ep)                        { return this.request('GET',    ep); },
    post(ep, d, fd = false)        { return this.request('POST',   ep, d, fd); },
    put(ep, d)                     { return this.request('PUT',    ep, d); },
    patch(ep, d)                   { return this.request('PATCH',  ep, d); },
    delete(ep)                     { return this.request('DELETE', ep); },

    // ─── Helpers Auth ────────────────────────────────────────────────────

    isLoggedIn() { return !!this.token(); },

    getRole() { return this.user()?.role || null; },

    isAdmin()      { return this.getRole() === 'admin'; },
    isTechnicien() { return this.getRole() === 'technicien'; },
    isUtilisateur(){ return this.getRole() === 'utilisateur'; },
};
