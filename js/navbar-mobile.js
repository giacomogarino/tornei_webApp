/* navbar-mobile.js
   Inserisce il pulsante hamburger nell'header e gestisce
   l'apertura/chiusura del menu su mobile.
   Nessuna modifica al PHP richiesta.
*/
(function () {
    var header = document.querySelector('header');
    var navbar = document.getElementById('navbar');
    if (!header || !navbar) return;

    /* Crea il pulsante ---------------------------------------- */
    var btn = document.createElement('button');
    btn.className = 'nav-toggle';
    btn.setAttribute('aria-expanded', 'false');
    btn.setAttribute('aria-controls', 'navbar');
    btn.setAttribute('aria-label', 'Apri menu');
    btn.innerHTML =
        '<svg class="icon-open" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" fill="none" aria-hidden="true">' +
            '<line x1="3" y1="6"  x2="21" y2="6"/>' +
            '<line x1="3" y1="12" x2="21" y2="12"/>' +
            '<line x1="3" y1="18" x2="21" y2="18"/>' +
        '</svg>' +
        '<svg class="icon-close" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" fill="none" aria-hidden="true">' +
            '<line x1="5"  y1="5"  x2="19" y2="19"/>' +
            '<line x1="19" y1="5"  x2="5"  y2="19"/>' +
        '</svg>';

    header.appendChild(btn);

    /* Toggle -------------------------------------------------- */
    btn.addEventListener('click', function () {
        var open = navbar.classList.toggle('is-open');
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    /* Chiude cliccando fuori ---------------------------------- */
    document.addEventListener('click', function (e) {
        if (!navbar.contains(e.target) && !btn.contains(e.target)) {
            navbar.classList.remove('is-open');
            btn.setAttribute('aria-expanded', 'false');
        }
    });

    /* Chiude al resize verso desktop -------------------------- */
    window.addEventListener('resize', function () {
        if (window.innerWidth > 768) {
            navbar.classList.remove('is-open');
            btn.setAttribute('aria-expanded', 'false');
        }
    });
})();
