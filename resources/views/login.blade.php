<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — Gestion Parc Informatique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .login-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.4);
            padding: 48px 40px;
            width: 100%;
            max-width: 440px;
        }
        .login-logo {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }
        .login-logo i { color: #fff; font-size: 28px; }
        .login-title { font-size: 1.5rem; font-weight: 700; color: #1e293b; text-align: center; margin-bottom: 4px; }
        .login-subtitle { color: #64748b; text-align: center; margin-bottom: 32px; font-size: 0.9rem; }
        .form-label { font-weight: 600; color: #374151; font-size: 0.875rem; }
        .form-control {
            border-radius: 10px;
            border: 1.5px solid #e2e8f0;
            padding: 12px 16px;
            font-size: 0.95rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.15);
        }
        .input-group-text {
            border-radius: 10px 0 0 10px;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-right: none;
            color: #64748b;
        }
        .input-group .form-control { border-radius: 0 10px 10px 0; }
        .btn-login {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border: none;
            border-radius: 10px;
            padding: 13px;
            font-size: 1rem;
            font-weight: 600;
            letter-spacing: 0.3px;
            width: 100%;
            color: #fff;
            transition: opacity 0.2s, transform 0.1s;
        }
        .btn-login:hover { opacity: 0.92; transform: translateY(-1px); color: #fff; }
        .btn-login:active { transform: translateY(0); }
        .btn-login:disabled { opacity: 0.6; transform: none; }
        .alert-danger { border-radius: 10px; font-size: 0.9rem; }
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-logo">
        <i class="fas fa-server"></i>
    </div>
    <h1 class="login-title">Gestion Parc IT</h1>
    <p class="login-subtitle">Connectez-vous à votre espace</p>

    <div id="alert-error" class="alert alert-danger d-none" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <span id="alert-message"></span>
    </div>

    <form id="login-form" novalidate>
        <div class="mb-4">
            <label for="email" class="form-label">Adresse email</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                <input type="email" class="form-control" id="email" placeholder="email@exemple.com" required autocomplete="email">
            </div>
        </div>
        <div class="mb-4">
            <label for="password" class="form-label">Mot de passe</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" class="form-control" id="password" placeholder="••••••••" required autocomplete="current-password">
                <button type="button" class="btn btn-outline-secondary" id="toggle-password" style="border-radius:0 10px 10px 0;border:1.5px solid #e2e8f0;border-left:none;">
                    <i class="fas fa-eye" id="eye-icon"></i>
                </button>
            </div>
        </div>
        <button type="submit" class="btn btn-login" id="btn-submit">
            <span id="btn-text"><i class="fas fa-sign-in-alt me-2"></i>Se connecter</span>
            <span id="btn-loading" class="d-none">
                <span class="spinner-border spinner-border-sm me-2" role="status"></span>Connexion...
            </span>
        </button>
    </form>

</div>

<script>
    const API_BASE = '/api';

    // Toggle password visibility
    document.getElementById('toggle-password').addEventListener('click', () => {
        const pwd = document.getElementById('password');
        const icon = document.getElementById('eye-icon');
        if (pwd.type === 'password') {
            pwd.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            pwd.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });

    document.getElementById('login-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const email    = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        const btnText  = document.getElementById('btn-text');
        const btnLoad  = document.getElementById('btn-loading');
        const btnSubmit = document.getElementById('btn-submit');
        const alertEl  = document.getElementById('alert-error');
        const alertMsg = document.getElementById('alert-message');

        // Show loading
        btnText.classList.add('d-none');
        btnLoad.classList.remove('d-none');
        btnSubmit.disabled = true;
        alertEl.classList.add('d-none');

        try {
            const res = await fetch(`${API_BASE}/auth/login`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ email, password }),
            });
            const json = await res.json();

            if (!res.ok) {
                throw new Error(json.message || 'Email ou mot de passe incorrect.');
            }

            // Store JWT + user info
            localStorage.setItem('token', json.data.access_token);
            localStorage.setItem('user', JSON.stringify(json.data.user));

            window.location.href = '/app';
        } catch (err) {
            alertMsg.textContent = err.message;
            alertEl.classList.remove('d-none');
        } finally {
            btnText.classList.remove('d-none');
            btnLoad.classList.add('d-none');
            btnSubmit.disabled = false;
        }
    });

    // If already logged in, redirect
    if (localStorage.getItem('token')) {
        window.location.href = '/app';
    }
</script>
</body>
</html>
