<h1>Home</h1>
<?php if (isset($_SESSION['user'])): ?>
    <p>Ciao, <strong><?php echo htmlspecialchars($_SESSION['user']['username']); ?></strong>!</p>
    <a href="/logout">Logout</a>
<?php else: ?>
    <a href="/login">Login</a>
<?php endif; ?>
<?php
	phpinfo();
?>
