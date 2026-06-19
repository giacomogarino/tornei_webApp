/**
 * matchora-risultato.js
 */
(function () {
    'use strict';

    const COLOR_OK  = 'var(--m-success, #16a34a)';
    const COLOR_ERR = 'var(--m-danger,  #dc2626)';

    function feedback(form, text, color, autohide) {
        let msg = form.querySelector('.js-risultato-msg');
        if (!msg) {
            msg = document.createElement('span');
            msg.className = 'js-risultato-msg';
            msg.style.cssText = 'margin-left:8px;font-size:13px;display:inline;';
            form.appendChild(msg);
        }
        msg.textContent = text;
        msg.style.color = color;
        msg.style.display = 'inline';
        if (autohide) {
            setTimeout(() => { msg.style.display = 'none'; }, 2800);
        }
    }

    function aggiornaMatchCard(form, casaVal, ospiteVal) {
        const match = form.closest('.m-match');
        if (!match) return;
        const scores = match.querySelectorAll('.m-match__score');
        if (scores.length >= 2) {
            scores[0].textContent = casaVal;
            scores[1].textContent = ospiteVal;
        }
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
        const head = match.querySelector('.m-match__head span:last-child');
        if (head) head.textContent = 'Terminata';
    }

    function aggiornaRigaGirone(form, casaVal, ospiteVal) {
        const tr = form.closest('tr');
        if (!tr) return;
        const celle = tr.querySelectorAll('td');
        celle.forEach(td => {
            const b = td.querySelector('b.m-num');
            if (b || td.textContent.trim() === '—' || td.textContent.trim() === '–') {
                td.innerHTML = `<b class="m-num">${casaVal} &ndash; ${ospiteVal}</b>`;
            }
        });
    }

    function handleSubmit(e) {
        e.preventDefault(); // <-- DEVE essere la prima cosa
        
        const form = e.currentTarget;
        
        // Log per debug
        console.log('Form submit intercettato:', form);
        
        const btn = form.querySelector('.js-risultato-btn');
        const casaInput = form.querySelector('.js-input-casa');
        const ospiteInput = form.querySelector('.js-input-ospite');
        const csrfInput = form.querySelector('input[name="csrf_token"]');

        if (!casaInput || !ospiteInput || !csrfInput) {
            console.error('Campi mancanti nel form');
            form.submit(); // fallback
            return;
        }

        const partitaId = form.dataset.partitaId;
        if (!partitaId) {
            console.error('data-partita-id mancante');
            feedback(form, 'ID partita mancante', COLOR_ERR, true);
            return;
        }

        const casaVal = casaInput.value.trim();
        const ospiteVal = ospiteInput.value.trim();

        if (casaVal === '' || ospiteVal === '') {
            feedback(form, 'Inserisci entrambi i punteggi.', COLOR_ERR, true);
            return;
        }

        if (isNaN(casaVal) || isNaN(ospiteVal)) {
            feedback(form, 'Inserisci numeri validi.', COLOR_ERR, true);
            return;
        }

        if (btn) { 
            btn.disabled = true; 
            btn.textContent = '…'; 
        }

        // Rimuovi messaggi precedenti
        const oldMsg = form.querySelector('.js-risultato-msg');
        if (oldMsg) oldMsg.style.display = 'none';

        const body = new URLSearchParams({
            csrf_token: csrfInput.value,
            partita_id: partitaId,
            casa: casaVal,
            ospite: ospiteVal,
        });

        console.log('Invio richiesta a /php/aggiorna_risultato.php con:', body.toString());

        fetch('/php/aggiorna_risultato.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: body.toString(),
        })
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (!data.ok) {
                feedback(form, data.msg || 'Errore.', COLOR_ERR, true);
                if (btn) { btn.disabled = false; btn.innerHTML = '✏️'; }
                return;
            }

            const c = data.data.punti_casa;
            const o = data.data.punti_ospite;

            casaInput.value = c;
            ospiteInput.value = o;
            aggiornaMatchCard(form, c, o);
            aggiornaRigaGirone(form, c, o);

            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '✏️';
                btn.className = btn.className.replace('m-btn--primary', 'm-btn--secondary');
            }

            feedback(form, '✓ Salvato', COLOR_OK, true);

            if (data.data.stato === 'terminata' || data.data.stato === 'completata') {
                setTimeout(() => window.location.reload(), 900);
            }
        })
        .catch(error => {
            console.error('Errore fetch:', error);
            feedback(form, 'Errore di rete: ' + error.message, COLOR_ERR, true);
            if (btn) { 
                btn.disabled = false; 
                btn.innerHTML = '✏️'; 
            }
        });
    }

    // ── Init ──────────────────────────────────────────────────────────
    function init() {
        const forms = document.querySelectorAll('.js-risultato-form');
        console.log('Trovati ' + forms.length + ' form .js-risultato-form');
        forms.forEach(f => {
            // Rimuovi eventuali listener duplicati
            f.removeEventListener('submit', handleSubmit);
            f.addEventListener('submit', handleSubmit);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();