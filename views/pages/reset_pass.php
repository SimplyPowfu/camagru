<div class="card" style="max-width: 400px; margin: 40px auto;">
    <h2 style="color: var(--primary); font-weight: 800; text-align: center; margin-bottom: 20px;">
        <i class="fa-solid fa-key"></i> Nuova Password
    </h2>

    <form id="passForm" autocomplete="off">
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 5px; color: var(--text-main); font-weight: 600;">Inserisci la nuova password</label>
            <input type="password" name="password" required autocomplete="new-password" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border); background: var(--background); outline: none;">
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 1rem;">
            Aggiorna Password
        </button>
    </form>
    
    <p id="message" style="margin-top: 15px; font-weight: 600; text-align: center;"></p>
</div>

<script>
    document.getElementById('passForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        const messageElement = document.getElementById('message');
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Salvataggio...';

        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        const urlParams = new URLSearchParams(window.location.search);
        data.token = urlParams.get('token'); 

        try {
            const response = await fetch('/api/reset', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                messageElement.style.color = 'var(--success)';
                messageElement.innerHTML = '<i class="fa-solid fa-circle-check"></i> Password aggiornata! Reindirizzamento...';
                setTimeout(() => { window.location.href = '/login'; }, 1500);
            } else {
                messageElement.style.color = 'var(--danger)';
                messageElement.textContent = result.message;
                btn.disabled = false;
                btn.innerHTML = 'Aggiorna Password';
            }
        } catch (error) {
            messageElement.style.color = 'var(--danger)';
            messageElement.textContent = 'Errore di connessione.';
            btn.disabled = false;
            btn.innerHTML = 'Aggiorna Password';
        }
    });
</script>