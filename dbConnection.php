<?php

function connectDB($db=null){
    $server = "localhost";
    $user = "root";
    $pass = "";
    $name = "social_media_db";

    if($db){
        $name = $db;
    }

    try {
        $conn = new mysqli($server, $user, $pass, $name);
        if(!$conn){
            echo("Lost connection: ".mysqli_connect_error());
        }
        return $conn;
    }
    catch (Exception $e){
        echo("Lost connection: ".$e);
    } 
}

?>