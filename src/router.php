<?php

    class Router {
        private $routes = [];

        // rotta GET
        public function get($path, $callback, $middleware = null) {
            $this->addRoute('GET', $path, $callback, $middleware);
        }

        // rotta POST
        public function post($path, $callback, $middleware = null) {
            $this->addRoute('POST', $path, $callback, $middleware);
        }

        // Metodo per aggiungere una logica aggiunta
        private function addRoute($method, $path, $callback, $middleware) {
            $this->routes[$method][$path] = [
                'callback' => $callback,
                'middleware' => $middleware
            ];
        }

        public function resolve($uri, $method) {
            // --- FIX PER RENDER HEALTH CHECK ---
            // Render usa richieste HEAD per verificare se il server è online.
            // Le "trasformiamo" in GET per evitare l'errore 405.
            if ($method === 'HEAD') {
                $method = 'GET';
            }
            // -----------------------------------

            $path = parse_url($uri, PHP_URL_PATH);

            // Verifica se la rotta esiste per il metodo richiesto
            if (isset($this->routes[$method][$path])) {
                $route = $this->routes[$method][$path];

                if ($route['middleware']) {
                    require_once __DIR__ . '/AuthMiddleware.php';
                    (new $route['middleware'])->handle();
                }

                return call_user_func($route['callback']);
            }

            // Gestione errore: Rotta esistente ma metodo sbagliato (es. POST su rotta GET)
            foreach ($this->routes as $m => $paths) {
                if ($m !== $method && isset($paths[$path])) {
                    http_response_code(405);
                    echo "405 Method Not Allowed";
                    return;
                }
            }

            http_response_code(404);
            require __DIR__ . "/../views/partials/header.php";
            require __DIR__ . '/../views/pages/404.php';
            require __DIR__ . "/../views/partials/footer.php";
        }
    }
?>