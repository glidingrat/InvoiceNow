<?php
// Připojení k databázi
$servername = "localhost";
$username = "root"; 
$password = "1234";
$dbname = "invoice_db"; 

$conn = new mysqli($servername, $username, $password, $dbname);

try {
    // Vytvoření připojení pomocí PDO
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    
    // Nastavení režimu chyb pro PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Připojení selhalo: " . $e->getMessage());
}

if ($conn->connect_error) {
    die("Připojení selhalo: " . $conn->connect_error);
}


?>