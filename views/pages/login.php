<div class="card auth-container">
    <h2 class="auth-header">
        <i class="fa-solid fa-right-to-bracket"></i> Accedi
    </h2>

    <form id="loginForm" autocomplete="off">
        <div class="form-group">
            <label class="form-label">Username</label>
            <input type="text" name="username" required autocomplete="off">
        </div>
        <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" required autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>
    
    <p id="message" class="form-message"></p>

    <hr class="auth-divider">

    <h4 class="auth-footer-title">Password smarrita?</h4>
    <form id="resetForm" autocomplete="off">
        <div class="form-group">
            <input type="email" name="email" placeholder="La tua email..." required autocomplete="off">
        </div>
        <button type="submit" class="btn btn-secondary w-100">
            <i class="fa-solid fa-envelope"></i> Invia Link di Reset
        </button>
    </form>
    
    <p id="reset-message" class="form-message"></p>

    <div class="auth-link-container">
        <a href="/register" class="auth-link">Non hai un account? Registrati</a>
    </div>
</div>

<script>
    document.getElementById('loginForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        const messageElement = document.getElementById('message');
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Accesso...';

        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch('/api/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                messageElement.className = 'form-message text-success';
                messageElement.innerHTML = '<i class="fa-solid fa-circle-check"></i> Login riuscito! Reindirizzamento...';
                setTimeout(() => { window.location.href = '/'; }, 1000);
            } else {
                messageElement.className = 'form-message text-danger';
                messageElement.textContent = result.message;
                btn.disabled = false;
                btn.innerHTML = 'Login';
            }
        } catch (error) {
            messageElement.className = 'form-message text-danger';
            messageElement.textContent = 'Errore di connessione al server.';
            btn.disabled = false;
            btn.innerHTML = 'Login';
        }
    });

    document.getElementById('resetForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        const messageElem = document.getElementById('reset-message');
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Invio...';

        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch ('/api/reset_token', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();

            if (result.success) {
                messageElem.className = 'form-message text-success';
                messageElem.innerHTML = '<i class="fa-solid fa-circle-check"></i> Email inviata!';
            } else {
                messageElem.className = 'form-message text-danger';
                messageElem.textContent = result.message;
            }
        } catch (error) {
            messageElem.className = 'form-message text-danger';
            messageElem.textContent = 'Errore di connessione al server.';
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-envelope"></i> Invia Link di Reset';
        }
    });
</script>