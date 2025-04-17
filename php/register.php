<?php
// Připojení k databázi
include 'db_connection.php';

// Nastavení hlavičky pro JSON odpovědi
header('Content-Type: application/json');

// Kontrola, zda metoda je POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Získání údajů z formuláře
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);

    // Ověření formátu e-mailu
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(array('error' => 'Neplatný e-mail.'));
        exit;
    }

    // Ověření požadavků na heslo
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
        echo json_encode(array('error' => 'Heslo musí obsahovat alespoň jedno velké písmeno, jedno malé písmeno, jednu číslici a mít délku alespoň 8 znaků.'));
        exit;
    }

    // Kontrola, zda uživatelské jméno již existuje
    $sql_check_username = "SELECT COUNT(*) FROM users WHERE username = ?";
    $stmt_check_username = $conn->prepare($sql_check_username);
    $stmt_check_username->bind_param("s", $username);
    $stmt_check_username->execute();
    $stmt_check_username->bind_result($username_count);
    $stmt_check_username->fetch();
    $stmt_check_username->close();

    // Pokud uživatelské jméno existuje, vrátíme chybu
    if ($username_count > 0) {
        echo json_encode(array('error' => 'Uživatelské jméno již existuje.'));
        exit;
    }

    // Hashování hesla
    $password_hashed = password_hash($password, PASSWORD_DEFAULT);

    // Ošetření vstupních dat pro SQL
    $username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    $first_name = htmlspecialchars($first_name, ENT_QUOTES, 'UTF-8');
    $last_name = htmlspecialchars($last_name, ENT_QUOTES, 'UTF-8');

    // Vložení do databáze s použitím prepared statements
    $sql = "INSERT INTO users (username, password, first_name, last_name, email) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        echo json_encode(array('error' => 'Chyba při přípravě dotazu.'));
        exit;
    }

    $stmt->bind_param("sssss", $username, $password_hashed, $first_name, $last_name, $email);

    if ($stmt->execute()) {
        echo json_encode(array('success' => 'Registrace úspěšná.'));
    } else {
        echo json_encode(array('error' => 'Chyba při ukládání do databáze.'));
    }

    $stmt->close();
}

// Zavření připojení k databázi
$conn->close();
?>
