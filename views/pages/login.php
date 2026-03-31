<div class="card" style="max-width: 400px; margin: 40px auto;">
    <h2 style="color: var(--primary); font-weight: 800; text-align: center; margin-bottom: 20px;">
        <i class="fa-solid fa-right-to-bracket"></i> Accedi
    </h2>

    <form id="loginForm" autocomplete="off">
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; color: var(--text-main); font-weight: 600;">Username</label>
            <input type="text" name="username" required autocomplete="off" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border); background: var(--background); outline: none;">
        </div>
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 5px; color: var(--text-main); font-weight: 600;">Password</label>
            <input type="password" name="password" required autocomplete="new-password" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border); background: var(--background); outline: none;">
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 1rem;">
            Login
        </button>
    </form>
    <p id="message" style="margin-top: 15px; font-weight: 600; text-align: center;"></p>

    <hr style="border: none; border-top: 1px solid var(--border); margin: 25px 0;">

    <h4 style="color: var(--text-main); text-align: center; margin-bottom: 15px; font-weight: 600;">Password smarrita?</h4>
    <form id="resetForm" autocomplete="off">
        <div style="margin-bottom: 15px;">
            <input type="email" name="email" placeholder="La tua email..." required autocomplete="off" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border); background: var(--background); outline: none;">
        </div>
        <button type="submit" class="btn btn-secondary" style="width: 100%; padding: 10px;">
            <i class="fa-solid fa-envelope"></i> Invia Link di Reset
        </button>
    </form>
    <p id="reset-message" style="margin-top: 15px; font-weight: 600; text-align: center;"></p>

    <div style="text-align: center; margin-top: 20px;">
        <a href="/register" style="color: var(--primary); text-decoration: none; font-weight: 600;">Non hai un account? Registrati</a>
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
                messageElement.style.color = 'var(--success)';
                messageElement.innerHTML = '<i class="fa-solid fa-circle-check"></i> Login riuscito! Reindirizzamento...';
                setTimeout(() => { window.location.href = '/'; }, 1000);
            } else {
                messageElement.style.color = 'var(--danger)';
                messageElement.textContent = result.message;
                btn.disabled = false;
                btn.innerHTML = 'Login';
            }
        } catch (error) {
            messageElement.style.color = 'var(--danger)';
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
                messageElem.style.color = 'var(--success)';
                messageElem.innerHTML = '<i class="fa-solid fa-circle-check"></i> Email inviata!';
            } else {
                messageElem.style.color = 'var(--danger)';
                messageElem.textContent = result.message;
            }
        } catch (error) {
            messageElem.style.color = 'var(--danger)';
            messageElem.textContent = 'Errore di connessione al server.';
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-envelope"></i> Invia Link di Reset';
        }
    });
</script>