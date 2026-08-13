<?php
/** @var array $upcoming @var array $stats @var array $faq @var array $testimonials @var array $partners @var array $albums @var array $anomalies @var array $beforeAfter @var array $gallery @var array $mapEvents @var int $totalParticipants */
use App\Helpers\Database;
use App\Helpers\I18n;

$title = '';
$pick = static fn(string $fr, string $ar) => I18n::pick($fr, $ar);
$isAr = I18n::direction() === 'rtl';
$ordre = settings('sections_order', ['actualites', 'apropos', 'fonctionnement', 'anomalies', 'albums', 'temoignages', 'partenaires', 'faq', 'timeline', 'before-after']);
$ordre = is_array($ordre) ? $ordre : ['actualites', 'apropos', 'fonctionnement', 'anomalies', 'albums', 'temoignages', 'partenaires', 'faq', 'timeline', 'before-after'];
$visible = static fn(string $section): bool => (string) settings('section_' . $section . '_visible', '1') === '1';

$heroTitre = $pick((string) settings('hero_titre_fr', ''), (string) settings('hero_titre_ar', ''));
$heroSub   = $pick((string) settings('hero_sous_titre_fr', ''), (string) settings('hero_sous_titre_ar', ''));
$timelineEvents = settings('timeline_events', []);
if (!is_array($timelineEvents)) $timelineEvents = [];

// Fonction de sÃ©curitÃ© pour le badge association (si elle n'existe pas dÃ©jÃ )
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
?>
<!DOCTYPE html>
<html lang="<?= $isAr ? 'ar' : 'fr' ?>" dir="<?= I18n::direction() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($heroTitre ?: __('app.name')) ?></title>
    <link rel="manifest" href="<?= url('manifest.json') ?>">
    <meta name="theme-color" content="#fcf9f2">
    <meta name="description" content="<?= e($heroSub ?: __('app.tagline')) ?>">
    
    <link rel="preconnect" href="https://api.mapbox.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7/css/materialdesignicons.min.css">

    <style>
        /* â•â•â• THÃˆME CLAIR NATURE â•â•â• */
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

        /* â•â•â• HERO AVEC VIDÃ‰O â•â•â• */
        .hero {
            position: relative; min-height: 100vh;
            display: flex; align-items: center; overflow: hidden;
            padding: 4rem 0 2rem;
            background: #1b2a1f;
        }
        .hero-video {
            position: absolute; top:0; left:0; width:100%; height:100%;
            object-fit: cover; z-index:0; opacity:0.35;
        }
        .hero-video-fallback {
            position: absolute; top:0; left:0; width:100%; height:100%;
            z-index:0;
            background: linear-gradient(135deg, #1b2a1f, #2a4a3a, #0d9488);
            opacity:0.6;
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

        /* â•â•â• ALBUMS AMÃ‰LIORÃ‰S â•â•â• */
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

        /* Autres sections (inchangÃ©es) */
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
        .faq-item[open] summary::after { content:'âˆ’'; }
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
        .landing-map .mapboxgl-ctrl { display:none; }
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
        #ia-assistant { position:fixed; bottom:2rem; right:2rem; z-index:8000; }
        .ia-toggle {
            width:64px; height:64px; border-radius:50%;
            background:var(--accent); color:#fff; border:none; font-size:2rem;
            box-shadow:0 12px 40px rgba(42,122,62,0.3); cursor:pointer; transition:0.3s;
            display:grid; place-items:center;
        }
        .ia-toggle:hover { transform:scale(1.05); background:#1f5e2e; }
        .ia-window {
            position:absolute; bottom:80px; right:0;
            width:360px; height:480px;
            background:#fff; border:1px solid var(--glass-border);
            border-radius:var(--radius); box-shadow:var(--shadow);
            display:none; flex-direction:column; overflow:hidden;
        }
        .ia-window.open { display:flex; }
        .ia-header {
            padding:1rem; background:var(--accent); color:#fff;
            font-weight:700; display:flex; justify-content:space-between; align-items:center;
        }
        .ia-messages { flex:1; padding:1rem; overflow-y:auto; display:flex; flex-direction:column; gap:0.5rem; }
        .ia-msg {
            padding:0.6rem 1rem; border-radius:1rem; max-width:80%;
            background:var(--bg-secondary); border:1px solid var(--glass-border);
            color:var(--text-primary);
        }
        .ia-msg.bot { align-self:flex-start; background:var(--accent); color:#fff; border:none; }
        .ia-msg.user { align-self:flex-end; background:#e8f0e8; }
        .ia-input {
            display:flex; padding:0.5rem; border-top:1px solid var(--glass-border);
            gap:0.5rem;
        }
        .ia-input input {
            flex:1; padding:0.6rem; border-radius:999px;
            border:1px solid var(--glass-border); background:var(--bg-primary);
            color:var(--text-primary);
        }
        .ia-input button {
            background:var(--accent); border:none; color:#fff;
            padding:0.6rem 1.2rem; border-radius:999px; cursor:pointer;
        }
        @media (max-width:1024px) {
            .hero-inner { grid-template-columns:1fr; text-align:center; }
            .hero-content { max-width:100%; }
            .hero-actions { justify-content:center; }
            .hero-trust { justify-content:center; }
            .hero-title { font-size:2.8rem; }
            #globe-container { height:300px; }
            .gallery-grid { column-count:2; }
        }
        @media (max-width:640px) {
            .section { padding:3rem 0; }
            .section-title { font-size:2rem; }
            .hero-title { font-size:2.2rem; }
            .cards-grid, .services-grid, .albums-grid, .before-after-grid { grid-template-columns:1fr; }
            .gallery-grid { column-count:1; }
            .ia-window { width:300px; right:-20px; }
            .hero-actions .btn { width:100%; justify-content:center; }
        }
        .sr-only { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); border:0; }
        :focus-visible { outline:3px solid var(--accent); outline-offset:2px; }
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

        <!-- â•â•â• HERO AVEC VIDÃ‰O DE NATURE â•â•â• -->
        <section class="hero" id="hero" aria-label="<?= e(__('landing.hero_badge')) ?>">
            <!-- VidÃ©o de nature -->
            <video class="hero-video" autoplay muted loop playsinline aria-hidden="true" preload="auto">
                <source src="<?= e((string) settings('hero_video_url', asset('/assets/video/hero.mp4'))) ?>" type="video/mp4">
            </video>
            <!-- DÃ©gradÃ© de secours -->
            <div class="hero-video-fallback" aria-hidden="true"></div>

            <canvas id="aurora-canvas" class="hero-aurora" aria-hidden="true"></canvas>
            <div class="hero-particles" aria-hidden="true">
                <?php for($i=0;$i<20;$i++): ?><i style="top:<?= rand(0,100)?>%;left:<?= rand(0,100)?>%;animation-delay:<?= rand(0,20)?>s"></i><?php endfor; ?>
            </div>

            <div class="container hero-inner">
                <div class="hero-content" data-reveal>
                    <span class="hero-badge">
                        <i class="mdi mdi-leaf"></i> <?= $isAr ? 'Ù…Ù†ØµØ© Ø®Ø¶Ø±Ø§Ø¡' : 'Plateforme verte' ?>
                    </span>
                    <h1 class="hero-title">
                        <?= e($heroTitre ?: __('app.name')) ?>
                        <span class="hero-title-accent"><?= $isAr ? 'Ù…Ø¹Ø§Ù‹ Ù„Ø¨ÙŠØ¦Ø© Ø£ÙØ¶Ù„' : 'Ensemble pour une meilleure nature' ?></span>
                    </h1>
                    <p class="hero-sub"><?= e($heroSub ?: __('app.tagline')) ?></p>
                    <div class="hero-actions">
                        <a class="btn btn-primary btn-lg" href="#carte"><i class="mdi mdi-tree"></i><?= e(__('landing.cta_explorer')) ?></a>
                        <a class="btn btn-outline btn-lg" href="<?= url('auth/register') ?>"><i class="mdi mdi-account-plus-outline"></i><?= e(__('landing.cta_register')) ?></a>
                        <a class="btn btn-outline btn-lg" href="<?= url('auth/register-association') ?>"><i class="mdi mdi-domain"></i><?= e(__('associations.inscription')) ?></a>
                    </div>
                    <div class="hero-trust">
                        <div class="trust-avatars"><span class="t-avatar">ðŸŒ±</span><span class="t-avatar">ðŸŒ¿</span><span class="t-avatar">ðŸŒ³</span><span class="t-avatar">+</span></div>
                        <span><strong>+<?= (int) $totalParticipants ?></strong> <?= $isAr ? 'Ù…Ø´Ø§Ø±ÙƒØ© Ù…ÙˆØ§Ø·Ù†Ø©' : __('landing.citoyen_participations') ?></span>
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
                                <span class="particle p1">ðŸŒ¿</span>
                                <span class="particle p2">ðŸƒ</span>
                                <span class="particle p3">ðŸŒ±</span>
                                <span class="particle p4">ðŸ’š</span>
                                <span class="particle p5">ðŸŒ³</span>
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

        <!-- â•â•â• SECTIONS DYNAMIQUES â•â•â• -->
        <?php $idx = 0; ?>
        <?php foreach ($ordre as $section): ?>
            <?php if (! $visible((string) $section)) continue; ?>
            <?php $alt = ($idx % 2 === 1); $idx++; ?>

            <?php if ($section === 'actualites'): ?>
                <section class="section section-news<?= $alt ? ' bg-muted' : '' ?>" id="actualites">
                    <div class="container">
                        <div class="section-head" data-reveal>
                            <span class="eyebrow"><i class="mdi mdi-newspaper-variant-outline"></i><?= $isAr ? 'Ø¢Ø®Ø± Ø§Ù„Ø£Ø®Ø¨Ø§Ø±' : 'ActualitÃ©s & Ã‰vÃ©nements' ?></span>
                            <h2 class="section-title"><?= $isAr ? 'Ø§Ù„Ø£Ø®Ø¨Ø§Ø± ÙˆØ§Ù„Ø£Ø­Ø¯Ø§Ø« Ø§Ù„Ù‚Ø§Ø¯Ù…Ø©' : 'ActualitÃ©s & Ã©vÃ©nements Ã  venir' ?></h2>
                            <p class="section-lead"><?= $isAr ? 'ØªØ§Ø¨Ø¹ Ø¢Ø®Ø± Ø§Ù„Ø£Ø®Ø¨Ø§Ø± ÙˆØ§Ù„ÙØ¹Ø§Ù„ÙŠØ§Øª Ø§Ù„Ù…Ø¨Ø±Ù…Ø¬Ø© Ø¹Ø¨Ø± Ø§Ù„ÙˆÙ„Ø§ÙŠØ©.' : 'Restez informÃ© des derniÃ¨res nouvelles et des prochaines activitÃ©s.' ?></p>
                        </div>

                        <!-- Filtres -->
                        <div class="news-filters" data-reveal>
                            <button class="news-filter-btn active" data-filter="all">
                                <i class="mdi mdi-apps"></i> <?= $isAr ? 'Ø§Ù„ÙƒÙ„' : 'Tout' ?>
                            </button>
                            <button class="news-filter-btn" data-filter="actualite">
                                <i class="mdi mdi-newspaper"></i> <?= $isAr ? 'Ø£Ø®Ø¨Ø§Ø±' : 'ActualitÃ©s' ?>
                            </button>
                            <button class="news-filter-btn" data-filter="evenement">
                                <i class="mdi mdi-calendar-star"></i> <?= $isAr ? 'Ø£Ø­Ø¯Ø§Ø«' : 'Ã‰vÃ©nements' ?>
                            </button>
                        </div>

                        <!-- Grille actualitÃ©s/Ã©vÃ©nements -->
                        <div class="news-grid" id="newsGrid">
                            <?php foreach ($news as $item): ?>
                                <div class="news-card" data-type="<?= e($item['type']) ?>" data-reveal>
                                    <?php if ($item['image']): ?>
                                        <div class="news-card-image">
                                            <img src="<?= e(asset($item['image'])) ?>" alt="<?= e($item['titre_fr']) ?>" loading="lazy">
                                            <span class="news-type-badge <?= $item['type'] === 'evenement' ? 'type-event' : 'type-news' ?>">
                                                <i class="mdi mdi-<?= $item['type'] === 'evenement' ? 'calendar-star' : 'newspaper' ?>"></i>
                                                <?= $item['type'] === 'evenement' ? ($isAr ? 'Ø­Ø¯Ø«' : 'Ã‰vÃ©nement') : ($isAr ? 'Ø®Ø¨Ø±' : 'ActualitÃ©') ?>
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <div class="news-card-image news-card-placeholder">
                                            <i class="mdi mdi-<?= $item['type'] === 'evenement' ? 'calendar-star' : 'newspaper' ?>"></i>
                                            <span class="news-type-badge <?= $item['type'] === 'evenement' ? 'type-event' : 'type-news' ?>">
                                                <i class="mdi mdi-<?= $item['type'] === 'evenement' ? 'calendar-star' : 'newspaper' ?>"></i>
                                                <?= $item['type'] === 'evenement' ? ($isAr ? 'Ø­Ø¯Ø«' : 'Ã‰vÃ©nement') : ($isAr ? 'Ø®Ø¨Ø±' : 'ActualitÃ©') ?>
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
                                                <?= e(mb_strimwidth((string) ($isAr ? ($item['description_ar'] ?: $item['description_fr']) : $item['description_fr']), 0, 120, 'â€¦')) ?>
                                            </p>
                                        <?php endif; ?>

                                        <?php if ($item['url_externe']): ?>
                                            <a href="<?= e($item['url_externe']) ?>" target="_blank" rel="noopener" class="news-card-link">
                                                <?= $isAr ? 'Ø§Ù„Ù…Ø²ÙŠØ¯' : 'En savoir plus' ?>
                                                <i class="mdi mdi-arrow-right"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <?php if (empty($news)): ?>
                                <div class="news-empty">
                                    <i class="mdi mdi-newspaper-variant-outline"></i>
                                    <p><?= $isAr ? 'Ù„Ø§ Ø£Ø®Ø¨Ø§Ø± Ø­Ø§Ù„ÙŠØ§Ù‹.' : 'Aucune actualitÃ© pour le moment.' ?></p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Ã‰vÃ©nements Ã  venir (depuis la table evenements) -->
                        <?php if (!empty($upcoming)): ?>
                        <div class="upcoming-section" data-reveal>
                            <h3 class="upcoming-title">
                                <i class="mdi mdi-calendar-clock"></i>
                                <?= $isAr ? 'ÙØ¹Ø§Ù„ÙŠØ§Øª Ù‚Ø§Ø¯Ù…Ø©' : 'Prochains Ã©vÃ©nements' ?>
                            </h3>
                            <div class="upcoming-grid">
                                <?php foreach ($upcoming as $ev): ?>
                                    <a class="upcoming-card" href="<?= url('checkin/' . \App\Helpers\QrCodeGenerator::tokenForEvent((int)$ev['id'])) ?>">
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
                                        <i class="mdi mdi-chevron-right upcoming-arrow"></i>
                                    </a>
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
                            <span class="eyebrow"><i class="mdi mdi-information-outline"></i><?= $isAr ? 'Ù…Ù† Ù†Ø­Ù†' : 'Qui sommes-nous' ?></span>
                            <h2 class="section-title left" style="text-align:left"><?= e($pick(settings('titre_apropos_fr',''), settings('titre_apropos_ar',''))) ?></h2>
                            <p class="apropos-text"><?= e($pick(settings('texte_apropos_fr',''), settings('texte_apropos_ar',''))) ?></p>
                            <ul class="about-points" style="list-style:none;margin-top:1rem">
                                <li><i class="mdi mdi-check-circle-outline" style="color:var(--accent)"></i> <?= $isAr ? 'Ø´Ø±Ø§ÙƒØ© Ù…ÙˆØ§Ø·Ù†Ø© Ø­Ù‚ÙŠÙ‚ÙŠØ©' : 'Partenariat citoyen authentique' ?></li>
                                <li><i class="mdi mdi-check-circle-outline" style="color:var(--accent)"></i> <?= $isAr ? 'Ø´ÙØ§ÙÙŠØ© ÙÙŠ ÙƒÙ„ Ù…Ø±Ø­Ù„Ø©' : 'Transparence Ã  chaque Ã©tape' ?></li>
                                <li><i class="mdi mdi-check-circle-outline" style="color:var(--accent)"></i> <?= $isAr ? 'Ø®Ø¯Ù…Ø© Ø¹Ù…ÙˆÙ…ÙŠØ© Ù…ØªØ¬Ø¯Ø¯Ø©' : 'Un service public qui se modernise' ?></li>
                            </ul>
                        </div>
                    </div>
                </section>

            <?php elseif ($section === 'fonctionnement'): ?>
                <?php $etapes = settings('fonctionnement_etapes', []); if(is_array($etapes) && count($etapes)>0): ?>
                <section class="section section-how<?= $alt ? ' bg-muted' : '' ?>" id="fonctionnement">
                    <div class="container">
                        <div class="section-head" data-reveal>
                            <span class="eyebrow"><i class="mdi mdi-cog-outline"></i><?= $isAr ? 'Ø·Ø±ÙŠÙ‚Ø© Ø§Ù„Ø¹Ù…Ù„' : 'Le processus' ?></span>
                            <h2 class="section-title"><?= e($pick(settings('titre_fonctionnement_fr',''), settings('titre_fonctionnement_ar',''))) ?></h2>
                            <p class="section-lead"><?= $isAr ? 'Ù…Ù† Ø§Ù„Ø¥Ø¨Ù„Ø§Øº Ø¥Ù„Ù‰ Ø§Ù„Ø¥Ù†Ø¬Ø§Ø²ØŒ Ù…Ø³Ø§Ø± ÙˆØ§Ø¶Ø­ ÙˆÙ…Ø¨Ø³Ø·.' : 'Du signalement Ã  la rÃ©alisation, un parcours clair et simplifiÃ©.' ?></p>
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
                            <span class="eyebrow"><i class="mdi mdi-tools"></i><?= $isAr ? 'Ø®Ø¯Ù…Ø§Øª Ø§Ù„ÙˆÙ„Ø§ÙŠØ©' : 'Nos services' ?></span>
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
                <section class="section section-albums<?= $alt ? ' bg-muted' : '' ?>" id="albums">
                    <div class="container">
                        <div class="section-head" data-reveal>
                            <span class="eyebrow"><i class="mdi mdi-camera-iris"></i><?= $isAr ? 'Ø°Ø§ÙƒØ±Ø© Ø§Ù„Ø£Ø­Ø¯Ø§Ø«' : 'La mÃ©moire des Ã©vÃ©nements' ?></span>
                            <h2 class="section-title"><?= e(__('landing.albums')) ?></h2>
                            <p class="section-lead"><?= e(__('landing.albums_sub')) ?></p>
                        </div>
                        <div class="album-filters-row" data-reveal data-reveal-delay="100">
                            <button type="button" class="filter-btn active" data-filter="all"><?= $isAr ? 'Ø§Ù„ÙƒÙ„' : 'Tous' ?></button>
                            <?php foreach($anomalies as $an): ?>
                                <button type="button" class="filter-btn" data-filter="anomaly-<?= (int)$an['id'] ?>"><?= e($an['icone']) ? e($an['icone']).' ' : '' ?><?= e($an['nom']) ?></button>
                            <?php endforeach; ?>
                        </div>
                        <div class="albums-grid" id="albumsGrid">
                            <?php if (!empty($albums)): ?>
                                <?php foreach($albums as $al): ?>
                                    <?php
                                        $albumAnomalies = [];
                                        if(!empty($al['id'])) {
                                            $albumAnomalies = Database::all('SELECT a.id FROM anomalies a JOIN anomalies_evenement ae ON ae.anomalie_id=a.id WHERE ae.evenement_id=?', [(int)$al['evenement_id']]);
                                        }
                                        $assoc = null;
                                        if (!empty($al['association_id'])) {
                                            $assoc = Database::one('SELECT id,nom,numero_agrement,valide FROM associations WHERE id=?', [(int)$al['association_id']]);
                                        }
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
                                            <p class="album-meta">
                                                <i class="mdi mdi-map-marker-outline"></i><?= e($al['adresse'] ?? '') ?>
                                                <span class="album-date"><?= isset($al['date_evenement']) ? e(date('d/m/Y', strtotime((string)$al['date_evenement']))) : '' ?></span>
                                            </p>
                                            <?php if(!empty($al['recit'])): ?>
                                                <blockquote class="album-recit">"<?= e(mb_substr((string)$al['recit'],0,120)) ?>â€¦"</blockquote>
                                            <?php endif; ?>
                                            <span class="album-voir"><?= e(__('landing.albums_voir')) ?> <i class="mdi mdi-arrow-right"></i></span>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="albums-placeholder"><i class="mdi mdi-camera mdi-48px"></i><p><?= $isAr ? 'Ù„Ø§ ØªÙˆØ¬Ø¯ Ø£Ù„Ø¨ÙˆÙ…Ø§Øª Ø­Ø§Ù„ÙŠØ§Ù‹.' : 'Aucun album disponible.' ?></p></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <!-- Lightbox Albums -->
                <div class="wh-lightbox" id="albumLightbox" role="dialog" aria-modal="true" aria-hidden="true">
                    <div class="wh-lightbox-backdrop" data-lb-close></div>
                    <div class="wh-lightbox-panel">
                        <button class="wh-lightbox-close" type="button" data-lb-close aria-label="<?= $isAr ? 'Ø¥ØºÙ„Ø§Ù‚' : 'Fermer' ?>"><i class="mdi mdi-close"></i></button>
                        <div class="wh-lightbox-stage">
                            <button class="wh-lightbox-nav prev" type="button" data-lb-prev aria-label="PrÃ©cÃ©dent"><i class="mdi mdi-chevron-left"></i></button>
                            <img class="wh-lightbox-img" id="lightboxImg" src="" alt="" draggable="false">
                            <button class="wh-lightbox-nav next" type="button" data-lb-next aria-label="Suivant"><i class="mdi mdi-chevron-right"></i></button>
                            <span class="wh-lightbox-counter" id="lightboxCounter"></span>
                            <p class="wh-lightbox-caption" id="lightboxCaption"></p>
                        </div>
                        <div class="wh-lightbox-narrative">
                            <h4><i class="mdi mdi-format-quote-open"></i><?= $isAr ? 'Ø§Ù„Ù‚ØµØ© Ø§Ù„Ø±Ø³Ù…ÙŠØ©' : 'RÃ©cit officiel' ?></h4>
                            <p id="lightboxNarrativeText"></p>
                        </div>
                    </div>
                </div>

            <?php elseif ($section === 'temoignages'): ?>
                <section class="section section-testimonials<?= $alt ? ' bg-muted' : '' ?>" id="temoignages">
                    <div class="container">
                        <div class="section-head" data-reveal>
                            <span class="eyebrow"><i class="mdi mdi-comment-quote-outline"></i><?= $isAr ? 'Ø¢Ø±Ø§Ø¡ Ø§Ù„Ù…ÙˆØ§Ø·Ù†ÙŠÙ†' : 'La voix citoyenne' ?></span>
                            <h2 class="section-title"><?= e(__('landing.temoignages')) ?></h2>
                        </div>
                        <div class="cards-grid">
                            <?php foreach($testimonials as $t): ?>
                                <div class="card testimonial" data-reveal>
                                    <div class="stars"><?= str_repeat('â˜…', (int)$t['note']) ?><?= str_repeat('â˜†', 5-(int)$t['note']) ?></div>
                                    <p><?= nl2br(e($pick($t['texte_fr']??'', $t['texte_ar']??''))) ?></p>
                                    <div class="testimonial-author">â€” <?= e($t['auteur']) ?><?= !empty($t['role']) ? ' Â· '.e($t['role']) : '' ?></div>
                                </div>
                            <?php endforeach; ?>
                            <?php if(empty($testimonials)): ?><div class="card empty"><?= $isAr ? 'Ù„Ø§ ØªÙˆØ¬Ø¯ Ø´Ù‡Ø§Ø¯Ø§Øª Ø­Ø§Ù„ÙŠØ§Ù‹.' : 'Aucun tÃ©moignage pour le moment.' ?></div><?php endif; ?>
                        </div>
                    </div>
                </section>

            <?php elseif ($section === 'partenaires'): ?>
                <section class="section section-partners<?= $alt ? ' bg-muted' : '' ?>" id="partenaires">
                    <div class="container">
                        <div class="section-head" data-reveal>
                            <span class="eyebrow"><i class="mdi mdi-handshake-outline"></i><?= $isAr ? 'Ù…Ø¹Ù‹Ø§ Ù†Ù†Ø¬Ø²' : 'Ensemble, on agit' ?></span>
                            <h2 class="section-title"><?= e(__('landing.partenaires')) ?></h2>
                        </div>
                        <div class="partners">
                            <?php foreach($partners as $p): ?>
                                <a class="partner-card" href="<?= e($p['url']??'#') ?>" target="_blank" rel="noopener" data-reveal><i class="mdi mdi-domain"></i><span><?= e($p['nom']) ?></span></a>
                            <?php endforeach; ?>
                            <?php if(empty($partners)): ?><div class="card empty"><?= $isAr ? 'Ù„Ø§ ÙŠÙˆØ¬Ø¯ Ø´Ø±ÙƒØ§Ø¡ Ø­Ø§Ù„ÙŠØ§Ù‹.' : 'Aucun partenaire pour le moment.' ?></div><?php endif; ?>
                        </div>
                    </div>
                </section>

            <?php elseif ($section === 'faq'): ?>
                <section class="section section-faq<?= $alt ? ' bg-muted' : '' ?>" id="faq">
                    <div class="container">
                        <div class="section-head" data-reveal>
                            <span class="eyebrow"><i class="mdi mdi-help-circle-outline"></i><?= $isAr ? 'Ø§Ø³ØªÙØ³Ø§Ø±Ø§ØªÙƒÙ…' : 'Vos questions' ?></span>
                            <h2 class="section-title"><?= e(__('landing.faq')) ?></h2>
                        </div>
                        <div class="faq">
                            <?php foreach($faq as $i=>$f): ?>
                                <details class="faq-item" <?= $i===0 ? 'open' : '' ?> data-reveal data-reveal-delay="<?= min($i*60,300) ?>">
                                    <summary><?= e($pick($f['question_fr']??'', $f['question_ar']??'')) ?></summary>
                                    <p><?= nl2br(e($pick($f['reponse_fr']??'', $f['reponse_ar']??''))) ?></p>
                                </details>
                            <?php endforeach; ?>
                            <?php if(empty($faq)): ?><p class="text-muted text-center"><?= $isAr ? 'Ù„Ø§ ØªÙˆØ¬Ø¯ Ø£Ø³Ø¦Ù„Ø© Ø´Ø§Ø¦Ø¹Ø© Ø­Ø§Ù„ÙŠØ§Ù‹.' : 'Aucune question frÃ©quente pour le moment.' ?></p><?php endif; ?>
                        </div>
                    </div>
                </section>

            <?php elseif ($section === 'timeline'): ?>
                <?php if(!empty($timelineEvents)): ?>
                <section class="section section-timeline<?= $alt ? ' bg-muted' : '' ?>" id="timeline">
                    <div class="container">
                        <div class="section-head" data-reveal>
                            <span class="eyebrow"><i class="mdi mdi-timeline-outline"></i><?= $isAr ? 'Ù…Ø³ÙŠØ±ØªÙ†Ø§' : 'Notre parcours' ?></span>
                            <h2 class="section-title"><?= $isAr ? 'Ù…Ø­Ø·Ø§Øª Ø¨Ø§Ø±Ø²Ø©' : 'Ã‰tapes marquantes' ?></h2>
                            <p class="section-lead"><?= $isAr ? 'Ø£Ø¨Ø±Ø² Ø§Ù„Ù…Ø­Ø·Ø§Øª ÙÙŠ Ù…Ø³ÙŠØ±Ø© Ø§Ù„ÙˆÙ„Ø§ÙŠØ©.' : 'Les moments clÃ©s de la wilaya.' ?></p>
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

            <?php elseif ($section === 'before-after'): ?>
                <?php if(!empty($beforeAfter)): ?>
                <section class="section section-ba<?= $alt ? ' bg-muted' : '' ?>" id="before-after">
                    <div class="container">
                        <div class="section-head" data-reveal>
                            <span class="eyebrow"><i class="mdi mdi-compare-horizontal"></i><?= $isAr ? 'Ø§Ù„Ù†ØªØ§Ø¦Ø¬ Ø¹Ù„Ù‰ Ø£Ø±Ø¶ Ø§Ù„ÙˆØ§Ù‚Ø¹' : 'Les rÃ©sultats concrets' ?></span>
                            <h2 class="section-title"><?= e(__('landing.before_after')) ?></h2>
                        </div>
                        <div class="before-after-grid">
                            <?php foreach($beforeAfter as $ba): ?>
                                <div class="ba-card" data-reveal>
                                    <div class="ba-slider" style="position:relative;aspect-ratio:16/9;overflow:hidden">
                                        <img src="<?= e($ba['image_before']) ?>" alt="<?= $isAr ? 'Ù‚Ø¨Ù„' : 'Avant' ?>" loading="lazy" class="ba-before-img" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover">
                                        <img src="<?= e($ba['image_after']) ?>" alt="<?= $isAr ? 'Ø¨Ø¹Ø¯' : 'AprÃ¨s' ?>" loading="lazy" class="ba-after-img" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;clip-path:inset(0 50% 0 0)">
                                        <input type="range" min="0" max="100" value="50" aria-label="<?= $isAr ? 'Ø´Ø±ÙŠØ· Ø§Ù„Ù…Ù‚Ø§Ø±Ù†Ø©' : 'Curseur de comparaison' ?>" style="position:absolute;inset:0;width:100%;height:100%;z-index:3;opacity:0;cursor:ew-resize">
                                        <div class="ba-handle" style="position:absolute;top:0;left:50%;width:4px;height:100%;background:#fff;z-index:2;transform:translateX(-50%);pointer-events:none;box-shadow:0 0 16px rgba(0,0,0,0.3)"></div>
                                        <span class="ba-label ba-before" style="position:absolute;bottom:0.8rem;left:0.8rem;background:rgba(255,255,255,0.85);padding:0.2rem 0.8rem;border-radius:999px;font-weight:700;font-size:0.7rem;z-index:2;color:var(--text-primary)"><?= $isAr ? 'Ù‚Ø¨Ù„' : 'Avant' ?></span>
                                        <span class="ba-label ba-after" style="position:absolute;bottom:0.8rem;right:0.8rem;background:rgba(255,255,255,0.85);padding:0.2rem 0.8rem;border-radius:999px;font-weight:700;font-size:0.7rem;z-index:2;color:var(--text-primary)"><?= $isAr ? 'Ø¨Ø¹Ø¯' : 'AprÃ¨s' ?></span>
                                    </div>
                                    <div class="ba-content">
                                        <h3><?= e($pick($ba['titre_fr']??'', $ba['titre_ar']??'')) ?></h3>
                                        <p class="ba-desc"><?= e($pick($ba['description_fr']??'', $ba['description_ar']??'')) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
                <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; ?>

        <!-- â•â•â• CARTE MAPBOX â•â•â• -->
        <section class="section section-map" id="carte">
            <div class="container">
                <div class="section-head" data-reveal>
                    <span class="eyebrow"><i class="mdi mdi-map-outline"></i><?= $isAr ? 'Ø§Ù„ØªØ¯Ø®Ù„Ø§Øª Ø§Ù„Ù…ÙŠØ¯Ø§Ù†ÙŠØ©' : 'Le terrain en direct' ?></span>
                    <h2 class="section-title"><?= e(__('landing.interventions')) ?></h2>
                    <p class="section-lead"><?= $isAr ? 'ØªØ§Ø¨Ø¹ Ø¹Ù…Ù„ÙŠØ§Øª Ø§Ù„ÙˆÙ„Ø§ÙŠØ© Ø¹Ù„Ù‰ Ø§Ù„Ø®Ø±ÙŠØ·Ø©.' : 'Suivez les opÃ©rations de la wilaya sur la carte.' ?></p>
                </div>
                <div class="landing-map" id="landingMap" role="region" aria-label="<?= e(__('landing.interventions')) ?>"></div>
            </div>
        </section>

        <!-- â•â•â• IA ASSISTANT â•â•â• -->
        <div id="ia-assistant">
            <button class="ia-toggle" id="iaToggle" aria-label="<?= $isAr ? 'Ø§Ù„Ù…Ø³Ø§Ø¹Ø¯ Ø§Ù„Ø°ÙƒÙŠ' : 'Assistant IA' ?>"><i class="mdi mdi-robot-outline"></i></button>
            <div class="ia-window" id="iaWindow">
                <div class="ia-header"><span><?= $isAr ? 'Ø§Ù„Ù…Ø³Ø§Ø¹Ø¯ Ø§Ù„Ø°ÙƒÙŠ' : 'Assistant IA' ?></span><button id="iaClose" style="background:none;border:none;color:#fff;font-size:1.5rem;cursor:pointer">&times;</button></div>
                <div class="ia-messages" id="iaMessages">
                    <div class="ia-msg bot"><?= $isAr ? 'Ù…Ø±Ø­Ø¨Ø§Ù‹! ÙƒÙŠÙ ÙŠÙ…ÙƒÙ†Ù†ÙŠ Ù…Ø³Ø§Ø¹Ø¯ØªÙƒ Ø§Ù„ÙŠÙˆÙ…ØŸ' : 'Bonjour ! Comment puis-je vous aider aujourdâ€™hui ?' ?></div>
                </div>
                <div class="ia-input">
                    <input type="text" id="iaInput" placeholder="<?= $isAr ? 'Ø§ÙƒØªØ¨ Ø³Ø¤Ø§Ù„Ùƒ...' : 'Ã‰crivez votre question...' ?>" aria-label="<?= $isAr ? 'Ø¥Ø¯Ø®Ø§Ù„ Ø§Ù„Ù†Øµ' : 'Saisie' ?>">
                    <button id="iaSend"><i class="mdi mdi-send"></i></button>
                </div>
            </div>
        </div>

    </div> <!-- fin .landing -->

    <!-- â•â•â• SCRIPTS â•â•â• -->
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

        // â”€â”€â”€ AURORA â”€â”€â”€
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

        // â”€â”€â”€ MAPBOX 3D â”€â”€â”€
        (function initMap() {
            const mapEl = document.getElementById('landingMap');
            if (!mapEl) return;
            const validEvents = mapEvents.filter(e => Number(e.latitude) && Number(e.longitude));
            if (validEvents.length === 0) {
                mapEl.innerHTML = `<p class="text-muted text-center py-4">${isAr ? 'Ù„Ø§ ØªÙˆØ¬Ø¯ ØªØ¯Ø®Ù„Ø§Øª Ù„Ø¹Ø±Ø¶Ù‡Ø§.' : 'Aucune intervention Ã  afficher.'}</p>`;
                return;
            }
            const script = document.createElement('script');
            script.src = 'https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js';
            script.onload = () => {
                mapboxgl.accessToken = 'VOTRE_CLE_MAPBOX';
                const map = new mapboxgl.Map({
                    container: 'landingMap',
                    style: 'mapbox://styles/mapbox/outdoors-v12',
                    center: [Number(validEvents[0].longitude), Number(validEvents[0].latitude)],
                    zoom: 10,
                    pitch: 45,
                    bearing: 0,
                    antialias: true
                });
                map.on('load', () => {
                    const points = validEvents.map(e => ({
                        type: 'Feature',
                        properties: { value: 0.5 },
                        geometry: { type: 'Point', coordinates: [Number(e.longitude), Number(e.latitude)] }
                    }));
                    map.addSource('events', {
                        type: 'geojson',
                        data: { type: 'FeatureCollection', features: points }
                    });
                    map.addLayer({
                        id: 'events-heat',
                        type: 'heatmap',
                        source: 'events',
                        paint: {
                            'heatmap-radius': 30,
                            'heatmap-weight': ['get', 'value'],
                            'heatmap-color': [
                                'interpolate', ['linear'], ['heatmap-density'],
                                0, 'rgba(42,122,62,0)',
                                0.4, '#2a7a3e',
                                0.7, '#0d9488',
                                1, '#eab308'
                            ]
                        }
                    });
                    validEvents.forEach(e => {
                        const popup = new mapboxgl.Popup({ offset: 25 })
                            .setHTML(`<strong>${e.adresse || ''}</strong><br><small>${e.commune_nom||''} Â· ${e.date_evenement||''}</small>`);
                        new mapboxgl.Marker({ color: '#2a7a3e', scale: 0.8 })
                            .setLngLat([Number(e.longitude), Number(e.latitude)])
                            .setPopup(popup)
                            .addTo(map);
                    });
                });
                map.addControl(new mapboxgl.NavigationControl({ showCompass: false }));
            };
            document.head.appendChild(script);
        })();

        // â”€â”€â”€ GSAP ANIMATIONS â”€â”€â”€
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

        // â”€â”€â”€ BEFORE / AFTER â”€â”€â”€
        document.querySelectorAll('.ba-slider input[type="range"]').forEach(slider => {
            const afterImg = slider.parentElement.querySelector('.ba-after-img');
            const handle = slider.parentElement.querySelector('.ba-handle');
            slider.addEventListener('input', () => {
                const val = slider.value;
                if (afterImg) afterImg.style.clipPath = `inset(0 ${100 - val}% 0 0)`;
                if (handle) handle.style.left = val + '%';
            });
        });

        // â”€â”€â”€ ALBUMS LIGHTBOX AMÃ‰LIORÃ‰E â”€â”€â”€
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
            // RÃ©cit
            const narrativeText = album.recit || (album.adresse ? album.adresse + ' â€” ' : '') + (album.date_evenement ? new Date(album.date_evenement).toLocaleDateString() : '');
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

        // â”€â”€â”€ FILTRES ALBUMS â”€â”€â”€
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

        // â”€â”€â”€ IA ASSISTANT â”€â”€â”€
        (function initIA() {
            const toggle = document.getElementById('iaToggle');
            const windowEl = document.getElementById('iaWindow');
            const close = document.getElementById('iaClose');
            const input = document.getElementById('iaInput');
            const send = document.getElementById('iaSend');
            const messages = document.getElementById('iaMessages');

            function toggleWindow() {
                windowEl.classList.toggle('open');
            }
            if (toggle) toggle.addEventListener('click', toggleWindow);
            if (close) close.addEventListener('click', toggleWindow);

            function sendMessage() {
                const text = input.value.trim();
                if (!text) return;
                const userMsg = document.createElement('div');
                userMsg.className = 'ia-msg user';
                userMsg.textContent = text;
                messages.appendChild(userMsg);
                input.value = '';
                messages.scrollTop = messages.scrollHeight;

                const botMsg = document.createElement('div');
                botMsg.className = 'ia-msg bot';
                botMsg.textContent = isAr ? 'Ø´ÙƒØ±Ø§Ù‹ Ù„Ø³Ø¤Ø§Ù„Ùƒ! Ø³Ø£Ù‚ÙˆÙ… Ø¨Ù…Ø¹Ø§Ù„Ø¬Ø© Ø·Ù„Ø¨Ùƒ.' : 'Merci pour votre question ! Je traite votre demande.';
                setTimeout(() => {
                    messages.appendChild(botMsg);
                    messages.scrollTop = messages.scrollHeight;
                }, 500);
            }

            if (send) send.addEventListener('click', sendMessage);
            if (input) input.addEventListener('keypress', e => { if (e.key === 'Enter') sendMessage(); });
        })();

        console.log('ðŸŒ¿ Landing Nature & Environnement (avec vidÃ©o et albums amÃ©liorÃ©s) chargÃ©e.');
    </script>

    <script nomodule>
        console.warn('Votre navigateur ne supporte pas les modules ES.');
    </script>
</body>
</html>
