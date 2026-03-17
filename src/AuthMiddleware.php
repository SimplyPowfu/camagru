<?php

	class AuthMiddleware extends Controller{
		public function handle() {
			if (!isset($_SESSION['user'])) {
				header('Location: /login');
				exit;
			}
		}
	}
?>