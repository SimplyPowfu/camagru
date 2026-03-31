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
			header('Content-Type: application/json');
			
			$data = json_decode(file_get_contents('php://input'), true);
			$user = $_SESSION['user'] ?? null;
			$imgBase64 = $data['image'] ?? '';
			$stickersData = $data['stickers'] ?? [];

			if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
				http_response_code(403);
				echo json_encode(['success' => false, 'message' => 'Richiesta non autorizzata (CSRF)']);
				return;
			}
			if (!$user || empty($imgBase64)) {
				echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
				return;
			}

			$imgData = preg_replace('#^data:image/\w+;base64,#i', '', $imgBase64);
			$imgData = str_replace(' ', '+', $imgData);
			$fileData = base64_decode($imgData);

			if (!$fileData) {
				echo json_encode(['success' => false, 'message' => 'Decodifica immagine fallita']);
				return;
			}

			$finfo = new finfo(FILEINFO_MIME_TYPE);
			$mimeType = $finfo->buffer($fileData);
			$allowedTypes = [
				'image/png'  => '.png',
				'image/jpeg' => '.jpg',
				'image/jpg'  => '.jpg'
			];

			if (!array_key_exists($mimeType, $allowedTypes)) {
				echo json_encode(['success' => false, 'message' => 'Tipo di file non consentito: ' . $mimeType]);
				return;
			}

			$folder = __DIR__ . '/../../public/uploads/';
			$filterDir = __DIR__ . '/../../public/filter/';
			$fileName = 'camagru_' . bin2hex(random_bytes(8)) . '.png';
			$filePath = $folder . $fileName;

			if (!is_dir($folder)) mkdir($folder, 0777, true);

			try {
				$baseImage = imagecreatefromstring($fileData);
				if (!$baseImage) {
					echo json_encode(['success' => false, 'message' => 'Impossibile elaborare l\'immagine di base']);
					return;
				}

				imagealphablending($baseImage, true);
				imagesavealpha($baseImage, true);

				if (is_array($stickersData)) {
					foreach ($stickersData as $sticker) {
						$safeFilename = basename($sticker['filename']);
						$stickerPath = $filterDir . $safeFilename;

						if (file_exists($stickerPath) && mime_content_type($stickerPath) === 'image/png') {
							$stickerImg = imagecreatefrompng($stickerPath);
							
							if ($stickerImg) {
								imagealphablending($stickerImg, true);
								imagesavealpha($stickerImg, true);

								$origWidth = imagesx($stickerImg);
								$origHeight = imagesy($stickerImg);
								
								$targetWidth = (int)$sticker['w'];
								$ratio = $origHeight / $origWidth;
								$targetHeight = (int)($targetWidth * $ratio);

								$targetX = (int)$sticker['x'];
								$targetY = (int)$sticker['y'];

								imagecopyresampled(
									$baseImage, $stickerImg,
									$targetX, $targetY, // Coordinate Destinazione
									0, 0, // Coordinate Sorgente
									$targetWidth, $targetHeight, // Dimensioni Destinazione
									$origWidth, $origHeight // Dimensioni Sorgente
								);
								imagedestroy($stickerImg);
							}
						}
					}
				}
				$saveSuccess = imagepng($baseImage, $filePath);
				imagedestroy($baseImage);
				if ($saveSuccess) {
					if (Photo::addPicture($user['id'], $fileName)) {
						echo json_encode(['success' => true, 'message' => 'Post Salvato!', 'file' => $fileName]);
					} else {
						unlink($filePath);
						echo json_encode(['success' => false, 'message' => 'Errore nel salvataggio sul database']);
					}
				} else {
					echo json_encode(['success' => false, 'message' => 'Errore nella creazione del file finale']);
				}
			} catch (Exception $e) {
				http_response_code(500);
				echo json_encode(['success' => false, 'message' => 'Errore interno del server durante il processamento dell\'immagine']);
			}
		}

		public function remove() {
			if (ob_get_length()) ob_clean();
			header('Content-Type: application/json');
			$data = json_decode(file_get_contents('php://input'), true);
			$user = $_SESSION['user'] ?? null;
			$file_path = $data['file_path'] ?? null;
			if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
				http_response_code(403);
				echo json_encode(['success' => false, 'message' => 'Richiesta non autorizzata (CSRF)']);
				return;
			}

			if (!$user || !$file_path) {
				echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
				return;
			}
			try {
				if (Photo::removePicture($user['id'], $file_path)){
					$folder = __DIR__ . '/../../public/uploads/';
					$filePath = $folder . $file_path;	
					unlink($filePath);
					echo json_encode(['success' => true, 'message' => 'Post Eliminato!']);
				}
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
			$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
			if (empty($limit)) {
				echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
				return;
			}
			try {
				$photos = Photo::getPictures($limit);
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
			if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
				http_response_code(403);
				echo json_encode(['success' => false, 'message' => 'Richiesta non autorizzata (CSRF)']);
				return;
			}

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
			if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
				http_response_code(403);
				echo json_encode(['success' => false, 'message' => 'Richiesta non autorizzata (CSRF)']);
				return;
			}

			if (!$image_id || !$user || !$comment) {
				echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
				return;
			}
			try {
				if (Photo::addComment($image_id, $user['id'], $comment)) {
					//manda la mail al creatore
					$creator = Photo::getCreatorDetails($image_id);
					if ($creator && filter_var($creator['notify_comments'], FILTER_VALIDATE_BOOLEAN) == true && $creator['username'] !== $user['username']) {
						$comUser = $user['username'];
						$post = Photo::getPictureToId($image_id);
						$link = "http://localhost:8080/post?post=". $post;
						sendEmail($creator['email'], "hanno commentato un tuo Post Camagru!", $comUser . " ha commentato un tuo post: " . $link . "\r\n\r\nHA COMMENTATO\r\n" . $user['username'] . ": " . $comment);
					}
					echo json_encode(['success' => true, 'message' => 'Commento Salvato!']);
					exit;
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