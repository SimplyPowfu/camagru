<?php

function getDatabaseConnection() {
    $host = $port = $dbname = $user = $pass = null;
    $envPath = __DIR__ . '/../.env';

    if (file_exists($envPath)) {
        $env = parse_ini_file($envPath);
        if ($env !== false) {
            $host = $env['DB_HOST'] ?? null;
            $port = $env['DB_PORT'] ?? null;
            $dbname = $env['DB_NAME'] ?? null;
            $user = $env['DB_USER'] ?? null;
            $pass = $env['DB_PASS'] ?? null;
        }
    } else {
        $host = getenv('DB_HOST');
        $port = getenv('DB_PORT');
        $dbname = getenv('DB_NAME');
        $user = getenv('DB_USER');
        $pass = getenv('DB_PASS');
    }

    if (!$host || !$port || !$dbname || !$user || !$pass) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Errore Server: Credenziali DB mancanti nell\'ambiente.']);
        exit;
    }

    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        
        // Opzioni di configurazione PDO
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        return new PDO($dsn, $user, $pass, $options);
        
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Connessione al DB fallita: ' . $e->getMessage()]);
        exit;
    }
}
