<?php

require_once __DIR__ . '/../utils/database.php';

class User {

    /**
     * Trova un utente per username
     */
    public static function findByUsername($username) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute(['username' => $username]);
        return $stmt->fetch();
    }

    /**
     * Trova un utente per email
     */
    public static function findByEmail($email) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    /**
     * Verifica se username o email esistono già (per la registrazione)
     */
    public static function exists($username, $email) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id FROM users WHERE username = :u OR email = :e LIMIT 1");
        $stmt->execute(['u' => $username, 'e' => $email]);
        return $stmt->fetch() !== false;
    }

    /**
     * Crea un nuovo utente
     */
    public static function create($username, $email, $password, $token) {
        $db = Database::getInstance();
        $sql = "INSERT INTO users (username, email, password, activation_token, is_active) 
                VALUES (:username, :email, :password, :token, 0)";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            'username' => $username,
            'email'    => $email,
            'password' => $password,
            'token'    => $token
        ]);
    }

    /**
     * prende un file_path e la salva sul db
     */
    // public static function addPicture($id, $file_path) {
        
    // }

    /**
     * Attiva un account tramite token
     */
    public static function activateByToken($token) {
        $db = Database::getInstance();
        // Cerchiamo l'utente con quel token
        $stmt = $db->prepare("SELECT id FROM users WHERE activation_token = :token AND is_active = 0 LIMIT 1");
        $stmt->execute(['token' => $token]);
        $user = $stmt->fetch();

        if ($user) {
            $update = $db->prepare("UPDATE users SET is_active = 1, activation_token = NULL WHERE id = :id");
            return $update->execute(['id' => $user['id']]);
        }
        return false;
    }

    /**
     * Gestione Password Reset: Imposta il token
     */
    public static function setResetToken($email, $token) {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE users SET reset_token = :token WHERE email = :email");
        return $stmt->execute(['token' => $token, 'email' => $email]);
    }

    /**
     * Gestione Password Reset: Verifica token e aggiorna password
     */
    public static function updatePasswordByResetToken($token, $hashedPassword) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id FROM users WHERE reset_token = :token LIMIT 1");
        $stmt->execute(['token' => $token]);
        $user = $stmt->fetch();

        if ($user) {
            $update = $db->prepare("UPDATE users SET password = :password, reset_token = NULL WHERE id = :id");
            return $update->execute([
                'password' => $hashedPassword, 
                'id' => $user['id']
            ]);
        }
        return false;
    }
    
    /**
     * Verifica se un reset token è valido
     */
    public static function isResetTokenValid($token) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id FROM users WHERE reset_token = :token LIMIT 1");
        $stmt->execute(['token' => $token]);
        return $stmt->fetch() !== false;
    }
}