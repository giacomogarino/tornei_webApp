<?php
require_once('templates/header.php');

$team = [
    [
        'nome'     => 'Giacomo Garino',
        'ruolo'    => 'Programmatore',
        'bio'      => 'Studente di 5° anno all\'ITIS Mario Delpozzo di Cuneo. 18 anni, appassionato di sviluppo software.',
        'email'    => 'giacomo.garino@itiscuneo.edu.it',
        'instagram'=> 'https://instagram.com/giacomo.garino_',
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
        'instagram'=> 'https://instagram.com/_luca.bertolotti_',
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
        'instagram'=> 'https://instagram.com/matteo_luciano56',
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
        'instagram'=> 'https://instagram.com/l1l_l14m',
        'foto'     => 'assets/team/liam.webp',
        'foto_position' => 'center 10%',
        'iniziali' => 'LT',
        'colore'   => 'var(--m-gold-600)',
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
        <p class="desc">Le persone che lavorano ogni giorno per rendere Matchora la migliore piattaforma per tornei.</p>
    </div>
</header>

<main class="m-page">
    <div class="m-container">

        <div class="team-grid">
            <?php foreach ($team as $i => $membro): ?>
            <div class="team-card">

                <!-- Foto / Avatar -->
                <div class="team-card__photo-wrap">
                    <?php if (!empty($membro['foto'])): ?>
                        <img
                            class="team-card__photo"
                            src="<?= htmlspecialchars($membro['foto']) ?>"
                            alt="Foto di <?= htmlspecialchars($membro['nome']) ?>"
                            style="object-position: <?= htmlspecialchars($membro['foto_position'] ?? 'center center') ?>;"
                        >
                    <?php else: ?>
                        <div class="team-card__avatar" style="background: <?= $membro['colore'] ?>;">
                            <span><?= htmlspecialchars($membro['iniziali']) ?></span>
                        </div>
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
                        <?php if (!empty($membro['instagram'])): ?>
                        <a href="<?= htmlspecialchars($membro['instagram']) ?>" target="_blank" rel="noopener" class="team-card__link team-card__link--instagram" title="Instagram">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                            <span>Instagram</span>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($membro['linkedin']) && $membro['linkedin'] !== '#'): ?>
                        <a href="<?= htmlspecialchars($membro['linkedin']) ?>" target="_blank" rel="noopener" class="team-card__link" title="LinkedIn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
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
.team-card__photo-wrap {
    position: relative;
    height: 220px;
    background: var(--m-bg-soft);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.team-card__photo {
    width: 100%;
    height: 100%;
    object-fit: cover;
    /* object-position viene applicato inline per rispettare il valore per-membro */
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
.team-card__link--instagram:hover { color: #e1306c; }
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