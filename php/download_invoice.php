<?php
session_start();
require_once 'db_connection.php'; // Připojení k databázi

// Získání invoice_id z URL
$invoiceId = $_GET['invoice_id'] ?? null;

if ($invoiceId) {
    // Načteme záznam faktury podle ID
    $stmt = $pdo->prepare("SELECT pdf_blob, docx_blob FROM invoices WHERE id = ?");
    $stmt->execute([$invoiceId]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($invoice) {
        // Pokud je k dispozici PDF soubor, nabídneme jeho stažení
        if (!empty($invoice['pdf_blob'])) {
            header("Content-Type: application/pdf");
            header("Content-Disposition: attachment; filename=\"invoice_{$invoiceId}.pdf\"");
            header("Content-Length: " . strlen($invoice['pdf_blob']));
            echo $invoice['pdf_blob'];
            exit;
        }
        // Pokud není PDF, ale DOCX je k dispozici, nabídneme DOCX
        elseif (!empty($invoice['docx_blob'])) {
            header("Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document");
            header("Content-Disposition: attachment; filename=\"invoice_{$invoiceId}.docx\"");
            header("Content-Length: " . strlen($invoice['docx_blob']));
            echo $invoice['docx_blob'];
            exit;
        } else {
            echo "Faktura s ID {$invoiceId} neobsahuje žádný soubor.";
        }
    } else {
        echo "Faktura s ID {$invoiceId} nebyla nalezena.";
    }
} else {
    echo "Chybí ID faktury.";
}
?>
