<?php
session_start();
require_once('dbConnection.php');

// Funkcje walidacyjne jak w rejestracji
function validatePassword($password) {
    $blacklistPatterns = [
        '/<[^>]*>/',
        '/<!--.*?-->/', '/<!--.*/', '/.*-->/'
    ];
    foreach ($blacklistPatterns as $pattern) {
        if (preg_match($pattern, $password)) return false;
    }
    if (strlen($password) < 6 || strlen($password) > 30) return false;
    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';

    if (!validatePassword($newPassword)) {
        $_SESSION['error'] = "Hasło niepoprawne – zabronione znaki lub zła długość.";
        header('Location: reset_password.php?token=INVALID'); // lub inna obsługa
        exit;
    }

    $xml = "
    <user>
        <email>" . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "</email>
        <password>" . htmlspecialchars($newPassword, ENT_QUOTES, 'UTF-8') . "</password>
    </user>";

    $xmlObject = simplexml_load_string($xml);
    if ($xmlObject === false) {
        $_SESSION['error'] = "Błąd przetwarzania danych.";
        header('Location: reset_password.php?token=INVALID');
        exit;
    }

    $conn = connectDB();

    
    $hashedPassword = hash('sha256', $xmlObject->password);

    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $hashedPassword, $xmlObject->email);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Hasło zostało zmienione pomyślnie!";
        header('Location: login.php');
        exit;
    } else {
        $_SESSION['error'] = "Błąd przy aktualizacji hasła.";
        header('Location: reset_password.php?token=INVALID');
        exit;
    }

    $stmt->close();
    $conn->close();
}
?>
