<?php

function getDatabaseConnection() {
	$env = parse_ini_file(__DIR__ . '/../.env');

	if ($env === false)
		die("Errore critico: Impossibile leggere il file .env.");

    $host = $env['DB_HOST'];
    $dbname = $env['DB_NAME'];
    $user = $env['DB_USER'];
    $pass = $env['DB_PASS'];

    try {
        // DSN (Data Source Name) - Specifica il driver, l'host e il database
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        
        // Opzioni di configurazione PDO
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lancia eccezioni in caso di errore
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Restituisce array associativi di default
            PDO::ATTR_EMULATE_PREPARES   => false,                  // Usa i veri prepared statements (Sicurezza SQL Injection)
        ];

        return new PDO($dsn, $user, $pass, $options);
        
    } catch (PDOException $e) {
        die("Connessione al database fallita: " . $e->getMessage());
    }
}