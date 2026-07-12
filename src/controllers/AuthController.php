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
                    'email' => $user['email'],
                    'notify_comments' => $user['notify_comments']
                ];

                $redirectUrl = $_SESSION['redirect_to'] ?? '/';
                unset($_SESSION['redirect_to']);

                echo json_encode(['success' => true, 'message' => 'Login effettuato', 'redirect' => $redirectUrl]);
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
                echo json_encode(['success' => true, 'message' => 'Registrazione completata!']);
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

    public function editProfile() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Richiesta non autorizzata (CSRF)']);
            return;
        }
        if (empty($data)) {
            echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
            return;
        }
        $oldUsername = $_SESSION['user']['username'] ?? null;
        if (!$oldUsername) {
            echo json_encode(['success' => false, 'message' => 'Utente non autenticato']);
            return;
        }

        $user = User::findByUsername($oldUsername);
        $cleanData = [];
        if (!empty($data['username'])) {
            if (User::findByUsername($data['username'])) {
                echo json_encode(['success' => false, 'message' => 'Username già utilizzato']);
                return;
            }
            $cleanData['username'] = $data['username'];
        }
        if (!empty($data['email']) && $data['email'] !== $user['email']) {
            if (User::findByEmail($data['email'])) {
                echo json_encode(['success' => false, 'message' => 'Email già utilizzata']);
                return;
            }
            $cleanData['email'] = $data['email'];
        }
        if (!empty($data['password'])) {
            $password = $data['password'];
            if (strlen($password) < 8 || !preg_match("/[a-z]/", $password) || !preg_match("/[0-9]/", $password)) {
                echo json_encode(['success' => false, 'message' => 'La password non rispetta i requisiti']);
                return;
            }
            if (password_verify($password, $user['password'])) {
                echo json_encode(['success' => false, 'message' => 'La nuova password non può essere uguale alla vecchia']);
                return;
            }
            $cleanData['password'] = password_hash($password, PASSWORD_BCRYPT);
        }
        // prendo il dato sempre attivo e controllo se e' diverso da quello salvato
        $incomingNotify = isset($data['notify_comments']) && filter_var($data['notify_comments'], FILTER_VALIDATE_BOOLEAN);
        $currentNotify = (bool)$user['notify_comments'];
        if ($incomingNotify !== $currentNotify) {
            $cleanData['notify_comments'] = $incomingNotify ? 1 : 0;
        }
        if (empty($cleanData)) {
            echo json_encode(['success' => false, 'message' => 'Nessun dato da aggiornare']);
            return;
        }

        try {
            if (User::update($user['id'], $cleanData)) {
                if (isset($cleanData['username']))
                    $_SESSION['user']['username'] = $cleanData['username'];
                if (isset($cleanData['email']))
                    $_SESSION['user']['email'] = $cleanData['email'];
                if (isset($cleanData['notify_comments'])) {
                    $_SESSION['user']['notify_comments'] = $cleanData['notify_comments'];
                }
                echo json_encode(['success' => true, 'message' => 'Valori aggiornati con successo']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Errore durante l\'aggiornamento']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Errore interno del server']);
        }
    }

    public function logout() {
        $_SESSION = [];
        session_destroy();
        header('Location: /');
        exit;
    }
}