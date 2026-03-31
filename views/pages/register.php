<div class="card" style="max-width: 400px; margin: 40px auto;">
    <h2 style="color: var(--primary); font-weight: 800; text-align: center; margin-bottom: 20px;">
        <i class="fa-solid fa-user-plus"></i> Registrati
    </h2>

    <form id="registerForm" autocomplete="off">
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; color: var(--text-main); font-weight: 600;">Email</label>
            <input type="email" name="email" required autocomplete="off" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border); background: var(--background); outline: none;">
        </div>
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; color: var(--text-main); font-weight: 600;">Username</label>
            <input type="text" name="username" required autocomplete="off" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border); background: var(--background); outline: none;">
        </div>
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 5px; color: var(--text-main); font-weight: 600;">Password</label>
            <input type="password" name="password" required autocomplete="new-password" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border); background: var(--background); outline: none;">
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 1rem;">
            Crea Account
        </button>
    </form>

    <p id="message" style="margin-top: 15px; font-weight: 600; text-align: center; line-height: 1.4;"></p>

    <div style="text-align: center; margin-top: 20px;">
        <a href="/login" style="color: var(--primary); text-decoration: none; font-weight: 600;">Hai già un account? Accedi</a>
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
                messageElement.style.color = 'var(--success)';
                messageElement.innerHTML = '<i class="fa-solid fa-circle-check"></i> Registrazione riuscita!<br>Controlla l\'email per attivare l\'account.';
                setTimeout(() => { window.location.href = '/login'; }, 2500);
            } else {
                messageElement.style.color = 'var(--danger)';
                messageElement.textContent = result.message;
                btn.disabled = false;
                btn.innerHTML = 'Crea Account';
            }
        } catch (error) {
            messageElement.style.color = 'var(--danger)';
            messageElement.textContent = 'Errore di connessione al server.';
            btn.disabled = false;
            btn.innerHTML = 'Crea Account';
        }
    });
</script>