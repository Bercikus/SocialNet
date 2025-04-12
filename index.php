<?php
 session_start(); 

?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
        
    </style>
</head>
<body>
    <nav>
        <a href="index.php">Strona główna</a>
        <a href="login.php">Login</a>
        <a href="register.php">Rejestracja</a>
    </nav>
    <?php
    if(isset($_SESSION['success'])){
        echo "<p style='color: green; margin-left: 15px'>  ".$_SESSION['success']."  </p>";
        unset($_SESSION['success']);
    }
    ?>
    <main>
         <h2>Strona Główna</h2>
    </main>  


    
</body>
</html>
