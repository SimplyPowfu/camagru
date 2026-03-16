<?php
    function runDatabaseSetup($pdo, $db_name) {
        try {
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$db_name` ");

            // Tabella Utenti: gestisce registrazione, attivazione e notifiche
            $sql_users = "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                activation_token VARCHAR(100),
                is_active TINYINT(1) DEFAULT 0,
                notify_comments TINYINT(1) DEFAULT 1,
                reset_token VARCHAR(100) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            $pdo->exec($sql_users);

            // Tabella Immagini: memorizza i percorsi dei file salvati sul server
            $sql_images = "CREATE TABLE IF NOT EXISTS images (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                file_path VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )";
            $pdo->exec($sql_images);

            // Tabella Commenti: per la funzionalità sociale della gallery
            $sql_comments = "CREATE TABLE IF NOT EXISTS comments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                image_id INT NOT NULL,
                user_id INT NOT NULL,
                content TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )";
            $pdo->exec($sql_comments);

            // Tabella Likes: per gestire i 'like' dei soli utenti connessi
            $sql_likes = "CREATE TABLE IF NOT EXISTS likes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                image_id INT NOT NULL,
                user_id INT NOT NULL,
                FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE KEY unique_like (image_id, user_id)
            )";
            $pdo->exec($sql_likes);
            return true;
        } catch (PDOException $e) {
            die("Errore durante il setup del database: " . $e->getMessage());
            return false;
        }
    }
?>