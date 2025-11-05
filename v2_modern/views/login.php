<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RSS Reader - Connexion</title>
    <link rel="stylesheet" href="/v2/assets/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <svg class="logo-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M6.18 15.64a2.18 2.18 0 0 1 2.18 2.18C8.36 19 7.38 20 6.18 20C5 20 4 19 4 17.82a2.18 2.18 0 0 1 2.18-2.18M4 4.44A15.56 15.56 0 0 1 19.56 20h-2.83A12.73 12.73 0 0 0 4 7.27V4.44m0 5.66a9.9 9.9 0 0 1 9.9 9.9h-2.83A7.07 7.07 0 0 0 4 12.93V10.1z" fill="currentColor"/>
                </svg>
                <h1>RSS Reader</h1>
                <p class="subtitle">Connexion à votre lecteur de flux</p>
            </div>

            <div id="error-message" class="error-message" style="display: none;"></div>

            <form id="login-form" class="login-form">
                <div class="form-group">
                    <label for="username">Nom d'utilisateur</label>
                    <input type="text" id="username" name="username" required autocomplete="username" autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>

                <button type="submit" class="btn-primary" id="login-btn">
                    <span class="btn-text">Se connecter</span>
                    <span class="btn-loader" style="display: none;">
                        <span class="loader"></span>
                    </span>
                </button>
            </form>

            <div class="login-footer">
                <p>Compte de test : admin / admin123</p>
            </div>
        </div>
    </div>

    <script>
        const loginForm = document.getElementById('login-form');
        const loginBtn = document.getElementById('login-btn');
        const errorMsg = document.getElementById('error-message');
        const btnText = loginBtn.querySelector('.btn-text');
        const btnLoader = loginBtn.querySelector('.btn-loader');

        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            // Show loader
            btnText.style.display = 'none';
            btnLoader.style.display = 'inline-block';
            loginBtn.disabled = true;
            errorMsg.style.display = 'none';

            try {
                const response = await fetch('/v2/api/auth/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ username, password })
                });

                const data = await response.json();

                if (data.success) {
                    window.location.href = '/v2/';
                } else {
                    errorMsg.textContent = data.message || 'Identifiants incorrects';
                    errorMsg.style.display = 'block';
                }
            } catch (error) {
                errorMsg.textContent = 'Erreur de connexion. Veuillez réessayer.';
                errorMsg.style.display = 'block';
            } finally {
                btnText.style.display = 'inline';
                btnLoader.style.display = 'none';
                loginBtn.disabled = false;
            }
        });
    </script>
</body>
</html>
