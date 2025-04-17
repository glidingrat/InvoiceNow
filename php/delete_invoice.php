<?php
session_start();

// Zkontrolujte, zda je uživatel přihlášen
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$invoice_id = $_GET['invoice_id'];

// Připojení k databázi
include 'db_connection.php'; // Include database connection

// Nejprve ověřte, zda faktura patří přihlášenému uživateli
$query = "SELECT * FROM invoices WHERE id = ? AND user_id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$invoice_id, $user_id]);

if ($stmt->rowCount() > 0) {
    // Faktura existuje, nyní ji smažeme
    $query = "DELETE FROM invoices WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$invoice_id]);
    header('Location: history_invoice.php');
    exit();
} else {
    echo "Faktura nenalezena nebo nemáte oprávnění ji smazat.";
}
