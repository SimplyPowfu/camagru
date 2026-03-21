<?php

	require_once __DIR__ . '/../controller.php';
	require_once __DIR__ . '/../models/photo.php';

	class PhotoController extends Controller {
		public function editing() {
			$this->view('editing');
		}

		public function post() {
			$this->view('post');
		}

		public function save() {
			if (ob_get_length()) ob_clean();
			// Leggiamo il corpo della richiesta JSON
			header('Content-Type: application/json');
			$data = json_decode(file_get_contents('php://input'), true);
			$user = $_SESSION['user'] ?? '';
			$img = $data['image'] ?? '';

			if (empty($user) || empty($img)) {
				echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
				return;
			}
			// Rimuoviamo l'intestazione "data:image/png;base64,"
			$img = str_replace('data:image/png;base64,', '', $img);
			$img = str_replace(' ', '+', $img);
			$fileData = base64_decode($img);

			// Definiamo il percorso di salvataggio
			$folder = __DIR__ . '/../../public/uploads/';
			$fileName = 'camagru_' . time() . '.png';
			$filePath = $folder . $fileName;
			if (!is_dir($folder))
				mkdir($folder, 0777, true);
			if (!is_writable($folder)) {
				echo json_encode([
					'success' => false, 
					'message' => 'ERRORE PERMESSI: La cartella non ha permessi.',
				]);
				return;
			}
			try {
				if (file_put_contents($filePath, $fileData)) {
					if (Photo::addPicture($user['id'], $fileName)) {
						echo json_encode(['success' => true, 'message' => 'Post Salvato!']);
					} else
						echo json_encode(['success' => false, 'message' => 'Impossibile caricare il File']);
				} else {
					echo json_encode(['success' => false, 'message' => 'Impossibile scrivere il file']);
				}
			} catch (Exception $e) {
				http_response_code(500);
				echo json_encode(['success' => false, 'message' => 'Errore interno']);
			}
		}

		public function getPictureToName() {
			header('Content-Type: application/json');
			$file_path = $_GET['file_path'] ?? '';
			if (empty($file_path)) {
				echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
				return;
			}
			try {
				$photos = Photo::getPictureToName($file_path);
				if (empty($photos)) {
					echo json_encode(['success' => false, 'message' => 'Nessuna foto trovata']);
					return;
				}
				echo json_encode([
					'success' => true, 
					'message' => 'Post caricato!',
					'data' => $photos
				]);
			} catch (Exception $e) {
				http_response_code(500);
				echo json_encode(['success' => false, 'message' => $e->getMessage()]);
			}
		}

		public function getNamePictures() {
			header('Content-Type: application/json');
			$username = $_GET['username'] ?? '';
			if (empty($username)) {
				echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
				return;
			}
			try {
				$photos = Photo::getNamePictures($username);
				if (empty($photos)) {
					echo json_encode(['success' => false, 'message' => 'Nessuna foto trovata']);
					return;
				}
				echo json_encode([
					'success' => true, 
					'message' => 'Foto caricate!',
					'data' => $photos
				]);
			} catch (Exception $e) {
				http_response_code(500);
				echo json_encode(['success' => false, 'message' => $e->getMessage()]);
			}
		}

		public function getPictures() {
			header('Content-Type: application/json');
			$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
			if (empty($limit)) {
				echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
				return;
			}
			try {
				$photos = Photo::getPictures($limit);
				if (empty($photos)) {
					echo json_encode(['success' => false, 'message' => 'Nessuna foto trovata']);
					return;
				}
				echo json_encode([
					'success' => true,
					'Foto caricate!',
					'data' => $photos
				]);
			} catch (Exception $e) {
				http_response_code(500);
				echo json_encode(['success' => false, 'message' => $e->getMessage()]);
			}
		}
	}
?>