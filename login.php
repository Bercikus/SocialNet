<?php
 
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
        </form>
        

    </main>  


    
</body>
</html>
