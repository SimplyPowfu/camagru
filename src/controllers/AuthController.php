<?php

require_once __DIR__ . '/../controller.php';
require_once __DIR__ . '/../utils/email.php';
require_once __DIR__ . '/../models/user.php';

class AuthController extends Controller {

    public function login() {
        $this->view('login');
    }

    public function handleLogin() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';

        if (empty($username) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Campi mancanti']);
            return;
        }

        try {
            $user = User::findByUsername($username);

            if ($user && password_verify($password, $user['password'])) {
                if ($user['is_active'] == 0) {
                    echo json_encode(['success' => false, 'message' => 'Account non attivato']);
                    return;
                }

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
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';

        if (empty($email) || empty($username) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Campi mancanti']);
            return;
        }

        if (strlen($password) < 8 || !preg_match("/[a-z]/", $password) || !preg_match("/[0-9]/", $password)) {
            echo json_encode(['success' => false, 'message' => 'La password non rispetta i requisiti']);
            return;
        }

        try {
            if (User::exists($username, $email)) {
                echo json_encode(['success' => false, 'message' => 'Username o Email già utilizzati']);
                return;
            }

            $token = bin2hex(random_bytes(50));
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            // Creazione tramite Model
            if (User::create($username, $email, $hashedPassword, $token)) {
                $link = "http://localhost:8080/activate?token=" . $token;
                sendEmail($email, "Attiva Account Camagru", "Clicca qui: " . $link);
                echo json_encode(['success' => true, 'message' => 'Registrazione completata! Controlla la mail.']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Errore interno']);
        }
    }

    public function activate() {
        $token = $_GET['token'] ?? '';
        if (User::activateByToken($token)) {
            header('Location: /login?activated=true');
        } else {
            die("Link non valido o scaduto.");
        }
    }

	public function reset_pass() {
        $this->view('reset_pass');
    }

    public function forgotPassword() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';

        if ($email && $user = User::findByEmail($email)) {
            $token = bin2hex(random_bytes(50));
            User::setResetToken($email, $token);
            $link = "http://localhost:8080/reset?token=" . $token;
            sendEmail($email, "Reset Password", "Link: " . $link);
        }
        echo json_encode(['success' => true, 'message' => 'Se l\'email esiste, riceverai un link.']);
    }

    public function reset() {
        $token = $_GET['token'] ?? '';
        if (User::isResetTokenValid($token)) {
            header('Location: /reset_pass?token=' . $token);
        } else {
            die("Token non valido.");
        }
    }

    public function reinitPassword() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        $token = $input['token'] ?? '';
        $password = $input['password'] ?? '';

        if (strlen($password) < 8 || !preg_match("/[0-9]/", $password)) {
            echo json_encode(['success' => false, 'message' => 'Password debole']);
            return;
        }

        if (User::updatePasswordByResetToken($token, password_hash($password, PASSWORD_BCRYPT))) {
            echo json_encode(['success' => true, 'message' => 'Password aggiornata!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Errore o token scaduto']);
        }
    }

    public function logout() {
        $_SESSION = [];
        session_destroy();
        header('Location: /');
        exit;
    }
}