<?php
session_start();
require_once '../vendor/autoload.php'; // Načtení PHPWord
require_once 'db_connection.php'; // Připojení k databázi

use PhpOffice\PhpWord\TemplateProcessor;
use Google\Client;
use Google\Service\Drive;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $templatePath = '../templates/invoice_template.docx';
    $templateProcessor = new TemplateProcessor($templatePath);
    
    $invoiceNumber = $_POST['invoiceNumber'] ?? time();
    $invoiceDate = date('Y-m-d');
    $invoiceDateDue = date('Y-m-d', strtotime('+14 days'));

    $fields = [
        'invoiceNumber', 'companyName', 'streetAddress', 'postalCity', 'City', 'Country', 'phone', 'ICO', 'DIC',
        'recipientName', 'recipientStreet', 'recipientPostalCity', 'recipientCity', 'recipientCompany',
        'recipientICO', 'recipientDIC', 'recipientPhone',
        'payment_method', 'acc_number', 'iban', 'bic'
    ];
    foreach ($fields as $field) {
        $templateProcessor->setValue($field, $_POST[$field] ?? '');
    }
    $templateProcessor->setValue('invoiceDate', $invoiceDate);
    $templateProcessor->setValue('invoiceDateDue', $invoiceDateDue);

    $products = json_decode($_POST['products'] ?? '[]', true);
    if (!empty($products)) {
        $templateProcessor->cloneRow('description', count($products));
        $total_dph = $total_price = 0;
        foreach ($products as $index => $product) {
            $rowIndex = $index + 1;
            foreach ($product as $key => $value) {
                $templateProcessor->setValue("{$key}#{$rowIndex}", $value);
            }
            $total_dph += $product['noDPH'];
            $total_price += $product['total_unit_price'];
        }
        $templateProcessor->setValue('total_dph', $total_dph);
        $templateProcessor->setValue('total', $total_price);
    }

    $tempDocx = tempnam(sys_get_temp_dir(), 'invoice_') . '.docx';
    $templateProcessor->saveAs($tempDocx);

    $client = new Client();
    $client->setAuthConfig('../service_account.json');
    $client->addScope(Drive::DRIVE);
    $driveService = new Drive($client);
    
    $fileMetadata = new Drive\DriveFile([
        'name' => basename($tempDocx),
        'mimeType' => 'application/vnd.google-apps.document'
    ]);
    
    $file = $driveService->files->create($fileMetadata, [
        'data' => file_get_contents($tempDocx),
        'mimeType' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'uploadType' => 'multipart'
    ]);
    
    $pdfFileId = $file->id;
    $pdfContent = $driveService->files->export($pdfFileId, 'application/pdf', ['alt' => 'media']);
    $pdfBlob = $pdfContent->getBody()->getContents();


    $stmtUser = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmtUser->execute([$_SESSION['username']]);
    $user_id = $stmtUser->fetchColumn() ?? 0;
    
    $stmt = $pdo->prepare("INSERT INTO invoices (
        user_id, invoice_number, invoice_date, invoice_date_due,
        company_name, street_address, postal_city, city, country, phone, ICO, DIC,
        recipient_name, recipient_street, recipient_city, recipient_postal, recipient_company, recipient_ICO, recipient_DIC, recipient_phone,
        payment_method, acc_number, iban, bic,
        total_dph, total, items, docx_blob, pdf_blob
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
    )");
    
    $stmt->execute([
        $user_id, $invoiceNumber, $invoiceDate, $invoiceDateDue,
        $_POST['companyName'] ?? '', $_POST['streetAddress'] ?? '', $_POST['postalCity'] ?? '', $_POST['City'] ?? '', $_POST['Country'] ?? '', $_POST['phone'] ?? '', $_POST['ICO'] ?? '', $_POST['DIC'] ?? '',
        $_POST['recipientName'] ?? '', $_POST['recipientStreet'] ?? '', $_POST['recipientCity'] ?? '', $_POST['recipientPostalCity'] ?? '', $_POST['recipientCompany'] ?? '', $_POST['recipientICO'] ?? '', $_POST['recipientDIC'] ?? '', $_POST['recipientPhone'] ?? '',
        $_POST['payment_method'] ?? '', $_POST['acc_number'] ?? '', $_POST['iban'] ?? '', $_POST['bic'] ?? '',
        $total_dph, $total_price, json_encode($products), file_get_contents($tempDocx), $pdfBlob
    ]);

    unlink($tempDocx);

    echo json_encode([
        'success' => true,
        'message' => 'Faktura byla úspěšně vygenerována a uložena.',
        'invoice_id' => $pdo->lastInsertId()
    ]);
}
?>
