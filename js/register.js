




document.querySelector('.logo').addEventListener('click', function (event) {
    event.preventDefault(); // Zabraňuje výchozímu chování odkazu
    
    const main = document.querySelector('main#register');
    
    // Přidání animace zmizení (fade-out)
    main.classList.add('fade-out');
    
    // Po dokončení animace zmizení přesměrování na jinou stránku
    main.addEventListener('animationend', () => {
        window.location.href = 'index.html';  // Změň URL na stránku, na kterou chceš přejít
    });
});

document.querySelector('.login').addEventListener('click', function (event) {
    event.preventDefault(); // Zabraňuje výchozímu chování odkazu
    
    const main = document.querySelector('main#register');
    
    // Přidání animace zmizení (fade-out)
    main.classList.add('fade-out');

    // Po dokončení animace zmizení přesměrování na jinou stránku
    main.addEventListener('animationend', () => {
        window.location.href = 'login.html';  // Změň URL na stránku, na kterou chceš přejít
    });
});

$(document).ready(function() {
    $('#register-form').submit(function(e) {
        e.preventDefault();  // Zabránit standardnímu odeslání formuláře

        // Odeslání formuláře pomocí AJAX
        $.ajax({
            url: 'php/register.php',
            type: 'POST',
            data: $('#register-form').serialize(),
            dataType: 'json', // Očekávaný formát odpovědi
            success: function(response) {
                if (response.success) {
                    // Přesměrování na stránku přihlášení
                    window.location.href = 'login.html';
                } else if (response.error) {
                    // Zobrazení chyby
                    $('#error-message').text(response.error).show();
                }
            },
            error: function() {
                $('#error-message').text('Nastala chyba při odesílání formuláře.').show();
            }
        });
        
    });
});





