<?php
session_start();
$_SESSION['isLogged'] = false;
header('Location: index.php');  
?>