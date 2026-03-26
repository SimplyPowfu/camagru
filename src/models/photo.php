<?php

require_once __DIR__ . '/../utils/database.php';
require_once __DIR__ . '/../models/user.php';

class Photo {
    /**
     * Aggiunge la picture al db Images
     */
    public static function addPicture($user_id, $file_path) {
        $db = Database::getInstance();
        $stmt = $db->prepare("INSERT INTO images (user_id, file_path) 
                VALUES (:user_id, :file_path)");
        
        return $stmt->execute([
            'user_id'   => $user_id,
            'file_path' => $file_path,
        ]);
    }

    /**
     * Elimina una picture dal db Images
     */
    public static function removePicture($user_id, $file_path) {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM images WHERE file_path = :file_path AND user_id = :user_id");
        
        $stmt->execute([
            'user_id'   => $user_id,
            'file_path' => $file_path,
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Aggiunge un commento ad una picture al db Comments
     */
    public static function addComment($image_id, $user_id, $comment) {
        $db = Database::getInstance();
        $stmt = $db->prepare("INSERT INTO comments (image_id, user_id, content) 
                VALUES (:image_id, :user_id, :comment)");
        
        return $stmt->execute([
            'image_id' => $image_id,
            'user_id'   => $user_id,
            'comment' => $comment
        ]);
    }

    /**
     * Ritorna l'email e la preferenza di notifica del proprietario dell'immagine
     */
    public static function getCreatorDetails($image_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT u.username, u.email, u.notify_comments 
                FROM users u 
                JOIN images i ON u.id = i.user_id 
                WHERE i.id = :image_id 
                LIMIT 1");
        $stmt->execute(['image_id' => $image_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Controlla se un utente ha già messo like a una foto
     */
    public static function checkLike($image_id, $user_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id FROM likes WHERE image_id = :image_id AND user_id = :user_id");
        $stmt->execute(['image_id' => $image_id, 'user_id' => $user_id]);
        return $stmt->fetch();
    }

    /**
     * Conta quanti like totali ha una foto
     */
    public static function countLikes($image_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM likes WHERE image_id = :image_id");
        $stmt->execute(['image_id' => $image_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? (int)$result['total'] : 0;
    }

    /**
     * Aggiunge un Like dal db Likes
     */
    public static function addLike($image_id, $user_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("INSERT INTO likes (image_id, user_id) VALUES (:image_id, :user_id)");
        return $stmt->execute(['image_id' => $image_id, 'user_id' => $user_id]);
    }

    /**
     * Rimuove un Like dal db Likes
     */
    public static function removeLike($image_id, $user_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM likes WHERE image_id = :image_id AND user_id = :user_id");
        return $stmt->execute(['image_id' => $image_id, 'user_id' => $user_id]);
    }

    /**
     * ritorna una picture con il path dal db Images
     */
    public static function getPictureToName($file_path) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT i.*, u.username 
            FROM images i 
            JOIN users u ON i.user_id = u.id 
            WHERE i.file_path = :file_path");
        $stmt->execute(['file_path' => $file_path]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function getPictureToId($image_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT file_path FROM images WHERE id = :image_id");
        $stmt->execute(['image_id' => $image_id]);
        
        return $stmt->fetchColumn();
    }

    /**
     * Prende le picture di uno user dal db Images
     */
    public static function getNamePictures($username) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT i.* FROM images i JOIN users u ON i.user_id = u.id WHERE u.username = :username ORDER BY i.created_at DESC");
        $stmt->execute(['username' => $username]);
        //fetchAll restituisce un array di righe
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * prendere i commenti di una picture dal db Comments
     */
    public static function getComment($image_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT c.*, u.username 
                FROM comments c 
                JOIN users u ON c.user_id = u.id 
                WHERE c.image_id = :image_id 
                ORDER BY c.created_at ASC");// ASC per leggerli in ordine cronologico
        $stmt->execute(['image_id' => $image_id]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
	/**
     * Prende N picture dal db Images
     */
    public static function getPictures($limit = 10) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM images ORDER BY created_at DESC LIMIT :limit");
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->execute();
        //fetchAll restituisce un array di righe
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}