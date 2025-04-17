<?php
session_start();
include 'db_connection.php'; // Include database connection

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php"); // Redirect to login page
    exit();
}

// Get user information
$username = $_SESSION['username'];
$stmt = $pdo->prepare("SELECT first_name, last_name, email, password FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

// Get company details
$company_stmt = $pdo->prepare("SELECT company_name, ico, dic, street_address, postal_city, city, country, phone FROM company_details WHERE user_id = (SELECT id FROM users WHERE username = ?)");
$company_stmt->execute([$username]);
$company = $company_stmt->fetch();

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $new_username = $_POST['username'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $company_update_password = $_POST['company_update_password'] ?? '';

    if ($_POST['form_type'] === 'user_info') {
        // Zpracování informací o uživateli
        if (password_verify($current_password, $user['password'])) {
            // Pokračujte aktualizací
        }
    }


    // User info update
    if ($_POST['form_type'] === 'user_info') {
        // Verify current password
        if (password_verify($current_password, $user['password'])) {
            // Update user information
            $update_data = [$first_name, $last_name, $email, $new_username, $username];
            
            // If new password is provided, hash it and include it in the update
            if (!empty($new_password)) {
                $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                // Vytvoříme nové pole s odpovídajícím počtem parametrů
                $update_data = [$first_name, $last_name, $email, $new_username, $hashed_new_password, $username];
                $updateStmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, username = ?, password = ? WHERE username = ?");
                $updateStmt->execute($update_data);
            } else {
                // Pokud není nové heslo zadáno
                $update_data = [$first_name, $last_name, $email, $new_username, $username];
                $updateStmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, username = ? WHERE username = ?");
                $updateStmt->execute($update_data);
            }
            

            // Update session
            $_SESSION['username'] = $new_username; // Save new username in session

            // Redirect back to settings page with success message
            header("Location: settings.php?success_user=Aktualizováno!");
            exit();
        } else {
            $error_message = "Aktuální heslo je nesprávné.";
        }
    }

    // Company information update

    if ($_POST['form_type'] === 'company_info') {
        if (password_verify($company_update_password, $user['password'])) {
            $company_name = $_POST['company_name'];
            $ico = $_POST['ico'];
            $dic = $_POST['dic'];
            $street_address = $_POST['street_address'];
            $postal_city = $_POST['postal_city'];
            $city = $_POST['city'];
            $country = $_POST['country'];
            $phone = $_POST['phone'];

            if ($company) {
                // Aktualizace existující společnosti
                $updateCompanyStmt = $pdo->prepare("UPDATE company_details SET company_name = ?, ico = ?, dic = ?, street_address = ?, postal_city = ?, city = ?, country = ?, phone = ? WHERE user_id = (SELECT id FROM users WHERE username = ?)");
                $updateCompanyStmt->execute([$company_name, $ico, $dic, $street_address, $postal_city, $city, $country, $phone, $username]);
            } else {
                // Vložení nové společnosti
                $insertCompanyStmt = $pdo->prepare("INSERT INTO company_details (user_id, company_name, ico, dic, street_address, postal_city, city, country, phone) VALUES ((SELECT id FROM users WHERE username = ?), ?, ?, ?, ?, ?, ?, ?)");
                $insertCompanyStmt->execute([$username, $company_name, $ico, $dic, $street_address, $postal_city, $city, $country, $phone]);
            }

            header("Location: settings.php?success_company=Aktualizováno!");
            exit();
        } else {
            $company_error_message = "Aktuální heslo je nesprávné pro změnu údajů o společnosti.";
        }
    }


// Password change
if ($_POST['form_type'] === 'change_password') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];

    if (password_verify($current_password, $user['password'])) {
        // Check password complexity
        if (strlen($new_password) >= 8 &&
            preg_match('/[A-Z]/', $new_password) &&
            preg_match('/[a-z]/', $new_password) &&
            preg_match('/\d/', $new_password)) {

            // Hash the new password
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

            // Update the password in the database
            $updatePasswordStmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
            $updatePasswordStmt->execute([$new_password_hash, $username]);

            header("Location: settings.php?success_password=Aktualizováno!");
            exit();
        } else {
            $password_error_message = "Nové heslo nesplňuje požadavky na složitost.";
        }
    } else {
        $password_error_message = "Aktuální heslo je nesprávné.";
    }
}

}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nastavení uživatelského účtu</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="icon" href="data:,">
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
            <span id="username"><strong><?php echo htmlspecialchars($username); ?></strong></span>
            <a href="logout.php" class="btn-logout">Odhlásit se</a>
        </div>
    </header>

    <div class="container">
        <div class="form-box">
            <h1>Uživatel</h1>
            <?php if (isset($_GET['success_user'])): ?>
                <p style="color: green;"><?php echo htmlspecialchars($_GET['success_user']); ?></p>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <p style="color: red;"><?php echo htmlspecialchars($error_message); ?></p>
            <?php endif; ?>
            <form action="settings.php" method="post">
                <input type="hidden" name="form_type" value="user_info">
                <div class="input-group">
                    <label for="username">Uživatelské jméno</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                </div>
                <div class="input-group">
                    <label for="first_name">Jméno</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                </div>
                <div class="input-group">
                    <label for="last_name">Příjmení</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                </div>
                <div class="input-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="input-group">
                    <label for="current_password">Heslo pro potvrzení</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                <button type="submit">Uložit změny</button>
            </form>
        </div>

        <div class="form-box">
    <h1>Společnost</h1>
    <?php if (isset($_GET['success_company'])): ?>
        <p style="color: green;"><?php echo htmlspecialchars($_GET['success_company']); ?></p>
    <?php endif; ?>

    <?php if (isset($company_error_message)): ?>
        <p style="color: red;"><?php echo htmlspecialchars($company_error_message); ?></p>
    <?php endif; ?>
    
    <form action="settings.php" method="post">
        <input type="hidden" name="form_type" value="company_info">
        
        <div class="input-group">
            <label for="company_name">Název společnosti</label>
            <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($company['company_name'] ?? ''); ?>" required>
        </div>
        <div class="input-group">
            <label for="street_address">Ulice a číslo domu</label>
            <input type="text" id="street_address" name="street_address" value="<?php echo htmlspecialchars($company['street_address'] ?? ''); ?>" required>
        </div>
        <div class="input-group">
            <label for="postal_city">PSČ</label>
            <input type="text" id="postal_city" name="postal_city" value="<?php echo htmlspecialchars($company['postal_city'] ?? ''); ?>" required>
        </div>
        <div class="input-group">
            <label for="city">Město</label>
            <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($company['city'] ?? ''); ?>" required>
        </div>
        <div class="input-group">
            <label for="phone">Telefon</label>
            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($company['phone'] ?? ''); ?>" required>
        </div>
        <div class="input-group">
            <label for="ico">IČO</label>
            <input type="text" id="ico" name="ico" value="<?php echo htmlspecialchars($company['ico'] ?? ''); ?>" required>
        </div>
        <div class="input-group">
            <label for="dic">DIČ</label>
            <input type="text" id="dic" name="dic" value="<?php echo htmlspecialchars($company['dic'] ?? ''); ?>" required>
        </div>
        <div class="input-group">
            <label for="country">Země</label>
            <input type="text" id="country" name="country" value="<?php echo htmlspecialchars($company['country'] ?? ''); ?>" required>
        </div>

        <div class="input-group">
            <label for="company_update_password">Heslo pro potvrzení</label>
            <input type="password" id="company_update_password" name="company_update_password" required>
        </div>
        
        <button type="submit">Uložit změny</button>
    </form>
</div>


        <div class="form-box">
            <h1>Změna hesla</h1>
            <?php if (isset($_GET['success_password'])): ?>
                <p style="color: green;"><?php echo htmlspecialchars($_GET['success_password']); ?></p>
            <?php endif; ?>
            
            <?php if (isset($password_error_message)): ?>
                <p style="color: red;"><?php echo htmlspecialchars($password_error_message); ?></p>
            <?php endif; ?>
            
            <form action="settings.php" method="post">
                <input type="hidden" name="form_type" value="change_password">
                <div class="input-group">
                    <label for="current_password">Aktuální heslo</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                <div class="input-group">
                    <label for="new_password">Nové heslo</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <small>
                    <ul>
                        <li>Minimálně 8 znaků</li>
                        <li>Alespoň jedno velké písmeno</li>
                        <li>Alespoň jedno malé písmeno</li>
                        <li>Alespoň jedna číslice</li>
                    </ul>
                </small>
                <button type="submit" class="btn">Změnit heslo</button>
            </form>
        </div>
    </div>
</body>
</html>
