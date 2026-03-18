<h1>Login</h1>

<form id="loginForm">
    <div>
        <label>Username</label>
        <input type="text" name="username" required>
    </div>
    <div>
        <label>Password</label>
        <input type="password" name="password" required>
    </div>
    <button type="submit">Login</button>
</form>

<p id="message" style="margin-top: 5px;"></p>

<a href="/register">Register</a>

<script>
    document.getElementById('loginForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const messageElement = document.getElementById('message');
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch('/api/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                messageElement.style.color = 'green';
                messageElement.textContent = 'Login riuscito! Reindirizzamento...';
                
                setTimeout(() => {
                    window.location.href = '/';
                }, 1000);
            } else {
                messageElement.style.color = 'red';
                messageElement.textContent = result.message;
            }
        } catch (error) {
            messageElement.style.color = 'red';
            messageElement.textContent = 'Errore di connessione al server.';
            console.error('Error:', error);
        }
    });
</script>