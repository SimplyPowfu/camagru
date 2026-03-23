<?php

	class Controller {
		protected function view($name, $data = []) {
			extract($data);
			require __DIR__ . "/../views/partials/header.php";
			echo '<div class="main-layout-container" style="display: flex; gap: 20px;">';
				echo '<main style="flex: 1;">';
					require __DIR__ . "/../views/pages/$name.php";
				echo '</main>';
				if (isset($_SESSION['user']))
					require __DIR__ . "/../views/partials/side.php";
			echo '</div>';
			require __DIR__ . "/../views/partials/footer.php";
		}
	}
?>