<?php

	require_once __DIR__ . '/../controller.php';

	class HomeController extends Controller {
		public function index() {
			$this->view('home');
		}
	}
?>