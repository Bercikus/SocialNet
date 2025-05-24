<?php
session_start();

$token = $_GET['token'] ?? '';
$matchedEmail = null;

//Sprawdzenie, czy token już był użyty
$usedFile = __DIR__ . '/used_tokens.txt';
$usedTokens = file_exists($usedFile) ? file($usedFile, FILE_IGNORE_NEW_LINES) : [];
if (in_array($token, $usedTokens)) {
    $_SESSION['error'] = "Ten link został już użyty.";
    header("Location: generate_link.php");
    exit;
}


$folder = __DIR__ . '/reset_links';

if ($token && is_dir($folder)) {
    foreach (scandir($folder) as $file) {
        if ($file === '.' || $file === '..') continue;

        //rozpakowanie pliku z linkiem
        $filePath = "$folder/$file";

        //Sprawdzenie czasu utworzenia pliku
        $fileTime = filemtime($filePath);
        if (time() - $fileTime > 13) {
            continue; // link wygasł – pomiń ten plik
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (count($lines) >= 2) {
            $emailFromFile = $lines[0];
            $linkFromFile = $lines[1];

            //czy link zawiera token przesłany w linku resetującym
            // if (strpos($linkFromFile, $token) !== false) {
            parse_str(parse_url($linkFromFile, PHP_URL_QUERY), $queryParams);
            if (isset($queryParams['token']) && hash_equals($queryParams['token'], $token)) { 
                $matchedEmail = $emailFromFile;

                //Token zostaje zapisany jako użyty
                file_put_contents($usedFile, $token . "\n", FILE_APPEND);

                break;
            }
        } 
    }
}

if (!$matchedEmail) {
    $_SESSION['error']  ="Nieprawidłowy lub wygasły link resetujący.";
    header("Location: generate_link.php");
    exit;
}
?>

<!-- Formularz do wpisania nowego hasła -->
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Zmień hasło</title></head>
<body>
    <h2>Ustaw nowe hasło</h2>
    <form action="change_password.php" method="POST">
        <input type="hidden" name="email" value="<?= htmlspecialchars($matchedEmail) ?>">
        <label>Nowe hasło:</label>
        <input type="password" name="new_password" required><br>
        <button type="submit">Zmień hasło</button>
    </form>
    <?php
        if (isset($_SESSION['error'])) {
            echo "<p style='color: red;'><strong>" . $_SESSION['error'] . "</strong></p>";
            unset($_SESSION['error']); // Usunięcie komunikatu po wyświetleniu
        }
    ?>
</body>
</html>
