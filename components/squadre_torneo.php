<?php

/**
 * Mostra le squadre approvate passate come array.
 *
 * @param array    $squadre    Risultato della query (array di associativi)
 * @param int|null $utente_id  ID utente loggato (opzionale, per label "sei il capitano")
 */
function mostra_squadre_approvate(array $squadre, ?int $utente_id = null): void
{
    if (empty($squadre)): ?>
        <div class="m-empty">
            <div class="m-empty__icon">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3"/><path d="M5.5 21a6.5 6.5 0 0 1 13 0"/></svg>
            </div>
            <h3>Nessuna squadra approvata</h3>
            <p class="m-muted">Le squadre approvate appariranno qui.</p>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column;">
            <?php foreach ($squadre as $i => $squadra):
                $nome = $squadra['nome'];
                $iniz = strtoupper(mb_substr($nome, 0, 2));
                $is_cap = ($utente_id && $squadra['capitano_id'] == $utente_id);
            ?>
                <div style="display: grid; grid-template-columns: 36px 1fr auto auto; gap: var(--m-3); padding: var(--m-3); align-items: center; <?= $i > 0 ? 'border-top: 1px solid var(--m-border);' : '' ?>">
                    <span class="m-avatar m-avatar--sq"><?= htmlspecialchars($iniz) ?></span>
                    <div>
                        <div style="font-weight: 500;"><?= htmlspecialchars($squadra['nome']) ?></div>
                        <?php if ($is_cap): ?>
                            <div class="m-muted" style="font-size: 12px;">Sei il capitano</div>
                        <?php endif; ?>
                    </div>
                    <span class="m-badge m-badge--success m-badge--dot">Approvata</span>
                    <a href="dettagli_squadra.php?id=<?= (int)$squadra['id'] ?>" class="m-btn m-btn--ghost m-btn--sm">Dettagli </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif;
}
