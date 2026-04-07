<div class="card auth-container">
    <h2 class="auth-header">
        <i class="fa-solid fa-key"></i> Nuova Password
    </h2>

    <form id="passForm" autocomplete="off">
        <div class="form-group">
            <label class="form-label">Inserisci la nuova password</label>
            <input type="password" name="password" required autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-primary w-100">
            Aggiorna Password
        </button>
    </form>
    
    <p id="message" class="form-message"></p>
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
                messageElement.className = 'form-message text-success';
                messageElement.innerHTML = '<i class="fa-solid fa-circle-check"></i> Password aggiornata! Reindirizzamento...';
                setTimeout(() => { window.location.href = '/login'; }, 1500);
            } else {
                messageElement.className = 'form-message text-danger';
                messageElement.textContent = result.message;
                btn.disabled = false;
                btn.innerHTML = 'Aggiorna Password';
            }
        } catch (error) {
            messageElement.className = 'form-message text-danger';
            messageElement.textContent = 'Errore di connessione.';
            btn.disabled = false;
            btn.innerHTML = 'Aggiorna Password';
        }
    });
</script>