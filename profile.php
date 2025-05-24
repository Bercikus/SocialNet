<?php
session_start();  
require_once('dbConnection.php');  

$userId = $_SESSION['userId'] ?? "";

if(!$userId){
    header('Location: login.php');   // Przekieruj na stronę logowania, jeśli użytkownik nie jest zalogowany
    exit();
}

$conn = connectDB();

$upload_base_directory = __DIR__ . '/uploads/';  
$user_upload_directory = $upload_base_directory . $userId . '/';    //scieżka do podfolderu użytkownika
$max_file_size = 10 * 1024 * 1024;

// Sprawdź, czy folder użytkownika istnieje, jeśli nie - utwórz go
if (!is_dir($user_upload_directory)) {
    mkdir($user_upload_directory, 0755, true);  
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_upload'])) {
    $file = $_FILES['file_upload'];

    // Walidacja 
    if (!$file['name']) { // nie wybrano pliku
        $_SESSION['error'] = 'Nie wybrano pliku do wgrania';
    } elseif ($file['error'] !== UPLOAD_ERR_OK) { // Sprawdź inne błędy wgrywania (poza brakiem pliku)
        $_SESSION['error'] = 'Błąd podczas wgrywania pliku. Kod błędu: ' . $file['error'];
    } elseif ($file['size'] > $max_file_size) { // Sprawdź, czy plik nie jest za duży
        $_SESSION['error'] = 'Plik jest za duży. Maksymalny rozmiar to ' . ($max_file_size / (1024 * 1024)) . ' MB';
    } else { 
        $original_filename = $file['name']; 
        $file_extension = pathinfo($original_filename, PATHINFO_EXTENSION); // Rozszerzenie pliku

        // Generowanie unikalnej nazwy pliku na serwerze
        $unique_filename = uniqid('file_', true) . '.' . $file_extension;  
        $destination_path = $user_upload_directory . $unique_filename; // Pełna ścieżka do zapisu pliku
        
        //przeniesienie wgranego pliku do lokalizacji uzytkownikla
        if (move_uploaded_file($file['tmp_name'], $destination_path)) {
            // jesli sie powiodlo 
            //zapisanie informacji o pliku w bazie danych 
            $stmt = $conn->prepare("INSERT INTO user_files (user_id, unique_filename, original_filename) VALUES (?, ?, ?)"); 
            $stmt->bind_param("iss", $userId, $unique_filename, $original_filename);

            if ($stmt->execute()) {
                $_SESSION['success'] = 'Plik "' . htmlspecialchars($original_filename) . '" został pomyślnie wgrany!';
            } else {
                $_SESSION['error'] = 'Błąd zapisu danych o pliku w bazie: ' . $stmt->error;
                // Jeśli zapis do bazy się nie udał, usuń wgrany plik z serwera, aby uniknąć "osieroconych" plików
                unlink($destination_path);
            }

            $stmt->close();
            $conn->close();
 
        }   else {
            $_SESSION['error'] = 'Wystąpił nieoczekiwany błąd podczas przenoszenia pliku';
        } 
    } 
      
    header('Location: profile.php');
    exit(); 
}




// --- POBIERANIE PLIKÓW Z BAZY DANYCH ---
$user_files = []; 
$stmt = $conn->prepare("SELECT id, unique_filename, original_filename, upload_date FROM user_files WHERE user_id = ? ORDER BY upload_date DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $user_files[] = $row;
    }
}
$stmt->close();

// --- ZAMKNIĘCIE POŁĄCZENIA Z BAZĄ DANYCH NA KOŃCU ---
$conn->close();



 
?>



<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
        .profile{
            display: flex; 
        }
        .profile h3{
            margin-left: 20px;
           
        }
        .description p{
            margin: 0; 
            
            padding: 10px;

            background-color: #f0f0f0;
        }
        .description h3{
            margin-bottom: 0px;
        }

        main{
            padding: 30px;
        }




/* Minimalne style CSS dla siatki miniatur */
 .file-grid {
        list-style: none;
        padding: 0;
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 20px;
    }
    .file-grid li {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .t {
        width: 150px;
        height: 150px;
        margin-bottom: 5px;
        object-fit: cover;
    }
    .f-name {
        width: 150px;
        text-align: center;
        word-wrap: break-word;
    }
         
.box {
    position: relative;  
}
        
 .x {
    position: absolute; /* Pozycjonowany względem .box */
    top: 0;            /* Na samej górze */
    right: 0;          /* Po prawej stronie */
    opacity: 0;        /* Ukryty domyślnie */
    
    /* Style wyglądu przycisku */
    background: red;
    color: white;
    width: 20px;
    height: 20px;
    text-align: center; /* Centruje 'x' poziomo */
    line-height: 20px; /* Centruje 'x' pionowo w małym kwadracie */
    cursor: pointer;   /* Zmienia kursor na rękę */
    border-radius: 5px; /* Lekko zaokrągla rogi */
    font-weight: bold; /* Pogrubia 'x' */
}

.box:hover .x {
    opacity: 1; /* To sprawia, że przycisk .x pojawia się, gdy najedziesz na .box */
}

.share-btn {
    position: absolute; top: 0; right: 25px; opacity: 0;
    background: #5cb85c; color: white;
    width: 20px; height: 20px;
    text-align: center; line-height: 20px;
    cursor: pointer; border-radius: 5px;
    font-size: 14px; font-weight: bold;
}

.box:hover .x, .box:hover .share-btn, .box:hover .download-link { opacity: 1; }
        
    </style>
</head>
<body>
    <nav>
        <a href="index.php">Strona główna</a>
        
        
        <?php
        if(isset($_SESSION['userId'])){
            echo "<a href='logout.php'>Wyloguj</a>";
            echo '<a href="profile.php">Profil</a>';
        } else{
            echo '<a href="login.php">Login</a>';
            echo '<a href="register.php">Rejestracja</a>';
        }
        ?>
    </nav>
     
    <main>
        <h2>Mój profil</h2> 
        <div class="profile">
            <img src="img/profile.png" width="200px" height="200" alt="">
            <div >
                <h3>Imię: </h3>
                <h3>Nazwisko: </h3>
            </div>
        </div>
        <div class="description">
            <h3>Opis </h3>
            <p>Lorem ipsum,  ex ullam nobis. Expedita, alias fuga error consectetur laboriosam vitae placeat possimus odit temporibus tempore eius enim dolorem ab? Ex nam nesciunt minima officia est repellendus reprehenderit porro ullam perspiciatis quidem vel iure sapiente asperiores inventore, iusto ipsa veniam voluptate alias nulla. Similique ullam molestiae quis labore autem natus ea doloremque ducimus, sapiente nostrum omnis cum. Sit molestiae iure minima magni aperiam nam dignissimos voluptatem deleniti sint id, cupiditate assumenda similique suscipit, repudiandae cumque. Molestias, quasi.</p>
        </div>  
        <hr>
        <h3>Pliki</h3>
        <form action="profile.php" method="post" enctype="multipart/form-data">
            <label for="file_upload">Wybierz plik do wgrania:</label>
            <input type="file" name="file_upload" id="file_upload">
            <button type="submit">Wgraj Plik</button>
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

        
        

        <ul class="file-grid">
            <?php if (!empty($user_files)): ?>
                <?php foreach ($user_files as $file): ?>
                    <li>
                        <div class="box" data-id="<?php echo $file['id']; ?>"> 
                            <img src="uploads/<?php echo $userId; ?>/<?php echo $file['unique_filename']; ?>" class="t">
                            <div class="x">&times;</div>
                            <div class="share-btn">S</div>
                        </div> 
                        <span class="f-name"><?php echo $file['original_filename']; ?></span>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li>Brak wgranych plików.</li>
            <?php endif; ?>
        </ul>
         








    </main>  

    <script>
        const deleteButtons = document.querySelectorAll('.x');
        deleteButtons.forEach(button => {
            button.addEventListener('click', () => {
                const fileId = button.closest('.box').dataset.id;
                
                // Pytamy użytkownika, czy na pewno chce usunąć plik
                if (!confirm('Czy na pewno chcesz usunąć ten plik? Tej operacji nie można cofnąć!')) {
                    return; // Jeśli użytkownik naciśnie "Anuluj", przerywamy działanie
                }

                // Wysyłanie żądania do serwera (do pliku delete_file.php)
                fetch('delete_file.php', {
                    method: 'POST', // Wysyłamy dane metodą POST
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded', // Informujemy serwer, jakiego typu dane wysyłamy
                    },
                    body: 'fileId=' + fileId // To są dane, które wysyłamy: ID pliku
                })
                .then(response => response.json()) // Odbieramy odpowiedź z serwera i próbujemy ją odczytać jako JSON
                .then(data => {
                    if (data.success) {
                        // Jeśli serwer powiedział, że operacja się powiodła (success: true)
                        button.closest('li').remove(); // Usuwamy cały element listy (miniatura + nazwa) ze strony
                        alert(data.message); // Wyświetlamy użytkownikowi komunikat o sukcesie (np. "Plik usunięty!")
                    } else {
                        // Jeśli serwer zgłosił błąd (success: false)
                        alert('Błąd: ' + data.message); // Wyświetlamy użytkownikowi komunikat o błędzie
                    }
                })
                .catch(error => {
                    // Jeśli coś pójdzie nie tak z samym połączeniem (np. brak internetu, błąd serwera)
                    console.error('Wystąpił błąd podczas wysyłania żądania:', error);
                    alert('Wystąpił błąd podczas komunikacji z serwerem. Spróbuj ponownie.');
                });
            });
        });


        //obsluga udostepniennia 
        const shareButtons = document.querySelectorAll('.share-btn');  
        shareButtons.forEach(button => {
            // Ustaw początkowy kolor przycisku na podstawie statusu 'is_public' z danych PHP
            // UWAGA: Aby to działało, musisz dodać data-is-public do elementu 'box' w HTML
            // Np. <div class="box" data-id="..." data-is-public="<?php echo $file['is_public'] ? 'true' : 'false'; ?>">
            const isPublicInitial = button.closest('.box').dataset.isPublic === 'true';
            button.style.backgroundColor = isPublicInitial ? '#007bff' : '#5cb85c'; // Niebieski dla publicznego, zielony dla prywatnego

            button.addEventListener('click', () => {
                const fileId = button.closest('.box').dataset.id;
                
                fetch('toggle_public.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'fileId=' + fileId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Zmień kolor przycisku na podstawie nowego statusu
                        button.style.backgroundColor = data.is_public ? '#007bff' : '#5cb85c'; 
                        // Opcjonalnie: zaktualizuj też data-is-public w HTML
                        button.closest('.box').dataset.isPublic = data.is_public ? 'true' : 'false';
                    } else {
                        console.error('Błąd zmiany statusu:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Błąd komunikacji z serwerem:', error);
                });
            });
        });


    </script>

    
</body>
</html>
