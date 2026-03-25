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

		public function remove() {
			if (ob_get_length()) ob_clean();
			header('Content-Type: application/json');
			$data = json_decode(file_get_contents('php://input'), true);
			$user = $_SESSION['user'] ?? null;
			$file_path = $data['file_path'] ?? null;

			if (!$user || !$file_path) {
				echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
				return;
			}
			try {
				if (Photo::removePicture($user['id'], $file_path))
					echo json_encode(['success' => true, 'message' => 'Post Eliminato!']);
				else
					echo json_encode(['success' => false, 'message' => 'Impossibile eliminare il post']);
			} catch (Exception $e) {
				http_response_code(500);
				echo json_encode(['success' => false, 'message' => $e->getMessage()]);
			}
		}

		public function getPictureToName() {
			if (ob_get_length()) ob_clean();
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
			if (ob_get_length()) ob_clean();
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
			if (ob_get_length()) ob_clean();
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

		public function checkLike() {
			if (ob_get_length()) ob_clean();
			header('Content-Type: application/json');
			$image_id = isset($_GET['image_id']) ? (int)$_GET['image_id'] : null;
			$user = $_SESSION['user'] ?? null;

			if (!$image_id || !$user) {
				echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
				return;
			}
			try {
				$existingLike = Photo::checkLike($image_id, $user['id']);
				$totalLikes = Photo::countLikes($image_id);
				if ($existingLike)
					echo json_encode(['success' => true, 'action' => 'like', 'count' => $totalLikes, 'message' => 'hai messo Like']);
				else 
					echo json_encode(['success' => true, 'action' => 'nonlike', 'count' => $totalLikes, 'message' => 'non hai messo Like']);
			} catch (Exception $e) {
				http_response_code(500);
				echo json_encode(['success' => false, 'message' => 'Errore nel database']);
			}
		}

		public function toggleLike() {
			if (ob_get_length()) ob_clean();
			header('Content-Type: application/json');
			$data = json_decode(file_get_contents('php://input'), true);
			$image_id = $data['image_id'] ?? null;
			$user = $_SESSION['user'] ?? null;

			if (!$image_id || !$user) {
				echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
				return;
			}
			try {
				$existingLike = Photo::checkLike($image_id, $user['id']);
				if ($existingLike) {
					Photo::removeLike($image_id, $user['id']);
					$totalLikes = Photo::countLikes($image_id);
					echo json_encode(['success' => true, 'action' => 'unliked', 'count' => $totalLikes, 'message' => 'Like rimosso']);
				} else {
					Photo::addLike($image_id, $user['id']);
					$totalLikes = Photo::countLikes($image_id);
					echo json_encode(['success' => true, 'action' => 'liked', 'count' => $totalLikes, 'message' => 'Like aggiunto']);
				}
			} catch (Exception $e) {
				http_response_code(500);
				echo json_encode(['success' => false, 'message' => 'Errore nel database']);
			}
		}

		public function addComment() {
			if (ob_get_length()) ob_clean();
			// Leggiamo il corpo della richiesta JSON
			header('Content-Type: application/json');
			$data = json_decode(file_get_contents('php://input'), true);
			$image_id = $data['image_id'] ?? null;
			$comment = $data['comment'] ?? null;
			$user = $_SESSION['user'] ?? null;

			if (!$image_id || !$user || !$comment) {
				echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
				return;
			}
			try {
				if (Photo::addComment($image_id, $user['id'], $comment)) {
					//manda la mail al creatore
					$creator = Photo::getCreatorDetails($image_id);
					if ($creator && $creator['notify_comments'] == 1 && $creator['username'] !== $user['username']) {
						$comUser = $user['username'];
						sendEmail($creator['email'], "hanno commentato un tuo Post!", `$comUser ha commentato un tuo post: http://localhost:8080/post?post=$image_id`);
					}
					echo json_encode(['success' => true, 'message' => 'Commento Salvato!']);
				}
				else
					echo json_encode(['success' => false, 'message' => 'Impossibile Salvare il commento']);
			} catch (Exception $e) {
				http_response_code(500);
				echo json_encode(['success' => false, 'message' => 'Errore nel database']);
			}
		}

		public function getComment() {
			if (ob_get_length()) ob_clean();
			// Leggiamo il corpo della richiesta JSON
			header('Content-Type: application/json');
			$image_id = $_GET['image_id'] ?? null;
			if (!$image_id) {
				echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
				return;
			}
			try {
				$comments = Photo::getComment($image_id);
				if (empty($comments)) {
					echo json_encode(['success' => false, 'message' => 'Nessuna commento trovato']);
					return;
				}
				echo json_encode([
					'success' => true, 
					'message' => 'Commenti caricati!',
					'data' => $comments
				]);
			} catch (Exception $e) {
				http_response_code(500);
				echo json_encode(['success' => false, 'message' => 'Errore nel database']);
			}
		}
	}

?>