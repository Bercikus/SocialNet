<?php
session_start(); 
require_once('dbConnection.php'); 

$isLogged = false;


if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = connectDB();
   
    $userLogin = $_POST['login'] ?? '';
    $userPassword = $_POST['password'] ?? '';
 
    $limitMinutes = 5;
    $maxAttempts = 3;
    $timeLimit = time() - ($limitMinutes * 60);

    $checkStmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM failed_logins WHERE login = ? AND UNIX_TIMESTAMP(attempt_time) > ?");
    mysqli_stmt_bind_param($checkStmt, "si", $userLogin, $timeLimit);
    mysqli_stmt_execute($checkStmt);
    mysqli_stmt_bind_result($checkStmt, $failedAttempts);
    mysqli_stmt_fetch($checkStmt);
    mysqli_stmt_close($checkStmt);

    if ($failedAttempts >= $maxAttempts) {
        $_SESSION['error'] = "To konto zostało tymczasowo zablokowane. Spróbuj ponownie za $limitMinutes minut.";
        mysqli_close($conn);
        header('Location: login.php');
        exit();
    } 
     
    $hashedPassword = hash('sha256', $userPassword);
    //$hashedPassword = password_hash($userPassword, PASSWORD_DEFAULT); 
    
    //$query = "SELECT * FROM users WHERE (usser='$userLogin' OR email='$userLogin') AND password='$hashedPassword'; "; 
    //$result = mysqli_multi_query($conn, $query);
    //prepared statement
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE (usser = ? OR email = ?) AND password = ?");
    if (!$stmt) {
        $_SESSION['error'] = "Błąd serwera (stmt). Spróbuj ponownie później.";
        mysqli_close($conn);
        header('Location: login.php');
        exit();
    }

    mysqli_stmt_bind_param($stmt, "sss", $userLogin, $userLogin, $hashedPassword);
    if (!mysqli_stmt_execute($stmt)) {
        $_SESSION['error'] = "Błąd serwera (execute). Spróbuj ponownie później.";
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        header('Location: login.php');
        exit();
    }

    $result = mysqli_stmt_get_result($stmt);

    if ($result) { 
        $user = mysqli_fetch_array($result, MYSQLI_ASSOC);
        if ($user && isset($user['id'])) {
            $userId = $user['id'];
            $userName = $user['usser'];

            $_SESSION['isLogged'] = true;
            $_SESSION['userId'] = $userId;
            $_SESSION['success'] = "Zalogowano pomyślnie!"; 

            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            header('Location: index.php'); 
            exit();
        } else { 
            $ip = $_SERVER['REMOTE_ADDR'];
            $insert = mysqli_prepare($conn, "INSERT INTO failed_logins (login, ip_address) VALUES (?, ?)");
            if ($insert) {
                mysqli_stmt_bind_param($insert, "ss", $userLogin, $ip);
                mysqli_stmt_execute($insert);  
                mysqli_stmt_close($insert);
            }

            $_SESSION['error'] = "Podałeś niepoprawny login lub hasło!";
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            header('Location: login.php');
            exit();
        } 
    } else {  
        // Nie wstawiamy do failed_logins przy błędzie serwera
        $_SESSION['error'] = "Błąd serwera. Spróbuj ponownie później."; 
        header('Location: login.php');
        exit();

    }

    mysqli_stmt_close($stmt); 
    mysqli_close($conn);

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
        <div class="header">Logowanie</div>
        <form action="login.php" method="POST">  
            <label for="login">Użytkownik </label>
            <input type="text" name="login" required>
            <br>
            <label for="password">Hasło </label>
            <input type="password" name="password" required> 
            <br>
            <input type="submit" value="Loguj">
            <br>
            <a href="generate_link.php">Zapomniałem hasło</a>
        </form>
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

    </main>  
 
</body>
</html>
