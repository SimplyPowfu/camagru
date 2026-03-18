<?php

	require_once __DIR__ . '/../controller.php';
	require_once __DIR__ . '/../utils/database.php';

	class AuthController extends Controller {

		public function login() {
			$this->view('login');
		}

		public function handleLogin() {
			header('Content-Type: application/json');

			$input = json_decode(file_get_contents('php://input'), true);
			$username = $input['username'] ?? '';
			$password = $input['password'] ?? '';

			if (empty($username) || empty($password)) {
				echo json_encode(['success' => false, 'message' => 'Campi mancanti']);
				return;
			}

			try {
				$db = Database::getInstance();
				$stmt = $db->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
				$stmt->execute(['username' => $username]);
				$user = $stmt->fetch();

				if ($user && password_verify($password, $user['password'])) {
					if ($user['is_active'] == 0) {
						echo json_encode(['success' => false, 'message' => 'Account non attivato']);
						return;
					}

					// Oggetto user
					$_SESSION['user'] = [
						'id' => $user['id'],
						'username' => $user['username'],
						'email' => $user['email']
					];

					echo json_encode(['success' => true, 'message' => 'Login effettuato']);
				} else {
					echo json_encode(['success' => false, 'message' => 'Credenziali errate']);
				}
			} catch (Exception $e) {
				http_response_code(500);
				echo json_encode(['success' => false, 'message' => 'Errore del server']);
			}
		}

		public function register() {
			$this->view('register');
		}

		public function handleRegister() {
			header('Content-Type: application/json');

			$input = json_decode(file_get_contents('php://input'), true);
			$email = $input['email'] ?? '';
			$username = $input['username'] ?? '';
			$password = $input['password'] ?? '';

			if (empty($email) ||empty($username) || empty($password)) {
				echo json_encode(['success' => false, 'message' => 'Campi mancanti']);
				return;
			}
			if (strlen($password) < 8 || !preg_match("/[a-z]/", $password) || !preg_match("/[0-9]/", $password)) {
				echo json_encode(['success' => false, 'message' => 'La password deve essere di almeno 8 caratteri e contenere numeri']);
				return;
			}

			try {
				$db = Database::getInstance();

				//Verifica se username o email esistono già
				$stmt = $db->prepare("SELECT id FROM users WHERE username = :u OR email = :e LIMIT 1");
				$stmt->execute(['u' => $username, 'e' => $email]);
				if ($stmt->fetch()) {
					echo json_encode(['success' => false, 'message' => 'Username o Email già utilizzati']);
					return;
				}

				$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

				//Generazione token per attivazione email
				$token = bin2hex(random_bytes(50));

				// Query per salvataggio sul Database
				$sql = "INSERT INTO users (username, email, password, activation_token, is_active) 
						VALUES (:username, :email, :password, :token, 1)";
				
				$stmt = $db->prepare($sql);
				$stmt->execute([
					'username' => $username,
					'email'    => $email,
					'password' => $hashedPassword,
					'token'    => $token
				]);

				// (TODO) Qui andrebbe la logica per inviare l'email (mail())
				echo json_encode([
					'success' => true, 
					'message' => 'Registrazione completata! Controlla la tua email per attivare l\'account.'
				]);

			} catch (Exception $e) {
				http_response_code(500);
				echo json_encode(['success' => false, 'message' => 'Errore interno: ' . $e->getMessage()]);
			}
		}

		public function editing() {
			$this->view('editing');
		}

		public function logout() {
			$_SESSION = [];

			//distrugge anche i cookie di sessione nel browser
			if (ini_get("session.use_cookies")) {
				$params = session_get_cookie_params();
				setcookie(session_name(), '', time() - 42000,
					$params["path"], $params["domain"],
					$params["secure"], $params["httponly"]
				);
			}

			session_destroy();
			header('Location: /login');
			exit;
		}
	}
?>