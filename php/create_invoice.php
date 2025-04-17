<?php
session_start();
require_once 'db_connection.php'; // Připojení k databázi

// Zkontroluj, zda je uživatel přihlášen
if (!isset($_SESSION['username'])) {
    header("Location: login.php"); // Přesměruj na přihlašovací stránku
    exit();
}

$username = $_SESSION['username'];

// Načtení údajů o společnosti
$company_stmt = $pdo->prepare("SELECT company_name, ico, dic, street_address, postal_city, city, country, phone FROM company_details WHERE user_id = (SELECT id FROM users WHERE username = ?)");
$company_stmt->execute([$username]);
$company = $company_stmt->fetch();

// Načtení údajů o faktuře, pokud je zadán invoice_id
$invoiceData = [];
if (isset($_GET['invoice_id'])) {
    $invoiceId = intval($_GET['invoice_id']);
    $userId = $_SESSION['user_id'] ?? 0; // Předpokládám, že ID uživatele je uloženo v session
    
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = :invoiceId AND user_id = :userId");
    $stmt->execute(['invoiceId' => $invoiceId, 'userId' => $userId]);
    $invoiceData = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <link rel="stylesheet" href="../css/create_style.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nová faktura</title>
    <script>
        function addProductRow() {
            let table = document.getElementById("productsTable");
            let row = table.insertRow();
            row.innerHTML = `
                <td><input type="text" name="description[]" required></td>
                <td><input type="number" name="ks[]" required onchange="updatePrice(this)"></td>
                <td><input type="number" name="priceKs[]" required onchange="updatePrice(this)"></td>
                <td><input type="number" name="noDPH[]" readonly></td>
                <td><input type="number" name="DPH[]" readonly></td>
                <td><input type="number" name="DPHvalue[]" value="21" required onchange="updatePrice(this)"></td>
                <td><input type="number" name="total_unit_price[]" readonly></td>
                <td><button type="button" onclick="removeRow(this)">X</button></td>
            `;
        }


        function removeRow(button) {
            let row = button.parentNode.parentNode;
            row.parentNode.removeChild(row);
            updateTotal();
        }

        

        function updatePrice(input) {
            let row = input.closest('tr');
            let ks = parseFloat(row.querySelector('input[name="ks[]"]').value) || 0;
            let priceKs = parseFloat(row.querySelector('input[name="priceKs[]"]').value) || 0;
            let dphRate = parseFloat(row.querySelector('input[name="DPHvalue[]"]').value) / 100;
            let noDPH = ks * priceKs;
            let dph = noDPH * dphRate;
            let totalPrice = noDPH + dph;

            row.querySelector('input[name="noDPH[]"]').value = noDPH.toFixed(2);
            row.querySelector('input[name="DPH[]"]').value = dph.toFixed(2);
            row.querySelector('input[name="total_unit_price[]"]').value = totalPrice.toFixed(2);

            updateTotal();
        }

        function updateTotal() {
            let totalNoDPH = 0, totalDPH = 0, totalPrice = 0;
            let table = document.getElementById("productsTable");

            // Přeskočíme hlavičkový řádek (index 0)
            for (let i = 1; i < table.rows.length; i++) {
                totalNoDPH += parseFloat(table.rows[i].querySelector('input[name="noDPH[]"]').value) || 0;
                totalDPH += parseFloat(table.rows[i].querySelector('input[name="DPH[]"]').value) || 0;
                totalPrice += parseFloat(table.rows[i].querySelector('input[name="total_unit_price[]"]').value) || 0;
            }

            document.getElementById("total_dph").value = totalNoDPH.toFixed(2);
            document.getElementById("total").value = totalPrice.toFixed(2);
        }

        function prepareFormData() {
            let products = [];
            let table = document.getElementById("productsTable");
            // Přeskočíme hlavičkový řádek
            for (let i = 1; i < table.rows.length; i++) {
                let row = table.rows[i];
                let product = {
                    description: row.querySelector('input[name="description[]"]').value,
                    ks: parseFloat(row.querySelector('input[name="ks[]"]').value) || 0,
                    priceKs: parseFloat(row.querySelector('input[name="priceKs[]"]').value) || 0,
                    noDPH: parseFloat(row.querySelector('input[name="noDPH[]"]').value) || 0,
                    DPH: parseFloat(row.querySelector('input[name="DPH[]"]').value) || 0,
                    DPHvalue: parseFloat(row.querySelector('input[name="DPHvalue[]"]').value) || 0,
                    total_unit_price: parseFloat(row.querySelector('input[name="total_unit_price[]"]').value) || 0
                };
                products.push(product);
            }

            // Přidání skrytého pole do formuláře s JSON daty
            let productsInput = document.createElement("input");
            productsInput.type = "hidden";
            productsInput.name = "products";
            productsInput.value = JSON.stringify(products);
            document.getElementById("invoiceForm").appendChild(productsInput);
        }

        document.addEventListener("DOMContentLoaded", function() {
            document.getElementById("invoiceForm").addEventListener("submit", function(event) {
                prepareFormData();
            });
        });
    </script>
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


    <form id="invoiceForm" action="generate_invoice.php" method="POST">
    <div class="form-container">
    <div class="form-box">
        <h3>Údaje o společnosti</h3>
        <label>Název firmy: <input type="text" name="companyName" value="<?= htmlspecialchars($company['company_name'] ?? '') ?>" required></label><br>
        <label>Adresa: <input type="text" name="streetAddress" value="<?= htmlspecialchars($company['street_address'] ?? '') ?>" required></label><br>
        <label>Město: <input type="text" name="City" value="<?= htmlspecialchars($company['city'] ?? '') ?>" required></label><br>
        <label>PSČ: <input type="text" name="postalCity" value="<?= htmlspecialchars($company['postal_city'] ?? '') ?>" required></label><br>
        <label>Země: <input type="text" name="Country" value="<?= htmlspecialchars($company['country'] ?? '') ?>" required></label><br>
        <label>Telefon: <input type="text" name="phone" value="<?= htmlspecialchars($company['phone'] ?? '') ?>" required></label><br>
        <label>IČO: <input type="text" name="ICO" value="<?= htmlspecialchars($company['ico'] ?? '') ?>" required></label><br>
        <label>DIČ: <input type="text" name="DIC" value="<?= htmlspecialchars($company['dic'] ?? '') ?>" required></label><br>
    </div>
    <div class="form-box">
        <h3>Údaje o odběrateli</h3>
        <label>Jméno: <input type="text" name="recipientName" value="<?php echo htmlspecialchars($invoiceData['recipient_name'] ?? ''); ?>" required></label><br>
        <label>Adresa: <input type="text" name="recipientStreet" value="<?php echo htmlspecialchars($invoiceData['recipient_street'] ?? ''); ?>" required></label><br>
        <label>Město: <input type="text" name="recipientCity" value="<?php echo htmlspecialchars($invoiceData['recipient_city'] ?? ''); ?>" required></label><br>
        <label>PSČ: <input type="text" name="recipientPostalCity" value="<?php echo htmlspecialchars($invoiceData['recipient_postal'] ?? ''); ?>" required></label><br>
        <label>Firma: <input type="text" name="recipientCompany"value="<?php echo htmlspecialchars($invoiceData['recipient_company'] ?? ''); ?>" ></label><br>
        <label>Telefon: <input type="text" name="recipientPhone" value="<?php echo htmlspecialchars($invoiceData['recipient_phone'] ?? ''); ?>" ></label><br>
        <label>IČO: <input type="text" name="recipientICO" value="<?php echo htmlspecialchars($invoiceData['recipient_ICO'] ?? ''); ?>" ></label><br>
        <label>DIČ: <input type="text" name="recipientDIC" value="<?php echo htmlspecialchars($invoiceData['recipient_DIC'] ?? ''); ?>"></label><br>
           </div>
    <div class="form-box">
        <h3>Fakturační údaje</h3>
        <label>Číslo faktury: <input type="text" name="invoiceNumber" value="<?php echo htmlspecialchars($invoiceData['invoice_number'] ?? ''); ?>"></label><br>
        <label>Datum vystavení: <input type="date" name="invoiceDate" value="<?php echo htmlspecialchars($invoiceData['invoice_date'] ?? ''); ?>" required></label><br>
        <label>Datum splatnosti: <input type="date" name="invoiceDateDue" value="<?php echo htmlspecialchars($invoiceData['invoice_date_due'] ?? ''); ?>" required></label><br>
        <label>Způsob platby: 
            <select name="payment_method" required>
                <option value="" <?php echo empty($invoiceData['payment_method']) ? 'selected' : ''; ?>>-- Vyberte způsob platby --</option>
                <option value="Bankovní převod" <?php echo ($invoiceData['payment_method'] ?? '') === 'Bankovní převod' ? 'selected' : ''; ?>>Bankovní převod</option>
                <option value="Hotově" <?php echo ($invoiceData['payment_method'] ?? '') === 'Hotově' ? 'selected' : ''; ?>>Hotově</option>
                <option value="Platební karta" <?php echo ($invoiceData['payment_method'] ?? '') === 'Platební karta' ? 'selected' : ''; ?>>Platební karta</option>
                <option value="Online platba" <?php echo ($invoiceData['payment_method'] ?? '') === 'Online platba' ? 'selected' : ''; ?>>Online platba</option>
                <option value="Jiný" <?php echo ($invoiceData['payment_method'] ?? '') === 'Jiný' ? 'selected' : ''; ?>>Jiný</option>
            </select>
        </label><br>
        <label>Číslo účtu: <input type="text" name="acc_number" value="<?php echo htmlspecialchars($invoiceData['acc_number'] ?? ''); ?>" ></label><br>
        <label>IBAN: <input type="text" name="iban" value="<?php echo htmlspecialchars($invoiceData['iban'] ?? ''); ?>" ></label><br>
        <label>BIC (SWIFT): <input type="text" name="bic" value="<?php echo htmlspecialchars($invoiceData['bic'] ?? ''); ?>" ></label><br>
    </div>
    <div>
            
    <button type="button" onclick="addProductRow()">Přidat položku</button>
        </div>
    <br>
        <table id="productsTable" style="height: 40px; margin-top: 1%;">
            <tr>
                <th>Popis</th>
                <th>KS</th>
                <th>CENA KS</th>
                <th>Bez DPH</th>
                <th>DPH</th>
                <th>DPH%</th>
                <th>CENA</th>
                <th></th>
            </tr>
            <?php 
            $items = json_decode($invoiceData['items'] ?? '[]', true);
            foreach ($items as $item) {
                echo '<tr>';
                echo '<td><input type="text" name="description[]" value="' . htmlspecialchars($item['description'] ?? '') . '"></td>';
                echo '<td><input type="number" name="ks[]" value="' . htmlspecialchars($item['ks'] ?? '') . '"></td>';
                echo '<td><input type="number" name="priceKs[]" value="' . htmlspecialchars($item['priceKs'] ?? '') . '"></td>';
                echo '<td><input type="number" name="noDPH[]" value="' . htmlspecialchars($item['noDPH'] ?? '') . '"></td>';
                echo '<td><input type="number" name="DPH[]" value="' . htmlspecialchars($item['DPH'] ?? '') . '"></td>';
                echo '<td><input type="number" name="DPHvalue[]" value="' . htmlspecialchars($item['DPHvalue'] ?? '') . '"></td>';
                echo '<td><input type="number" name="total_unit_price[]" value="' . htmlspecialchars($item['total_unit_price'] ?? '') . '"></td>';
                echo '<td><button type="button" onclick="removeRow(this)">X</button></td>';
                echo '</tr>';
            }
            ?>
        </table>
        <br>
      
       
   

    <div class="form-box">
        <h3>Souhrn</h3>
        <label>DPH Celkem: <input type="number" id="total_dph" name="total_dph" value="<?php echo htmlspecialchars($invoiceData['total_dph'] ?? ''); ?>" readonly></label><br>
        <label>Celkem k úhradě: <input type="number" id="total" name="total" value="<?php echo htmlspecialchars($invoiceData['total'] ?? ''); ?>" readonly></label><br>

        <button type="submit">Vygenerovat fakturu</button>
    </div>
    </div>
    </form>


<div id="invoiceModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Generuji fakturu...</h2>
        <p id="loadingText">Načítání...</p> <!-- Text pro načítání -->
        <div id="loadingIndicator" style="display: block;"> <!-- Indikátor načítání -->
            <div id="loadingSpinner"></div> <!-- Spinner pro načítání -->
        </div>
        <a id="pdfLink" href="#" download style="display: none;">
            <button type="button">Stáhnout PDF</button>
        </a>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    document.getElementById("invoiceForm").addEventListener("submit", function(event) {
        event.preventDefault(); // Zabráníme standardnímu odeslání formuláře

        // Zobrazíme modální okno a indikátor načítání
        document.getElementById("invoiceModal").style.display = "block";
        document.getElementById("loadingIndicator").style.display = "block";
        document.getElementById("loadingText").innerText = "Načítání...";

        // Zakážeme tlačítko a změníme text
        const submitButton = document.querySelector("button[type='submit']");
        submitButton.disabled = true;
        submitButton.innerText = "Generuji fakturu...";

        let formData = new FormData(this);

        // Odeslání formuláře přes AJAX
        fetch("generate_invoice.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json()) // Očekáváme JSON odpověď
        .then(data => {
            // Skrytí indikátoru načítání
            document.getElementById("loadingIndicator").style.display = "none";

            if (data.success) {
                // Změníme text na úspěšnou zprávu
                document.querySelector(".modal-content h2").innerText = "Faktura byla úspěšně vygenerována a uložena!";
                document.getElementById("loadingText").innerText = "Stáhněte si fakturu:";

                // Sestavíme odkazy pro stažení z DB pomocí download_invoice.php
                // Předpokládáme, že generate_invoice.php vrací invoice_id
                document.getElementById("pdfLink").href = "download_invoice.php?invoice_id=" + data.invoice_id + "&type=pdf";

                // Zobrazíme odkazy
                document.getElementById("pdfLink").style.display = "inline-block";
            } else {
                alert("Chyba při ukládání faktury.");
            }
        })
        .catch(error => {
            document.getElementById("loadingIndicator").style.display = "none";
            console.error("Chyba při odesílání formuláře:", error);
            alert("Došlo k chybě při odesílání dat.");
        })
        .finally(() => {
            // Obnovení tlačítka po dokončení
            submitButton.disabled = false;
            submitButton.innerText = "Vygenerovat fakturu";
        });
    });

    // Zavření modálního okna
    document.querySelector(".close").addEventListener("click", function() {
        document.getElementById("invoiceModal").style.display = "none";
    });
});
</script>

</body>
</html>






