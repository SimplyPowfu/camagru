<?php
	
	session_start();
	
	require_once __DIR__ . '/../src/router.php';
	require_once __DIR__ . '/../src/controllers/HomeController.php';
	require_once __DIR__ . '/../src/controllers/AuthController.php';

	$router = new Router();

	// Controllers
	$home = new HomeController();
	$auth = new AuthController();

	// Routes get
	$router->get('/', [$home, 'index']);
	$router->get('/login', [$auth, 'login']);
	$router->get('/register', [$auth, 'register']);
	$router->get('/editing', [$auth, 'editing'], 'AuthMiddleware');
	$router->get('/logout', [$auth, 'logout']);

	//Routes post
	$router->post('/api/register', [$auth, 'handleRegister']);
	$router->post('/api/login', [$auth, 'handleLogin']);

	// Resolve request
	$router->resolve($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
?>