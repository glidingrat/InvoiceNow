<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

require_once 'db_connection.php';

// Získání vyhledávacích parametrů
$search_invoice_number = $_GET['invoice_number'] ?? '';
$search_invoice_date = $_GET['invoice_date'] ?? '';

$query = "SELECT * FROM invoices WHERE user_id = ?";
$params = [$user_id];

// Přesné hledání podle čísla faktury (operátor '=' místo 'LIKE')
if (!empty($search_invoice_number)) {
    $query .= " AND invoice_number = ?";
    $params[] = $search_invoice_number; // Zde už nepřidáváme wildcard znaky
}

if (!empty($search_invoice_date)) {
    $query .= " AND invoice_date = ?";
    $params[] = $search_invoice_date;
}

$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$invoices = $stmt->fetchAll();
?>


<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historie Faktur</title>
    <link rel="stylesheet" href="../css/history_style.css">
    <style>
        .search-bar {
            margin: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
            flex-direction: column;
        }

        .search-bar input[type="text"],
        .search-bar input[type="date"] {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }

        .invoice-history {
            max-height: 70vh;
            overflow-y: auto;
            margin: 40px 10%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 12px;
        }

    </style>
</head>
<body>
    <header>
        <span class="logo">InvoiceNow</span>
        <nav>
            <ul class="nav_links">
                <li><strong><a href="../php/history_invoice.php">Historie</a></strong></li>
                <li><strong><a href="../php/create_invoice.php">Nová Faktura</a></strong></li>
                <li><strong><a href="../php/settings.php">Nastavení</a></strong></li>
            </ul>
        </nav>
        <div class="user-info">
            <span id="username"><strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
            <a href="logout.php" class="btn-logout">Odhlásit se</a>
        </div>
    </header>

    <main>
        <div class="search-bar">
            <form method="GET" action="history_invoice.php">
                <input type="text" name="invoice_number" placeholder="Číslo faktury" value="<?php echo htmlspecialchars($search_invoice_number); ?>">
                <input type="date" name="invoice_date" value="<?php echo htmlspecialchars($search_invoice_date); ?>">
                <button type="submit" class="btn-search">Vyhledat</button>
                <a href="history_invoice.php" class="btn-reset">Reset</a>
            </form>
        </div>

        <div class="invoice-history">
            <?php if (count($invoices) > 0): ?>
                <?php foreach ($invoices as $invoice): ?>
                    <div class="invoice-item">
                        <div class="invoice-info">
                            <p><strong>Faktura č. <?php echo htmlspecialchars($invoice['invoice_number']); ?></strong></p>
                            <p>Vystaveno: <?php echo date('d.m.Y', strtotime($invoice['invoice_date'])); ?></p>
                        </div>
                        <div class="invoice-actions">
                            <a href="create_invoice.php?invoice_id=<?php echo $invoice['id']; ?>" class="btn-edit">Upravit</a>
                            <a href="download_invoice.php?invoice_id=<?php echo $invoice['id']; ?>" class="btn-download">Stáhnout</a>
                            <a href="delete_invoice.php?invoice_id=<?php echo $invoice['id']; ?>" class="btn-delete" onclick="return confirm('Opravdu chcete smazat tuto fakturu?');">Smazat</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <h2>Žádné faktury nebyly nalezeny.</h2>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
