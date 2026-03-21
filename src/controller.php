<?php

	class Controller {
		protected function view($name, $data = []) {
			extract($data);
			require __DIR__ . "/../views/partials/header.php";
			require __DIR__ . "/../views/pages/$name.php";
			require __DIR__ . "/../views/partials/footer.php";
		}
	}
?>