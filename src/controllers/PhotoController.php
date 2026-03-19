<?php

	require_once __DIR__ . '/../controller.php';

	class PhotoController extends Controller {
		public function editing() {
			$this->view('editing');
		}
		// public function save() {
		// 	// Leggiamo il corpo della richiesta JSON
		// 	$json = file_get_contents('php://input');
		// 	$data = json_decode($json, true);

		// 	if (!isset($data['image'])) {
		// 		echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
		// 		return;
		// 	}

		// 	$img = $data['image'];
		// 	// Rimuoviamo l'intestazione "data:image/png;base64,"
		// 	$img = str_replace('data:image/png;base64,', '', $img);
		// 	$img = str_replace(' ', '+', $img);
			
		// 	// Decodifichiamo i dati binari
		// 	$fileData = base64_decode($img);

		// 	// Definiamo il percorso di salvataggio
		// 	$folder = __DIR__ . '/../../public/uploads/';
		// 	$fileName = 'camagru_' . time() . '.png';
		// 	$filePath = $folder . $fileName;

		// 	// Creiamo la cartella se non esiste (sicurezza extra)
		// 	if (!is_dir($folder)) {
		// 		mkdir($folder, 0777, true);
		// 	}

		// 	// Scriviamo il file
		// 	if (file_put_contents($filePath, $fileData)) {
		// 		echo json_encode([
		// 			'success' => true, 
		// 			'message' => 'Salvato!',
		// 			'file' => $fileName
		// 		]);
		// 	} else {
		// 		echo json_encode(['success' => false, 'message' => 'Impossibile scrivere il file']);
		// 	}
		// }
	}
?>