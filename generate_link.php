<?php
session_start();
require_once('dbConnection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //CAPTCHA
    $secret = '6Lf1mUMrAAAAAGngbPEOIvprh3Y2BZHfAeKeqf00';
    $captcha = $_POST['g-recaptcha-response'] ?? '';

    if (!$captcha) {
        $_SESSION['error'] = 'Musisz potwierdzić, że nie jesteś robotem!!!';
        header("Location: generate_link.php");
        exit;
    }

    $verifyResponse = file_get_contents(
        "https://www.google.com/recaptcha/api/siteverify?secret=$secret&response=$captcha"
    );

    $responseData = json_decode($verifyResponse);

    if (!$responseData->success) {
        $_SESSION['success'] = 'Błąd weryfikacji CAPTCHA.';
        header("Location: generate_link.php");
        exit;
    }



    $conn = connectDB();
    $email = $_POST['email'] ?? '';

    //sprawdź czy e-mail istnieje w bazie
    $stmt = $conn->prepare("SELECT 1 FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    $_SESSION['success'] = "Jeśli e-mail istnieje w systemie, zostanie wysłany link do resetu hasła";
    
    if ($stmt->num_rows > 0) {
        // Generowanie tokenu
        $token = bin2hex(random_bytes(16)); 
        $resetLink = "http://localhost/socialnet/reset_password.php?token=$token";

        // Upewniamy się, że folder istnieje
        $folder = __DIR__ . '/reset_links';
        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }

        // Sanitizacja e-maila do użycia jako nazwa pliku
        $safeEmail = str_replace(['@', '.'], '_', $email); // np. user_example_com
        $filePath = "$folder/$safeEmail.txt";

        // Zapisujemy link do pliku (nadpisując)
        file_put_contents($filePath, $email . "\n" . $resetLink);

       
    } else {
     
        
        
    }


    $stmt->close();
    $conn->close();
    header("Location: generate_link.php");
    exit;
}
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <title>Document</title>
</head>
<body>
    <h3>Reset hasła</h3>
    <form method="POST" action="generate_link.php">
        <label for="email">Podaj swój email:</label>
        <input type="email" name="email" required>
        <div class="g-recaptcha" data-sitekey="6Lf1mUMrAAAAAFygB-RUBTsfJt3_6lwubN_wTz8f"></div>
        <input type="submit" value="Resetuj hasło">
        <?php
        if(isset($_SESSION['success'])){
            echo "<p style='color: green; margin-left: 15px'>  ".$_SESSION['success']."  </p>";
            unset($_SESSION['success']);
        }
        else if (isset($_SESSION['error'])) {
            echo "<p style='color: red;'><strong>" . $_SESSION['error'] . "</strong></p>";
            unset($_SESSION['error']); // Usunięcie komunikatu po wyświetleniu
        }
        ?>
    </form>
    
</body>
</html>