
document.querySelector('.logo').addEventListener('click', function (event) {
    event.preventDefault(); // Zabraňuje výchozímu chování odkazu
    
    const main = document.querySelector('main#login');
    
    // Přidání animace zmizení (fade-out)
    main.classList.add('fade-out');
    
    // Po dokončení animace zmizení přesměrování na jinou stránku
    main.addEventListener('animationend', () => {
        window.location.href = 'index.html';  // Změň URL na stránku, na kterou chceš přejít
    });
});


document.querySelector('.register').addEventListener('click', function (event) {
    event.preventDefault(); // Zabraňuje výchozímu chování odkazu
    
    const main = document.querySelector('main#login');
    
    // Přidání animace zmizení (fade-out)
    main.classList.add('fade-out');

    // Po dokončení animace zmizení přesměrování na jinou stránku
    main.addEventListener('animationend', () => {
        window.location.href = 'register.html';  // Změň URL na stránku, na kterou chceš přejít
    });
});