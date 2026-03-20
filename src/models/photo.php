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

    public static function getPictureToName($file_path) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM images WHERE file_path = :filepath");
        $stmt->execute(['file_path' => $file_path]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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