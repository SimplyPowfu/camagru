<?php

	require_once __DIR__ . '/../controller.php';
	require_once __DIR__ . '/../utils/database.php';

	class AuthController extends Controller {

		public function login() {
			$this->view('login');
		}

		public function register() {
			$this->view('register');
		}

		public function editing() {

			$this->view('editing');
		}
	}
?>