<?php
	session_start();
	$route = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
	
	switch ($route) {
		case '/':
			echo "<h1>Benvenuto nella Home/Gallery</h1>";
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

	require_once __DIR__ . '/../config/database.php';
	require_once __DIR__ . '/../config/setup.php';

	try {
		$db = getDatabaseConnection();
		
		$check = $db->query("SHOW TABLES LIKE 'users'");
		if ($check->rowCount() == 0) {
			$env = parse_ini_file(__DIR__ . '/../.env', false, INI_SCANNER_RAW);
			if (runDatabaseSetup($db, $env['DB_NAME'])) {
				echo "<p style='color: blue;'>⚙️ Sistema inizializzato automaticamente.</p>";
			} else {
				die("Errore critico durante l'inizializzazione automatica.");
			}
		}
		else
			echo "<p style='color: blue;'>⚙️ Sistema trovato.</p>";
	} catch (Exception $e) {
		echo "<p style='color: red;'>❌ Errore di connessione: " . $e->getMessage() . "</p>";
	}
	phpinfo();
?>