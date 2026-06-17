/**
 * matchora-risultato.js
 * Intercetta i form .js-risultato-form e aggiorna il risultato via AJAX
 * senza ricaricare la pagina. Se il server risponde con "playoff generato"
 * o "torneo completato" ricarica automaticamente per mostrare il nuovo stato.
 */
(function () {
    'use strict';

    // ── Colori feedback ──────────────────────────────────────────────
    const COLOR_OK  = 'var(--m-success, #16a34a)';
    const COLOR_ERR = 'var(--m-danger,  #dc2626)';

    // ── Mostra messaggio inline accanto al bottone ────────────────────
    function feedback(form, text, color, autohide) {
        const msg = form.querySelector('.js-risultato-msg');
        if (!msg) return;
        msg.textContent = text;
        msg.style.color   = color;
        msg.style.display = 'inline';
        if (autohide) setTimeout(() => { msg.style.display = 'none'; }, 2800);
    }

    // ── Aggiorna visivamente score nel tabellone playoff ─────────────
    function aggiornaMatchCard(form, casaVal, ospiteVal) {
        // Risali al .m-match e aggiorna le .m-match__score
        const match = form.closest('.m-match');
        if (!match) return;
        const scores = match.querySelectorAll('.m-match__score');
        if (scores.length >= 2) {
            scores[0].textContent = casaVal;
            scores[1].textContent = ospiteVal;
        }
        // Aggiorna classi winner/loser
        const rows = match.querySelectorAll('.m-match__row');
        if (rows.length >= 2) {
            rows[0].classList.remove('m-match__row--winner', 'm-match__row--loser');
            rows[1].classList.remove('m-match__row--winner', 'm-match__row--loser');
            if (parseInt(casaVal) > parseInt(ospiteVal)) {
                rows[0].classList.add('m-match__row--winner');
                rows[1].classList.add('m-match__row--loser');
            } else if (parseInt(ospiteVal) > parseInt(casaVal)) {
                rows[1].classList.add('m-match__row--winner');
                rows[0].classList.add('m-match__row--loser');
            }
        }
        // Aggiorna head status
        const head = match.querySelector('.m-match__head span:last-child');
        if (head) head.textContent = 'Terminata';
    }

    // ── Aggiorna riga nella tabella gironi ───────────────────────────
    function aggiornaRigaGirone(form, casaVal, ospiteVal) {
        const tr = form.closest('tr');
        if (!tr) return;
        // La cella risultato è quella con <b class="m-num"> o il placeholder
        const celle = tr.querySelectorAll('td');
        // Cerca la cella che contiene il risultato (ha &ndash; o è vuota/–)
        celle.forEach(td => {
            if (td.querySelector('b.m-num') || td.textContent.trim() === '—') {
                td.innerHTML = `<b class="m-num">${casaVal} &ndash; ${ospiteVal}</b>`;
            }
        });
    }

    // ── Handler principale ───────────────────────────────────────────
    function handleSubmit(e) {
        e.preventDefault();
        const form = e.currentTarget;
        const btn  = form.querySelector('.js-risultato-btn');

        const casaInput   = form.querySelector('.js-input-casa');
        const ospiteInput = form.querySelector('.js-input-ospite');
        const csrfInput   = form.querySelector('input[name="csrf_token"]');

        if (!casaInput || !ospiteInput || !csrfInput) {
            // Fallback: submit normale
            form.submit(); return;
        }

        const casaVal   = casaInput.value.trim();
        const ospiteVal = ospiteInput.value.trim();

        if (casaVal === '' || ospiteVal === '') {
            feedback(form, 'Inserisci entrambi i punteggi.', COLOR_ERR, true);
            return;
        }

        // Stato di caricamento
        if (btn) { btn.disabled = true; btn.textContent = '…'; }

        const body = new URLSearchParams({
            csrf_token:  csrfInput.value,
            partita_id:  form.dataset.partitaId,
            casa:        casaVal,
            ospite:      ospiteVal,
        });

        fetch('/php/aggiorna_risultato.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    body.toString(),
        })
        .then(r => r.json())
        .then(data => {
            if (!data.ok) {
                feedback(form, data.msg || 'Errore.', COLOR_ERR, true);
                if (btn) { btn.disabled = false; btn.innerHTML = '&#9998;'; }
                return;
            }

            const c = data.data.punti_casa;
            const o = data.data.punti_ospite;

            // Aggiorna UI inline
            casaInput.value   = c;
            ospiteInput.value = o;
            aggiornaMatchCard(form, c, o);
            aggiornaRigaGirone(form, c, o);

            // Rendi il bottone "modifica" stile secondario
            if (btn) {
                btn.disabled  = false;
                btn.innerHTML = '&#9998;';
                btn.className = btn.className
                    .replace('m-btn--primary', 'p-btn--secondary');
            }

            feedback(form, '✓ Salvato', COLOR_OK, true);

            // Se il torneo è completato o il turno è cambiato → ricarica dopo breve pausa
            if (data.data.stato === 'terminata') {
                // Attendi 800ms poi ricarica per aggiornare classifica/playoff
                setTimeout(() => window.location.reload(), 900);
            }
        })
        .catch(() => {
            feedback(form, 'Errore di rete. Riprova.', COLOR_ERR, true);
            if (btn) { btn.disabled = false; btn.innerHTML = '&#9998;'; }
        });
    }

    // ── Init: attacca a tutti i form presenti e futuri ────────────────
    function init() {
        document.querySelectorAll('.js-risultato-form').forEach(f => {
            f.addEventListener('submit', handleSubmit);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
