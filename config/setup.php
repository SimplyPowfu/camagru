<?php

$host = getenv('DB_HOST');
$name = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$port = getenv('DB_PORT');

if (!$host && file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env', false, INI_SCANNER_RAW);
    $host = $env['DB_HOST'] ?? 'db';
    $name = $env['DB_NAME'] ?? 'camagru';
    $user = $env['DB_USER'] ?? 'user';
    $pass = $env['DB_PASS'] ?? 'pass';
    $port = $env['DB_PORT'] ?? '3306';
}

try {
    // Ricordati di aggiungere la porta anche qui
    $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "📡 Connesso a MySQL ($host)\n";

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$name`");
    echo "🗄️ Database '$name' pronto.\n";

    $check = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($check->rowCount() > 0) {
        echo "✅ Tabelle già esistenti. Setup saltato.\n";
        exit(0);
    }

    echo "⚙️ Creazione schema in corso...\n";
    
    $queries = [
        "users" => "CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            activation_token VARCHAR(100),
            is_active TINYINT(1) DEFAULT 0,
            notify_comments TINYINT(1) DEFAULT 1,
            reset_token VARCHAR(100) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",

        "images" => "CREATE TABLE images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            filter_3d VARCHAR(50) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_image_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",

        "comments" => "CREATE TABLE comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            image_id INT NOT NULL,
            user_id INT NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_comment_image FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
            CONSTRAINT fk_comment_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",

        "likes" => "CREATE TABLE likes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            image_id INT NOT NULL,
            user_id INT NOT NULL,
            CONSTRAINT fk_like_image FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
            CONSTRAINT fk_like_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_like (image_id, user_id)
        ) ENGINE=InnoDB"
    ];

    foreach ($queries as $tableName => $sql) {
        $pdo->exec($sql);
        echo "   [OK] Tabella: $tableName\n";
    }

    echo "🚀 Setup completato con successo!\n";

} catch (PDOException $e) {
    fprintf(STDERR, "❌ Errore critico durante il setup: %s\n", $e->getMessage());
    exit(1);
}