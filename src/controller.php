<?php

    class Controller {
        protected function view($name, $data = []) {
            extract($data);
            
            require __DIR__ . "/../views/partials/header.php";
			
            echo '<main class="main-content">';
                require __DIR__ . "/../views/pages/$name.php";
            echo '</main>';
            
            if (isset($_SESSION['user']) && $name != 'profile') {
                require __DIR__ . "/../views/partials/side.php";
            }
            require __DIR__ . "/../views/partials/footer.php";
        }
    }
?>