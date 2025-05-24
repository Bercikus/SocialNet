<?php
session_start(); // ZABEZPIECZENIE: Rozpoczyna sesję, aby sprawdzić, czy użytkownik jest zalogowany.
require_once('dbConnection.php');

$userId = $_SESSION['userId'] ?? null;
if (!$userId || !isset($_POST['fileId'])) {
    // ZABEZPIECZENIE: Sprawdza, czy użytkownik jest zalogowany i czy ID pliku zostało przesłane.
    // Bez tego każdy mógłby wysłać żądanie usunięcia.
    echo json_encode(['success' => false, 'message' => 'Brak uprawnień.']);
    exit();
}

$fileId = $_POST['fileId'];
$conn = connectDB();

$stmt = $conn->prepare("SELECT unique_filename FROM user_files WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $fileId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) { // ZABEZPIECZENIE: Sprawdza, czy plik istnieje I CZY NALEŻY DO ZALOGOWANEGO UŻYTKOWNIKA.
                               // To kluczowe, aby użytkownik nie mógł usunąć cudzych plików.
    $file_data = $result->fetch_assoc();
    $unique_filename = $file_data['unique_filename'];
    $file_path = __DIR__ . '/uploads/' . $userId . '/' . $unique_filename;

    if (file_exists($file_path) && unlink($file_path)) {
        $delete_stmt = $conn->prepare("DELETE FROM user_files WHERE id = ? AND user_id = ?");
        $delete_stmt->bind_param("ii", $fileId, $userId);
        if ($delete_stmt->execute()) { // ZABEZPIECZENIE: Usuwa plik z bazy DANYCH, również sprawdzając ID użytkownika.
                                        // Zapobiega usunięciu cudzych wpisów.
            echo json_encode(['success' => true, 'message' => 'Usunięto.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Błąd bazy.']);
        }
        $delete_stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Błąd pliku.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Brak dostępu.']);
}

$stmt->close();
$conn->close();
?>