<?php
require_once('templates/header.php');

$team = [
    [
        'nome'     => 'Giacomo Garino',
        'ruolo'    => 'Programmatore',
        'bio'      => 'Studente di 5° anno all\'ITIS Mario Delpozzo di Cuneo. 18 anni, appassionato di sviluppo software.',
        'email'    => 'giacomo.garino@itiscuneo.edu.it',
        'foto'     => 'assets/team/gari.webp',
        'foto_position' => 'center 10%',
        'iniziali' => 'GG',
        'colore'   => 'var(--m-primary-500)',
    ],
    [
        'nome'     => 'Luca Bertolotti',
        'ruolo'    => 'Programmatore',
        'bio'      => 'Studente di 5° anno all\'ITIS Mario Delpozzo di Cuneo. 18 anni, appassionato di sviluppo software.',
        'email'    => 'luca.bertolotti@itiscuneo.edu.it',
        'foto'     => 'assets/team/cluchy.webp',
        'foto_position' => 'center 5%',
        'iniziali' => 'LB',
        'colore'   => '#2e6df2',
    ],
    [
        'nome'     => 'Matteo Luciano',
        'ruolo'    => 'Programmatore',
        'bio'      => 'Studente di 5° anno all\'ITIS Mario Delpozzo di Cuneo. 18 anni, appassionato di sviluppo software.',
        'email'    => 'matteo.luciano@itiscuneo.edu.it',
        'foto'     => 'assets/team/ciano.webp',
        'foto_position' => 'center 10%',
        'iniziali' => 'ML',
        'colore'   => 'var(--m-success-500)',
    ],
    [
        'nome'     => 'Sai Liam Tu',
        'ruolo'    => 'Programmatore',
        'bio'      => 'Studente di 5° anno all\'ITIS Mario Delpozzo di Cuneo. 18 anni, appassionato di sviluppo software.',
        'email'    => 'sailiam.tu@itiscuneo.edu.it',
        'foto'     => 'assets/team/liam.webp',
        'foto_position' => 'center 10%',
        'iniziali' => 'LT',
        'colore'   => 'var(--m-gold-600)',
    ],
    [
        'nome'     => 'Claude AI',
        'ruolo'    => 'Assistente AI',
        'bio'      => 'Intelligenza artificiale di Anthropic che supporta il team di Matchora in analisi, sviluppo e miglioramento continuo della piattaforma.',
        'email'    => '',
        'linkedin' => 'https://claude.ai',
        'linkedin_label' => 'claude.ai',
        'foto'     => '',
        'iniziali' => 'AI',
        'colore'   => '#c57a2e',
        'is_ai'    => true,
    ],
];
?>

<header class="t-hero">
    <div class="m-container">
        <div class="t-breadcrumb">
            <a href="index.php">Home</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
            <a href="contatti.php">Contatti</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
            <span>Il Team</span>
        </div>
        <h1>Il nostro Team</h1>
        <p class="desc">Le persone (e l'AI) che lavorano ogni giorno per rendere Matchora la migliore piattaforma per tornei.</p>
    </div>
</header>

<main class="m-page">
    <div class="m-container">

        <div class="team-grid">
            <?php foreach ($team as $i => $membro): ?>
            <div class="team-card <?= !empty($membro['is_ai']) ? 'team-card--ai' : '' ?>">

                <!-- Foto / Avatar -->
                <div class="team-card__photo-wrap">
                    <?php if (!empty($membro['foto'])): ?>
                        <img class="team-card__photo" src="<?= htmlspecialchars($membro['foto']) ?>" alt="Foto di <?= htmlspecialchars($membro['nome']) ?>">
                    <?php else: ?>
                        <div class="team-card__avatar" style="background: <?= $membro['colore'] ?>;">
                            <?php if (!empty($membro['is_ai'])): ?>
                                <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 2a5 5 0 0 1 5 5v1h1a3 3 0 0 1 0 6h-1v1a5 5 0 0 1-10 0v-1H6a3 3 0 0 1 0-6h1V7a5 5 0 0 1 5-5z"/>
                                    <circle cx="9" cy="9" r="1" fill="currentColor"/>
                                    <circle cx="15" cy="9" r="1" fill="currentColor"/>
                                </svg>
                            <?php else: ?>
                                <span><?= htmlspecialchars($membro['iniziali']) ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($membro['is_ai'])): ?>
                        <span class="team-card__ai-badge">AI</span>
                    <?php endif; ?>
                </div>

                <!-- Info -->
                <div class="team-card__body">
                    <div class="team-card__role"><?= htmlspecialchars($membro['ruolo']) ?></div>
                    <h3 class="team-card__name"><?= htmlspecialchars($membro['nome']) ?></h3>
                    <p class="team-card__bio"><?= htmlspecialchars($membro['bio']) ?></p>

                    <!-- Link -->
                    <div class="team-card__links">
                        <?php if (!empty($membro['email'])): ?>
                        <a href="mailto:<?= htmlspecialchars($membro['email']) ?>" class="team-card__link" title="Email">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            <span><?= htmlspecialchars($membro['email']) ?></span>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($membro['linkedin']) && $membro['linkedin'] !== '#'): ?>
                        <a href="<?= htmlspecialchars($membro['linkedin']) ?>" target="_blank" rel="noopener" class="team-card__link" title="<?= !empty($membro['is_ai']) ? 'Sito' : 'LinkedIn' ?>">
                            <?php if (!empty($membro['is_ai'])): ?>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                            <?php else: ?>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
                            <?php endif; ?>
                            <span><?= htmlspecialchars($membro['linkedin_label'] ?? 'LinkedIn') ?></span>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
            <?php endforeach; ?>
        </div>

        <!-- CTA contatti -->
        <div class="team-cta">
            <p>Vuoi contattare direttamente il team?</p>
            <a href="contatti.php" class="m-btn m-btn--primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                Scrivici un messaggio
            </a>
        </div>

    </div>
</main>

<style>
.team-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: var(--m-5);
    margin-bottom: var(--m-10);
}
.team-card {
    background: var(--m-surface);
    border: 1px solid var(--m-border);
    border-radius: var(--m-r-lg);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: transform var(--m-t), box-shadow var(--m-t), border-color var(--m-t);
    box-shadow: var(--m-sh-1);
}
.team-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--m-sh-3);
    border-color: var(--m-primary-200);
}
.team-card--ai {
    border-color: #e8d8b0;
    background: linear-gradient(160deg, #fffdf5 0%, var(--m-surface) 60%);
}
.team-card--ai:hover {
    border-color: var(--m-gold-400);
    box-shadow: 0 8px 28px rgba(243, 156, 18, 0.18);
}
.team-card__photo-wrap {
    position: relative;
    height: 180px;
    background: var(--m-bg-soft);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.team-card--ai .team-card__photo-wrap {
    background: linear-gradient(135deg, #fff8e8 0%, #fdefc8 100%);
}
.team-card__photo {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: top center;
}
.team-card__avatar {
    width: 88px;
    height: 88px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-family: var(--m-font-display);
    font-size: 30px;
    font-weight: 700;
    letter-spacing: -0.02em;
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    flex-shrink: 0;
}
.team-card__ai-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: var(--m-gold-500);
    color: #2a1d00;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.1em;
    padding: 3px 8px;
    border-radius: var(--m-r-full);
    font-family: var(--m-font-display);
}
.team-card__body {
    padding: var(--m-5);
    display: flex;
    flex-direction: column;
    flex: 1;
}
.team-card__role {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--m-primary-600);
    margin-bottom: 4px;
}
.team-card--ai .team-card__role {
    color: var(--m-gold-600);
}
.team-card__name {
    font-size: 18px;
    font-weight: 700;
    margin: 0 0 var(--m-3);
    letter-spacing: -0.01em;
    color: var(--m-text);
}
.team-card__bio {
    font-size: 13.5px;
    color: var(--m-text-soft);
    line-height: 1.55;
    margin: 0 0 auto;
    padding-bottom: var(--m-4);
}
.team-card__links {
    display: flex;
    flex-direction: column;
    gap: 6px;
    border-top: 1px solid var(--m-border);
    padding-top: var(--m-3);
    margin-top: var(--m-3);
}
.team-card__link {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 13px;
    font-weight: 500;
    color: var(--m-text-mute);
    text-decoration: none;
    transition: color var(--m-t);
    overflow: hidden;
}
.team-card__link span {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.team-card__link:hover { color: var(--m-primary-600); }
.team-card--ai .team-card__link:hover { color: var(--m-gold-600); }
.team-cta {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: var(--m-4);
    padding: var(--m-10) 0;
    text-align: center;
}
.team-cta p {
    font-size: 16px;
    color: var(--m-text-soft);
    margin: 0;
}
@media (max-width: 720px) {
    .team-grid { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 480px) {
    .team-grid { grid-template-columns: 1fr; }
}
</style>

<?php require_once('templates/footer.php'); ?>