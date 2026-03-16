<?php
	session_start();
	$route = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

	echo "<h1>Camagru: Ambiente pronto!</h1>";
	echo "<p>Il server PHP sta funzionando correttamente.</p>";

	switch ($route) {
		case '/':
			// Chiama il controller della Home/Gallery
			echo "<h2>Benvenuto nella Home/Gallery</h2>";
			break;
		case '/editing':
			// Protezione area privata 
			if (!isset($_SESSION['user'])) {
				header('Location: /login');
				exit;
			}
			// Carica la pagina di editing
			break;
		default:
			http_response_code(404);
			echo "Pagina non trovata";
			break;
	}
	// Questo ti mostra se le estensioni GD e PDO sono caricate
	phpinfo();
?>