<?php
	
	session_start();
	
	require_once __DIR__ . '/../src/router.php';
	require_once __DIR__ . '/../src/controllers/HomeController.php';
	require_once __DIR__ . '/../src/controllers/AuthController.php';
	require_once __DIR__ . '/../src/controllers/PhotoController.php';

	$router = new Router();

	// Controllers
	$home = new HomeController();
	$auth = new AuthController();
	$photo = new PhotoController();

	// Routes get
	$router->get('/', [$home, 'index']);
	$router->get('/profile', [$home, 'profile'], 'AuthMiddleware');
	
		//Auth
	$router->get('/login', [$auth, 'login']);//pagina login
	$router->get('/reset', [$auth, 'reset']);//get da mail con reset_token
	$router->get('/reset_pass', [$auth, 'reset_pass']);//pagina reset mail
	$router->get('/register', [$auth, 'register']);//pagina register
	$router->get('/activate', [$auth, 'activate']);//get da mail
	$router->get('/logout', [$auth, 'logout']);//delete session

		//Protected
	$router->get('/editing', [$photo, 'editing'], 'AuthMiddleware');//pagina editing foto
	$router->get('/post', [$photo, 'post'], 'AuthMiddleware');//pagina post foto
	$router->get('/api/post/checkLike', [$photo, 'checkLike'], 'AuthMiddleware');
	$router->get('/api/post/loadComments', [$photo, 'getComment'], 'AuthMiddleware');
	
	$router->get('/api/post/picture', [$photo, 'getPictureToName'], 'AuthMiddleware');//get che ritorna una foto di uno user
	$router->get('/api/user/picture', [$photo, 'getNamePictures'], 'AuthMiddleware');//get che ritorna un array di foto di uno user
	$router->get('/api/gallery/picture', [$photo, 'getPictures']);//get che ritorna un array di n foto
	
	//Routes post (API)
	$router->post('/api/register', [$auth, 'handleRegister']);
	$router->post('/api/login', [$auth, 'handleLogin']);
	$router->post('/api/reset_token', [$auth, 'forgotPassword']);
	$router->post('/api/reset', [$auth, 'reinitPassword']);
	//Protected
	$router->post('/api/edit/profile', [$auth, 'editProfile'], 'AuthMiddleware');
	$router->post('/api/save', [$photo, 'save'], 'AuthMiddleware');//Save picture in uploads/ and db
	$router->post('/api/remove', [$photo, 'remove'], 'AuthMiddleware');
	$router->post('/api/post/toggleLike', [$photo, 'toggleLike'], 'AuthMiddleware');//Aggiunge o leva un Like ad una foto
	$router->post('/api/post/addComment', [$photo, 'addComment'], 'AuthMiddleware');//Aggiunge un commento ad un post

	// Resolve request
	$router->resolve($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
?>