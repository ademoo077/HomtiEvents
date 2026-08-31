<?php
/** @var array $upcoming @var array $stats @var array $faq @var array $testimonials @var array $partners @var array $albums @var array $anomalies @var array $beforeAfter @var array $gallery @var array $mapEvents @var int $totalParticipants */
use App\Helpers\Database;
use App\Helpers\I18n;

$title = '';
$pick = static fn(string $fr, string $ar) => I18n::pick($fr, $ar);
$isAr = I18n::direction() === 'rtl';
$ordre = settings('sections_order', ['actualites', 'apropos', 'fonctionnement', 'anomalies', 'albums', 'temoignages', 'partenaires', 'galerie', 'faq']);
$ordre = is_array($ordre) ? $ordre : ['actualites', 'apropos', 'fonctionnement', 'anomalies', 'albums', 'temoignages', 'partenaires', 'galerie', 'faq'];
$albumsInOrder = in_array('albums', $ordre, true);
$visible = static fn(string $section): bool => (string) settings('section_' . $section . '_visible', '1') === '1';

$heroTitre = $pick((string) settings('hero_titre_fr', ''), (string) settings('hero_titre_ar', ''));
$heroSub   = $pick((string) settings('hero_sous_titre_fr', ''), (string) settings('hero_sous_titre_ar', ''));
$timelineEvents = settings('timeline_events', []);
if (!is_array($timelineEvents)) $timelineEvents = [];

// Configuration carte (CMS)
$mapCfg = [
    'visible'  => (string) settings('map_visible', '1') === '1',
    'style'    => (string) settings('map_style', 'light') === 'dark' ? 'dark' : 'light',
    'heatmap'  => (string) settings('map_heatmap', '1') === '1',
    'zoom'     => (int) settings('map_zoom', 0),
    'lat'      => (float) settings('map_center_lat', 0),
    'lng'      => (float) settings('map_center_lng', 0),
];

// Fonction de sécurité pour le badge association (si elle n'existe pas déjà)
if (!function_exists('association_badge')) {
    function association_badge($assoc) {
        if (empty($assoc) || !is_array($assoc)) return '';
        $nom = htmlspecialchars($assoc['nom'] ?? 'Association', ENT_QUOTES, 'UTF-8');
        $valide = isset($assoc['valide']) && (int)$assoc['valide'] === 1;
        $agrement = isset($assoc['numero_agrement']) ? htmlspecialchars($assoc['numero_agrement'], ENT_QUOTES, 'UTF-8') : '';
        $class = $valide ? 'badge-association-agreer' : 'badge-association-en-attente';
        $label = $valide ? __('common.association_agreer') : __('common.association_en_attente');
        $icon = $valide ? 'mdi-shield-check' : 'mdi-clock-outline';
        return "<span class=\"$class\" title=\"$nom\"><i class=\"mdi $icon\"></i> $label</span>";
    }
}

// Section "Album photos & détails des événements" (remplace l'ancien avant/après).
// Alimentée par les albums publiés (données réelles de la base : événements,
// commune, adresse, date, association, récit, photos).
$renderAlbumEventSection = static function (bool $alt = false) use ($albums, $anomalies, $isAr): void {
    ?>
    <section class="section section-albums<?= $alt ? ' bg-muted' : '' ?>" id="albums">
        <div class="container">
            <div class="section-head" data-reveal>
                <span class="eyebrow"><i class="mdi mdi-camera-iris"></i><?= $isAr ? 'ألبوم الصور وتفاصيل الأحداث' : 'Album photos & détails des événements' ?></span>
                <h2 class="section-title"><?= $isAr ? 'أحداث الولاية في صور' : 'Nos événements en images' ?></h2>
                <p class="section-lead"><?= $isAr ? 'تصفح الألبومات واكتشف تفاصيل كل حدث منظم عبر الولاية.' : 'Parcourez les albums et découvrez les détails de chaque événement organisé dans la wilaya.' ?></p>
            </div>
            <div class="album-filters-row" data-reveal data-reveal-delay="100">
                <button type="button" class="filter-btn active" data-filter="all"><?= $isAr ? 'الكل' : 'Tous' ?></button>
                <?php foreach($anomalies as $an): ?>
                    <button type="button" class="filter-btn" data-filter="anomaly-<?= (int)$an['id'] ?>"><?= e($an['icone']) ? e($an['icone']).' ' : '' ?><?= e($an['nom']) ?></button>
                <?php endforeach; ?>
            </div>
            <div class="albums-grid" id="albumsGrid">
                <?php if (!empty($albums)): ?>
                    <?php foreach($albums as $al): ?>
                        <?php
                            $albumAnomalies = $al['anomalies'] ?? [];
                            $assoc = $al['association'] ?? null;
                        ?>
                        <article class="album-card" data-reveal data-album-id="<?= (int)$al['id'] ?>" data-anomalies="<?= implode(',', array_column($albumAnomalies, 'id')) ?>">
                            <button type="button" class="album-cover album-open" onclick="openAlbumLightbox(<?= (int)$al['id'] ?>)" aria-label="<?= e($al['titre']) ?>">
                                <?php if(!empty($al['display_image'])): ?>
                                    <img src="<?= asset((string)$al['display_image']) ?>" alt="<?= e($al['titre']) ?>" loading="lazy">
                                <?php elseif(!empty($al['couverture'])): ?>
                                    <img src="<?= asset((string)$al['couverture']) ?>" alt="<?= e($al['titre']) ?>" loading="lazy">
                                <?php else: ?>
                                    <div class="placeholder-cover"><i class="mdi mdi-image-multiple"></i></div>
                                <?php endif; ?>
                                <span class="album-count"><i class="mdi mdi-image"></i> <?= (int)($al['nb_photos_count'] ?? 0) ?> <?= e(__('landing.albums_photos')) ?></span>
                            </button>
                            <div class="album-body">
                                <h3><?= e($al['titre']) ?></h3>
                                <?php if($assoc): ?><div class="album-assoc"><?= association_badge($assoc) ?></div><?php endif; ?>
                                <div class="album-event-details">
                                    <?php if(!empty($al['date_evenement'])): ?>
                                        <span class="album-ev-date"><i class="mdi mdi-calendar-outline"></i><?= e(date('d/m/Y', strtotime((string)$al['date_evenement']))) ?></span>
                                    <?php endif; ?>
                                    <?php if(!empty($al['commune_nom'])): ?>
                                        <span class="album-ev-lieu"><i class="mdi mdi-map-marker-outline"></i><?= e($al['commune_nom']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if(!empty($al['adresse'])): ?>
                                    <p class="album-adresse"><i class="mdi mdi-office-building-marker-outline"></i><?= e($al['adresse']) ?></p>
                                <?php endif; ?>
                                <?php if(!empty($al['recit'])): ?>
                                    <blockquote class="album-recit">"<?= e(mb_substr((string)$al['recit'],0,140)) ?>…"</blockquote>
                                <?php endif; ?>
                                <div class="album-actions">
                                    <button type="button" class="btn btn-outline btn-sm" onclick="openAlbumLightbox(<?= (int)$al['id'] ?>)"><i class="mdi mdi-image-multiple-outline"></i><?= $isAr ? 'عرض الألبوم' : 'Voir l\'album' ?></button>
                                    <?php if(!empty($al['evenement_id'])): ?>
                                        <a class="btn btn-primary btn-sm" href="<?= url('evenement/' . (int)$al['evenement_id']) ?>"><i class="mdi mdi-calendar-check-outline"></i><?= $isAr ? 'تفاصيل الحدث' : 'Détails de l\'événement' ?></a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="albums-placeholder"><i class="mdi mdi-camera mdi-48px"></i><p><?= $isAr ? 'لا توجد ألبومات حالياً.' : 'Aucun album disponible.' ?></p></div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php
};
?>
<!DOCTYPE html>
<html lang="<?= $isAr ? 'ar' : 'fr' ?>" dir="<?= I18n::direction() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($heroTitre ?: __('app.name')) ?></title>
    <link rel="manifest" href="<?= url('manifest.json') ?>">
    <meta name="theme-color" content="#0F2B22">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="حومتي ايفانت">
    <link rel="icon" href="<?= url('/favicon.ico') ?>">
    <link rel="icon" type="image/svg+xml" href="<?= url('/favicon.svg') ?>">
    <link rel="apple-touch-icon" href="<?= url('/apple-touch-icon.png') ?>">
    <meta name="description" content="<?= e($heroSub ?: __('app.tagline')) ?>">
    <meta property="og:title" content="<?= e($heroTitre ?: __('app.name')) ?>">
    <meta property="og:description" content="<?= e($heroSub ?: __('app.tagline')) ?>">
    <meta property="og:image" content="<?= e(asset('/assets/img/icon-192.png')) ?>">
    <meta property="og:locale" content="<?= $isAr ? 'ar_DZ' : 'fr_DZ' ?>">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="preconnect" href="https://basemaps.cartocdn.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7/css/materialdesignicons.min.css">

    <style>
        /* ═══ THÈME CLAIR NATURE ═══ */
        :root {
            --bg-primary: #fcf9f2;
            --bg-secondary: #f0f7f0;
            --bg-muted: #e8f0e8;
            --text-primary: #1b2a1f;
            --text-secondary: #3d5a47;
            --text-muted: #5a7a64;
            --accent: #2a7a3e;
            --accent-soft: #4caf6e;
            --accent-light: #d4edda;
            --glass-bg: rgba(255,255,255,0.85);
            --glass-border: rgba(42,122,62,0.15);
            --shadow: 0 8px 30px rgba(0,20,10,0.08);
            --radius: 1.25rem;
            --font: 'Inter', system-ui, sans-serif;
        }
        * { box-sizing: border-box; margin:0; padding:0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: var(--font);
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
        }
        a { color: var(--accent); text-decoration: none; }
        .container { max-width: 1280px; margin: 0 auto; padding: 0 1.5rem; }
        .text-muted { color: var(--text-muted); }
        .text-center { text-align: center; }
        .py-4 { padding-block: 1.5rem; }
        .mt-1 { margin-top: 0.5rem; }
        .mt-4 { margin-top: 2rem; }
        .bg-muted { background: var(--bg-secondary); }
        .bg-muted .section-title { color: var(--text-primary); }

        /* Boutons */
        .btn {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.7rem 1.8rem; border-radius: 999px;
            font-weight: 600; font-size: 1rem;
            transition: all 0.3s ease; border: 2px solid transparent; cursor: pointer;
        }
        .btn-primary { background: var(--accent); border-color: var(--accent); color: #fff; }
        .btn-primary:hover { background: #1f5e2e; transform: scale(1.02); }
        .btn-outline { border-color: var(--accent); color: var(--accent); background: transparent; }
        .btn-outline:hover { background: var(--accent); color: #fff; }
        .btn-lg { padding: 0.9rem 2.2rem; font-size: 1.1rem; }

        /* Sections */
        .section { padding: 5rem 0; }
        .section-head {
            text-align: center;
            max-width: 720px;
            margin: 0 auto 3rem;
        }
        .eyebrow {
            display: inline-flex; align-items: center; gap: 0.5rem;
            font-size: 0.8rem; font-weight: 700; letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--accent);
            background: var(--accent-light);
            padding: 0.3rem 1rem; border-radius: 999px;
            margin-bottom: 0.75rem;
        }
        .section-title {
            font-size: 2.5rem; font-weight: 800; line-height: 1.2;
            margin-bottom: 0.75rem; color: var(--text-primary);
        }
        .section-lead { font-size: 1.1rem; color: var(--text-secondary); }

        /* ═══ HERO AVEC VIDÉO ═══ */
        .hero {
            position: relative; min-height: 100vh;
            display: flex; align-items: center; overflow: hidden;
            padding: 4rem 0 2rem;
            background: #1b2a1f;
        }
        .hero-video {
            position: absolute; top:0; left:0; width:100%; height:100%;
            background-size: cover; background-position: center; background-repeat: no-repeat;
            z-index:0; opacity:0.5;
        }
        .hero-video-fallback {
            position: absolute; top:0; left:0; width:100%; height:100%;
            z-index:0;
            background: linear-gradient(135deg, #1b2a1f, #2a4a3a, #0d9488);
            opacity:0.35;
        }
        .hero-aurora {
            position: absolute; top:0; left:0; width:100%; height:100%;
            z-index:1; pointer-events:none; opacity:0.3;
        }
        .hero-particles {
            position: absolute; inset:0; z-index:1; overflow:hidden;
        }
        .hero-particles i {
            position: absolute;
            width: 6px; height: 6px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            animation: float-particle 20s infinite alternate;
        }
        @keyframes float-particle {
            0% { transform: translate(0,0) scale(0.5); opacity:0; }
            100% { transform: translate(80px, -150px) scale(1.8); opacity:1; }
        }
        .hero-inner {
            position: relative; z-index:2;
            display: grid; grid-template-columns: 1fr 1fr; gap: 3rem;
            align-items: center; padding-top: 2rem;
        }
        .hero-content { max-width: 640px; }
        .hero-badge {
            display: inline-flex; align-items: center; gap:0.5rem;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(8px);
            padding: 0.3rem 1rem; border-radius: 999px;
            font-size:0.8rem; border:1px solid rgba(255,255,255,0.2);
            color: #fff;
            margin-bottom:1rem;
        }
        .hero-title {
            font-size: 3.8rem; font-weight: 900; line-height:1.1;
            margin-bottom:1rem; color: #fff;
            text-shadow: 0 4px 30px rgba(0,0,0,0.3);
        }
        .hero-title-accent {
            display:block;
            background: linear-gradient(135deg, #0d9488, #4caf6e, #eab308);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero-sub {
            font-size:1.25rem; color: rgba(255,255,255,0.85);
            margin-bottom:2rem; max-width:480px;
            text-shadow: 0 2px 20px rgba(0,0,0,0.2);
        }
        .hero-actions { display:flex; flex-wrap:wrap; gap:1rem; margin-bottom:2.5rem; }
        .hero-actions .btn-primary { background: rgba(42,122,62,0.9); border-color: rgba(42,122,62,0.9); }
        .hero-actions .btn-primary:hover { background: #1f5e2e; }
        .hero-actions .btn-outline { border-color: rgba(255,255,255,0.4); color: #fff; }
        .hero-actions .btn-outline:hover { background: rgba(255,255,255,0.15); border-color: #fff; }
        .hero-trust {
            display:flex; align-items:center; gap:1rem;
            color: rgba(255,255,255,0.8);
        }
        .trust-avatars { display:flex; }
        .t-avatar {
            width:2.4rem; height:2.4rem; border-radius:50%;
            border:2px solid rgba(255,255,255,0.3);
            background: rgba(255,255,255,0.15);
            color:#fff;
            display:grid; place-items:center; font-weight:700; font-size:0.8rem;
            margin-right:-0.5rem;
            backdrop-filter: blur(4px);
        }
        .hero-visual { display:flex; justify-content:center; align-items:center; min-height:400px; }
        #globe-container { width:100%; height:450px; border-radius:var(--radius); position:relative; overflow:hidden; }

        /* Illustration hero */
        .hero-illustration {
            width: 100%; height: 100%;
            display: flex; align-items: center; justify-content: center;
            position: relative;
        }
        .illustration-circle {
            width: 280px; height: 280px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0.05) 100%);
            border: 2px solid rgba(255,255,255,0.15);
            position: absolute;
            animation: pulse-glow 3s ease-in-out infinite;
        }
        .illustration-ring {
            width: 320px; height: 320px;
            border-radius: 50%;
            border: 1px dashed rgba(255,255,255,0.2);
            position: absolute;
            animation: spin-slow 20s linear infinite;
        }
        .illustration-icon {
            width: 120px; height: 120px;
            border-radius: 50%;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            display: flex; align-items: center; justify-content: center;
            font-size: 3.5rem; color: #fff;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            position: relative; z-index: 2;
        }
        .illustration-particles {
            position: absolute; width: 100%; height: 100%;
        }
        .particle {
            position: absolute; font-size: 1.5rem;
            animation: float-particle 4s ease-in-out infinite;
        }
        .particle.p1 { top: 20%; left: 15%; animation-delay: 0s; }
        .particle.p2 { top: 15%; right: 20%; animation-delay: 0.8s; }
        .particle.p3 { bottom: 25%; left: 10%; animation-delay: 1.6s; }
        .particle.p4 { bottom: 20%; right: 15%; animation-delay: 2.4s; }
        .particle.p5 { top: 50%; left: 5%; animation-delay: 3.2s; }

        @keyframes pulse-glow {
            0%, 100% { transform: scale(1); opacity: 0.8; }
            50% { transform: scale(1.05); opacity: 1; }
        }
        @keyframes spin-slow {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        @keyframes float-particle {
            0%, 100% { transform: translateY(0) rotate(0deg); opacity: 0.7; }
            50% { transform: translateY(-15px) rotate(10deg); opacity: 1; }
        }

        .hero-stats {
            display:grid; grid-template-columns:repeat(auto-fit, minmax(150px,1fr));
            gap:2rem; padding:2rem 0;
            border-top:1px solid rgba(255,255,255,0.15);
            margin-top:2rem;
            position:relative; z-index:2;
        }
        .hero-stat { text-align:center; display:flex; flex-direction:column; }
        .hero-stat strong { font-size:2rem; font-weight:800; color:#fff; text-shadow: 0 2px 20px rgba(0,0,0,0.2); }
        .stat-ico { font-size:1.8rem; margin-bottom:0.25rem; color:rgba(255,255,255,0.7); }
        .stat-label { font-size:0.85rem; color:rgba(255,255,255,0.6); }

        /* Cartes */
        .cards-grid {
            display:grid; grid-template-columns:repeat(auto-fill, minmax(280px,1fr));
            gap:1.5rem;
        }
        .card {
            background: #fff;
            border-radius: var(--radius);
            padding:1.5rem;
            box-shadow: var(--shadow);
            border:1px solid var(--glass-border);
            transition: all 0.3s ease;
        }
        .card:hover { transform: translateY(-6px); box-shadow: 0 12px 40px rgba(42,122,62,0.1); }
        .card.empty { text-align:center; color:var(--text-muted); padding:3rem; }
        .event-card .date-badge {
            display:flex; flex-direction:column; align-items:center;
            background: var(--accent); border-radius:12px;
            padding:0.2rem 0.6rem; color:#fff; font-weight:700; line-height:1.2;
        }
        .event-card .day { font-size:1.6rem; }
        .event-card .month { font-size:0.7rem; text-transform:uppercase; }
        .event-meta { font-size:0.85rem; display:flex; gap:1rem; flex-wrap:wrap; color:var(--text-secondary); }
        .event-voir { margin-top:1rem; display:inline-flex; align-items:center; gap:0.3rem; font-weight:600; color:var(--accent); }
        .status { padding:0.15rem 0.8rem; border-radius:999px; font-size:0.7rem; font-weight:700; }
        .status.programme { background: #d4edda; color:#155724; }

        /* Services */
        .services-grid {
            display:grid; grid-template-columns:repeat(auto-fill, minmax(180px,1fr));
            gap:1.5rem;
        }
        .service-card {
            background:#fff; border-radius:var(--radius); padding:1.5rem; text-align:center;
            box-shadow:var(--shadow); border:1px solid var(--glass-border);
            transition:0.3s;
        }
        .service-card:hover { transform:translateY(-4px); }
        .service-ico { font-size:2.5rem; display:block; margin-bottom:0.5rem; color:var(--accent); }
        .chip-count { font-size:0.7rem; background:var(--accent-light); padding:0.2rem 0.8rem; border-radius:999px; color:var(--accent); }

        /* ═══ ALBUMS AMÉLIORÉS ═══ */
        .album-filters-row {
            display:flex; flex-wrap:wrap; gap:0.5rem;
            margin-bottom:2rem; justify-content:center;
        }
        .filter-btn {
            border:1px solid var(--glass-border);
            background:#fff; color:var(--text-secondary);
            padding:0.4rem 1.2rem; border-radius:999px;
            font-weight:600; cursor:pointer; transition:0.2s;
        }
        .filter-btn.active, .filter-btn:hover {
            background:var(--accent); color:#fff; border-color:var(--accent);
        }
        .albums-grid {
            display:grid; grid-template-columns:repeat(auto-fill, minmax(280px,1fr));
            gap:1.5rem;
        }
        .album-card {
            background:#fff; border-radius:var(--radius); overflow:hidden;
            box-shadow:var(--shadow); border:1px solid var(--glass-border);
            break-inside:avoid;
            transition:0.3s;
        }
        .album-card:hover { transform:translateY(-4px); box-shadow:0 12px 30px rgba(0,0,0,0.1); }
        .album-cover {
            width:100%; aspect-ratio:16/10; overflow:hidden;
            display:grid; place-items:center; position:relative;
            background:var(--bg-secondary); border:0; padding:0; cursor:pointer;
        }
        .album-cover img { width:100%; height:100%; object-fit:cover; transition:0.4s; }
        .album-card:hover .album-cover img { transform:scale(1.03); }
        .album-count {
            position:absolute; bottom:0.5rem; right:0.5rem;
            background:rgba(0,0,0,0.7); color:#fff;
            padding:0.2rem 0.8rem; border-radius:999px;
            font-size:0.7rem; backdrop-filter:blur(4px);
        }
        .album-body { padding:1.2rem; }
        .album-body h3 { font-size:1.2rem; margin-bottom:0.3rem; }
        .album-assoc { margin-top:0.3rem; }
        .album-meta { font-size:0.85rem; color:var(--text-secondary); display:flex; flex-wrap:wrap; gap:0.5rem; align-items:center; }
        .album-date { margin-left:auto; }
        .album-recit {
            font-style:italic; color:var(--text-secondary);
            border-left:3px solid var(--accent); padding-left:0.8rem;
            margin:0.5rem 0; font-size:0.9rem;
        }
        .album-voir {
            display:inline-flex; align-items:center; gap:0.3rem;
            font-weight:600; color:var(--accent); margin-top:0.5rem;
        }
        .placeholder-cover {
            display:flex; align-items:center; justify-content:center;
            width:100%; height:100%; background:var(--bg-secondary);
            color:var(--text-muted); font-size:3rem;
        }
        .albums-placeholder {
            grid-column:1/-1; text-align:center; padding:3rem;
            color:var(--text-muted);
        }

        /* Lightbox */
        .wh-lightbox {
            position:fixed; inset:0; z-index:9999;
            display:flex; align-items:center; justify-content:center;
            padding:1rem; opacity:0; visibility:hidden; transition:0.3s;
        }
        .wh-lightbox.open { opacity:1; visibility:visible; }
        .wh-lightbox-backdrop {
            position:absolute; inset:0;
            background:rgba(0,0,0,0.8); backdrop-filter:blur(8px);
        }
        .wh-lightbox-panel {
            position:relative; max-width:1080px; width:100%; max-height:94vh;
            background:#fff; border-radius:var(--radius); overflow:hidden;
            transform:scale(0.95); transition:0.3s;
            display:flex; flex-direction:column;
        }
        .wh-lightbox.open .wh-lightbox-panel { transform:scale(1); }
        .wh-lightbox-close {
            position:absolute; top:0.8rem; right:0.8rem; z-index:5;
            background:rgba(0,0,0,0.4); border:none; border-radius:50%;
            color:#fff; width:40px; height:40px; font-size:1.5rem; cursor:pointer;
            transition:0.3s;
        }
        .wh-lightbox-close:hover { background:rgba(200,0,0,0.6); transform:rotate(90deg); }
        .wh-lightbox-stage {
            display:flex; align-items:center; justify-content:center;
            background:#111; min-height:300px; position:relative;
            flex:1;
        }
        .wh-lightbox-img {
            max-width:100%; max-height:66vh; object-fit:contain;
        }
        .wh-lightbox-nav {
            position:absolute; top:50%; transform:translateY(-50%);
            background:rgba(255,255,255,0.2); backdrop-filter:blur(4px);
            border:none; width:46px; height:46px; border-radius:50%;
            font-size:1.8rem; cursor:pointer; z-index:3;
            color:#fff; transition:0.3s;
            display:grid; place-items:center;
        }
        .wh-lightbox-nav:hover { background:rgba(255,255,255,0.4); }
        .wh-lightbox-nav.prev { left:0.5rem; }
        .wh-lightbox-nav.next { right:0.5rem; }
        .wh-lightbox-counter {
            position:absolute; bottom:0.8rem; left:1rem;
            background:rgba(0,0,0,0.6); padding:0.2rem 0.8rem;
            border-radius:999px; font-size:0.8rem; z-index:3;
            color:#fff;
        }
        .wh-lightbox-caption {
            position:absolute; bottom:0.8rem; right:1rem;
            background:rgba(0,0,0,0.6); padding:0.2rem 0.8rem;
            border-radius:999px; font-size:0.8rem; z-index:3;
            max-width:60%; color:#fff;
        }
        .wh-lightbox-narrative {
            padding:1.2rem;
            border-top:1px solid var(--glass-border);
            background:var(--bg-secondary);
            color:var(--text-primary);
        }

        /* Autres sections (inchangées) */
        .testimonial .stars { color:#eab308; font-size:1.2rem; letter-spacing:2px; margin-bottom:0.5rem; }
        .testimonial-author { margin-top:0.5rem; font-size:0.9rem; color:var(--text-secondary); }
        .partners { display:flex; flex-wrap:wrap; gap:1.5rem; justify-content:center; }
        .partner-card {
            background:#fff; border-radius:var(--radius); padding:1rem 2rem;
            display:flex; align-items:center; gap:0.5rem;
            box-shadow:var(--shadow); border:1px solid var(--glass-border);
            transition:0.3s; color:var(--text-primary);
        }
        .partner-card:hover { border-color:var(--accent); transform:translateY(-4px); }
        .faq-item {
            background:#fff; border-radius:var(--radius);
            margin-bottom:0.75rem; padding:0.5rem 1.5rem;
            box-shadow:var(--shadow); border:1px solid var(--glass-border);
        }
        .faq-item summary { font-weight:700; padding:0.75rem 0; cursor:pointer; list-style:none; display:flex; justify-content:space-between; }
        .faq-item summary::after { content:'+'; font-size:1.5rem; color:var(--accent); }
        .faq-item[open] summary::after { content:'−'; }
        .faq-item p { padding-bottom:1.2rem; color:var(--text-secondary); }
        .timeline {
            display:flex; flex-direction:column; gap:0;
            padding-left:2rem; border-left:4px solid var(--accent);
            max-width:800px; margin:0 auto;
        }
        .timeline-item {
            padding:1.5rem 2rem; position:relative;
            background:#fff; border-radius:var(--radius);
            box-shadow:var(--shadow); border:1px solid var(--glass-border);
            margin-bottom:1.5rem;
        }
        .timeline-item::before {
            content:''; position:absolute; left:-2.7rem; top:2rem;
            width:1rem; height:1rem; background:var(--accent); border-radius:50%;
            border:3px solid #fff;
        }
        .timeline-date { font-weight:700; color:var(--accent); }
        .timeline-title { font-size:1.3rem; font-weight:700; }
        .before-after-grid {
            display:grid; grid-template-columns:repeat(auto-fill, minmax(300px,1fr));
            gap:2rem;
        }
        .ba-card {
            background:#fff; border-radius:var(--radius); overflow:hidden;
            box-shadow:var(--shadow); border:1px solid var(--glass-border);
        }
        .ba-slider { position:relative; width:100%; aspect-ratio:16/9; overflow:hidden; }
        .ba-slider img { position:absolute; top:0; left:0; width:100%; height:100%; object-fit:cover; }
        .ba-slider input[type="range"] {
            position:absolute; top:0; left:0; width:100%; height:100%;
            z-index:3; opacity:0; cursor:ew-resize;
        }
        .ba-handle {
            position:absolute; top:0; left:50%; width:4px; height:100%;
            background:#fff; z-index:2; transform:translateX(-50%);
            pointer-events:none; box-shadow:0 0 16px rgba(0,0,0,0.3);
        }
        .ba-label {
            position:absolute; bottom:0.8rem;
            background:rgba(255,255,255,0.85); padding:0.2rem 0.8rem;
            border-radius:999px; font-weight:700; font-size:0.7rem; z-index:2;
            color:var(--text-primary);
        }
        .ba-before { left:0.8rem; }
        .ba-after { right:0.8rem; }
        .ba-content { padding:1.2rem; }
        .ba-desc { color:var(--text-secondary); }
        .landing-map { width:100%; height:500px; border-radius:var(--radius); overflow:hidden; border:1px solid var(--glass-border); background:#fff; }
        .landing-map .map-fallback { height:100%; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:.5rem; padding:2rem; text-align:center; color:var(--text-secondary); background:linear-gradient(180deg,#f0f7ef,#e6f2e4); }
        .landing-map .map-fallback i { font-size:2.6rem; color:#2a7a3e; }
        .landing-map .map-fallback strong { color:var(--text-primary); font-size:1.1rem; }
        .landing-map .map-tile-note { position:absolute; top:12px; left:50%; transform:translateX(-50%); display:flex; align-items:center; gap:.4rem; padding:.5rem .9rem; border-radius:99px; font-size:.8rem; background:rgba(255,255,255,.92); color:#b45309; border:1px solid rgba(217,119,6,.35); box-shadow:0 8px 24px rgba(0,0,0,.12); z-index:500; max-width:90%; }
        [dir="rtl"] .landing-map .map-tile-note { left:auto; right:50%; transform:translateX(50%); }
        .gallery-grid { column-count:3; column-gap:1rem; }
        .gallery-item {
            display:block; break-inside:avoid; margin-bottom:1rem;
            border-radius:var(--radius); overflow:hidden; position:relative;
            background:#fff; border:1px solid var(--glass-border);
        }
        .gallery-item img { width:100%; display:block; transition:0.4s; }
        .gallery-item:hover img { transform:scale(1.03); }
        .gallery-overlay {
            position:absolute; inset:0;
            background:linear-gradient(to top, rgba(0,0,0,0.6), transparent);
            display:flex; flex-direction:column; justify-content:flex-end;
            padding:1rem; opacity:0; transition:0.3s;
        }
        .gallery-item:hover .gallery-overlay { opacity:1; }
        .gallery-title { color:#fff; font-weight:700; }
        .gallery-type { font-size:0.7rem; background:rgba(0,0,0,0.5); padding:0.1rem 0.6rem; border-radius:999px; align-self:flex-start; color:#fff; }

        /* ════════════════ IA ASSISTANT — PREMIUM ════════════════ */
        #ia-assistant {
            position:fixed; bottom:1.5rem; right:1.5rem; z-index:8000;
        }
        [dir="rtl"] #ia-assistant { right:auto; left:1.5rem; }
        .ia-toggle {
            width:60px; height:60px; border-radius:50%;
            background:linear-gradient(135deg,#E7C866,#D4AF37);
            color:#14392E; border:none; cursor:pointer;
            box-shadow:0 8px 32px rgba(212,175,55,0.45);
            display:grid; place-items:center;
            position:relative; transition:transform .3s, box-shadow .3s;
        }
        .ia-toggle:hover { transform:scale(1.08); box-shadow:0 12px 40px rgba(212,175,55,0.6); }
        .ia-toggle-icon { font-size:1.75rem; display:grid; place-items:center; z-index:1; }
        .ia-toggle-pulse {
            position:absolute; inset:-6px; border-radius:50%;
            border:3px solid rgba(212,175,55,0.5);
            animation:iaPulse 2s ease-out infinite;
        }
        @keyframes iaPulse {
            0%   { transform:scale(1); opacity:1; }
            100% { transform:scale(1.5); opacity:0; }
        }
        .ia-window {
            position:absolute; bottom:72px; right:0;
            width:380px; height:520px;
            background:#fff; border-radius:20px;
            box-shadow:0 16px 48px rgba(0,0,0,0.18), 0 0 0 1px rgba(0,0,0,0.04);
            display:none; flex-direction:column; overflow:hidden;
            transform-origin:bottom right;
            animation:iaOpen .3s cubic-bezier(.34,1.56,.64,1);
        }
        [dir="rtl"] .ia-window { right:auto; left:0; transform-origin:bottom left; }
        .ia-window.open { display:flex; }
        @keyframes iaOpen {
            from { opacity:0; transform:scale(.92) translateY(8px); }
            to   { opacity:1; transform:scale(1) translateY(0); }
        }
        .ia-header {
            padding:.85rem 1rem;
            background:linear-gradient(135deg,#1A4D3E,#0F2B22);
            color:#fff; display:flex; align-items:center; justify-content:space-between;
        }
        .ia-header-left { display:flex; align-items:center; gap:.65rem; }
        .ia-avatar {
            width:38px; height:38px; border-radius:50%;
            background:linear-gradient(135deg,#E7C866,#D4AF37);
            display:grid; place-items:center; font-size:1.15rem; color:#14392E;
            position:relative; flex-shrink:0;
        }
        .ia-avatar-status {
            position:absolute; bottom:0; right:0;
            width:10px; height:10px; border-radius:50%;
            background:#4ade80; border:2px solid #1A4D3E;
        }
        .ia-header-text { display:flex; flex-direction:column; line-height:1.2; }
        .ia-header-text strong { font-size:.9rem; }
        .ia-header-text small { font-size:.7rem; opacity:.7; }
        .ia-close-btn {
            background:none; border:none; color:rgba(255,255,255,.7); font-size:1.3rem;
            cursor:pointer; padding:.25rem; border-radius:8px; transition:.2s;
        }
        .ia-close-btn:hover { color:#fff; background:rgba(255,255,255,.12); }
        .ia-messages {
            flex:1; padding:1rem; overflow-y:auto;
            display:flex; flex-direction:column; gap:.6rem;
            scroll-behavior:smooth;
        }
        .ia-msg-row { display:flex; gap:.45rem; align-items:flex-end; }
        .ia-msg-row.bot { align-self:flex-start; }
        .ia-msg-row.user { align-self:flex-end; flex-direction:row-reverse; }
        .ia-msg-avatar {
            width:28px; height:28px; border-radius:50%;
            background:#1A4D3E; color:#fff; font-size:.75rem;
            display:grid; place-items:center; flex-shrink:0;
        }
        .ia-msg {
            padding:.6rem .9rem; border-radius:16px; max-width:80%;
            font-size:.88rem; line-height:1.45;
            word-wrap:break-word; white-space:pre-wrap;
        }
        .ia-msg.bot {
            background:#F0F7F0; color:#1a1a1a;
            border-bottom-left-radius:4px;
        }
        .ia-msg.user {
            background:linear-gradient(135deg,#1A4D3E,#166534); color:#fff;
            border-bottom-right-radius:4px;
        }
        .ia-quick-replies {
            display:flex; flex-wrap:wrap; gap:.4rem;
            padding:.2rem .5rem .6rem; justify-content:center;
        }
        .ia-quick-btn {
            display:inline-flex; align-items:center; gap:.3rem;
            padding:.4rem .75rem; border-radius:999px; font-size:.78rem;
            border:1px solid #d4af37; background:#fff; color:#14392E;
            cursor:pointer; font-weight:600; transition:.2s; white-space:nowrap;
        }
        .ia-quick-btn:hover { background:#D4AF37; color:#14392E; transform:translateY(-1px); }
        .ia-quick-btn i { font-size:.9rem; }
        .ia-typing {
            display:none; padding:.4rem 1rem; gap:.3rem; align-items:center;
        }
        .ia-typing.active { display:flex; }
        .ia-typing-dot {
            width:7px; height:7px; border-radius:50%;
            background:#999; animation:iaTypingBounce 1.2s ease-in-out infinite;
        }
        .ia-typing-dot:nth-child(2) { animation-delay:.15s; }
        .ia-typing-dot:nth-child(3) { animation-delay:.3s; }
        @keyframes iaTypingBounce {
            0%,60%,100% { transform:translateY(0); }
            30% { transform:translateY(-5px); }
        }
        .ia-input {
            display:flex; padding:.6rem; border-top:1px solid #eee; gap:.4rem;
        }
        .ia-input input {
            flex:1; padding:.6rem .9rem; border-radius:999px;
            border:1px solid #e2e2e2; background:#f9fafb;
            font-size:.88rem; outline:none; transition:.2s;
        }
        .ia-input input:focus { border-color:#D4AF37; box-shadow:0 0 0 2px rgba(212,175,55,.2); }
        .ia-input button[type="submit"] {
            width:40px; height:40px; border-radius:50%; border:none;
            background:linear-gradient(135deg,#E7C866,#D4AF37);
            color:#14392E; font-size:1.1rem; cursor:pointer;
            display:grid; place-items:center; transition:.2s; flex-shrink:0;
        }
        .ia-input button[type="submit"]:hover { transform:scale(1.08); }
        @media (max-width:1024px) {
            #ia-assistant { bottom:1.25rem; right:1.25rem; }
            .ia-window { width:360px; height:480px; }
        }
        @media (max-width:640px) {
            #ia-assistant { bottom:1rem; right:1rem; }
            [dir="rtl"] #ia-assistant { right:auto; left:1rem; }
            .ia-toggle { width:54px; height:54px; }
            .ia-toggle-icon { font-size:1.5rem; }
            .ia-window {
                position:fixed; inset:0; width:100%; height:100%;
                border-radius:0; bottom:0; right:0;
                animation:iaOpenMobile .3s ease-out;
            }
            [dir="rtl"] .ia-window { right:0; left:0; }
            @keyframes iaOpenMobile {
                from { opacity:0; transform:translateY(20px); }
                to   { opacity:1; transform:translateY(0); }
            }
            .ia-msg { max-width:88%; }
        }
        .sr-only { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); border:0; }
         :focus-visible { outline:3px solid var(--accent); outline-offset:2px; }

        /* Actualités & événements + prochains événements */
        .news-filters { display:flex; flex-wrap:wrap; gap:0.5rem; justify-content:center; margin-bottom:2rem; }
        .news-filter-btn {
            border:1px solid var(--glass-border); background:#fff; color:var(--text-secondary);
            padding:0.4rem 1.2rem; border-radius:999px; font-weight:600; cursor:pointer; transition:0.2s;
        }
        .news-filter-btn.active, .news-filter-btn:hover { background:var(--accent); color:#fff; border-color:var(--accent); }
        .news-grid {
            display:grid; grid-template-columns:repeat(auto-fill, minmax(280px,1fr)); gap:1.5rem;
        }
        .news-card {
            background:#fff; border-radius:var(--radius); overflow:hidden;
            box-shadow:var(--shadow); border:1px solid var(--glass-border);
        }
        .news-card-image { position:relative; }
        .news-card-image img { width:100%; display:block; aspect-ratio:16/9; object-fit:cover; }
        .news-card-placeholder {
            width:100%; aspect-ratio:16/9; background:var(--bg-secondary);
            display:grid; place-items:center; color:var(--text-muted); font-size:2.5rem;
        }
        .news-type-badge {
            position:absolute; top:0.75rem; inset-inline-start:0.75rem;
            background:rgba(0,0,0,0.55); color:#fff; padding:0.2rem 0.7rem;
            border-radius:999px; font-size:0.7rem; display:inline-flex; align-items:center; gap:0.25rem;
        }
        .news-card-body { padding:1.2rem 1.2rem 0.8rem; }
        .news-card-title { font-size:1.15rem; font-weight:700; margin:0 0 0.5rem; }
        .news-card-desc { font-size:0.9rem; color:var(--text-secondary); margin:0 0 0.7rem; }
        .news-card-link { display:inline-flex; align-items:center; gap:0.3rem; font-weight:600; }
        .news-card-link .mdi { font-size:1rem; }
        .news-empty { grid-column:1/-1; text-align:center; padding:3rem; color:var(--text-muted); }

        /* Prochains événements */
        .upcoming-section {
            margin-top:2.75rem; position:relative; overflow:hidden;
            background:linear-gradient(180deg, rgba(26,77,62,0.07), rgba(212,175,55,0.05) 60%, rgba(26,77,62,0.03));
            border:1px solid rgba(42,122,62,0.14);
            border-radius:1.25rem; padding:1.4rem 1.5rem 1.6rem;
        }
        .upcoming-section::before {
            content:''; position:absolute; top:-70px; inset-inline-end:-70px;
            width:200px; height:200px; pointer-events:none;
            background:radial-gradient(circle, rgba(212,175,55,0.20), transparent 70%);
        }
        .upcoming-title {
            display:flex; align-items:center; gap:0.65rem;
            font-size:1.2rem; font-weight:800; margin-bottom:1.4rem;
            color:var(--text-primary); letter-spacing:-0.01em; position:relative;
        }
        .upcoming-title i { color:var(--accent); font-size:1.35rem; }
        .upcoming-title::after {
            content:''; flex:1; height:1px; margin-inline-start:0.9rem;
            background:linear-gradient(to right, rgba(212,175,55,0.7), rgba(212,175,55,0.05));
        }
        .upcoming-count {
            flex:0 0 auto; display:inline-flex; align-items:center; gap:0.3rem;
            background:#fff; border:1px solid rgba(212,175,55,0.55); color:var(--accent);
            font-size:0.7rem; font-weight:800; padding:0.18rem 0.7rem; border-radius:999px;
            box-shadow:0 2px 8px rgba(212,175,55,0.15);
        }
        .upcoming-count .mdi { font-size:0.85rem; }
        .upcoming-grid {
            display:grid; grid-template-columns:repeat(auto-fill, minmax(270px,1fr)); gap:1.1rem;
            position:relative;
        }
        .upcoming-card {
            display:flex; gap:0.9rem; align-items:center;
            position:relative; overflow:hidden;
            background:linear-gradient(180deg, #ffffff, #fdfbf2);
            border-radius:1rem; padding:0.85rem 1rem;
            border:1px solid rgba(42,122,62,0.15);
            box-shadow:0 6px 18px rgba(15,43,34,0.06);
            transition:0.25s ease;
            width:100%; text-align:start; cursor:pointer; font-family:inherit; font-size:inherit;
        }
        .upcoming-card::before {
            content:''; position:absolute; inset:0 0 auto 0; height:3px;
            background:linear-gradient(90deg, var(--accent), #D4AF37, var(--accent));
            opacity:0; transition:0.25s ease;
        }
        .upcoming-card:hover {
            transform:translateY(-3px);
            box-shadow:0 16px 32px rgba(15,43,34,0.14);
            border-color:rgba(212,175,55,0.5);
        }
        .upcoming-card:hover::before { opacity:1; }
        .upcoming-card .upcoming-date {
            flex:0 0 auto; display:flex; flex-direction:column; align-items:center; justify-content:center;
            position:relative; min-width:50px; min-height:58px;
            background:linear-gradient(180deg, #2E6E5C, #1A4D3E);
            color:#fff; border-radius:0.9rem; padding:0.5rem 0.6rem;
            border:1px solid rgba(212,175,55,0.4);
            box-shadow:inset 0 0 0 1px rgba(255,255,255,0.07), 0 8px 16px rgba(15,43,34,0.28);
            transition:0.25s ease;
        }
        .upcoming-card .upcoming-date::after {
            content:''; position:absolute; top:0; inset-inline:0.5rem; height:2px;
            border-radius:999px; background:linear-gradient(90deg, transparent, #D4AF37, transparent);
        }
        .upcoming-card:hover .upcoming-date { transform:scale(1.06); }
        .upcoming-day { font-size:1.5rem; font-weight:800; line-height:1; }
        .upcoming-month { font-size:0.6rem; text-transform:uppercase; letter-spacing:0.09em; color:#E7C866; font-weight:700; margin-top:2px; }
        .upcoming-info { flex:1; min-width:0; }
        .upcoming-info h4 {
            font-size:0.95rem; font-weight:700; margin:0 0 0.3rem; color:var(--text-primary);
            display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
        }
        .upcoming-info p {
            font-size:0.78rem; color:var(--text-secondary); margin:0;
            display:flex; flex-wrap:wrap; align-items:center; gap:0.35rem;
        }
        .upcoming-info p .mdi { color:var(--accent); font-size:0.95rem; }
        .upcoming-qr-badge {
            margin-inline-start:auto; flex:0 0 auto;
            display:grid; place-items:center; width:38px; height:38px;
            border-radius:12px; color:var(--accent); font-size:1.25rem;
            background:rgba(212,175,55,0.10);
            border:1px solid rgba(212,175,55,0.55);
            box-shadow:inset 0 0 0 1px rgba(255,255,255,0.4);
            transition:0.25s ease;
        }
        .upcoming-card:hover .upcoming-qr-badge {
            background:var(--accent); color:#fff; border-color:var(--accent);
            transform:scale(1.08) rotate(-6deg);
        }

        .qr-modal { position:fixed; inset:0; z-index:1200; display:none; align-items:center; justify-content:center; padding:1rem; }
        .qr-modal.open { display:flex; }
        .qr-modal-overlay { position:absolute; inset:0; background:rgba(6,20,15,0.72); backdrop-filter:blur(3px); }
        .qr-modal-card {
            position:relative; z-index:1; width:100%; max-width:360px;
            background:linear-gradient(160deg, #0F2B22 0%, #14392E 60%, #1A4D3E 100%);
            border:1px solid rgba(212,175,55,0.45); border-radius:20px;
            box-shadow:0 30px 70px rgba(0,0,0,0.5);
            overflow:hidden; text-align:center; color:#FAF6EC;
            animation:qrPop 0.25s ease-out;
        }
        @keyframes qrPop { from { transform:scale(0.9); opacity:0; } to { transform:scale(1); opacity:1; } }
        .qr-modal-head {
            display:flex; align-items:center; justify-content:space-between; gap:0.6rem;
            padding:0.9rem 1.1rem; border-bottom:1px solid rgba(212,175,55,0.25);
        }
        .qr-modal-head h3 { margin:0; font-size:1rem; font-weight:800; letter-spacing:-0.01em; display:flex; align-items:center; gap:0.45rem; }
        .qr-modal-head h3 .mdi { color:#F0C95C; font-size:1.15rem; }
        .qr-modal-close {
            background:none; border:none; color:#FAF6EC; cursor:pointer; font-size:1.35rem; line-height:1;
            padding:0.15rem 0.35rem; border-radius:8px;
        }
        .qr-modal-close:hover { background:rgba(212,175,55,0.18); color:#FFD75E; }
        .qr-modal-body { padding:1.4rem 1.4rem 1.2rem; }
        .qr-box {
            display:inline-block; padding:0.7rem; background:#fff; border-radius:14px;
            box-shadow:0 0 0 4px rgba(212,175,55,0.35), 0 14px 34px rgba(0,0,0,0.35);
        }
        .qr-box img { display:block; width:190px; height:190px; }
        .qr-event-name { margin:0.95rem 0 0.4rem; font-size:1.05rem; font-weight:800; }
        .qr-event-meta { font-size:0.82rem; color:rgba(250,246,236,0.82); margin:0 0 1rem; display:flex; flex-direction:column; gap:0.25rem; align-items:center; }
        .qr-event-meta span { display:flex; align-items:center; gap:0.35rem; }
        .qr-note {
            font-size:0.78rem; color:rgba(250,246,236,0.75); line-height:1.5;
            background:rgba(212,175,55,0.10); border:1px solid rgba(212,175,55,0.3);
            border-radius:10px; padding:0.6rem 0.8rem; display:flex; gap:0.45rem; align-items:flex-start; text-align:start;
        }
        .qr-note .mdi { color:#F0C95C; font-size:1rem; margin-top:0.1rem; flex:0 0 auto; }
        .qr-modal-foot { padding:0.8rem 1.2rem 1rem; border-top:1px solid rgba(212,175,55,0.22); font-size:0.7rem; color:rgba(250,246,236,0.6); }
        .qr-loading { width:190px; height:190px; display:grid; place-items:center; color:rgba(20,57,46,0.6); font-size:1.4rem; }
        .qr-error { color:#dc2626; font-size:0.85rem; font-weight:600; padding:0.5rem; }
        .qr-actions { display:flex; gap:0.6rem; justify-content:center; margin-top:1rem; }
        .qr-share-btn {
            display:inline-flex; align-items:center; gap:0.4rem;
            padding:0.55rem 1.1rem; border-radius:999px; font-weight:600; font-size:0.85rem;
            border:1px solid rgba(212,175,55,0.4); background:rgba(212,175,55,0.12);
            color:#FAF6EC; cursor:pointer; transition:0.25s;
        }
        .qr-share-btn:hover { background:rgba(212,175,55,0.3); transform:translateY(-1px); }
        .qr-share-btn.wa { background:#25D366; border-color:#25D366; color:#fff; }
        .qr-share-btn.wa:hover { background:#1da851; }
        .qr-share-btn.download { background:rgba(255,255,255,0.1); border-color:rgba(255,255,255,0.3); }

        @media (max-width:520px) {
            .upcoming-section { padding:1.2rem 1rem 1.3rem; }
            .upcoming-title { font-size:1.05rem; }
        }
        @media (max-width:350px) {
            .upcoming-grid { grid-template-columns:1fr; }
            .upcoming-card { padding:0.75rem; }
            .upcoming-info p { flex-direction:column; align-items:flex-start; gap:0.15rem; }
        }


        .badge-association-agreer {
            display:inline-flex; align-items:center; gap:0.3rem;
            background:#d4edda; color:#155724; padding:0.1rem 0.6rem;
            border-radius:999px; font-size:0.7rem; font-weight:600;
        }
        .badge-association-en-attente {
            display:inline-flex; align-items:center; gap:0.3rem;
            background:#fff3cd; color:#856404; padding:0.1rem 0.6rem;
            border-radius:999px; font-size:0.7rem; font-weight:600;
        }
    </style>
</head>
<body>
    <div class="landing" id="top">

        <!-- ═══ HERO AVEC IMAGE DE FOND ═══ -->
        <section class="hero" id="hero" aria-label="<?= e(__('landing.hero_badge')) ?>">
            <!-- Image de fond -->
            <?php $heroBg = (string) settings('hero_image', '/assets/img/hero-background.jpg'); ?>
            <?php $heroBg = (is_file(public_path($heroBg))) ? asset($heroBg) : asset('/assets/img/hero-background.jpg'); ?>
            <div class="hero-video" aria-hidden="true" style="background-image:url('<?= e($heroBg) ?>')"></div>
            <!-- Dégradé de secours -->
            <div class="hero-video-fallback" aria-hidden="true"></div>

            <canvas id="aurora-canvas" class="hero-aurora" aria-hidden="true"></canvas>
            <div class="hero-particles" aria-hidden="true">
                <?php for($i=0;$i<20;$i++): ?><i style="top:<?= rand(0,100)?>%;left:<?= rand(0,100)?>%;animation-delay:<?= rand(0,20)?>s"></i><?php endfor; ?>
            </div>

            <div class="container hero-inner">
                <div class="hero-content" data-reveal>
                    <span class="hero-badge">
                        <i class="mdi mdi-leaf"></i> <?= $isAr ? 'منصة خضراء' : 'Plateforme verte' ?>
                    </span>
                    <h1 class="hero-title">
                        <?= e($heroTitre ?: __('app.name')) ?>
                        <span class="hero-title-accent"><?= $isAr ? 'معاً من أجل مستقبل أفضل' : 'Ensemble pour un avenir meilleur' ?></span>
                    </h1>
                    <p class="hero-sub"><?= e($heroSub ?: __('app.tagline')) ?></p>
                    <div class="hero-actions">
                        <a class="btn btn-primary btn-lg" href="#carte"><i class="mdi mdi-tree"></i><?= e(__('landing.cta_explorer')) ?></a>
                        <a class="btn btn-outline btn-lg" href="<?= url('auth/register') ?>"><i class="mdi mdi-account-plus-outline"></i><?= e(__('landing.cta_register')) ?></a>
                        <a class="btn btn-outline btn-lg" href="<?= url('auth/register-association') ?>"><i class="mdi mdi-domain"></i><?= e(__('associations.inscription')) ?></a>
                    </div>
                    <div class="hero-trust">
                        <div class="trust-avatars"><span class="t-avatar">🌱</span><span class="t-avatar">🌿</span><span class="t-avatar">🌳</span><span class="t-avatar">+</span></div>
                        <span><strong>+<?= (int) $totalParticipants ?></strong> <?= $isAr ? 'مشاركة مواطنة' : __('landing.citoyen_participations') ?></span>
                    </div>
                </div>
                <div class="hero-visual" aria-hidden="true">
                    <div id="globe-container">
                        <div class="hero-illustration">
                            <div class="illustration-circle"></div>
                            <div class="illustration-ring"></div>
                            <div class="illustration-icon">
                                <i class="mdi mdi-tree"></i>
                            </div>
                            <div class="illustration-particles">
                                <span class="particle p1">🌿</span>
                                <span class="particle p2">🍃</span>
                                <span class="particle p3">🌱</span>
                                <span class="particle p4">💚</span>
                                <span class="particle p5">🌳</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="container">
                <div class="hero-stats" role="list" aria-label="<?= e(__('landing.stat_band_title')) ?>">
                    <?php foreach ($stats as $i => $s): ?>
                        <div class="hero-stat" role="listitem" data-reveal data-reveal-delay="<?= $i * 100 ?>">
                            <span class="stat-ico"><i class="mdi <?= e($s['icone']) ?>"></i></span>
                            <strong data-count="<?= (int) $s['valeur'] ?>">0</strong>
                            <span class="stat-label"><?= e($s['libelle']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- ═══ PWA — DISPONIBLE SUR TÉLÉPHONE ═══ -->
        <section class="pwa-promo" id="pwa" style="background:linear-gradient(135deg,#0F2B22 0%,#1A4D3E 100%);color:#FAF6EC;padding:2.5rem 0;position:relative;overflow:hidden">
            <div style="position:absolute;inset:0;background:radial-gradient(600px 300px at 20% 0%,rgba(212,175,55,.18),transparent 60%),radial-gradient(500px 250px at 90% 100%,rgba(13,148,136,.18),transparent 60%);pointer-events:none"></div>
            <div class="container" style="position:relative;z-index:1">
                <div style="display:grid;grid-template-columns:1.1fr .9fr;gap:2rem;align-items:center">
                    <div>
                        <span style="display:inline-flex;align-items:center;gap:.4rem;background:rgba(212,175,55,.18);border:1px solid rgba(212,175,55,.35);color:#FFD75E;padding:.25rem .75rem;border-radius:999px;font-size:.75rem;font-weight:700;letter-spacing:.04em"><i class="mdi mdi-cellphone-wireless"></i> <?= $isAr ? 'متاح على الهاتف' : 'Disponible sur téléphone' ?> <span style="background:#25D366;color:#fff;padding:.1rem .4rem;border-radius:999px;font-size:.65rem;margin-inline-start:.3rem">PWA</span></span>
                        <h2 style="font-size:2rem;font-weight:900;margin:.75rem 0 .5rem;line-height:1.15"><?= $isAr ? 'ثبّت <span style="color:#FFD75E">حومتي ايفانت</span> على هاتفك' : 'Installez <span style="color:#FFD75E">حومتي ايفانت</span> sur votre téléphone' ?></h2>
                        <p style="color:rgba(250,246,236,.78);font-size:1rem;line-height:1.6;margin:0 0 1.25rem"><?= $isAr ? 'وصول فوري، يعمل بدون إنترنت، إشعارات فورية، مسح QR بسرعة. لا حاجة لمتجر.' : 'Accès instantané, hors-ligne, notifications push, scan QR rapide. Sans App Store.' ?></p>
                        <div style="display:flex;flex-wrap:wrap;gap:.6rem;margin-bottom:1rem">
                            <button id="pwaInstallBtn" style="display:inline-flex;align-items:center;gap:.5rem;padding:.8rem 1.4rem;border-radius:999px;border:none;background:linear-gradient(135deg,#FFD75E,#FFC02E);color:#0F2B22;font-weight:800;cursor:pointer;box-shadow:0 8px 20px rgba(212,175,55,.35)"><i class="mdi mdi-download" style="font-size:1.2rem"></i> <?= $isAr ? 'ثبّت الآن' : 'Installer maintenant' ?></button>
                            <a href="#pwa-help" onclick="document.getElementById('pwaHelp').scrollIntoView({behavior:'smooth'}); return false;" style="display:inline-flex;align-items:center;gap:.4rem;padding:.8rem 1.2rem;border-radius:999px;border:1px solid rgba(255,255,255,.25);color:#FAF6EC;text-decoration:none;font-weight:600"><i class="mdi mdi-help-circle-outline"></i> <?= $isAr ? 'كيف؟' : 'Comment ?' ?></a>
                            <span id="pwaInstalledBadge" style="display:none;align-items:center;gap:.4rem;background:rgba(74,222,128,.18);border:1px solid rgba(74,222,128,.35);color:#4ade80;padding:.5rem .85rem;border-radius:999px;font-size:.8rem;font-weight:700"><i class="mdi mdi-check-circle"></i> <?= $isAr ? 'مثبت' : 'Installée' ?></span>
                        </div>
                        <div style="display:flex;flex-wrap:wrap;gap:.75rem;font-size:.78rem;color:rgba(250,246,236,.65)">
                            <span><i class="mdi mdi-wifi-off" style="color:#FFD75E"></i> <?= $isAr ? 'يعمل بدون إنترنت' : 'Fonctionne hors-ligne' ?></span>
                            <span><i class="mdi mdi-bell-ring" style="color:#FFD75E"></i> Push</span>
                            <span><i class="mdi mdi-qrcode-scan" style="color:#FFD75E"></i> QR</span>
                            <span><i class="mdi mdi-update" style="color:#FFD75E"></i> Auto MAJ</span>
                        </div>
                    </div>
                    <div style="display:flex;flex-direction:column;align-items:center;gap:1rem">
                        <?php $pwaQrUrl = \App\Helpers\QrCodeGenerator::pngDataUri(url('/'), 220); ?>
                        <div style="background:#fff;padding:.6rem;border-radius:18px;box-shadow:0 12px 32px rgba(0,0,0,.25)"><img id="pwaQr" src="<?= $pwaQrUrl ?>" alt="QR <?= e(url('/')) ?>" style="width:160px;height:160px;border-radius:12px;display:block" width="160" height="160"></div>
                        <div style="font-size:.75rem;color:rgba(250,246,236,.7);text-align:center"><i class="mdi mdi-qrcode me-1"></i><?= $isAr ? 'امسح للفتح على الهاتف' : 'Scannez pour ouvrir sur mobile' ?><br><span style="opacity:.6"><?= e(url('/')) ?></span><br><a href="<?= $pwaQrUrl ?>" download="wilaya-harmonia-qr.png" style="color:#FFD75E;font-size:.7rem;text-decoration:underline"><i class="mdi mdi-download me-1"></i><?= $isAr ? 'تحميل QR' : 'Télécharger QR' ?></a></div>
                    </div>
                </div>
            </div>
        </section>
        <section id="pwaHelp" style="background:#fff;padding:2rem 0;border-top:1px solid #e8f0e8">
            <div class="container" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;text-align:center">
                <div style="background:var(--bg-secondary);border-radius:1rem;padding:1.25rem;border:1px solid var(--glass-border)"><i class="mdi mdi-android" style="font-size:1.8rem;color:#3DDC84"></i><h4 style="margin:.5rem 0 .25rem">Android / Chrome</h4><p style="font-size:.82rem;color:var(--text-secondary);margin:0"><?= $isAr ? 'افتح في Chrome → ⋮ → تثبيت التطبيق' : 'Ouvrez dans Chrome → ⋮ → Installer l’app' ?></p></div>
                <div style="background:var(--bg-secondary);border-radius:1rem;padding:1.25rem;border:1px solid var(--glass-border)"><i class="mdi mdi-apple" style="font-size:1.8rem;color:#000"></i><h4 style="margin:.5rem 0 .25rem">iPhone / Safari</h4><p style="font-size:.82rem;color:var(--text-secondary);margin:0"><?= $isAr ? 'شارك → إضافة إلى الشاشة الرئيسية' : 'Partager → Sur l’écran d’accueil' ?></p></div>
                <div style="background:var(--bg-secondary);border-radius:1rem;padding:1.25rem;border:1px solid var(--glass-border)"><i class="mdi mdi-laptop" style="font-size:1.8rem;color:var(--accent)"></i><h4 style="margin:.5rem 0 .25rem">PC / Edge</h4><p style="font-size:.82rem;color:var(--text-secondary);margin:0"><?= $isAr ? 'شريط العنوان → تثبيت' : 'Barre d’adresse → Installer' ?></p></div>
            </div>
        </section>

        <!-- ═══ SECTIONS DYNAMIQUES ═══ -->
        <?php $idx = 0; ?>
        <?php foreach ($ordre as $section): ?>
            <?php if (! $visible((string) $section)) continue; ?>
            <?php $section = str_replace('_', '-', (string) $section); ?>
            <?php $alt = ($idx % 2 === 1); $idx++; ?>

            <?php if ($section === 'actualites'): ?>
                <section class="section section-news<?= $alt ? ' bg-muted' : '' ?>" id="actualites">
                    <div class="container">
                        <div class="section-head" data-reveal>
                            <span class="eyebrow"><i class="mdi mdi-newspaper-variant-outline"></i><?= $isAr ? 'آخر الأخبار' : 'Actualités & Événements' ?></span>
                            <h2 class="section-title"><?= $isAr ? 'الأخبار والأحداث القادمة' : 'Actualités & événements à venir' ?></h2>
                            <p class="section-lead"><?= $isAr ? 'تابع آخر الأخبار والفعاليات المبرمجة عبر الولاية.' : 'Restez informé des dernières nouvelles et des prochaines activités.' ?></p>
                        </div>

                        <!-- Filtres -->
                        <div class="news-filters" data-reveal>
                            <button class="news-filter-btn active" data-filter="all">
                                <i class="mdi mdi-apps"></i> <?= $isAr ? 'الكل' : 'Tout' ?>
                            </button>
                            <button class="news-filter-btn" data-filter="actualite">
                                <i class="mdi mdi-newspaper"></i> <?= $isAr ? 'أخبار' : 'Actualités' ?>
                            </button>
                            <button class="news-filter-btn" data-filter="evenement">
                                <i class="mdi mdi-calendar-star"></i> <?= $isAr ? 'أحداث' : 'Événements' ?>
                            </button>
                        </div>

                        <!-- Grille actualités/événements -->
                        <div class="news-grid" id="newsGrid">
                            <?php foreach ($news as $item): ?>
                                <div class="news-card" data-type="<?= e($item['type']) ?>" data-reveal>
                                    <?php if ($item['image']): ?>
                                        <div class="news-card-image">
                                            <img src="<?= e(asset($item['image'])) ?>" alt="<?= e($item['titre_fr']) ?>" loading="lazy">
                                            <span class="news-type-badge <?= $item['type'] === 'evenement' ? 'type-event' : 'type-news' ?>">
                                                <i class="mdi mdi-<?= $item['type'] === 'evenement' ? 'calendar-star' : 'newspaper' ?>"></i>
                                                <?= $item['type'] === 'evenement' ? ($isAr ? 'حدث' : 'Événement') : ($isAr ? 'خبر' : 'Actualité') ?>
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <div class="news-card-image news-card-placeholder">
                                            <i class="mdi mdi-<?= $item['type'] === 'evenement' ? 'calendar-star' : 'newspaper' ?>"></i>
                                            <span class="news-type-badge <?= $item['type'] === 'evenement' ? 'type-event' : 'type-news' ?>">
                                                <i class="mdi mdi-<?= $item['type'] === 'evenement' ? 'calendar-star' : 'newspaper' ?>"></i>
                                                <?= $item['type'] === 'evenement' ? ($isAr ? 'حدث' : 'Événement') : ($isAr ? 'خبر' : 'Actualité') ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>

                                    <div class="news-card-body">
                                        <div class="news-card-meta">
                                            <?php if ($item['date_event']): ?>
                                                <span class="news-date">
                                                    <i class="mdi mdi-calendar-outline"></i>
                                                    <?= e(date('d M Y', strtotime((string) $item['date_event']))) ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($item['lieu']): ?>
                                                <span class="news-location">
                                                    <i class="mdi mdi-map-marker-outline"></i>
                                                    <?= e($item['lieu']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <h3 class="news-card-title">
                                            <?= e($isAr ? ($item['titre_ar'] ?: $item['titre_fr']) : $item['titre_fr']) ?>
                                        </h3>

                                        <?php if ($item['description_fr']): ?>
                                            <p class="news-card-desc">
                                                <?= e(mb_strimwidth((string) ($isAr ? ($item['description_ar'] ?: $item['description_fr']) : $item['description_fr']), 0, 120, '…')) ?>
                                            </p>
                                        <?php endif; ?>

                                        <?php if ($item['url_externe']): ?>
                                            <a href="<?= e($item['url_externe']) ?>" target="_blank" rel="noopener" class="news-card-link">
                                                <?= $isAr ? 'المزيد' : 'En savoir plus' ?>
                                                <i class="mdi mdi-arrow-right"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <?php if (empty($news)): ?>
                                <div class="news-empty">
                                    <i class="mdi mdi-newspaper-variant-outline"></i>
                                    <p><?= $isAr ? 'لا أخبار حالياً.' : 'Aucune actualité pour le moment.' ?></p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Événements à venir (depuis la table evenements) -->
                        <?php if (settings('general_upcoming_visible', '1') !== '0' && !empty($upcoming)): ?>
                        <div class="upcoming-section" data-reveal>
                            <h3 class="upcoming-title">
                                <i class="mdi mdi-calendar-clock"></i>
                                <?= $isAr ? 'فعاليات قادمة' : 'Prochains événements' ?>
                                <span class="upcoming-count">
                                    <i class="mdi mdi-calendar-star"></i>
                                    <?= (int) count($upcoming) ?> <?= $isAr ? 'فعالية' : 'à venir' ?>
                                </span>
                            </h3>
                            <div class="upcoming-grid">
                                <?php foreach ($upcoming as $ev): ?>
                                    <button type="button" class="upcoming-card"
                                            data-qr-open
                                            data-qr-id="<?= (int) $ev['id'] ?>"
                                            data-qr-adresse="<?= e((string) ($ev['adresse'] ?? '')) ?>"
                                            data-qr-date="<?= e((new DateTimeImmutable((string)$ev['date_evenement']))->format('d/m/Y')) ?>"
                                            data-qr-heure="<?= e((string) ($ev['heure'] ?? '')) ?>"
                                            data-qr-commune="<?= e((string) ($ev['commune_nom'] ?? '')) ?>">
                                        <div class="upcoming-date">
                                            <span class="upcoming-day"><?= e((new DateTimeImmutable((string)$ev['date_evenement']))->format('d')) ?></span>
                                            <span class="upcoming-month"><?= e((new DateTimeImmutable((string)$ev['date_evenement']))->format('M')) ?></span>
                                        </div>
                                        <div class="upcoming-info">
                                            <h4><?= e($ev['adresse']) ?></h4>
                                            <p>
                                                <i class="mdi mdi-clock-outline"></i><?= e($ev['heure'] ?? '') ?>
                                                <i class="mdi mdi-map-marker-outline ms-2"></i><?= e($ev['commune_nom'] ?? '') ?>
                                            </p>
                                        </div>
                                        <span class="upcoming-qr-badge"><i class="mdi mdi-qrcode-scan"></i></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </section>

            <?php elseif ($section === 'apropos'): ?>
                <section class="section section-about<?= $alt ? ' bg-muted' : '' ?>" id="apropos">
                    <div class="container about-inner" style="display:grid;grid-template-columns:1fr 1fr;gap:3rem;align-items:center">
                        <div class="about-visual" data-reveal aria-hidden="true" style="display:flex;justify-content:center">
                            <div style="width:200px;height:200px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#0d9488);display:grid;place-items:center;font-size:5rem;color:#fff"><i class="mdi mdi-tree"></i></div>
                        </div>
                        <div class="about-text" data-reveal data-reveal-delay="120">
                            <span class="eyebrow"><i class="mdi mdi-information-outline"></i><?= $isAr ? 'من نحن' : 'Qui sommes-nous' ?></span>
                            <h2 class="section-title left" style="text-align:left"><?= e($pick(settings('titre_apropos_fr',''), settings('titre_apropos_ar',''))) ?></h2>
                            <p class="apropos-text"><?= e($pick(settings('texte_apropos_fr',''), settings('texte_apropos_ar',''))) ?></p>
                            <ul class="about-points" style="list-style:none;margin-top:1rem">
                                <li><i class="mdi mdi-check-circle-outline" style="color:var(--accent)"></i> <?= $isAr ? 'شراكة مواطنة حقيقية' : 'Partenariat citoyen authentique' ?></li>
                                <li><i class="mdi mdi-check-circle-outline" style="color:var(--accent)"></i> <?= $isAr ? 'شفافية في كل مرحلة' : 'Transparence à chaque étape' ?></li>
                                <li><i class="mdi mdi-check-circle-outline" style="color:var(--accent)"></i> <?= $isAr ? 'خدمة عمومية متجددة' : 'Un service public qui se modernise' ?></li>
                            </ul>
                        </div>
                    </div>
                </section>

            <?php elseif ($section === 'fonctionnement'): ?>
                <?php $etapes = settings('fonctionnement_etapes', []); if(is_array($etapes) && count($etapes)>0): ?>
                <section class="section section-how<?= $alt ? ' bg-muted' : '' ?>" id="fonctionnement">
                    <div class="container">
                        <div class="section-head" data-reveal>
                            <span class="eyebrow"><i class="mdi mdi-cog-outline"></i><?= $isAr ? 'طريقة العمل' : 'Le processus' ?></span>
                            <h2 class="section-title"><?= e($pick(settings('titre_fonctionnement_fr',''), settings('titre_fonctionnement_ar',''))) ?></h2>
                            <p class="section-lead"><?= $isAr ? 'من الإبلاغ إلى الإنجاز، مسار واضح ومبسط.' : 'Du signalement à la réalisation, un parcours clair et simplifié.' ?></p>
                        </div>
                        <div class="steps" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:2rem">
                            <?php foreach($etapes as $i=>$etape): ?>
                                <div class="step-card" data-reveal data-reveal-delay="<?= $i*90 ?>" style="background:#fff;border-radius:var(--radius);padding:1.5rem;text-align:center;box-shadow:var(--shadow);border:1px solid var(--glass-border)">
                                    <span class="step-num" style="background:var(--accent);color:#fff;width:2.5rem;height:2.5rem;display:grid;place-items:center;border-radius:50%;margin:0 auto 0.5rem"><?= $i+1 ?></span>
                                    <span class="step-ico" style="font-size:2rem;color:var(--accent)"><i class="mdi <?= e($etape['icone']??'mdi-star') ?>"></i></span>
                                    <h3><?= e($pick($etape['titre_fr']??'', $etape['titre_ar']??'')) ?></h3>
                                    <p style="color:var(--text-secondary)"><?= e($pick($etape['texte_fr']??'', $etape['texte_ar']??'')) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
                <?php endif; ?>

            <?php elseif ($section === 'anomalies'): ?>
                <section class="section section-services<?= $alt ? ' bg-muted' : '' ?>" id="anomalies">
                    <div class="container">
                        <div class="section-head" data-reveal>
                            <span class="eyebrow"><i class="mdi mdi-tools"></i><?= $isAr ? 'خدمات الولاية' : 'Nos services' ?></span>
                            <h2 class="section-title"><?= e(__('landing.anomalies')) ?></h2>
                            <p class="section-lead"><?= e(__('landing.anomalies_sub')) ?></p>
                        </div>
                        <div class="services-grid">
                            <?php foreach($anomalies as $a): ?>
                                <div class="service-card" data-reveal style="--card-tint:<?= e($a['couleur']??'#2a7a3e') ?>">
                                    <span class="service-ico"><i class="mdi <?= e(str_replace('fa-','mdi-', $a['icone']??'mdi-alert')) ?>"></i></span>
                                    <strong><?= e($a['nom']) ?></strong>
                                    <span class="chip chip-count"><?= (int)$a['total'] ?> <?= e(__('landing.anomalies_traitees')) ?></span>
                                </div>
                            <?php endforeach; ?>
                            <?php if(empty($anomalies)): ?><div class="card empty"><?= e(__('landing.anomalies_vide')) ?></div><?php endif; ?>
                        </div>
                    </div>
                </section>

            <?php elseif ($section === 'albums'): ?>
                <?php $renderAlbumEventSection($alt); ?>

            <?php elseif ($section === 'temoignages'): ?>
                <section class="section section-testimonials<?= $alt ? ' bg-muted' : '' ?>" id="temoignages">
                    <div class="container">
                        <div class="section-head" data-reveal>
                            <span class="eyebrow"><i class="mdi mdi-comment-quote-outline"></i><?= $isAr ? 'آراء المواطنين' : 'La voix citoyenne' ?></span>
                            <h2 class="section-title"><?= e(__('landing.temoignages')) ?></h2>
                        </div>
                        <div class="cards-grid">
                            <?php foreach($testimonials as $t): ?>
                                <div class="card testimonial" data-reveal>
                                    <div class="stars"><?= str_repeat('★', (int)$t['note']) ?><?= str_repeat('☆', 5-(int)$t['note']) ?></div>
                                    <p><?= nl2br(e($pick($t['texte_fr']??'', $t['texte_ar']??''))) ?></p>
                                    <div class="testimonial-author">— <?= e($t['auteur']) ?><?= !empty($t['role']) ? ' · '.e($t['role']) : '' ?></div>
                                </div>
                            <?php endforeach; ?>
                            <?php if(empty($testimonials)): ?><div class="card empty"><?= $isAr ? 'لا توجد شهادات حالياً.' : 'Aucun témoignage pour le moment.' ?></div><?php endif; ?>
                        </div>
                    </div>
                </section>

            <?php elseif ($section === 'partenaires'): ?>
                <section class="section section-partners<?= $alt ? ' bg-muted' : '' ?>" id="partenaires">
                    <div class="container">
                        <div class="section-head" data-reveal>
                            <span class="eyebrow"><i class="mdi mdi-handshake-outline"></i><?= $isAr ? 'معًا ننجز' : 'Ensemble, on agit' ?></span>
                            <h2 class="section-title"><?= e(__('landing.partenaires')) ?></h2>
                        </div>
                        <div class="partners">
                            <?php foreach($partners as $p): ?>
                                <a class="partner-card" href="<?= e($p['url']??'#') ?>" target="_blank" rel="noopener" data-reveal><i class="mdi mdi-domain"></i><span><?= e($p['nom']) ?></span></a>
                            <?php endforeach; ?>
                            <?php if(empty($partners)): ?><div class="card empty"><?= $isAr ? 'لا يوجد شركاء حالياً.' : 'Aucun partenaire pour le moment.' ?></div><?php endif; ?>
                        </div>
                    </div>
                </section>

            <?php elseif ($section === 'faq'): ?>
                <section class="section section-faq<?= $alt ? ' bg-muted' : '' ?>" id="faq">
                    <div class="container">
                        <div class="section-head" data-reveal>
                            <span class="eyebrow"><i class="mdi mdi-help-circle-outline"></i><?= $isAr ? 'استفساراتكم' : 'Vos questions' ?></span>
                            <h2 class="section-title"><?= e(__('landing.faq')) ?></h2>
                        </div>
                        <div class="faq">
                            <?php foreach($faq as $i=>$f): ?>
                                <details class="faq-item" <?= $i===0 ? 'open' : '' ?> data-reveal data-reveal-delay="<?= min($i*60,300) ?>">
                                    <summary><?= e($pick($f['question_fr']??'', $f['question_ar']??'')) ?></summary>
                                    <p><?= nl2br(e($pick($f['reponse_fr']??'', $f['reponse_ar']??''))) ?></p>
                                </details>
                            <?php endforeach; ?>
                            <?php if(empty($faq)): ?><p class="text-muted text-center"><?= $isAr ? 'لا توجد أسئلة شائعة حالياً.' : 'Aucune question fréquente pour le moment.' ?></p><?php endif; ?>
                        </div>
                    </div>
                </section>

            <?php elseif ($section === 'timeline'): ?>
                <?php if(!empty($timelineEvents)): ?>
                <section class="section section-timeline<?= $alt ? ' bg-muted' : '' ?>" id="timeline">
                    <div class="container">
                        <div class="section-head" data-reveal>
                            <span class="eyebrow"><i class="mdi mdi-timeline-outline"></i><?= $isAr ? 'مسيرتنا' : 'Notre parcours' ?></span>
                            <h2 class="section-title"><?= $isAr ? 'محطات بارزة' : 'Étapes marquantes' ?></h2>
                            <p class="section-lead"><?= $isAr ? 'أبرز المحطات في مسيرة الولاية.' : 'Les moments clés de la wilaya.' ?></p>
                        </div>
                        <div class="timeline">
                            <?php foreach($timelineEvents as $ev): ?>
                                <div class="timeline-item" data-reveal>
                                    <span class="timeline-date"><?= e($ev['date'] ?? '') ?></span>
                                    <div class="timeline-title"><?= e($pick($ev['titre_fr']??'', $ev['titre_ar']??'')) ?></div>
                                    <p style="color:var(--text-secondary)"><?= e($pick($ev['desc_fr']??'', $ev['desc_ar']??'')) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
                <?php endif; ?>

            <?php elseif ($section === 'galerie'): ?>
                <?php if(!empty($gallery)): ?>
                <section class="section section-gallery<?= $alt ? ' bg-muted' : '' ?>" id="galerie">
                    <div class="container">
                        <div class="section-head" data-reveal>
                            <span class="eyebrow"><i class="mdi mdi-image-multiple-outline"></i><?= $isAr ? 'معرض الصور' : 'La galerie photos' ?></span>
                            <h2 class="section-title"><?= e(__('landing.galerie')) ?></h2>
                            <p class="section-lead"><?= $isAr ? 'لقطات من الميدان عبر الولاية.' : 'Des instantanés du terrain à travers la wilaya.' ?></p>
                        </div>
                        <div class="gallery-grid">
                            <?php foreach($gallery as $g): ?>
                                <figure class="gallery-item" data-reveal>
                                    <?php $gTitre = $pick($g['titre_fr'] ?? '', $g['titre_ar'] ?? ''); ?>
                                    <?php $gImage = $g['image'] ?? ''; ?>
                                    <?php if(!empty($g['lien'])): ?><a href="<?= e($g['lien']) ?>" target="_blank" rel="noopener" aria-label="<?= e($gTitre) ?>"><?php endif; ?>
                                        <img src="<?= asset((string) $gImage) ?>" alt="<?= e($gTitre) ?>" loading="lazy">
                                    <?php if(!empty($g['lien'])): ?></a><?php endif; ?>
                                    <?php if(!empty($gTitre)): ?><figcaption><?= e($gTitre) ?></figcaption><?php endif; ?>
                                </figure>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
                <?php endif; ?>

            <?php elseif ($section === 'before-after'): ?>
                <?php if (!$albumsInOrder && !empty($albums)): ?>
                    <?php $renderAlbumEventSection($alt); ?>
                <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; ?>

        <!-- Lightbox Albums (toujours présent pour la section albums / événements) -->
        <div class="wh-lightbox" id="albumLightbox" role="dialog" aria-modal="true" aria-hidden="true">
            <div class="wh-lightbox-backdrop" data-lb-close></div>
            <div class="wh-lightbox-panel">
                <button class="wh-lightbox-close" type="button" data-lb-close aria-label="<?= $isAr ? 'إغلاق' : 'Fermer' ?>"><i class="mdi mdi-close"></i></button>
                <div class="wh-lightbox-stage">
                    <button class="wh-lightbox-nav prev" type="button" data-lb-prev aria-label="Précédent"><i class="mdi mdi-chevron-left"></i></button>
                    <img class="wh-lightbox-img" id="lightboxImg" src="" alt="" draggable="false">
                    <button class="wh-lightbox-nav next" type="button" data-lb-next aria-label="Suivant"><i class="mdi mdi-chevron-right"></i></button>
                    <span class="wh-lightbox-counter" id="lightboxCounter"></span>
                    <p class="wh-lightbox-caption" id="lightboxCaption"></p>
                </div>
                <div class="wh-lightbox-narrative">
                    <h4><i class="mdi mdi-format-quote-open"></i><?= $isAr ? 'القصة الرسمية' : 'Récit officiel' ?></h4>
                    <p id="lightboxNarrativeText"></p>
                </div>
            </div>
        </div>

        <!-- ═══ CARTE (LEAFLET / OPENSTREETMAP) ═══ -->
        <?php if ($mapCfg['visible']): ?>
        <section class="section section-map" id="carte">
            <div class="container">
                <div class="section-head" data-reveal>
                    <span class="eyebrow"><i class="mdi mdi-map-outline"></i><?= $isAr ? 'التدخلات الميدانية' : 'Le terrain en direct' ?></span>
                    <h2 class="section-title"><?= e(__('landing.interventions')) ?></h2>
                    <p class="section-lead"><?= $isAr ? 'تابع عمليات الولاية على الخريطة.' : 'Suivez les opérations de la wilaya sur la carte.' ?></p>
                </div>
                <div class="landing-map" id="landingMap" role="region" aria-label="<?= e(__('landing.interventions')) ?>"></div>
            </div>
        </section>
        <?php endif; ?>

                <!-- ═══ IA ASSISTANT ═══ -->
        <div id="ia-assistant" dir="<?= $isAr ? 'rtl' : 'ltr' ?>">
            <button class="ia-toggle" id="iaToggle" aria-label="<?= $isAr ? 'المساعد الذكي' : 'Assistant IA' ?>">
                <span class="ia-toggle-icon"><i class="mdi mdi-robot-outline"></i></span>
                <span class="ia-toggle-pulse"></span>
            </button>
            <div class="ia-window" id="iaWindow">
                <div class="ia-header">
                    <div class="ia-header-left">
                        <div class="ia-avatar"><i class="mdi mdi-robot"></i><span class="ia-avatar-status"></span></div>
                        <div class="ia-header-text">
                            <strong><?= $isAr ? 'المساعد الذكي' : 'Assistant IA' ?></strong>
                            <small><?= $isAr ? 'حومتي ايفانت' : 'حومتي ايفانت' ?></small>
                        </div>
                    </div>
                    <button id="iaClose" class="ia-close-btn" aria-label="<?= $isAr ? 'إغلاق' : 'Fermer' ?>"><i class="mdi mdi-close"></i></button>
                </div>
                <div class="ia-messages" id="iaMessages">
                    <div class="ia-msg-row bot">
                        <div class="ia-msg-avatar"><i class="mdi mdi-robot"></i></div>
                        <div class="ia-msg bot"><?= $isAr ? 'مرحباً! 👋 أنا مساعد حومتي ايفانت الذكي. كيف يمكنني مساعدتك اليوم؟' : 'Bonjour ! 👋 Je suis l\'assistant de حومتي ايفانت. Comment puis-je vous aider ?' ?></div>
                    </div>
                    <div class="ia-quick-replies" id="iaQuickReplies">
                        <button class="ia-quick-btn" data-key="events"><i class="mdi mdi-calendar-star"></i> <?= $isAr ? 'فعاليات قادمة' : 'Événements' ?></button>
                        <button class="ia-quick-btn" data-key="join"><i class="mdi mdi-account-group"></i> <?= $isAr ? 'جمعية' : 'Association' ?></button>
                        <button class="ia-quick-btn" data-key="signal"><i class="mdi mdi-alert-circle"></i> <?= $isAr ? 'إبلاغ' : 'Signaler' ?></button>
                        <button class="ia-quick-btn" data-key="contact"><i class="mdi mdi-phone"></i> <?= $isAr ? 'تواصل' : 'Contact' ?></button>
                    </div>
                </div>
                <div class="ia-typing" id="iaTyping"><span class="ia-typing-dot"></span><span class="ia-typing-dot"></span><span class="ia-typing-dot"></span></div>
                <form class="ia-input" id="iaForm" autocomplete="off">
                    <input type="text" id="iaInput" placeholder="<?= $isAr ? 'اكتب سؤالك...' : 'Écrivez votre question...' ?>" aria-label="<?= $isAr ? 'إدخال النص' : 'Saisie' ?>" />
                    <button type="submit" id="iaSend" aria-label="Envoyer"><i class="mdi mdi-send"></i></button>
                </form>
            </div>
        </div>

    </div> <!-- fin .landing -->

    <!-- ═══ MODAL QR CODE — Événements programmés ═══ -->
    <div class="qr-modal" id="qrModal" role="dialog" aria-modal="true" aria-hidden="true" aria-label="<?= $isAr ? 'رمز الاستجابة السريعة للحدث' : 'QR code de l\'événement' ?>">
        <div class="qr-modal-overlay" data-qr-close></div>
        <div class="qr-modal-card">
            <div class="qr-modal-head">
                <h3><i class="mdi mdi-qrcode-scan"></i><?= $isAr ? 'رمز الاستجابة السريعة' : 'QR code de l\'événement' ?></h3>
                <button type="button" class="qr-modal-close" data-qr-close aria-label="<?= $isAr ? 'إغلاق' : 'Fermer' ?>">&times;</button>
            </div>
            <div class="qr-modal-body">
                <div class="qr-box">
                    <div class="qr-loading" id="qrLoading"><i class="mdi mdi-qrcode"></i></div>
                    <img id="qrImg" src="" alt="QR code" style="display:none" width="190" height="190">
                </div>
                <h4 class="qr-event-name" id="qrEventName"></h4>
                <p class="qr-event-meta" id="qrEventMeta"></p>
                <p class="qr-note">
                    <i class="mdi mdi-information-outline"></i>
                    <span><?= $isAr ? 'امسح هذا الرمز لتسجيل حضورك في الحدث. إذا لم تكن مسجلاً الدخول، سيُطلب منك تسجيل الدخول أولاً.' : 'Scannez ce code pour enregistrer votre présence à l\'événement. Si vous n\'êtes pas connecté(e), une connexion vous sera demandée.' ?></span>
                </p>
                <div class="qr-actions" id="qrActions">
                    <button type="button" class="qr-share-btn wa" id="qrShareWa" title="<?= $isAr ? 'مشاركة عبر واتساب' : 'Partager sur WhatsApp' ?>">
                        <i class="mdi mdi-whatsapp"></i> <?= $isAr ? 'مشاركة' : 'Partager' ?>
                    </button>
                    <button type="button" class="qr-share-btn download" id="qrDownload" title="<?= $isAr ? 'تحميل الرمز' : 'Télécharger le QR' ?>">
                        <i class="mdi mdi-download"></i> <?= $isAr ? 'تحميل' : 'Télécharger' ?>
                    </button>
                    <a class="qr-share-btn" id="qrDetailLink" href="#" target="_blank" rel="noopener" title="<?= $isAr ? 'عرض التفاصيل' : 'Voir les détails' ?>">
                        <i class="mdi mdi-open-in-new"></i> <?= $isAr ? 'الحدث' : 'Événement' ?>
                    </a>
                </div>
            </div>
            <div class="qr-modal-foot"><?= e(__('app.name')) ?></div>
        </div>
    </div>

    <!-- ═══ SCRIPTS ═══ -->
    <script type="importmap">
    {
        "imports": {
            "three": "https://unpkg.com/three@0.160.0/build/three.module.js",
            "three/addons/": "https://unpkg.com/three@0.160.0/examples/jsm/"
        }
    }
    </script>

    <script type="module">
        const mapEvents = <?= json_encode($mapEvents ?? [], JSON_UNESCAPED_UNICODE) ?>;
        const mapCfg = <?= json_encode($mapCfg, JSON_UNESCAPED_UNICODE) ?>;
        const albumsData = <?= json_encode($albums ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
        const isAr = <?= json_encode($isAr) ?>;

        function assetPath(p) {
            if (!p) return '';
            if (/^(https?:)?\/\//.test(p) || p.charAt(0) === '/') return p;
            return '/' + p;
        }
        function escAttr(s) {
            return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        }

        // ─── MODAL QR CODE — événements programmés ───
        (function initQrModal() {
            const qrModal = document.getElementById('qrModal');
            if (!qrModal) return;
            const qrImg = document.getElementById('qrImg');
            const qrLoading = document.getElementById('qrLoading');
            const qrName = document.getElementById('qrEventName');
            const qrMeta = document.getElementById('qrEventMeta');
            const qrStreamBase = <?= json_encode(url('event/qr/stream/')) ?>;
            const qrEventBase = <?= json_encode(url('evenement/')) ?>;
            let currentEventId = null;
            let currentEventName = '';

            function openQr(btn) {
                currentEventId = btn.dataset.qrId;
                currentEventName = btn.dataset.qrAdresse || '';
                qrName.textContent = currentEventName;
                const parts = [];
                if (btn.dataset.qrDate) parts.push((isAr ? 'التاريخ' : 'Date') + ' : ' + btn.dataset.qrDate);
                if (btn.dataset.qrHeure) parts.push((isAr ? 'الساعة' : 'Heure') + ' : ' + btn.dataset.qrHeure);
                if (btn.dataset.qrCommune) parts.push(btn.dataset.qrCommune);
                qrMeta.replaceChildren();
                parts.forEach(function (p) {
                    const s = document.createElement('span');
                    s.textContent = p;
                    qrMeta.appendChild(s);
                });
                qrImg.style.display = 'none';
                qrLoading.style.display = 'grid';
                qrLoading.innerHTML = '<i class="mdi mdi-qrcode"></i>';
                const img = new Image();
                img.onload = function () {
                    qrImg.src = img.src;
                    qrImg.style.display = 'block';
                    qrLoading.style.display = 'none';
                };
                img.onerror = function () {
                    qrLoading.style.display = 'none';
                    qrLoading.innerHTML = '<span class="qr-error">' + (isAr ? 'الرمز غير متاح' : 'QR code indisponible') + '</span>';
                };
                img.src = qrStreamBase + btn.dataset.qrId;
                // Update links
                var detailLink = document.getElementById('qrDetailLink');
                if (detailLink) detailLink.href = qrEventBase + btn.dataset.qrId;
                qrModal.classList.add('open');
                qrModal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            }
            function closeQr() {
                qrModal.classList.remove('open');
                qrModal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            }
            document.querySelectorAll('[data-qr-open]').forEach(function (b) {
                b.addEventListener('click', function () { openQr(b); });
            });
            qrModal.querySelectorAll('[data-qr-close]').forEach(function (el) {
                el.addEventListener('click', closeQr);
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && qrModal.classList.contains('open')) closeQr();
            });
            // WhatsApp share
            var waBtn = document.getElementById('qrShareWa');
            if (waBtn) {
                waBtn.addEventListener('click', function () {
                    var url = window.location.origin + '/evenement/' + currentEventId;
                    var text = (isAr ? ' qr شارك هذا الحدث عبر رمز ' : 'Participez à cet événement ! QR: ') + currentEventName;
                    var waUrl = 'https://wa.me/?text=' + encodeURIComponent(text + '\n' + url);
                    window.open(waUrl, '_blank', 'noopener,noreferrer');
                });
            }
            // Download QR
            var dlBtn = document.getElementById('qrDownload');
            if (dlBtn) {
                dlBtn.addEventListener('click', function () {
                    if (!qrImg.src || qrImg.style.display === 'none') return;
                    var a = document.createElement('a');
                    a.href = qrImg.src;
                    a.download = 'qr-evenement-' + currentEventId + '.png';
                    a.click();
                });
            }
        })();

        // ─── AURORA ───
        const auroraCanvas = document.getElementById('aurora-canvas');
        if (auroraCanvas) {
            const ctx = auroraCanvas.getContext('2d');
            let w = auroraCanvas.width = auroraCanvas.clientWidth;
            let h = auroraCanvas.height = auroraCanvas.clientHeight;
            let t = 0;
            function resizeAurora() {
                const rect = auroraCanvas.parentElement.getBoundingClientRect();
                auroraCanvas.width = rect.width; auroraCanvas.height = rect.height;
                w = auroraCanvas.width; h = auroraCanvas.height;
            }
            window.addEventListener('resize', resizeAurora);
            resizeAurora();
            function drawAurora() {
                t += 0.003;
                const grd = ctx.createRadialGradient(w*0.2, h*0.1, 50, w*0.5, h*0.5, Math.max(w,h)*0.8);
                const hue1 = (Math.sin(t*0.3)*20 + 120) % 360;
                const hue2 = (Math.cos(t*0.4)*20 + 180) % 360;
                grd.addColorStop(0, `hsla(${hue1}, 70%, 70%, 0.25)`);
                grd.addColorStop(0.5, `hsla(${hue2}, 60%, 60%, 0.15)`);
                grd.addColorStop(1, `hsla(${hue1+30}, 50%, 50%, 0)`);
                ctx.clearRect(0,0,w,h);
                ctx.fillStyle = grd;
                ctx.fillRect(0,0,w,h);
                requestAnimationFrame(drawAurora);
            }
            drawAurora();
        }

        // ─── CARTE — Leaflet + tuiles gratuites (OpenStreetMap / CARTO), sans clé ───
        const noMapTitle = (isAr ? 'الخريطة غير متاحة حالياً' : 'Carte indisponible pour le moment');

        (function initMap() {
            const mapEl = document.getElementById('landingMap');
            if (!mapEl) return;
            const validEvents = mapEvents.filter(e => Number(e.latitude) && Number(e.longitude));
            if (validEvents.length === 0) {
                mapEl.innerHTML = `<p class="text-muted text-center py-4">${isAr ? 'لا توجد تدخلات لعرضها.' : 'Aucune intervention à afficher.'}</p>`;
                return;
            }
            if (typeof L === 'undefined') {
                mapEl.innerHTML = `<div class="map-fallback" role="alert"><i class="mdi mdi-map-marker-off"></i><strong>${noMapTitle}</strong><span>${isAr ? 'تعذر تحميل مكتبة الخريطة.' : 'Impossible de charger la bibliothèque cartographique.'}</span></div>`;
                return;
            }

            const points = validEvents
                .map(e => [Number(e.latitude), Number(e.longitude)])
                .filter(p => Number.isFinite(p[0]) && Number.isFinite(p[1]));

            const map = L.map('landingMap', { scrollWheelZoom: false });

            const tileUrl = mapCfg.style === 'dark'
                ? 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png'
                : 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png';
            L.tileLayer(tileUrl, {
                attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
                subdomains: 'abcd',
                maxZoom: 19
            }).addTo(map);

            if (mapCfg.heatmap && typeof L.heatLayer === 'function' && points.length > 1) {
                L.heatLayer(points, {
                    radius: 34,
                    blur: 22,
                    maxZoom: 12,
                    gradient: { 0.3: '#2a7a3e', 0.6: '#0d9488', 1: '#eab308' }
                }).addTo(map);
            }

            validEvents.forEach(e => {
                const lat = Number(e.latitude);
                const lng = Number(e.longitude);
                if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
                L.marker([lat, lng])
                    .bindPopup(`<strong>${escAttr(e.adresse || '')}</strong><br><small>${escAttr(e.commune_nom || '')} · ${escAttr(e.date_evenement || '')}</small>`)
                    .addTo(map);
            });

            if (mapCfg.zoom > 0 && Number.isFinite(mapCfg.lat) && Number.isFinite(mapCfg.lng) && (mapCfg.lat || mapCfg.lng)) {
                map.setView([mapCfg.lat, mapCfg.lng], mapCfg.zoom);
            } else if (points.length === 1) {
                map.setView(points[0], 12);
            } else {
                map.fitBounds(points, { padding: [36, 36], maxZoom: 13 });
            }

            let tileFails = 0;
            map.on('tileerror', () => {
                tileFails += 1;
                if (tileFails >= 4 && !mapEl.querySelector('.map-tile-note')) {
                    const note = document.createElement('div');
                    note.className = 'map-tile-note';
                    note.setAttribute('role', 'alert');
                    note.innerHTML = `<i class="mdi mdi-wifi-off"></i><span>${isAr ? 'تعذر تحميل الخريطة — تحقق من الاتصال بالإنترنت.' : 'Impossible de charger la carte — vérifiez votre connexion Internet.'}</span>`;
                    mapEl.appendChild(note);
                }
            });
        })();

        // ─── GSAP ANIMATIONS ───
        async function loadGSAP() {
            const { default: gsap } = await import('https://cdn.esm.sh/gsap');
            const { ScrollTrigger } = await import('https://cdn.esm.sh/gsap/ScrollTrigger');
            gsap.registerPlugin(ScrollTrigger);

            document.querySelectorAll('[data-reveal]').forEach(el => {
                const delay = parseFloat(el.dataset.revealDelay) / 1000 || 0;
                gsap.from(el, {
                    scrollTrigger: { trigger: el, toggleActions: 'play none none none' },
                    y: 40,
                    opacity: 0,
                    duration: 0.9,
                    delay: delay,
                    ease: 'power3.out'
                });
            });

            document.querySelectorAll('[data-count]').forEach(el => {
                const target = parseInt(el.dataset.count);
                gsap.from(el, {
                    scrollTrigger: { trigger: el, toggleActions: 'play none none none' },
                    textContent: 0,
                    duration: 2,
                    ease: 'power1.out',
                    onUpdate: () => { el.textContent = Math.round(el.textContent); },
                    onComplete: () => { el.textContent = target; }
                });
            });
        }
        loadGSAP();

        // ─── BEFORE / AFTER ───
        document.querySelectorAll('.ba-slider input[type="range"]').forEach(slider => {
            const afterImg = slider.parentElement.querySelector('.ba-after-img');
            const handle = slider.parentElement.querySelector('.ba-handle');
            slider.addEventListener('input', () => {
                const val = slider.value;
                if (afterImg) afterImg.style.clipPath = `inset(0 ${100 - val}% 0 0)`;
                if (handle) handle.style.left = val + '%';
            });
        });

        // ─── ALBUMS LIGHTBOX AMÉLIORÉE ───
        window.__albumsData = albumsData;
        let __currentAlbumId = null;
        let __currentPhotoIndex = 0;
        let __lightboxImages = [];

        window.openAlbumLightbox = function(albumId) {
            const album = window.__albumsData.find(a => Number(a.id) === Number(albumId));
            if (!album) {
                console.warn('Album introuvable:', albumId);
                return;
            }
            __currentAlbumId = Number(albumId);
            // Construction de la liste des photos (couverture + photos)
            let images = [];
            if (album.couverture) {
                images.push({ image: album.couverture, legende: album.titre || '' });
            }
            if (album.photos && Array.isArray(album.photos)) {
                album.photos.forEach(p => {
                    if (p && p.image) {
                        images.push({ image: p.image, legende: p.legende || '' });
                    }
                });
            }
            if (images.length === 0) {
                images.push({ image: null, legende: 'Aucune image' });
            }
            __lightboxImages = images;
            // Récit
            const narrativeText = album.recit || (album.adresse ? album.adresse + ' — ' : '') + (album.date_evenement ? new Date(album.date_evenement).toLocaleDateString() : '');
            document.getElementById('lightboxNarrativeText').textContent = narrativeText;
            // Affichage
            __currentPhotoIndex = 0;
            showLightboxPhoto();
            const box = document.getElementById('albumLightbox');
            box.setAttribute('aria-hidden', 'false');
            box.classList.add('open');
            document.body.classList.add('wh-lb-open');
        };

        function showLightboxPhoto() {
            const imgs = __lightboxImages;
            const idx = __currentPhotoIndex;
            const img = imgs[idx];
            const imgEl = document.getElementById('lightboxImg');
            if (img && img.image) {
                imgEl.src = assetPath(img.image);
                imgEl.alt = img.legende || '';
                imgEl.style.display = '';
            } else {
                imgEl.removeAttribute('src');
                imgEl.style.display = 'none';
            }
            document.getElementById('lightboxCounter').textContent = (idx + 1) + ' / ' + imgs.length;
            const cap = document.getElementById('lightboxCaption');
            cap.textContent = img && img.legende ? img.legende : '';
            cap.style.display = (img && img.legende) ? '' : 'none';
            const prevBtn = document.querySelector('[data-lb-prev]');
            const nextBtn = document.querySelector('[data-lb-next]');
            if (prevBtn) prevBtn.style.visibility = imgs.length > 1 ? 'visible' : 'hidden';
            if (nextBtn) nextBtn.style.visibility = imgs.length > 1 ? 'visible' : 'hidden';
        }

        window.closeAlbumLightbox = function() {
            document.getElementById('albumLightbox').classList.remove('open');
            document.getElementById('albumLightbox').setAttribute('aria-hidden', 'true');
            document.body.classList.remove('wh-lb-open');
            __currentAlbumId = null;
        };

        function stepLightbox(dir) {
            const imgs = __lightboxImages;
            if (imgs.length === 0) return;
            __currentPhotoIndex = (__currentPhotoIndex + dir + imgs.length) % imgs.length;
            showLightboxPhoto();
        }

        document.addEventListener('click', e => {
            if (e.target.closest('[data-lb-close]')) { window.closeAlbumLightbox(); return; }
            if (e.target.closest('[data-lb-prev]')) { stepLightbox(-1); return; }
            if (e.target.closest('[data-lb-next]')) { stepLightbox(1); return; }
        });
        document.addEventListener('keydown', e => {
            const box = document.getElementById('albumLightbox');
            if (!box.classList.contains('open')) return;
            if (e.key === 'Escape') { window.closeAlbumLightbox(); }
            else if (e.key === 'ArrowLeft') { stepLightbox(-1); }
            else if (e.key === 'ArrowRight') { stepLightbox(1); }
        });

        // ─── FILTRES ALBUMS ───
        const filterContainer = document.querySelector('.album-filters-row');
        if (filterContainer) {
            filterContainer.addEventListener('click', e => {
                const btn = e.target.closest('.filter-btn');
                if (!btn) return;
                filterContainer.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                const filterVal = btn.dataset.filter;
                document.querySelectorAll('.albums-grid .album-card').forEach(card => {
                    if (filterVal === 'all') { card.style.display = ''; return; }
                    const filterId = filterVal.replace('anomaly-', '');
                    const anomalies = (card.dataset.anomalies || '').split(',');
                    const show = anomalies.indexOf(filterId) !== -1;
                    card.style.display = show ? '' : 'none';
                });
            });
        }

        // ─── IA ASSISTANT — 100% CLIENT-SIDE ───
        (function initIA() {
            var toggle    = document.getElementById('iaToggle');
            var win       = document.getElementById('iaWindow');
            var closeBtn  = document.getElementById('iaClose');
            var form      = document.getElementById('iaForm');
            var input     = document.getElementById('iaInput');
            var messages  = document.getElementById('iaMessages');
            var typing    = document.getElementById('iaTyping');
            var quickWrap = document.getElementById('iaQuickReplies');
            var chatLang  = document.documentElement.dir === 'rtl' ? 'ar' : 'fr';
            var isOpen = false;
            var chatHistory = [];

            /* ── Open / Close ── */
            function openChat() { isOpen = true; win.classList.add('open'); input.focus(); }
            function closeChat() { isOpen = false; win.classList.remove('open'); }
            function toggleChat() { isOpen ? closeChat() : openChat(); }
            if (toggle) toggle.addEventListener('click', toggleChat);
            if (closeBtn) closeBtn.addEventListener('click', closeChat);
            document.addEventListener('keydown', function(e) { if (e.key === 'Escape' && isOpen) closeChat(); });

            /* ── Typing ── */
            function showTyping() { typing.classList.add('active'); messages.scrollTop = messages.scrollHeight; }
            function hideTyping() { typing.classList.remove('active'); }

            /* ── Add message ── */
            function addMessage(text, sender, animate) {
                var row = document.createElement('div');
                row.className = 'ia-msg-row ' + sender;
                if (sender === 'bot') {
                    var av = document.createElement('div');
                    av.className = 'ia-msg-avatar';
                    av.innerHTML = '<i class="mdi mdi-robot"></i>';
                    row.appendChild(av);
                }
                var bubble = document.createElement('div');
                bubble.className = 'ia-msg ' + sender;
                bubble.textContent = text;
                row.appendChild(bubble);
                if (animate) { row.style.opacity = '0'; }
                messages.appendChild(row);
                if (animate) {
                    requestAnimationFrame(function() {
                        row.style.transition = 'opacity .3s';
                        row.style.opacity = '1';
                    });
                }
                messages.scrollTop = messages.scrollHeight;
            }

            /* ── Render quick replies ── */
            function renderQuickReplies(replies) {
                if (!quickWrap) return;
                quickWrap.innerHTML = '';
                replies.forEach(function(r) {
                    var btn = document.createElement('button');
                    btn.className = 'ia-quick-btn';
                    btn.innerHTML = '<i class="mdi ' + r.icon + '"></i> ' + (chatLang === 'ar' ? r.ar : r.fr);
                    btn.addEventListener('click', function() { sendText(chatLang === 'ar' ? r.ar : r.fr); });
                    quickWrap.appendChild(btn);
                });
            }

            var QUICKS = [
                { key:'events', fr:'Événements à venir', ar:'فعاليات قادمة', icon:'mdi-calendar-star' },
                { key:'join',   fr:'Rejoindre une association', ar:'الانضمام لجمعية', icon:'mdi-account-group' },
                { key:'signal', fr:'Signaler un problème', ar:'الإبلاغ عن مشكلة', icon:'mdi-alert-circle' },
                { key:'contact',fr:'Contacter la Wilaya', ar:'التواصل مع الولاية', icon:'mdi-phone' },
            ];

            /* ── Collect data from page DOM ── */
            function getStats() {
                var stats = {};
                document.querySelectorAll('.stat-card').forEach(function(card) {
                    var val = card.querySelector('.stat-value');
                    var lbl = card.querySelector('.stat-label');
                    if (val && lbl) stats[lbl.textContent.trim()] = val.textContent.trim();
                });
                return stats;
            }

            function getFAQ() {
                var faqs = [];
                document.querySelectorAll('.faq-item').forEach(function(item) {
                    var q = item.querySelector('.faq-question');
                    var a = item.querySelector('.faq-answer');
                    if (q && a) faqs.push({ q: q.textContent.trim(), a: a.textContent.trim() });
                });
                return faqs;
            }

            function getUpcoming() {
                var events = [];
                document.querySelectorAll('.upcoming-card').forEach(function(card) {
                    var title = card.querySelector('.event-title, .card-title, h3');
                    var date  = card.querySelector('.event-date, .card-date, time');
                    if (title) events.push({ title: title.textContent.trim(), date: date ? date.textContent.trim() : '' });
                });
                return events;
            }

            /* ── Smart pattern matching (100% client-side) ── */
            function getReply(text) {
                var l = text.toLowerCase().replace(/[?!.,;:'"()]/g, '').trim();

                // ── Greetings ──
                if (/^(bonjour|salut|hello|coucou|bonsoir|hey|yo|hi|bonjour|ahlan|marhaba|salam)/.test(l)) {
                    return chatLang === 'ar'
                        ? 'أهلاً وسهلاً! 👋 أنا مساعد حومتي ايفانت الذكي. كيف يمكنني مساعدتك اليوم؟'
                        : 'Bonjour ! 👋 Je suis l\'assistant de حومتي ايفانت. Comment puis-je vous aider ?';
                }

                // ── Thanks ──
                if (/^(merci|thanks|shukran|jazak|barak)/.test(l)) {
                    return chatLang === 'ar'
                        ? 'على الرحب والسعة! لا تتردد في طرح أي سؤال آخر. 😊'
                        : 'Avec plaisir ! N\'hésitez pas à poser d\'autres questions. 😊';
                }

                // ── Goodbye ──
                if (/^(au revoir|bye|a bientot|tchao|وداعا|مع السلامة)/.test(l)) {
                    return chatLang === 'ar'
                        ? 'مع السلامة! نتمنى لك يوماً سعيداً. 👋'
                        : 'Au revoir ! Bonne journée à vous. 👋';
                }

                // ── Events ──
                if (/événement|event|activité|manifestation|festival|قادم|فعاليات|حدث|fn|activity/.test(l)) {
                    var evs = getUpcoming();
                    if (evs.length > 0) {
                        var lines = chatLang === 'ar' ? ['📅 الفعاليات القادمة:'] : ['📅 Prochains événements :'];
                        evs.forEach(function(e) { lines.push('• ' + e.title + (e.date ? ' — ' + e.date : '')); });
                        return lines.join('\n');
                    }
                    return chatLang === 'ar'
                        ? 'لا توجد فعاليات قادمة حالياً. تابعنا للحصول على آخر الأخبار!'
                        : 'Aucun événement à venir pour le moment. Restez connecté !';
                }

                // ── Associations ──
                if (/association|rejoindre|membre|adhérer|انضم|جمعية|عضو|اشترك|join/.test(l)) {
                    return chatLang === 'ar'
                        ? '🤝 للانضمام لجمعية:\n\n1️⃣ اذهب إلى صفحة "الجمعيات"\n2️⃣ اختر الجمعية المناسبة\n3️⃣ قدم طلبك عبر الإنترنت\n\nستتلقى تأكيداً بعد مراجعة طلبك.'
                        : '🤝 Pour rejoindre une association :\n\n1️⃣ Rendez-vous sur la page « Associations »\n2️⃣ Choisissez celle qui vous convient\n3️⃣ Faites votre demande en ligne\n\nVous recevrez une confirmation après traitement.';
                }

                // ── Report ──
                if (/signaler|problème|anomalie|bug|erreur|signal|إبلاغ|شكوى|مشكلة|عطل|report/.test(l)) {
                    return chatLang === 'ar'
                        ? '📢 للإبلاغ عن مشكلة:\n\n1️⃣ سجّل الدخول إلى حسابك\n2️⃣ انتقل إلى لوحة التحكم\n3️⃣ اضغط على "الإبلاغ"\n4️⃣ اختر نوع المشكلة وأرسل التقرير\n\nسيتم مراجعة بلاغك من الولاية.'
                        : '📢 Pour signaler un problème :\n\n1️⃣ Connectez-vous à votre compte\n2️⃣ Accédez à votre tableau de bord\n3️⃣ Cliquez sur « Signaler »\n4️⃣ Choisissez le type et soumettez\n\nVotre signalement sera traité par la Wilaya.';
                }

                // ── Contact ──
                if (/contact|téléphone|tel|email|appel|wilaya|consul|اتصل|تواصل|هاتف|بريد|phone/.test(l)) {
                    return chatLang === 'ar'
                        ? '📞 التواصل مع الولاية:\n\n📧 wilaya@wilaya-harmonia.dz\n🌐 استخدم نموذج الاتصال في أسفل الصفحة\n⏰ متاحون الأحد–الخميس 8 صباحاً–4 مساءً'
                        : '📞 Contactez la Wilaya :\n\n📧 wilaya@wilaya-harmonia.dz\n🌐 Formulaire de contact en bas de page\n⏰ Dimanche–Jeudi, 8h–16h';
                }

                // ── Login / Account ──
                if (/connexion|login|se connecter|compte|inscription|تسجيل|دخول|حساب|register|mot de passe|password/.test(l)) {
                    return chatLang === 'ar'
                        ? '🔐 للوصول إلى حسابك:\n\n👤 citizen → صفحة تسجيل الدخول العادية\n🏛️ جمعية → حساب association\n\nنسيت كلمة المرور؟ استخدم رابط "نسيت كلمة المرور".'
                        : '🔐 Pour accéder à votre compte :\n\n👤 Citoyen → page de connexion standard\n🏛️ Association → compte association\n\nMot de passe oublié ? Cliquez sur « Mot de passe oublié ».';
                }

                // ── Stats ──
                if (/stat|nombre|combien|total|données|إحصائي|عدد|chiffre/.test(l)) {
                    var s = getStats();
                    var keys = Object.keys(s);
                    if (keys.length > 0) {
                        var lines = chatLang === 'ar' ? ['📊 إحصائيات حومتي ايفانت:'] : ['📊 Statistiques de حومتي ايفانت :'];
                        keys.forEach(function(k) { lines.push('• ' + k + ' : ' + s[k]); });
                        return lines.join('\n');
                    }
                    return chatLang === 'ar'
                        ? '📊 منصة حومتي ايفانت تربط المواطنين والجمعيات في ولاية الحومة.'
                        : '📊 حومتي ايفانت connecte citoyens et associations de la Wilaya.';
                }

                // ── FAQ match ──
                var faqs = getFAQ();
                for (var i = 0; i < faqs.length; i++) {
                    var qWords = faqs[i].q.toLowerCase().split(/\s+/);
                    var matchCount = 0;
                    qWords.forEach(function(w) { if (w.length > 2 && l.indexOf(w) !== -1) matchCount++; });
                    if (matchCount >= 2 || l.indexOf(faqs[i].q.toLowerCase().substring(0, 15)) !== -1) {
                        return faqs[i].a;
                    }
                }

                // ── What is / who are you ──
                if (/quoi|c'est quoi|qu'est|who|what|man|شأن|من أنت|ما هو|تعريف/.test(l)) {
                    return chatLang === 'ar'
                        ? '🤖 حومتي ايفانت منصة رقمية مواطينة تربط المواطنين بالجمعيات والفعاليات في ولاية الحومة.\n\nيمكنك من خلالها:\n• متابعة الفعاليات\n• الانضمام للجمعيات\n• الإبلاغ عن المشاكل\n• التواصل مع الولاية'
                        : '🤖 حومتي ايفانت est une plateforme citoyenne qui connecte les habitants, associations et événements de la Wilaya.\n\nVous pouvez :\n• Suivre les événements\n• Rejoindre des associations\n• Signaler des problèmes\n• Contacter la Wilaya';
                }

                // ── Help ──
                if (/aide|help|comment|كيف|مساعدة|sa'ida/.test(l)) {
                    return chatLang === 'ar'
                        ? '💡 كيف يمكنني مساعدتك؟ جرّب أن تسأل عن:\n\n• الفعاليات القادمة\n• الانضمام لجمعية\n• الإبلاغ عن مشكلة\n• التواصل مع الولاية\n• تسجيل الدخول\n• إحصائيات المنصة'
                        : '💡 Comment puis-je vous aider ? Essayez de demander :\n\n• Les événements à venir\n• Rejoindre une association\n• Signaler un problème\n• Contacter la Wilaya\n• Se connecter\n• Les statistiques';
                }

                // ── Fallback ──
                return chatLang === 'ar'
                    ? '🤔 لم أفهم سؤالك تماماً. يمكنك:\n\n• إعادة صياغة سؤالك\n• استخدام أزرار المواضيع أدناه\n• السؤال عن: الفعاليات، الجمعيات، الإبلاغ، التواصل'
                    : '🤔 Je n\'ai pas bien compris. Vous pouvez :\n\n• Reformuler votre question\n• Utiliser les boutons de sujets ci-dessous\n• Demander sur : événements, associations, signalement, contact';
            }

            /* ── Send ── */
            function sendText(text) {
                if (!text || !text.trim()) return;
                addMessage(text, 'user', false);
                input.value = '';
                showTyping();

                var reply = getReply(text);

                setTimeout(function() {
                    hideTyping();
                    addMessage(reply, 'bot', true);
                    renderQuickReplies(QUICKS);
                }, 400 + Math.random() * 400);
            }

            form.addEventListener('submit', function(e) { e.preventDefault(); sendText(input.value); });
            renderQuickReplies(QUICKS);
        })();

        console.log('🌿 Landing Nature & Environnement (avec vidéo et albums améliorés) chargée.');
    </script>

    <script>
    // PWA — Install (QR serveur) — Fix ثبّت الآن
    (function(){
        var btn=document.getElementById('pwaInstallBtn'), badge=document.getElementById('pwaInstalledBadge');
        var url=location.origin + '/';
        var deferred=null;
        var isIos = /iphone|ipad|ipod/i.test(navigator.userAgent);
        var isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;

        function showHelpModal(msg){
            var help=document.getElementById('pwaHelp');
            if(help) help.scrollIntoView({behavior:'smooth', block:'center'});
            var t=document.createElement('div');
            t.textContent=msg;
            t.style.cssText='position:fixed;bottom:1.5rem;left:50%;transform:translateX(-50%);background:#0F2B22;color:#FAF6EC;padding:.75rem 1.1rem;border-radius:999px;z-index:9999;box-shadow:0 8px 24px rgba(0,0,0,.25);font-size:.85rem;max-width:90vw;text-align:center';
            document.body.appendChild(t);
            setTimeout(function(){ t.remove(); }, 4000);
            if(help){
                help.style.outline='3px solid #FFD75E';
                help.style.outlineOffset='4px';
                help.style.borderRadius='1rem';
                setTimeout(function(){ help.style.outline=''; help.style.outlineOffset=''; }, 2000);
            }
        }

        window.addEventListener('beforeinstallprompt',function(e){
            e.preventDefault();
            deferred=e;
            if(btn){ btn.disabled=false; btn.style.opacity='1'; btn.title='Cliquez pour installer'; }
            console.log('[PWA] beforeinstallprompt prêt');
        });
        window.addEventListener('appinstalled',function(){
            if(badge) badge.style.display='inline-flex';
            if(btn) btn.style.display='none';
            localStorage.setItem('pwa_installed','1');
            console.log('[PWA] installée');
        });

        if(btn){
            btn.addEventListener('click', async function(e){
                e.preventDefault();
                console.log('[PWA] click, deferred:', !!deferred, 'isIos:', isIos, 'standalone:', isStandalone);
                if(isStandalone){
                    showHelpModal('<?= $isAr ? 'التطبيق مثبت بالفعل' : 'Application déjà installée' ?> ✓');
                    return;
                }
                if(deferred){
                    try{
                        deferred.prompt();
                        var c=await deferred.userChoice;
                        console.log('[PWA] choice:', c.outcome);
                        if(c.outcome==='accepted'){
                            if(typeof gtag!=='undefined') gtag('event','pwa_install');
                            if(badge) badge.style.display='inline-flex';
                            btn.style.display='none';
                        } else {
                            showHelpModal('<?= $isAr ? 'يمكنك التثبيت لاحقاً من القائمة' : 'Vous pourrez installer depuis ⋮ → Installer' ?>');
                        }
                        deferred=null;
                    } catch(err){ console.error(err); showHelpModal('Erreur: '+err.message); }
                    return;
                }
                // Fallback sans prompt
                if(isIos){
                    showHelpModal('<?= $isAr ? 'iPhone: شارك → إضافة إلى الشاشة الرئيسية' : 'iPhone : Partager → Sur l’écran d’accueil' ?>');
                    return;
                }
                // Vérifier si PWA installable mais prompt pas encore déclenché (besoin d'interaction)
                if(location.protocol!=='https:' && location.hostname!=='localhost' && location.hostname!=='127.0.0.1'){
                    showHelpModal('<?= $isAr ? 'التثبيت يتطلب HTTPS' : 'Installation nécessite HTTPS' ?>');
                    return;
                }
                // Desktop Chrome sans prompt → afficher aide
                showHelpModal('<?= $isAr ? 'Chrome: ⋮ → تثبيت التطبيق  أو  شريط العنوان → تثبيت' : 'Chrome : ⋮ → Installer l’app  ou  barre d’adresse → Installer' ?>');
                document.getElementById('pwaHelp')?.scrollIntoView({behavior:'smooth', block:'center'});
                // aussi proposer partage
                if(navigator.share){
                    setTimeout(function(){ navigator.share({title:document.title, url:url}).catch(function(){}); }, 800);
                }
            });
        }
        // déjà installée ?
        if(isStandalone || localStorage.getItem('pwa_installed')==='1'){
            if(badge) badge.style.display='inline-flex';
            if(btn) btn.style.display='none';
        }
        // SW + debug
        if('serviceWorker' in navigator){
            navigator.serviceWorker.register('/sw.js').then(function(r){ console.log('[PWA] SW ok', r.scope); }).catch(function(e){ console.error('[PWA] SW fail', e); });
            navigator.serviceWorker.addEventListener('controllerchange', function(){ console.log('[PWA] SW updated'); });
        } else {
            console.warn('[PWA] SW non supporté');
            if(btn) btn.title='PWA non supporté sur ce navigateur';
        }
        // Exposer pour debug console
        window.__pwaDebug = function(){ return {deferred:!!deferred, isIos:isIos, isStandalone:isStandalone, sw: 'serviceWorker' in navigator, protocol:location.protocol}; };
    })();
    </script>
    <script nomodule>
        console.warn('Votre navigateur ne supporte pas les modules ES.');
    </script>
</body>
</html>
