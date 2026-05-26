
<style>
/* ── Toggle gironi mode ─────────────────────────────── */
.gm-toggle {
    display: inline-flex;
    align-items: center;
    gap: 0;
    border-radius: 999px;
    overflow: hidden;
    border: 1.5px solid var(--m-border);
    background: var(--m-surface);
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: border-color .2s;
}
.gm-toggle:hover { border-color: var(--m-primary-400); }
.gm-toggle__option {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    transition: background .18s, color .18s;
    color: var(--m-text-mute);
    white-space: nowrap;
}
.gm-toggle__option--active-auto {
    background: var(--m-primary-100, #dbeafe);
    color: var(--m-primary-700, #1d4ed8);
}
.gm-toggle__option--active-manual {
    background: #fef3c7;
    color: #92400e;
}
.gm-toggle__divider {
    width: 1px;
    align-self: stretch;
    background: var(--m-border);
}
.gm-toggle-form { display: inline; }
.gm-toggle-form button {
    background: none;
    border: none;
    padding: 0;
    cursor: pointer;
    line-height: 0;
}

/* ── Layout responsive dettagli_torneo ──────────────── */
.dt-layout {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: var(--m-6);
    align-items: start;
}

/* Tabella dati torneo: su desktop 2 colonne (label | valore) */
.dt-dati-dl {
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: var(--m-3) var(--m-4);
    font-size: 14px;
    margin: 0;
}

/* Header hero: titolo + badge stato + azioni */
.dt-hero-inner {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: var(--m-4);
    flex-wrap: wrap;
}

/* Squadre iscritte header */
.dt-squadre-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--m-3);
    flex-wrap: wrap;
}

/* ── Mobile (<= 700px) ─────────────────────────────── */
@media (max-width: 700px) {
    .dt-layout {
        grid-template-columns: 1fr;
    }
    /* Sidebar va sopra il contenuto principale su mobile */
    .dt-layout aside {
        order: -1;
    }

    /* Azioni rapide sidebar: bottoni in colonna su mobile (già block, ok) */

    /* Tabella dati torneo: una sola colonna su mobile */
    .dt-dati-dl {
        grid-template-columns: 1fr;
        gap: var(--m-2);
    }
    .dt-dati-dl dt {
        margin-top: var(--m-3);
        font-size: 11px;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }
    .dt-dati-dl dt:first-child {
        margin-top: 0;
    }
    .dt-dati-dl dd {
        margin: 0;
    }

    /* Hero: titolo + badge in colonna */
    .dt-hero-inner {
        flex-direction: column;
        align-items: flex-start;
    }

    /* Tab: scroll orizzontale se non entrano */
    .m-tabs {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        flex-wrap: nowrap;
        scrollbar-width: none;
    }
    .m-tabs::-webkit-scrollbar { display: none; }
    .m-tab { white-space: nowrap; }

    /* Squadre iscritte: header in colonna */
    .dt-squadre-header {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<?php
$torneo = $navbar_data['torneo'];
$isOrganizzatore = $navbar_data['isOrganizzatore'] ?? false;
$stato_label = $navbar_data['stato_label'];
$formato_label = $navbar_data['formato_label'];
$tipo_label = $navbar_data['tipo_label'];
$isFollowing = $navbar_data['isFollowing'] ?? false;
?>

<header class="t-hero">
    <div class="m-container">
        <div class="t-breadcrumb">
            <a href="index.php">Home</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
            <span><?= htmlspecialchars($torneo['nome']) ?></span>
        </div>

        <div class="dt-hero-inner">
            <div>
                <div style="display:flex; gap:8px; margin-bottom:var(--m-3); flex-wrap:wrap;">
                    <span class="t-chip<?= $torneo['stato'] === 'in_corso' ? ' t-chip--live' : '' ?>">
                        <span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:currentColor;"></span>
                        <?= htmlspecialchars($stato_label[$torneo['stato']] ?? $torneo['stato']) ?>
                    </span>
                    <span class="t-chip">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <?php if ($torneo['visibilita'] === 'privato'): ?>
                                <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            <?php else: ?>
                                <circle cx="12" cy="12" r="9"/><path d="M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18M3 12h18"/>
                            <?php endif; ?>
                        </svg>
                        <?= $torneo['visibilita'] === 'privato' ? 'Privato' : 'Pubblico' ?>
                    </span>
                    <?php if (!empty($torneo['sport'])): ?>
                        <span class="t-chip">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="9"/>
                                <path d="M12 3v18M5 7l14 10"/>
                            </svg>
                            <?= htmlspecialchars($torneo['sport']) ?>
                        </span>
                    <?php endif; ?>

                    <?php if (!empty($torneo['luogo'])): ?>
                        <span class="t-chip">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11z"/>
                                <circle cx="12" cy="10" r="2.5"/>
                            </svg>
                            <?= htmlspecialchars($torneo['luogo']) ?>
                        </span>
                    <?php endif; ?>
                    <span class="t-chip">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                            <path d="M3 6h18M3 12h18M3 18h18"/>
                        </svg>

                        <?= htmlspecialchars($formato_label[$torneo['formato']] ?? $torneo['formato']) ?>
                    </span>
                </div>
                <h1><?= htmlspecialchars($torneo['nome']) ?></h1>
                <?php if (!empty($torneo['descrizione'])): ?>
                    <p class="desc"><?= htmlspecialchars($torneo['descrizione']) ?></p>
                <?php endif; ?>
            </div>

            <div style="display:flex; gap:var(--m-2); flex-wrap:wrap;">
                <form method="POST" style="display:inline;">
                    <?= csrf_field() ?>
                    <button type="submit" name="toggle_follow" class="m-btn <?= $isFollowing ? 'm-btn--secondary' : 'm-btn--gold' ?>">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="<?= $isFollowing ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.29 1.51 4.04 3 5.5l7 7Z"/></svg>
                        <?= $isFollowing ? 'Stai seguendo' : 'Segui torneo' ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>