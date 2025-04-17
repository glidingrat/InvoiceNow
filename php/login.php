<?php
session_start();

include 'db_connection.php'; // Zahrň připojení k databázi

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Ověření uživatelského jména
    $sql = "SELECT id, username, password FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) { // Kontrola, zda bylo příkazové pole úspěšně vytvořeno
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($id, $username_db, $hashed_password);

        if ($stmt->fetch()) {
            // Ověření hesla
            if (password_verify($password, $hashed_password)) {
                $_SESSION['username'] = $username_db;
                $_SESSION['user_id'] = $id;  
                header("Location: settings.php"); // Přesměrování na hlavní stránku po přihlášení
                exit;
            } else {
                // Nesprávné heslo
                header("Location: ../login.html?error=Nesprávné heslo.");
                exit;
            }
        } else {
            // Uživatel neexistuje
            header("Location: ../login.html?error=Uživatel neexistuje.");
            exit;
        }

        $stmt->close();
    } else {
        header("Location: ../login.html?error=Došlo k chybě při přípravě dotazu.");
        exit;
    }
}

$conn->close();
?>
