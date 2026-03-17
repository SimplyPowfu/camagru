<?php
	class Database {
		private static $instance = null;

		public static function getInstance() {
			if (self::$instance === null) {
				require_once __DIR__ . '/../../config/database.php';
				self::$instance = getDatabaseConnection();
			}
			return self::$instance;
		}
	}
?>