<?php

function getDatabaseConnection() {
    // 1. Definiamo le variabili cercando prima nell'ambiente cloud
    $host = getenv('DB_HOST');
    $dbname = getenv('DB_NAME');
    $user = getenv('DB_USER');
    $pass = getenv('DB_PASS');
    $port = getenv('DB_PORT'); // Aiven usa la porta 24641, non la 3306

    // 2. Fallback per il locale: se non trova variabili d'ambiente, usa il file .env
    if (!$host && file_exists(__DIR__ . '/../.env')) {
        $env = parse_ini_file(__DIR__ . '/../.env');
        if ($env) {
            $host = $env['DB_HOST'];
            $dbname = $env['DB_NAME'];
            $user = $env['DB_USER'];
            $pass = $env['DB_PASS'];
            $port = $env['DB_PORT'] ?? '3306'; 
        }
    }

    // Se mancano dati critici, blocchiamo l'esecuzione in modo pulito
    if (!$host || !$user) {
        die("Errore critico: Credenziali del database mancanti.");
    }

    try {
        // NOTA: Abbiamo aggiunto 'port' al DSN! È obbligatorio per Aiven.
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        return new PDO($dsn, $user, $pass, $options);
        
    } catch (PDOException $e) {
        // In produzione non si stampa mai l'errore a schermo (rischio di sicurezza)
        error_log("Connessione al database fallita: " . $e->getMessage());
        die("Impossibile connettersi al database. Riprova più tardi.");
    }
}
?>