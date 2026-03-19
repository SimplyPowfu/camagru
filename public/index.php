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
		//Auth
	$router->get('/login', [$auth, 'login']);//pagina login
	$router->get('/reset', [$auth, 'reset']);//get da mail con reset_token
	$router->get('/reset_pass', [$auth, 'reset_pass']);//pagina reset mail
	$router->get('/register', [$auth, 'register']);//pagina register
	$router->get('/activate', [$auth, 'activate']);//get da mail
	$router->get('/logout', [$auth, 'logout']);//delete session

		//Protected
	$router->get('/editing', [$auth, 'editing'], 'AuthMiddleware');//pagina editing foto

	//Routes post (API)
	$router->post('/api/register', [$auth, 'handleRegister']);
	$router->post('/api/login', [$auth, 'handleLogin']);
	$router->post('/api/reset_token', [$auth, 'forgotPassword']);
	$router->post('/api/reset', [$auth, 'reinitPassword']);

	// Resolve request
	$router->resolve($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
?>