<?php

	class Router {
		private $routes = [];

		public function get($path, $callback, $middleware = null) {
			$this->routes['GET'][$path] = [
				'callback' => $callback,
				'middleware' => $middleware
			];
		}
		public function resolve($uri, $method) {
			$path = parse_url($uri, PHP_URL_PATH);

			if (isset($this->routes[$method][$path])) {
				$route = $this->routes[$method][$path];
				if ($route['middleware']) {
					require_once __DIR__ . '/AuthMiddleware.php';
					(new $route['middleware'])->handle();
				}
				return call_user_func($route['callback']);
			}
			http_response_code(404);
			require __DIR__ . '/../views/pages/404.php';
		}
	}
?>