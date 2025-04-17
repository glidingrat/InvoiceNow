document.querySelector('.answers-button').addEventListener('click', function (event) {
    event.preventDefault(); // Zabraňuje výchozímu chování odkazu
    
    const main = document.querySelector('main#index');
    
    // Přidání animace zmizení (fade-out)
    main.classList.add('fade-out');

    // Po dokončení animace zmizení přesměrování na jinou stránku
    main.addEventListener('animationend', () => {
        window.location.href = 'register.html';  // Změň URL na stránku, na kterou chceš přejít
    });
});


