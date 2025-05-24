<?php
session_start(); 
require_once('dbConnection.php');


function validateLogin($input) {
    return preg_match('/^[a-zA-Z0-9_]{6,30}$/', $input);
}

function validatePassword($password) {
    // Wyrażenie regularne do wykrywania niedozwolonych wzorców:
    // 1. Otwarte < i zamknięte >
    // 2. Komentarze XML, tj. <!-- oraz -->
    $blacklistPatterns = [
        '/<[^>]*>/',       // Dowolne otwarte < i zamknięte >
        '/<!--.*?-->/',     // Komentarze XML: <!-- -->
        '/<!--.*/',          
        '/.*-->/' 
    ];
    
    // Sprawdzenie każdego wzorca na liście
    foreach ($blacklistPatterns as $pattern) {
        if (preg_match($pattern, $password)) {
            return false; // Nie przechodzi walidacji
        }
    }

    // Opcjonalnie: Możesz dodać dodatkowe ograniczenia (np. długość hasła)
    if (strlen($password) < 6 || strlen($password) > 30) {
        return false;
    }

    return true; // Hasło jest bezpieczne
}

function validateEmail($email) {
    if(strlen($email) > 60) return false;

    // Sprawdzenie, czy e-mail nie zawiera niebezpiecznych znaków XML (np. <, >, &, " i spacje)
    if (preg_match('/[<>\"\'&\s]/', $email)) { 
        return false;  // Zawiera potencjalnie niebezpieczne znaki
    }

    // Sprawdzenie poprawności formatu e-maila zgodnie ze standardami
    if (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) { 
        return false;  // E-mail nie ma poprawnego formatu
    }

    // Dodatkowa walidacja dla domeny, aby kropka i myślnik były prawidłowo rozmieszczone
    list($localPart, $domainPart) = explode('@', $email);
    $domainParts = explode('.', $domainPart);
 
    foreach ($domainParts as $domainSegment) {
        // Sprawdzenie, czy segment domeny nie zaczyna się ani nie kończy myślnikiem
        if (preg_match('/^-/', $domainSegment) || preg_match('/-$/', $domainSegment)) { 
            return false;  // Domena nie może zaczynać się ani kończyć myślnikiem
        }
    }

    return true;
}
 




if (isset($_COOKIE['user_id'])) {
    header('Location: index.php');
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Pobranie danych z formularza
    $userLogin = $_POST['login'] ?? ''; 
    $userPassword = $_POST['password'] ?? '';
    $userEmail = $_POST['email'] ?? '';
    $userRole = 'gosc'; 
      
    if(!validateLogin($userLogin))
    {
        $_SESSION['error'] = "Login jest niepoprawny! Musi mieć od 6 do 30 znaków i może zawierać tylko litery, cyfry lub znak podkreślenia (_)";
        header('Location: register.php');
        exit;
    }else if(!validatePassword($userPassword)){
        $_SESSION['error'] = "Hasło jest niepoprawne! Być może zawiera niedozwolone sekwencje znaków. Odpowiednie hasło musi mieć także długość 6-30 znaków";
        header('Location: register.php');
        exit;
    }else if(!validateEmail($userEmail)){
        $_SESSION['error'] = "Email jest niepoprawny! Być może zawiera niedozwolone sekwencje znaków";
        header('Location: register.php');
        exit;
    }


    // Tworzenie ramki XML
    // Zabezpieczenie które pomaga w eliminowaniu niebezpiecznych znaków - poprzez zamianeich nakody znaków
    // co zapobiega atakom takim jak XML Injection
    ///*
    $xml = "
    <user>
        <usser>" . htmlspecialchars($userLogin, ENT_QUOTES, 'UTF-8') . "</usser>
        <password>" . htmlspecialchars($userPassword, ENT_QUOTES, 'UTF-8') . "</password>
        <email>" . htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8') . "</email>
        <roll>" . htmlspecialchars($userRole, ENT_QUOTES, 'UTF-8') . "</roll> 
    </user>";
   //*/  
    /*
    $xml = "
    <user>
        <usser>$userLogin</usser>
        <password>$userPassword</password>
        <email>$userEmail</email>
        <roll>$userRole</roll> 
    </user>";
    */


    // Tworzenie obiektu PHP z XML
    $xmlObject = simplexml_load_string($xml);

    if ($xmlObject === false) {
        //błąd przy przetwarzaniu XML 
    } else {  
        // Połączenie z bazą danych 
        $conn = connectDB();
 
        if ($conn->connect_error) {
            die("Błąd połączenia: " . $conn->connect_error);
        }

        // Sprawdzenie, czy użytkownik już istnieje
        $stmt = $conn->prepare("SELECT id FROM users WHERE usser = ? OR email = ?");
        $stmt->bind_param("ss", $xmlObject->usser, $xmlObject->email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) { 
            //Użytkownik z takim loginem lub email istnieje 
            $_SESSION['error'] = "Użytkownik z takim loginem lub email istnieje !";
            header('Location: register.php');
            exit;
        } else {
            // Hashowanie hasła
            //$hashedPassword = password_hash($xmlObject->password, PASSWORD_DEFAULT); 
            $hashedPassword = hash('sha256', $xmlObject->password);
            // Wstawienie użytkownika do bazy danych
            $stmt = $conn->prepare("INSERT INTO users (usser, password, roll, email) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $xmlObject->usser, $hashedPassword, $xmlObject->roll, $xmlObject->email);

            if ($stmt->execute()) {
                //Konto zostało utworzone pomyślnie!
                $_SESSION['success'] = "Zarejestrowano pomyślnie!";
                header('Location: index.php');
                exit;
            } else {
                //Błąd podczas tworzenia konta
            }
        }

        $stmt->close();
        $conn->close();
    }
}
?>




<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="style.css"> 
</head>
<body>
    <nav>
        <a href="index.php">Strona główna</a>
        <a href="login.php">Login</a>
        <a href="register.php">Rejestracja</a>
    </nav>
    <main>
        <div class="header">Rejestracjia</div>
        <form action="register.php" method="POST">  
            <label for="login">Użytkownik </label>
            <input type="text" name="login" required>
            <br>
            <label for="password">Hasło </label>
            <input type="password" name="password" required>
            <br>
            <label for="email">Email </label>
            <input type="text" name="email" required> 
            <br>
            <input type="submit" value="Rejestruj">
        </form>
        <?php
        if (isset($_SESSION['error'])) {
            echo "<p style='color: red;'><strong>" . $_SESSION['error'] . "<strong></p>";
            unset($_SESSION['error']); // Usunięcie komunikatu po wyświetleniu
        }
        ?>

    </main>  


    
</body>
</html>
