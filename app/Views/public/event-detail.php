<?php
/** @var array $event, array $photos, array|null $album, bool $hasParticipated, bool $isPublic, array $og */
use App\Helpers\I18n;

$isAr = I18n::direction() === 'rtl';
$dir  = I18n::direction();
$locale = I18n::locale();
$langAttr = I18n::langAttribute();
$appName = e(settings('app.name') ?: __('app.name'));

$eventDateStr = (string) ($event['date_evenement'] ?? '');
$eventTime    = $event['heure'] ?? '';
$eventDateObj = new DateTimeImmutable($eventDateStr);
$todayStr     = date('Y-m-d');
$isPast       = $eventDateStr < $todayStr;
$isToday      = $eventDateStr === $todayStr;
$dateDebut    = $event['date_debut'] ?? null;
$statut       = (string) ($event['statut'] ?? 'PROGRAMME');
$openForScan  = in_array($statut, ['PROGRAMME', 'QR_GENERE', 'EN_COURS'], true);
$anomalies    = $event['anomalies'] ?? '';
$eventId      = (int) ($event['id'] ?? 0);
$hasQr        = $openForScan && $eventId > 0;
$qrStreamUrl  = $hasQr ? url('event/qr/stream/' . $eventId) : '';
$eventUrl     = url('evenement/' . $eventId);
$eventTitle   = (string) ($event['adresse'] ?? 'Événement');
$commune      = (string) ($event['commune_nom'] ?? '');
$asso         = (string) ($event['association_nom'] ?? '');
$description  = (string) ($event['description'] ?? '');
$participants = (int) ($event['participants_count'] ?? 0);
$capacite     = (int) ($event['capacite'] ?? 0);
$lat          = (float) ($event['latitude'] ?? 0);
$lng          = (float) ($event['longitude'] ?? 0);
$hasMap       = $lat !== 0.0 && $lng !== 0.0;
$heroImage    = !empty($og['image']) ? $og['image'] : '';
$heroGradient = 'linear-gradient(135deg, #0F2B22 0%, #1A4D3E 40%, #0d9488 100%)';

/* ── Partage enrichi (WhatsApp / email) ── */
$eventDateFmt = ($eventDateObj && $eventDateStr !== '') ? $eventDateObj->format('d/m/Y') : '';
$dateRange    = ($dateDebut && $dateDebut !== $eventDateStr)
    ? (new DateTimeImmutable((string) $dateDebut))->format('d/m/Y') . ' → ' . $eventDateFmt
    : $eventDateFmt;
$eventTimeFmt = $eventTime !== '' ? substr((string) $eventTime, 0, 5) : '';
$eventLocation = trim(implode(' — ', array_filter([$commune])));
$shareLines = array_filter([
    $isAr ? '📅 حدث: ' . $eventTitle : '📅 Événement : ' . $eventTitle,
    $dateRange !== ''      ? ($isAr ? '📆 التاريخ: ' . $dateRange          : '📆 Date : ' . $dateRange)          : '',
    $eventTimeFmt !== ''   ? ($isAr ? '⏰ الساعة: ' . $eventTimeFmt        : '🕐 Heure : ' . $eventTimeFmt)      : '',
    $eventLocation !== ''  ? ($isAr ? '📍 المكان: ' . $eventLocation       : '📍 Lieu : ' . $eventLocation)      : '',
    $asso !== ''           ? ($isAr ? '🏛 الجمعية: ' . $asso                : '🏛 Association : ' . $asso)        : '',
    $description !== ''    ? ($isAr ? 'ℹ️ ' . mb_strimwidth($description, 0, 180) : 'ℹ️ ' . mb_strimwidth($description, 0, 180)) : '',
    '',
    $eventUrl,
]);
$shareText    = implode("\n", $shareLines);
$shareSubject = $isAr ? 'دعوة إلى حدث: ' . $eventTitle : 'Invitation à un événement : ' . $eventTitle;
?>
<!DOCTYPE html>
<html lang="<?= e($langAttr) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#0F2B22">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title><?= e($eventTitle) ?> — <?= $appName ?></title>
    <meta name="description" content="<?= e(mb_strimwidth($description !== '' ? $description : $eventTitle, 0, 160)) ?>">
    <meta property="og:title" content="<?= e($eventTitle) ?> — <?= $appName ?>">
    <meta property="og:description" content="<?= e(mb_strimwidth($description !== '' ? $description : $eventTitle, 0, 160)) ?>">
    <?php if ($heroImage !== ''): ?><meta property="og:image" content="<?= e($heroImage) ?>"><?php endif; ?>
    <meta property="og:url" content="<?= e($eventUrl) ?>">
    <meta property="og:type" content="event">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="icon" href="<?= asset('/assets/img/icon-192.png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400..700;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        /* ═══════════════════════════════════════════════
           STANDALONE PUBLIC EVENT PAGE — FULL ISOLATION
           No Bootstrap, no citoyen.css, no sidebar
           ═══════════════════════════════════════════════ */
        :root {
            color-scheme: light only;
            --bg: #faf8f3; --bg-card: #ffffff; --bg-subtle: #f3f8f4;
            --text: #1b2a1f; --text-secondary: #4a6355; --muted: #6b7c72;
            --accent: #1a7a42; --accent-dark: #0f5e2e; --accent-light: #e6f5ec;
            --gold: #c9a84c; --gold-light: #f5ecd0;
            --danger: #dc3545;
            --radius: 16px; --radius-sm: 10px; --radius-full: 999px;
            --shadow-sm: 0 2px 8px rgba(0,20,10,0.04);
            --shadow: 0 4px 24px rgba(0,20,10,0.06);
            --shadow-lg: 0 12px 40px rgba(0,20,10,0.10);
            --font: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            --transition: 0.25s cubic-bezier(.4,0,.2,1);
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; -webkit-text-size-adjust: 100%; }
        body {
            font-family: var(--font); background: var(--bg); color: var(--text);
            line-height: 1.65; min-height: 100vh; overflow-x: hidden;
        }
        a { color: var(--accent); text-decoration: none; transition: color var(--transition); }
        a:hover { color: var(--accent-dark); }
        img { max-width: 100%; height: auto; display: block; }
        .container { width: 100%; max-width: 880px; margin: 0 auto; padding: 0 1.25rem; }

        /* ── NAV ── */
        .topbar {
            position: sticky; top: 0; z-index: 200;
            background: rgba(250,248,243,0.82); backdrop-filter: blur(16px) saturate(1.4);
            -webkit-backdrop-filter: blur(16px) saturate(1.4);
            border-bottom: 1px solid rgba(26,122,66,0.08);
            transition: box-shadow var(--transition);
        }
        .topbar.scrolled { box-shadow: 0 2px 20px rgba(0,20,10,0.08); }
        .topbar-inner {
            display: flex; align-items: center; justify-content: space-between;
            height: 56px;
        }
        .topbar-brand {
            display: flex; align-items: center; gap: 0.5rem;
            font-weight: 700; font-size: 0.95rem; color: var(--text);
        }
        .topbar-brand .icon {
            width: 32px; height: 32px; border-radius: 10px;
            background: linear-gradient(135deg, var(--accent), var(--gold));
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 1rem;
        }
        .topbar-actions { display: flex; align-items: center; gap: 0.5rem; }
        .topbar-btn {
            display: inline-flex; align-items: center; gap: 0.3rem;
            padding: 0.4rem 0.9rem; border-radius: var(--radius-full);
            font-size: 0.8rem; font-weight: 600; border: none; cursor: pointer;
            transition: all var(--transition);
        }
        .topbar-btn-ghost {
            background: transparent; color: var(--text-secondary);
            border: 1px solid rgba(26,122,66,0.15);
        }
        .topbar-btn-ghost:hover { background: var(--accent-light); color: var(--accent); }
        .topbar-btn-primary { background: var(--accent); color: #fff; }
        .topbar-btn-primary:hover { background: var(--accent-dark); transform: translateY(-1px); }

        /* ── HERO ── */
        .hero {
            position: relative; padding: 3rem 0 2.5rem; overflow: hidden;
            background: <?= $heroGradient ?>;
        }
        .hero::after {
            content: ''; position: absolute; inset: 0;
            background:
                radial-gradient(ellipse 60% 50% at 85% 15%, rgba(201,168,76,0.18), transparent),
                radial-gradient(ellipse 50% 40% at 15% 85%, rgba(13,148,136,0.12), transparent);
            pointer-events: none;
        }
        .hero-content { position: relative; z-index: 1; color: #fff; }
        .hero-badges { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1rem; }
        .badge {
            display: inline-flex; align-items: center; gap: 0.3rem;
            padding: 0.22rem 0.75rem; border-radius: var(--radius-full);
            font-size: 0.72rem; font-weight: 700; letter-spacing: 0.02em;
            backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);
        }
        .badge-status {
            background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.18);
            color: rgba(255,255,255,0.9);
        }
        .badge-today {
            background: rgba(234,179,8,0.28); border: 1px solid rgba(234,179,8,0.45);
            color: #fde68a;
        }
        .badge-past {
            background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12);
            color: rgba(255,255,255,0.6);
        }
        .hero h1 {
            font-size: clamp(1.6rem, 4vw, 2.4rem); font-weight: 800;
            line-height: 1.2; margin-bottom: 0.6rem;
            text-shadow: 0 2px 20px rgba(0,0,0,0.15);
        }
        .hero-desc {
            font-size: 1rem; color: rgba(255,255,255,0.82);
            max-width: 600px; margin-bottom: 1.5rem; line-height: 1.7;
        }
        .hero-meta {
            display: flex; flex-wrap: wrap; gap: 0.4rem;
        }
        .meta-chip {
            display: inline-flex; align-items: center; gap: 0.3rem;
            padding: 0.35rem 0.85rem; border-radius: var(--radius-full);
            background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.14);
            font-size: 0.82rem; color: rgba(255,255,255,0.88);
        }
        .meta-chip .mdi { color: var(--gold); font-size: 1rem; }

        /* ── SECTION ── */
        .section { padding: 1.75rem 0; }
        .section-title {
            display: flex; align-items: center; gap: 0.5rem;
            font-size: 0.95rem; font-weight: 700; color: var(--text);
            margin-bottom: 1rem; text-transform: uppercase; letter-spacing: 0.04em;
        }
        .section-title .icon-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), var(--gold));
        }

        /* ── CARDS ── */
        .card {
            background: var(--bg-card); border-radius: var(--radius);
            padding: 1.5rem; box-shadow: var(--shadow);
            border: 1px solid rgba(26,122,66,0.06);
            transition: box-shadow var(--transition), transform var(--transition);
        }
        .card:hover { box-shadow: var(--shadow-lg); }

        /* ── PARTICIPANTS ── */
        .participants-block { display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap; }
        .participants-number {
            font-size: 2.5rem; font-weight: 800; line-height: 1;
            background: linear-gradient(135deg, var(--accent), var(--gold));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .participants-label { font-size: 0.85rem; color: var(--muted); }
        .participants-actions { margin-left: auto; }
        .btn {
            display: inline-flex; align-items: center; gap: 0.4rem;
            padding: 0.65rem 1.5rem; border-radius: var(--radius-full);
            font-weight: 700; font-size: 0.9rem; border: none; cursor: pointer;
            transition: all var(--transition); text-decoration: none;
        }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-primary:hover { background: var(--accent-dark); color: #fff; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(26,122,66,0.25); }
        .btn-success-bg { background: var(--accent-light); color: var(--accent); }
        .btn-muted { background: var(--bg-subtle); color: var(--muted); cursor: default; }
        .progress-track {
            width: 100%; height: 6px; border-radius: 6px;
            background: var(--bg-subtle); margin-top: 1rem; overflow: hidden;
        }
        .progress-fill {
            height: 100%; border-radius: 6px;
            background: linear-gradient(90deg, var(--accent), var(--gold));
            transition: width 0.6s ease;
        }
        .progress-text { font-size: 0.78rem; color: var(--muted); margin-top: 0.3rem; }

        /* ── QR SECTION ── */
        .qr-card {
            background: linear-gradient(135deg, #0F2B22 0%, #1a3d30 100%);
            border: 1px solid rgba(201,168,76,0.2);
        }
        .qr-layout { display: flex; gap: 2rem; align-items: center; flex-wrap: wrap; }
        .qr-visual {
            flex: 0 0 auto; position: relative;
            background: #fff; border-radius: 16px; padding: 0.7rem;
            box-shadow: 0 0 0 3px rgba(201,168,76,0.3), var(--shadow-lg);
        }
        .qr-visual img { width: 180px; height: 180px; border-radius: 12px; display: block; }
        .qr-visual .qr-pulse {
            position: absolute; inset: -6px; border-radius: 20px;
            border: 2px solid rgba(201,168,76,0.25);
            animation: qrPulse 2s ease-in-out infinite;
        }
        @keyframes qrPulse {
            0%, 100% { opacity: 0.3; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.02); }
        }
        .qr-text { flex: 1; min-width: 220px; color: #fff; }
        .qr-text h3 {
            font-size: 1.1rem; font-weight: 700; margin-bottom: 0.3rem;
            display: flex; align-items: center; gap: 0.4rem;
        }
        .qr-text h3 .mdi { color: var(--gold); }
        .qr-text p { font-size: 0.85rem; color: rgba(255,255,255,0.65); margin-bottom: 1rem; }
        .qr-actions { display: flex; gap: 0.6rem; flex-wrap: wrap; }
        .btn-wa {
            background: #25D366; color: #fff; font-weight: 700;
        }
        .btn-wa:hover { background: #1ebe5b; color: #fff; transform: translateY(-2px); box-shadow: 0 4px 16px rgba(37,211,102,0.3); }
        .btn-qr-dl {
            background: rgba(255,255,255,0.1); color: #fff;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .btn-qr-dl:hover { background: rgba(255,255,255,0.18); color: #fff; }

        /* ── MAP ── */
        .map-wrap {
            border-radius: var(--radius); overflow: hidden;
            border: 1px solid rgba(26,122,66,0.08);
        }
        .map-wrap #evMap { width: 100%; height: 340px; background: var(--bg-subtle); }

        /* ── ANOMALIES ── */
        .tags { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-bottom: 1rem; }
        .tag {
            display: inline-flex; align-items: center; gap: 0.25rem;
            padding: 0.22rem 0.7rem; border-radius: var(--radius-full);
            font-size: 0.73rem; font-weight: 600;
            background: #fef3cd; color: #856404;
        }

        /* ── ALBUM ── */
        .album-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 0.75rem;
        }
        .album-grid a {
            display: block; border-radius: var(--radius-sm); overflow: hidden;
            aspect-ratio: 4/3; background: var(--bg-subtle);
        }
        .album-grid img {
            width: 100%; height: 100%; object-fit: cover;
            transition: transform 0.35s ease;
        }
        .album-grid a:hover img { transform: scale(1.06); }
        .recit-block {
            margin-top: 1.5rem; padding: 1.25rem 1.5rem;
            background: var(--bg-subtle); border-radius: var(--radius);
            border-left: 3px solid var(--accent);
        }
        .recit-block h4 {
            font-size: 0.9rem; font-weight: 700; margin-bottom: 0.5rem;
            display: flex; align-items: center; gap: 0.4rem; color: var(--accent);
        }
        .recit-block p { font-size: 0.9rem; color: var(--text-secondary); line-height: 1.7; }

        /* ── EMPTY STATE ── */
        .empty-state {
            text-align: center; padding: 2.5rem 1rem; color: var(--muted);
        }
        .empty-state .mdi { font-size: 2.5rem; margin-bottom: 0.5rem; display: block; }

        /* ── FOOTER ── */
        .site-footer {
            margin-top: 3rem; padding: 2rem 0;
            border-top: 1px solid rgba(26,122,66,0.08);
            text-align: center;
        }
        .site-footer p { font-size: 0.82rem; color: var(--muted); margin-bottom: 0.5rem; }
        .site-footer a { font-size: 0.85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 0.3rem; }
        .site-footer a:hover { gap: 0.5rem; }

        /* ── ANIMATIONS ── */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-in {
            animation: fadeUp 0.5s ease-out both;
        }
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }

        /* ── RESPONSIVE ── */
        @media (max-width: 640px) {
            .hero { padding: 2rem 0 1.75rem; }
            .hero h1 { font-size: 1.5rem; }
            .hero-meta { gap: 0.3rem; }
            .meta-chip { font-size: 0.75rem; padding: 0.28rem 0.65rem; }
            .qr-layout { flex-direction: column; text-align: center; }
            .qr-actions { justify-content: center; }
            .participants-block { flex-direction: column; text-align: center; align-items: stretch; }
            .participants-actions { margin-left: 0; }
            .btn { justify-content: center; }
            .album-grid { grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); }
        }

        /* ── SHARE BUTTONS ── */
        .hero-share {
            display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
        }
        .share-label {
            font-size: 0.82rem; font-weight: 600; color: rgba(255,255,255,0.7);
            display: flex; align-items: center; gap: 4px;
        }
        .share-buttons { display: flex; gap: 8px; }
        .share-btn {
            display: flex; align-items: center; justify-content: center;
            width: 36px; height: 36px; border-radius: 50%;
            background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.18);
            color: #fff; font-size: 1rem; text-decoration: none;
            transition: all 0.25s;
        }
        .share-btn:hover { transform: translateY(-2px); color: #fff; }
        .share-fb:hover { background: #1877F2; border-color: #1877F2; box-shadow: 0 4px 12px rgba(24,119,242,0.4); }
        .share-tw:hover { background: #000; border-color: #000; box-shadow: 0 4px 12px rgba(0,0,0,0.4); }
        .share-wa:hover { background: #25D366; border-color: #25D366; box-shadow: 0 4px 12px rgba(37,211,102,0.4); }
        .share-ma:hover { background: #7c3aed; border-color: #7c3aed; box-shadow: 0 4px 12px rgba(124,58,237,0.4); }
        .share-li:hover { background: #0A66C2; border-color: #0A66C2; box-shadow: 0 4px 12px rgba(10,102,194,0.4); }
        @media (max-width: 640px) {
            .hero-share { justify-content: center; }
        }
    </style>
</head>
<body>

<!-- ── TOPBAR ── -->
<nav class="topbar" id="topbar">
    <div class="container topbar-inner">
        <a href="<?= url('/') ?>" class="topbar-brand">
            <span class="icon"><i class="mdi mdi-map-marker-star-outline"></i></span>
            <span><?= $appName ?></span>
        </a>
        <div class="topbar-actions">
            <a href="<?= url($isAr ? 'lang/fr' : 'lang/ar') ?>" class="topbar-btn topbar-btn-ghost">
                <?= $isAr ? 'FR' : 'ع' ?>
            </a>
            <a href="<?= url('/') ?>" class="topbar-btn topbar-btn-ghost">
                <i class="mdi mdi-arrow-left"></i> <?= $isAr ? 'الرئيسية' : 'Accueil' ?>
            </a>
            <?php if (! is_logged()): ?>
            <a href="<?= url('auth/login') ?>" class="topbar-btn topbar-btn-primary">
                <i class="mdi mdi-login"></i> <?= $isAr ? 'دخول' : 'Connexion' ?>
            </a>
            <?php else: ?>
            <a href="<?= url('citoyen') ?>" class="topbar-btn topbar-btn-primary">
                <i class="mdi mdi-view-dashboard-outline"></i> <?= $isAr ? 'لوحة التحكم' : 'Espace perso.' ?>
            </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- ── HERO ── -->
<header class="hero">
    <div class="container hero-content">
        <div class="hero-badges animate-in">
            <span class="badge badge-status">
                <i class="mdi mdi-<?= $openForScan ? 'qrcode-scan' : 'check-circle-outline' ?>"></i>
                <?= e(statut_label($statut)) ?>
            </span>
            <?php if ($isToday): ?>
                <span class="badge badge-today"><i class="mdi mdi-lightning-bolt"></i> <?= $isAr ? 'اليوم' : "Aujourd'hui" ?></span>
            <?php elseif ($isPast): ?>
                <span class="badge badge-past"><i class="mdi mdi-history"></i> <?= $isAr ? 'منتهي' : 'Terminé' ?></span>
            <?php endif; ?>
            <?php foreach (array_filter(array_map('trim', explode(',', $anomalies))) as $an): ?>
                <span class="badge badge-today"><i class="mdi mdi-alert-outline"></i> <?= e($an) ?></span>
            <?php endforeach; ?>
        </div>

        <h1 class="animate-in delay-1"><?= e($eventTitle) ?></h1>

        <?php if ($description !== ''): ?>
            <p class="hero-desc animate-in delay-2"><?= e($description) ?></p>
        <?php endif; ?>

        <div class="hero-meta animate-in delay-3">
            <span class="meta-chip"><i class="mdi mdi-calendar"></i> <?php
                if ($dateDebut && $dateDebut !== $eventDateStr) {
                    echo e((new DateTimeImmutable((string) $dateDebut))->format('d/m/Y')) . ' → ';
                }
                echo e($eventDateObj->format('d/m/Y'));
            ?></span>
            <?php if ($eventTime !== ''): ?>
                <span class="meta-chip"><i class="mdi mdi-clock-outline"></i> <?= e(substr((string) $eventTime, 0, 5)) ?></span>
            <?php endif; ?>
            <?php if ($commune !== ''): ?>
                <span class="meta-chip"><i class="mdi mdi-map-marker"></i> <?= e($commune) ?></span>
            <?php endif; ?>
            <?php if ($asso !== ''): ?>
                <span class="meta-chip"><i class="mdi mdi-account-group"></i> <?= e($asso) ?></span>
            <?php endif; ?>
        </div>

        <!-- SHARE BUTTONS -->
        <div class="hero-share animate-in delay-3" style="margin-top:1rem">
            <span class="share-label"><?= $isAr ? 'شارك' : 'Partager' ?> <i class="mdi mdi-share-variant-outline"></i></span>
            <div class="share-buttons">
                <a class="share-btn share-fb" href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($eventUrl) ?>" target="_blank" rel="noopener noreferrer" title="Facebook">
                    <i class="mdi mdi-facebook"></i>
                </a>
                <a class="share-btn share-tw" href="https://twitter.com/intent/tweet?url=<?= urlencode($eventUrl) ?>&text=<?= urlencode($eventTitle) ?>" target="_blank" rel="noopener noreferrer" title="Twitter/X">
                    <i class="mdi mdi-twitter"></i>
                </a>
                <?php $waHref = 'https://wa.me/?text=' . urlencode($shareText); ?>
                <a class="share-btn share-wa" href="<?= e($waHref) ?>" target="_blank" rel="noopener noreferrer" title="WhatsApp">
                    <i class="mdi mdi-whatsapp"></i>
                </a>
                <a class="share-btn share-ma" href="mailto:?subject=<?= urlencode($shareSubject) ?>&body=<?= urlencode($shareText) ?>" title="<?= $isAr ? 'إرسال بالبريد' : 'Partager par email' ?>">
                    <i class="mdi mdi-email-outline"></i>
                </a>
                <a class="share-btn share-li" href="https://www.linkedin.com/shareArticle?mini=true&url=<?= urlencode($eventUrl) ?>&title=<?= urlencode($eventTitle) ?>" target="_blank" rel="noopener noreferrer" title="LinkedIn">
                    <i class="mdi mdi-linkedin"></i>
                </a>
            </div>
        </div>
    </div>
</header>

<!-- ── CONTENT ── -->
<main class="container">

    <!-- PARTICIPANTS -->
    <section class="section animate-in">
        <div class="card">
            <div class="participants-block">
                <div>
                    <div class="participants-number"><?= $participants ?></div>
                    <div class="participants-label">
                        <?= $isAr ? 'مشارك مسجّل' : 'participants enregistrés' ?>
                        <?php if ($capacite > 0): ?>
                            <span style="color:var(--muted)">/ <?= $capacite ?> <?= $isAr ? 'مقعد' : 'places' ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="participants-actions">
                    <?php if ($hasParticipated): ?>
                        <span class="btn btn-success-bg"><i class="mdi mdi-check-circle"></i> <?= $isAr ? 'تمّت مشاركتك' : 'Vous avez participé' ?></span>
                    <?php elseif ($openForScan && !$isPast): ?>
                        <a href="<?= url('qrcode/scan-optimise') ?>" class="btn btn-primary">
                            <i class="mdi mdi-qrcode-scan"></i> <?= $isAr ? 'أمسح للتسجيل' : 'Scanner pour participer' ?>
                        </a>
                    <?php elseif ($isPublic && !$isPast): ?>
                        <span class="btn btn-muted"><i class="mdi mdi-lock-outline"></i> <?= $isAr ? 'سجّل الدخول للمشاركة' : 'Connexion requise' ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($capacite > 0): ?>
                <?php $capPct = min(100, round(($participants / max(1, $capacite)) * 100)); ?>
                <div class="progress-track"><div class="progress-fill" style="width:<?= $capPct ?>%"></div></div>
                <div class="progress-text"><?= $capPct ?>% <?= $isAr ? 'ممتلئ' : 'rempli' ?></div>
            <?php endif; ?>
        </div>
    </section>

    <!-- QR CODE -->
    <?php if ($hasQr): ?>
    <section class="section animate-in">
        <div class="card qr-card">
            <div class="qr-layout">
                <div class="qr-visual">
                    <div class="qr-pulse"></div>
                    <img src="<?= e($qrStreamUrl) ?>" alt="QR Code — <?= e($eventTitle) ?>" width="180" height="180" loading="lazy" id="evQrImg">
                </div>
                <div class="qr-text">
                    <h3><i class="mdi mdi-qrcode-scan"></i> <?= $isAr ? 'رمز الحضور' : 'QR Code de présence' ?></h3>
                    <p><?= $isAr ? 'امسح الرمز الإلكتروني لتسجيل حضورك في هذا الحدث.' : 'Scannez ce code pour enregistrer votre participation à cet événement.' ?></p>
                    <div class="qr-actions">
                        <button type="button" class="btn btn-wa" onclick="shareWA()">
                            <i class="mdi mdi-whatsapp"></i> <?= $isAr ? 'واتساب' : 'WhatsApp' ?>
                        </button>
                        <button type="button" class="btn btn-qr-dl" onclick="shareEmail()">
                            <i class="mdi mdi-email-outline"></i> <?= $isAr ? 'البريد' : 'Email' ?>
                        </button>
                        <button type="button" class="btn btn-qr-dl" onclick="downloadQr()">
                            <i class="mdi mdi-download-outline"></i> <?= $isAr ? 'حفظ' : 'Télécharger' ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- MAP -->
    <?php if ($hasMap): ?>
    <section class="section animate-in">
        <div class="section-title"><span class="icon-dot"></span> <?= $isAr ? 'الموقع' : 'Localisation' ?></div>
        <div class="map-wrap"><div id="evMap"></div></div>
    </section>
    <?php endif; ?>

    <!-- ALBUM (past events) -->
    <?php if ($isPast): ?>
    <section class="section animate-in">
        <div class="section-title"><span class="icon-dot"></span> <?= $isAr ? 'ألبوم الصور' : 'Album photos' ?></div>
        <?php if ($album !== null): ?>
            <div class="card">
                <?php if (!empty($album['titre'])): ?>
                    <h3 style="font-size:1rem;font-weight:700;margin-bottom:1rem;"><?= e((string) $album['titre']) ?></h3>
                <?php endif; ?>
                <?php if (!empty($photos)): ?>
                    <div class="album-grid">
                        <?php foreach ($photos as $photo): ?>
                            <a href="<?= asset((string) $photo['image']) ?>" target="_blank" rel="noopener" title="<?= e((string) ($photo['legende'] ?? '')) ?>">
                                <img src="<?= e(photo_src($photo)) ?>" alt="<?= e((string) ($photo['legende'] ?? '')) ?>" loading="lazy">
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="mdi mdi-image-off-outline"></i>
                        <p><?= $isAr ? 'لا توجد صور بعد' : 'Aucune photo pour le moment' ?></p>
                    </div>
                <?php endif; ?>
                <?php if (!empty($album['recit'])): ?>
                    <div class="recit-block">
                        <h4><i class="mdi mdi-format-quote-open"></i> <?= $isAr ? 'القصة' : 'Récit' ?></h4>
                        <p><?= nl2br(e((string) $album['recit'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="empty-state">
                    <i class="mdi mdi-camera-off-outline"></i>
                    <p><?= $isAr ? 'لا يتوفر ألبوم صور لهذا الحدث' : 'Aucun album photo disponible pour cet événement' ?></p>
                </div>
            </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>

</main>

<!-- ── FOOTER ── -->
<footer class="site-footer">
    <div class="container">
        <p><?= $appName ?> — <?= $isAr ? 'منصة التنسيق المواطني' : 'Plateforme de coordination citoyenne' ?></p>
        <a href="<?= url('/') ?>"><?= $isAr ? 'العودة إلى الرئيسية' : 'Retour à l\'accueil' ?> <i class="mdi mdi-arrow-right"></i></a>
    </div>
</footer>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    'use strict';

    var isAr = <?= $isAr ? 'true' : 'false' ?>;
    var eventTitle = <?= json_encode($eventTitle) ?>;
    var eventUrl = <?= json_encode($eventUrl) ?>;

    /* ── Sticky nav shadow ── */
    var topbar = document.getElementById('topbar');
    if (topbar) {
        window.addEventListener('scroll', function () {
            topbar.classList.toggle('scrolled', window.scrollY > 10);
        }, { passive: true });
    }

    /* ── WhatsApp share ── */
    window.shareWA = function () {
        var text = <?= json_encode($shareText, JSON_UNESCAPED_UNICODE) ?>;
        window.open('https://wa.me/?text=' + encodeURIComponent(text), '_blank', 'noopener,noreferrer');
    };

    /* ── Email share ── */
    window.shareEmail = function () {
        var subject = <?= json_encode($shareSubject, JSON_UNESCAPED_UNICODE) ?>;
        var body    = <?= json_encode($shareText, JSON_UNESCAPED_UNICODE) ?>;
        window.location.href = 'mailto:?subject=' + encodeURIComponent(subject) + '&body=' + encodeURIComponent(body);
    };

    /* ── Download QR ── */
    window.downloadQr = function () {
        var img = document.getElementById('evQrImg');
        if (!img || !img.src) return;
        var a = document.createElement('a');
        a.href = img.src;
        a.download = 'qr-<?= $eventId ?>.png';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    };

    <?php if ($hasMap): ?>
    /* ── Map ── */
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof L === 'undefined') return;
        var map = L.map('evMap', { scrollWheelZoom: false, zoomControl: true }).setView([<?= $lat ?>, <?= $lng ?>], 15);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://carto.com/">CARTO</a> &copy; <a href="https://osm.org/">OSM</a>',
            maxZoom: 19
        }).addTo(map);
        L.marker([<?= $lat ?>, <?= $lng ?>])
            .addTo(map)
            .bindPopup('<strong>' + <?= json_encode(e($eventTitle)) ?> + '</strong><br>' + <?= json_encode(e($commune)) ?>)
            .openPopup();
    });
    <?php endif; ?>

    /* ── Intersection Observer for scroll animations ── */
    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.style.animationPlayState = 'running';
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        document.querySelectorAll('.animate-in').forEach(function (el) {
            el.style.animationPlayState = 'paused';
            observer.observe(el);
        });
    }
})();
</script>

</body>
</html>
