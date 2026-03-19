<h1>Reset Password</h1>

<form id="passForm">
    <div>
        <label>Password</label>
        <input type="password" name="password" required>
    </div>
    <button type="submit">Reset</button>
</form>
<p id="message" style="margin-top: 5px;"></p>

<script>
    document.getElementById('passForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const messageElement = document.getElementById('message');
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
                messageElement.style.color = 'green';
                messageElement.textContent = 'Password aggiornata! Reindirizzamento...';
                setTimeout(() => { window.location.href = '/login'; }, 1000);
            } else {
                messageElement.style.color = 'red';
                messageElement.textContent = result.message;
            }
        } catch (error) {
            messageElement.style.color = 'red';
            messageElement.textContent = 'Errore di connessione.';
        }
    });
</script>