<?php

	class AuthMiddleware extends Controller{
		public function handle() {
			if (!isset($_SESSION['user'])) {
				$_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
				header('Location: /login');
				exit;
			}
		}
	}
?>