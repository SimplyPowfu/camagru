<div class="card auth-container">
    <h2 class="auth-header">
        <i class="fa-solid fa-user-plus"></i> Registrati
    </h2>

    <form id="registerForm" autocomplete="off">
        <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" required autocomplete="off">
        </div>
        <div class="form-group">
            <label class="form-label">Username</label>
            <input type="text" name="username" required autocomplete="off">
        </div>
        <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" required autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-primary w-100">
            Crea Account
        </button>
    </form>

    <p id="message" class="form-message"></p>

    <div class="auth-link-container">
        <a href="/login" class="auth-link">Hai già un account? Accedi</a>
    </div>
</div>

<script>
    document.getElementById('registerForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        const messageElement = document.getElementById('message');

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Registrazione...';

        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch('/api/register', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            
            if (result.success) {
                messageElement.className = 'form-message text-success';
                messageElement.innerHTML = '<i class="fa-solid fa-circle-check"></i> Registrazione riuscita!<br>Controlla l\'email per attivare l\'account.';
                setTimeout(() => { window.location.href = '/login'; }, 2500);
            } else {
                messageElement.className = 'form-message text-danger';
                messageElement.textContent = result.message;
                btn.disabled = false;
                btn.innerHTML = 'Crea Account';
            }
        } catch (error) {
            messageElement.className = 'form-message text-danger';
            messageElement.textContent = 'Errore di connessione al server.';
            btn.disabled = false;
            btn.innerHTML = 'Crea Account';
        }
    });
</script>