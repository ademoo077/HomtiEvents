<?php
/** @var array $event @var ?array $commune @var ?array $association @var array $anomalies
 *  @var array $epics @var int $participants @var ?array $qr @var array $historique
 *  @var array $transitions @var array $statuts @var string $statutActuel @var array $epicsListe
 *  @var array $photos @var ?string $qrStreamUrl @var ?string $qrDownloadUrl @var ?array $album
 */
use App\Helpers\Database;
use App\Helpers\EvenementService;
use App\Helpers\I18n;
use App\Helpers\QrCodeGenerator;

$title = __('evenements.title') . ' #' . (int) $event['id'];
$page  = 'wilaya.evenements.show';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';

$badgeColor = static function (string $statut): string {
    return match (statut_key($statut)) {
        'en_attente'            => 'badge-amber',
        'modification_demandee' => 'badge-amber',
        'valide'                => 'badge-blue',
        'programme'             => 'badge-cyan',
        'qr_genere'             => 'badge-violet',
        'en_cours'              => 'badge-blue',
        'termine'               => 'badge-green',
        'refuse'                => 'badge-red',
        default                 => 'badge-gray',
    };
};

$isDeleted = ! empty($event['deleted_at']);
$permission = static function (string $p): bool {
    return can($p);
};
?>

<style>
/* ═══ Evenements Show — Inline Critical UI ═══ */

/* Hero */
.wh-ev-show{background:linear-gradient(135deg,#0B5ED7 0%,#6C63FF 60%,#198754 100%)}
.wh-ev-show::before{background:
    radial-gradient(560px 300px at 12% 95%,rgba(255,255,255,.12),transparent),
    radial-gradient(420px 220px at 92% 8%,rgba(255,255,255,.08),transparent)}
.wh-ev-show::after{width:360px;height:360px}
.ev-hero-actions{display:flex;flex-wrap:wrap;gap:.4rem}
.wh-ev-show .btn-light{background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.3);color:#fff;backdrop-filter:blur(4px);font-weight:600}
.wh-ev-show .btn-light:hover{background:rgba(255,255,255,.3);color:#fff}
.wh-ev-show .btn-outline-danger{border-color:rgba(255,255,255,.4);color:#fff}
.wh-ev-show .btn-outline-danger:hover{background:#dc3545;border-color:#dc3545;color:#fff}
.wh-ev-show .btn-outline-light{border-color:rgba(255,255,255,.35);color:rgba(255,255,255,.9)}
.wh-ev-show .btn-outline-light:hover{background:rgba(255,255,255,.15);color:#fff}
.wh-ev-show .badge-status{position:relative;z-index:1;background:rgba(255,255,255,.2);color:#fff;font-weight:600;font-size:.72rem;padding:.3em .7em;border-radius:999px;backdrop-filter:blur(4px)}
@media(max-width:767.98px){.wh-ev-show{padding:1.25rem 1rem}.ev-hero-actions{margin-top:.5rem}}

/* KPI row */
.ev-kpi-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:.65rem}
.ev-kpi{display:flex;align-items:center;gap:.75rem;padding:.85rem 1rem;border-radius:var(--wh-radius);background:var(--wh-white);border:1px solid var(--wh-border);box-shadow:var(--wh-shadow);border-left:4px solid var(--kpi-c,var(--wh-blue));transition:transform .15s,box-shadow .15s}
.ev-kpi .kpi-icon{width:40px;height:40px;border-radius:.65rem;display:grid;place-items:center;font-size:1.15rem;flex-shrink:0;background:var(--kpi-bg,var(--wh-blue-soft));color:var(--kpi-c,var(--wh-blue))}
.ev-kpi .kpi-val{font-size:1.35rem;font-weight:800;line-height:1.1;font-family:var(--wh-font-heading)}
.ev-kpi .kpi-label{font-size:.72rem;color:var(--wh-text-muted);font-weight:500}

/* Section cards */
.ev-card{background:var(--wh-white);border:1px solid var(--wh-border);border-radius:var(--wh-radius);box-shadow:var(--wh-shadow);overflow:hidden;margin-bottom:1rem}
.ev-card-head{display:flex;align-items:center;justify-content:space-between;padding:.7rem 1.1rem;border-bottom:1px solid var(--wh-border);background:linear-gradient(135deg,var(--wh-gray-soft),#fff);font-weight:700;font-size:.88rem}
.ev-card-head .mdi{font-size:1.1rem;margin-inline-end:.45rem}
.ev-card-body{padding:1.1rem}

/* Status badge in hero */
.ev-statut-badge{display:inline-flex;align-items:center;gap:.3rem;padding:.25em .65em;border-radius:999px;font-size:.72rem;font-weight:700;background:rgba(255,255,255,.2);color:#fff;backdrop-filter:blur(4px)}
.ev-statut-badge .mdi{font-size:.85rem}

/* Info grid (dl) */
.ev-info-dl{margin:0}
.ev-info-dl dt{font-size:.78rem;color:var(--wh-text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.03em;padding:.4rem 0 .15rem;border-bottom:1px dashed var(--wh-border-light,#eee)}
.ev-info-dl dd{font-size:.9rem;padding:.4rem 0 .3rem;margin:0 0 .15rem;border-bottom:1px solid var(--wh-border-light,#f5f5f5)}
.ev-info-dl dd:last-child{border-bottom:none}

/* Pipeline card */
.ev-pipeline-card .card-body{padding:.75rem 1rem}

/* Status form */
.ev-status-form{background:var(--wh-gray-soft);border-radius:var(--wh-radius);padding:1rem}

/* Anomaly table */
.ev-anomaly-table{font-size:.82rem}
.ev-anomaly-table th{font-size:.72rem;text-transform:uppercase;letter-spacing:.03em;color:var(--wh-text-muted);font-weight:700;padding:.5rem .6rem;border-bottom:2px solid var(--wh-border)}
.ev-anomaly-table td{padding:.5rem .6rem;vertical-align:middle;border-bottom-color:var(--wh-border-light,#f0f0f0)}

/* Photo gallery */
.ev-gallery{display:grid;grid-template-columns:repeat(auto-fill,minmax(90px,1fr));gap:.4rem}
.ev-gallery-item{position:relative;border-radius:.5rem;overflow:hidden;aspect-ratio:1;background:var(--wh-gray-soft);cursor:pointer;transition:transform .15s}
.ev-gallery-item:hover{transform:scale(1.04)}
.ev-gallery-item img{width:100%;height:100%;object-fit:cover}
.ev-gallery-more{position:absolute;inset:0;display:grid;place-items:center;background:rgba(0,0,0,.45);color:#fff;font-weight:700;font-size:1.1rem;border-radius:.5rem}

/* Timeline */
.ev-timeline{position:relative;padding-inline-start:1.5rem}
.ev-timeline::before{content:'';position:absolute;top:.4rem;bottom:.4rem;left:.5rem;width:2px;background:var(--wh-border);border-radius:1px}
.ev-timeline-item{position:relative;padding-bottom:1rem}
.ev-timeline-item:last-child{padding-bottom:0}
.ev-timeline-dot{position:absolute;left:-1.25rem;top:.35rem;width:10px;height:10px;border-radius:50%;border:2px solid var(--wh-white);box-shadow:0 0 0 2px var(--wh-blue);background:var(--wh-blue)}
.ev-timeline-dot.green{box-shadow:0 0 0 2px var(--wh-green);background:var(--wh-green)}
.ev-timeline-dot.red{box-shadow:0 0 0 2px var(--wh-red);background:var(--wh-red)}
.ev-timeline-dot.amber{box-shadow:0 0 0 2px #d97706;background:#d97706}
.ev-timeline-content{font-size:.85rem}
.ev-timeline-content .ev-pill{display:inline-flex;align-items:center;gap:.2rem;padding:.15em .5em;border-radius:999px;font-size:.68rem;font-weight:600}
.ev-timeline-meta{font-size:.75rem;color:var(--wh-text-muted);margin-top:.2rem}

/* QR card */
.ev-qr-card{text-align:center}
.ev-qr-img{max-width:180px;border:3px solid var(--wh-border);border-radius:.75rem;padding:.5rem;margin:0 auto .75rem;background:#fff}
.ev-qr-info{font-size:.8rem;color:var(--wh-text-muted);line-height:1.5}
.ev-qr-info strong{color:var(--wh-text);font-size:.85rem;display:block;margin-bottom:.2rem}
.ev-qr-info a{color:var(--wh-blue);text-decoration:none;word-break:break-all}

/* Audit log */
.ev-audit-item{padding:.6rem 0;border-bottom:1px solid var(--wh-border-light,#f0f0f0)}
.ev-audit-item:last-child{border-bottom:none}
.ev-audit-action{font-size:.82rem;font-weight:600;color:var(--wh-text)}
.ev-audit-time{font-size:.72rem;color:var(--wh-text-muted)}
.ev-audit-note{font-size:.78rem;color:var(--wh-text-muted);margin-top:.15rem}

/* Validation form */
.ev-validate-card{border-left:4px solid var(--wh-green)!important}
.ev-validate-card .ev-card-head{background:linear-gradient(135deg,var(--wh-green-soft),#fff);color:var(--wh-green)}

/* Reassign card */
.ev-reassign-card{border-left:4px solid var(--wh-blue)!important}

/* EPIC table */
.ev-epic-row{transition:background .12s}
.ev-epic-row:hover{background:var(--wh-gray-soft)!important}
</style>

<div class="wh-page">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-2"><ol class="breadcrumb small mb-0" style="--bs-breadcrumb-divider:'›'"><li class="breadcrumb-item"><a href="<?= url('wilaya/evenements') ?>" class="text-decoration-none"><i class="mdi mdi-calendar-star me-1"></i>Événements</a></li><li class="breadcrumb-item active" aria-current="page">#<?= (int)$event['id'] ?> <?= e(mb_strimwidth($event['adresse']??'',0,28,'…')) ?></li><li class="breadcrumb-item"><a href="#" onclick="navigator.clipboard.writeText(location.href); showToast('Lien copié','success'); return false;" class="text-decoration-none"><i class="mdi mdi-link"></i></a></li></ol></nav>
    <!-- ═══ HERO MAX === -->
    <div class="wh-hero-panel wh-ev-show mb-4 position-relative">
        <?php if ($slaCountdown): ?><span class="position-absolute top-0 end-0 m-2 badge <?= $slaCountdown['overdue']?'bg-danger':'bg-warning text-dark' ?>" style="z-index:2"><i class="mdi mdi-timer-outline me-1"></i><?= e($slaCountdown['label']) ?></span><?php endif; ?>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h1 class="d-flex align-items-center gap-2">
                        <i class="mdi mdi-calendar-star"></i>
                        <?= e(__('evenements.title')) ?> #<?= (int) $event['id'] ?>
                        <button class="btn btn-sm btn-outline-light py-0 px-1" style="font-size:.65rem" onclick="navigator.clipboard.writeText('<?= (int)$event['id'] ?>'); showToast('ID copié','success')">#<?= (int)$event['id'] ?></button>
                    </h1>
                    <span class="ev-statut-badge" style="animation:<?= $statutActuel==='EN_COURS'?'pulse 1.6s infinite':'' ?>"><i class="mdi mdi-circle" style="width:6px;height:6px;border-radius:50%;background:currentColor"></i><?= e(statut_label((string) $event['statut'])) ?></span>
                    <?php if ($tauxRemplissage!==null): ?><span class="badge bg-white text-dark" style="font-size:.65rem"><i class="mdi mdi-account-group me-1"></i><?= (int)$tauxRemplissage ?>%</span><?php endif; ?>
                </div>
                <p class="mb-0">
                    <i class="mdi mdi-map-marker me-1"></i><?= e($event['adresse']) ?>
                    <?php if ($commune): ?><span class="ms-1" style="opacity:.6">· <?= e($commune['nom']) ?><?php if($commune['nom_ar']): ?> / <?= e($commune['nom_ar']) ?><?php endif; ?></span><?php endif; ?>
                    <?php if ($event['date_evenement']): ?><span class="ms-2" style="opacity:.7"><i class="mdi mdi-calendar-outline me-1"></i><?= e(date('d/m/Y', strtotime((string) $event['date_evenement']))) ?></span><?php endif; ?>
                    <?php if ($event['heure']): ?><span style="opacity:.7"> à <?= e(substr((string) $event['heure'], 0, 5)) ?></span><?php endif; ?>
                    <?php if ($event['capacite']): ?><span class="ms-2" style="opacity:.6"><i class="mdi mdi-account-multiple me-1"></i><?= (int)$event['capacite'] ?> places</span><?php endif; ?>
                </p>
                <?php if ($event['date_evenement']): $days=(int)floor((strtotime($event['date_evenement'])-time())/86400); if($days>=0 && $days<=7): ?><div class="mt-2"><span class="badge bg-white text-primary" style="font-size:.65rem"><i class="mdi mdi-clock-alert me-1"></i><?= $days===0?'Aujourd’hui':"J-{$days}" ?> — compte à rebours</span></div><?php endif; endif; ?>
            </div>
            <div class="ev-hero-actions">
                <div class="btn-group btn-group-sm">
                    <a class="btn btn-outline-light" href="https://wa.me/?text=<?= urlencode(url('evenement/'.(int)$event['id'])) ?>" target="_blank" title="Partager WhatsApp"><i class="mdi mdi-whatsapp"></i></a>
                    <a class="btn btn-outline-light" href="mailto:?subject=<?= urlencode($event['adresse']) ?>&body=<?= urlencode(url('evenement/'.(int)$event['id'])) ?>" title="Email"><i class="mdi mdi-email-outline"></i></a>
                    <button class="btn btn-outline-light" onclick="window.print()" title="Imprimer"><i class="mdi mdi-printer"></i></button>
                </div>
                <a class="btn btn-sm btn-outline-light" href="<?= url('evenement/' . (int) $event['id'] . '/ical') ?>" title="Exporter iCal"><i class="mdi mdi-calendar-export me-1"></i>iCal</a>
                <?php if (! $isDeleted && $permission('evenement.edit')): ?><a class="btn btn-sm btn-light" href="<?= url('wilaya/evenements/' . (int) $event['id'] . '/edit') ?>"><i class="mdi mdi-pencil me-1"></i><?= e(__('common.edit')) ?></a><?php endif; ?>
                <?php if (! $isDeleted && $permission('evenement.delete')): ?><form method="post" action="<?= url('wilaya/evenements/' . (int) $event['id'] . '/archiver') ?>" data-confirm="<?= e(__('common.archive')) ?>" class="d-inline"><?= csrf_field() ?><button type="submit" class="btn btn-sm btn-outline-danger"><i class="mdi mdi-archive me-1"></i><?= e(__('common.archive')) ?></button></form><?php endif; ?>
                <?php if ($isDeleted && $permission('evenement.delete')): ?><form method="post" action="<?= url('wilaya/evenements/' . (int) $event['id'] . '/restaurer') ?>" data-confirm="<?= e(__('common.restore_confirm')) ?>" class="d-inline"><?= csrf_field() ?><button type="submit" class="btn btn-sm btn-success"><i class="mdi mdi-restore me-1"></i><?= e(__('common.restore')) ?></button></form><?php endif; ?>
            </div>
        </div>
        <?php if ($tauxRemplissage!==null): ?><div class="progress mt-3" style="height:6px;background:rgba(255,255,255,.25)"><div class="progress-bar bg-white" style="width:<?= (int)$tauxRemplissage ?>%"></div></div><?php endif; ?>
    </div>
    <style>@keyframes pulse{0%,100%{opacity:1}50%{opacity:.5}}</style>

    <!-- ═══ KPI MAX === -->
    <div class="ev-kpi-row mb-4">
        <div class="ev-kpi rounded-2xl transition hover:-translate-y-1 hover:shadow-wh-xl" style="--kpi-c:var(--wh-blue);--kpi-bg:var(--wh-blue-soft)">
            <div class="kpi-icon"><i class="mdi mdi-calendar-star"></i></div>
            <div class="flex-grow-1">
                <div class="kpi-val"><?= $event['date_evenement'] ? e(date('d/m/Y', strtotime((string) $event['date_evenement']))) : '—' ?><?php if($event['heure']): ?><span style="font-size:.65rem;color:var(--wh-text-muted)"> <?= e(substr($event['heure'],0,5)) ?></span><?php endif; ?></div>
                <div class="kpi-label"><?= e(__('evenements.program.date')) ?><?php if($event['date_evenement'] && $slaCountdown && !$slaCountdown['overdue']): ?><span class="badge bg-warning text-dark ms-1" style="font-size:.6rem"><?= e($slaCountdown['label']) ?></span><?php endif; ?></div>
                <?php if($event['date_evenement']): $d=(strtotime($event['date_evenement'])-time())/86400; $pct=max(0,min(100,100-($d/30)*100)); ?><div class="progress mt-1" style="height:4px"><div class="progress-bar" style="width:<?= (int)$pct ?>%;background:var(--wh-blue)"></div></div><?php endif; ?>
            </div>
        </div>
        <div class="ev-kpi rounded-2xl transition hover:-translate-y-1 hover:shadow-wh-xl" style="--kpi-c:<?= $tauxRemplissage!==null && $tauxRemplissage>=95?'var(--wh-red)':($tauxRemplissage>=80?'#b45309':'var(--wh-green)') ?>;--kpi-bg:<?= $tauxRemplissage>=95?'#f8d7da':($tauxRemplissage>=80?'#fff3cd':'var(--wh-green-soft)') ?>">
            <div class="kpi-icon" style="background:<?= $tauxRemplissage>=95?'#f8d7da':($tauxRemplissage>=80?'#fff3cd':'var(--wh-green-soft)') ?>;color:<?= $tauxRemplissage>=95?'var(--wh-red)':($tauxRemplissage>=80?'#b45309':'var(--wh-green)') ?>"><i class="mdi mdi-account-group"></i></div>
            <div class="flex-grow-1">
                <div class="kpi-val"><?= (int) $participants ?><?= ! empty($event['capacite']) ? '<span style="font-size:.75rem;font-weight:500;color:var(--wh-text-muted)"> / ' . (int) $event['capacite'] . '</span>' : '' ?><?php if($tauxRemplissage!==null): ?><span class="badge <?= $tauxRemplissage>=95?'bg-danger':($tauxRemplissage>=80?'bg-warning text-dark':'bg-success') ?> ms-1" style="font-size:.6rem"><?= (int)$tauxRemplissage ?>%</span><?php endif; ?></div>
                <div class="kpi-label"><?= e(__('evenements.participants_count')) ?><?php if(!empty($event['capacite']) && (int)$event['capacite'] - (int)$participants <=5 && (int)$event['capacite']>0): ?><span class="text-danger ms-1" style="font-size:.65rem">Presque complet</span><?php endif; ?></div>
                <?php if($tauxRemplissage!==null): ?><div class="progress mt-1" style="height:4px"><div class="progress-bar <?= $tauxRemplissage>=95?'bg-danger':($tauxRemplissage>=80?'bg-warning':'bg-success') ?>" style="width:<?= (int)$tauxRemplissage ?>%"></div></div><?php endif; ?>
            </div>
        </div>
        <div class="ev-kpi rounded-2xl transition hover:-translate-y-1 hover:shadow-wh-xl" style="--kpi-c:#b45309;--kpi-bg:#fff3cd">
            <div class="kpi-icon" style="background:#fff3cd;color:#b45309"><i class="mdi mdi-satellite-variant"></i></div>
            <div>
                <div class="kpi-val"><?= count($epics) ?><?= $event['assigned_org_id']?' <i class="mdi mdi-check-circle text-success" style="font-size:.9rem"></i>':'' ?></div>
                <div class="kpi-label"><?= e(__('evenements.epics_assigned')) ?><?php if(count($epics)===0): ?><span class="badge bg-danger ms-1" style="font-size:.6rem">À affecter</span><?php endif; ?></div>
            </div>
        </div>
        <div class="ev-kpi rounded-2xl transition hover:-translate-y-1 hover:shadow-wh-xl" style="--kpi-c:var(--wh-red);--kpi-bg:#f8d7da">
            <div class="kpi-icon" style="background:#f8d7da;color:var(--wh-red)"><i class="mdi mdi-alert-octagon"></i></div>
            <div>
                <div class="kpi-val"><?= count($anomalies) ?></div>
                <div class="kpi-label"><?= e(__('evenements.anomalies')) ?><?php if(count($anomalyDetailsFull??[])>0): $resolues=count(array_filter($anomalyDetailsFull, fn($a)=>($a['statut']??'')==='RESOLUE')); ?><span class="text-success ms-1" style="font-size:.65rem"><?= (int)$resolues ?>/<?= count($anomalyDetailsFull) ?> résolues</span><?php endif; ?></div>
            </div>
        </div>
    </div>

    <!-- ═══ PROCHAINE ACTION + COMPLÉTUDE + SUGGESTIONS (vision dossier) ═══ -->
    <?php $pa = $prochaineAction ?? []; ?>
    <?php $comp = $completude ?? ['score' => 100, 'manque' => []]; ?>
    <?php $prio = $priorite ?? ['niveau' => 'normal', 'raisons' => []]; ?>
    <?php $sugs = $suggestions ?? []; ?>
    <?php if (! empty($pa)): ?>
    <?php
        $paColor = match ($pa['priorite'] ?? 'moyenne') {
            'haute'  => 'var(--wh-red)',
            'moyenne'=> 'var(--wh-amber)',
            default  => 'var(--wh-green)',
        };
        $paBg = match ($pa['priorite'] ?? 'moyenne') {
            'haute'  => '#f8d7da',
            'moyenne'=> '#fff3cd',
            default  => 'var(--wh-green-soft)',
        };
        $paIcon = ($pa['priorite'] ?? '') === 'haute' ? 'mdi-alert-circle' : 'mdi-arrow-right-bold-circle-outline';
        $priNivColor = match ($prio['niveau'] ?? 'normal') {
            'urgent'   => 'var(--wh-red)',
            'important'=> 'var(--wh-amber)',
            default    => 'var(--wh-green)',
        };
        $compColor = (int) $comp['score'] >= 90 ? 'var(--wh-green)' : ((int) $comp['score'] >= 60 ? 'var(--wh-amber)' : 'var(--wh-red)');
    ?>
    <div class="ev-card mb-4" style="border-inline-start:4px solid <?= e($paColor) ?>">
        <div class="ev-card-head font-heading">
            <span><i class="mdi <?= e($paIcon) ?>"></i><?= $isAr ? 'الإجراء التالي' : 'Prochaine action' ?></span>
            <span class="d-flex align-items-center gap-2">
                <span class="badge border" style="background:#fff;color:<?= e($priNivColor) ?>">
                    <i class="mdi <?= $prio['niveau']==='urgent' ? 'mdi-alert-octagon' : ($prio['niveau']==='important' ? 'mdi-fire' : 'mdi-check-circle') ?> me-1"></i>
                    <?= e(ucfirst((string) ($prio['niveau'] ?? 'normal'))) ?>
                </span>
                <span class="badge border" style="background:<?= e($paBg) ?>;color:<?= e($paColor) ?>"><?= e(statut_label((string) ($pa['statut'] ?? ''))) ?></span>
            </span>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-3 p-3">
            <div style="width:44px;height:44px;border-radius:.6rem;display:grid;place-items:center;background:<?= e($paBg) ?>;color:<?= e($paColor) ?>;font-size:1.3rem">
                <i class="mdi <?= $pa['priorite']==='haute' ? 'mdi-alert-octagon' : ($pa['priorite']==='moyenne' ? 'mdi-clipboard-alert' : 'mdi-check-decagram') ?>"></i>
            </div>
            <div class="flex-grow-1">
                <div class="fw-bold" style="font-size:.95rem"><?= e($pa['titre']) ?></div>
                <div class="small d-flex flex-wrap gap-3 mt-1" style="color:var(--wh-text-muted)">
                    <span><i class="mdi mdi-account-tie me-1"></i><?= e($isAr ? 'المسؤول' : 'Responsable') ?>: <b class="text-dark"><?= e($pa['responsable']) ?></b></span>
                    <?php if (! empty($pa['sla_label'])): ?>
                    <span class="<?= $pa['overdue'] ? 'text-danger fw-semibold' : '' ?>">
                        <i class="mdi mdi-timer-outline me-1"></i><?= e($isAr ? 'المدة المتبقية' : 'Délai restant') ?>: <b><?= e($pa['sla_label']) ?></b>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (! empty($pa['lien'])): ?>
                <a href="<?= e($pa['lien']) ?>" class="btn btn-sm" style="background:<?= e($paColor) ?>;color:#fff">
                    <?= e($isAr ? 'فتح الملف' : 'Ouvrir le dossier') ?><i class="mdi mdi-arrow-right ms-1"></i>
                </a>
            <?php endif; ?>
        </div>
        <?php if (! empty($prio['raisons'])): ?>
        <div class="px-3 pb-2 small" style="color:var(--wh-text-muted)">
            <?php foreach ($prio['raisons'] as $r): ?>
                <span class="me-3"><i class="mdi mdi-information-outline text-danger me-1"></i><?= e($r) ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-7">
            <div class="ev-card h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="fw-bold small"><i class="mdi mdi-clipboard-check-outline me-1 text-primary"></i><?= $isAr ? 'اكتمال الملف' : 'Complétude du dossier' ?></span>
                    <span class="fw-bold" style="color:<?= e($compColor) ?>"><?= (int) $comp['score'] ?>%</span>
                </div>
                <div class="progress mb-3" style="height:8px">
                    <div class="progress-bar" style="width:<?= (int) $comp['score'] ?>%;background:<?= e($compColor) ?>"></div>
                </div>
                <?php if (empty($comp['manque'])): ?>
                    <div class="small text-success"><i class="mdi mdi-check-circle me-1"></i><?= $isAr ? 'الملف مكتمل' : 'Dossier complet' ?></div>
                <?php else: ?>
                    <div class="small text-danger fw-semibold mb-1"><?= $isAr ? 'عناصر ناقصة' : 'Éléments manquants' ?>:</div>
                    <div class="d-flex flex-wrap gap-1">
                        <?php foreach ($comp['manque'] as $m): ?>
                            <span class="badge <?= $m['important'] ? 'bg-danger-subtle text-danger' : 'bg-secondary-subtle text-secondary' ?>">
                                <i class="mdi mdi-close-circle me-1"></i><?= e($m['libelle']) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php $est = $estimation ?? ['jours' => 0, 'label' => '', 'confiance' => 'basse']; ?>
                <?php if (! empty($est['label'])): ?>
                <div class="d-flex align-items-center gap-2 mt-3 pt-2 border-top small" style="color:var(--wh-text-muted)">
                    <i class="mdi mdi-clock-fast text-primary"></i>
                    <span><?= $isAr ? 'المدة التقديرية للمعالجة' : 'Délai de traitement estimé' ?>:</span>
                    <b class="text-dark"><?= e($est['label']) ?></b>
                    <span class="badge border ms-auto" style="background:<?= $est['confiance']==='haute' ? 'var(--wh-green-soft)' : ($est['confiance']==='moyenne' ? '#fff3cd' : '#e9ecef') ?>;color:var(--wh-text)"><?= e(ucfirst($est['confiance'])) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php if (! empty($sugs)): ?>
        <div class="col-md-5">
            <div class="ev-card h-100">
                <span class="fw-bold small d-block mb-2"><i class="mdi mdi-lightbulb-on-outline me-1 text-warning"></i><?= $isAr ? 'اقتراحات تلقائية' : 'Suggestions automatiques' ?></span>
                <ul class="small mb-0 ps-3" style="color:var(--wh-text)">
                    <?php foreach ($sugs as $s): ?>
                        <li class="mb-1" style="color:var(--wh-text-muted)"><?= e($s) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if (! empty($escalades)): ?>
    <div class="ev-card mb-4" style="border-inline-start:4px solid var(--wh-red)">
        <div class="ev-card-head font-heading">
            <span><i class="mdi mdi-alert-rhombus-outline text-danger"></i><?= $isAr ? 'المتابعة والتصعيد' : 'Suivi & escalades' ?> <span class="badge bg-danger ms-1" style="font-size:.65rem"><?= count($escalades) ?></span></span>
        </div>
        <div class="p-3">
            <?php foreach ($escalades as $sc): ?>
                <?php $scCol = $sc['gravite']==='haute' ? 'var(--wh-red)' : ($sc['gravite']==='moyenne' ? 'var(--wh-amber)' : 'var(--wh-blue)'); ?>
                <div class="d-flex align-items-center gap-2 py-1">
                    <span class="badge border" style="background:#fff;color:<?= e($scCol) ?>"><i class="mdi <?= $sc['gravite']==='haute' ? 'mdi-alert-octagon' : 'mdi-alert-circle-outline' ?> me-1"></i><?= e(ucfirst($sc['gravite'])) ?></span>
                    <span class="small flex-grow-1"><?= e($sc['label']) ?></span>
                    <?php if (! empty($sc['epics'])): ?><span class="small" style="color:var(--wh-text-muted)">· <?= e(implode(', ', $sc['epics'])) ?></span><?php endif; ?>
                </div>
            <?php endforeach; ?>
            <div class="small mt-2" style="color:var(--wh-text-muted)"><i class="mdi mdi-information-outline me-1"></i><?= $isAr ? 'يجري تحديث هذا تلقائيًا مرة واحدة لكل ملف' : 'Rafraîchit automatiquement à chaque ouverture du dossier' ?></div>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>

    <!-- ═══ PHOTOS ═══ -->
    <?php if (! empty($photos)): ?>
    <div class="ev-card mb-4">
        <div class="ev-card-head font-heading">
            <span><i class="mdi mdi-image-multiple"></i><?= e(__('common.gallery')) ?> <span class="badge bg-secondary" style="font-size:.65rem"><?= count($photos) ?></span></span>
            <a href="<?= url('wilaya/evenements/' . (int) $event['id'] . '/photos') ?>" class="btn btn-sm btn-outline-primary">
                <i class="mdi mdi-cog me-1"></i><?= e(__('common.gallery')) ?>
            </a>
        </div>
        <div class="ev-card-body">
            <div class="ev-gallery" id="evGallery">
                <?php foreach (array_slice($photos, 0, 8) as $photo): ?>
                    <a href="<?= e(photo_src($photo)) ?>" class="ev-gallery-item" data-lightbox="ev" title="<?= e($photo['legende'] ?? $photo['image'] ?? '') ?>">
                        <?php if (! empty($photo['image'])): ?>
                            <img src="<?= e(photo_src($photo)) ?>" alt="<?= e($photo['legende'] ?? '') ?>" loading="lazy">
                        <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center h-100 text-muted"><i class="mdi mdi-image-off"></i></div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
                <?php if (count($photos) > 8): ?>
                    <a href="<?= url('wilaya/evenements/' . (int) $event['id'] . '/photos') ?>" class="ev-gallery-item" style="text-decoration:none">
                        <div class="ev-gallery-more">+<?= count($photos) - 8 ?></div>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplelightbox@2.14.3/dist/simple-lightbox.min.css">
        <script src="https://cdn.jsdelivr.net/npm/simplelightbox@2.14.3/dist/simple-lightbox.min.js"></script>
        <script>document.addEventListener('DOMContentLoaded',function(){ if(window.SimpleLightbox) new SimpleLightbox('#evGallery a[data-lightbox]',{captionsData:'title'}); });</script>
    </div>
    <?php elseif (can('gallery.upload')): ?>
    <div class="ev-card mb-4">
        <div class="ev-card-body text-center" style="padding:2rem">
            <i class="mdi mdi-image-plus" style="font-size:2.5rem;color:var(--wh-gray-light);display:block;margin-bottom:.5rem"></i>
            <p class="mb-2" style="color:var(--wh-text-muted)"><?= e(__('gallery.no_photos')) ?></p>
            <a href="<?= url('wilaya/evenements/' . (int) $event['id'] . '/photos/create') ?>" class="btn btn-sm btn-primary">
                <i class="mdi mdi-plus me-1"></i><?= e(__('gallery.add_photos')) ?>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- ═══ PIPELINE ═══ -->
    <div class="ev-card ev-pipeline-card mb-4">
        <div class="ev-card-head font-heading">
            <span><i class="mdi mdi-chart-timeline-variant"></i><?= $isAr ? 'مسار الفعالية' : 'Pipeline de progression' ?></span>
        </div>
        <div class="ev-card-body">
            <?php
                $statut = (string) ($event['statut'] ?? 'EN_ATTENTE');
                include __DIR__ . '/../../partials/pipeline.php';
            ?>
        </div>
    </div>

    <!-- ═══ STATUS CHANGE — Modale "Demander modifications" === -->
    <?php if (! $isDeleted): ?>
    <div class="ev-card mb-4">
        <div class="ev-card-head font-heading">
            <span><i class="mdi mdi-source-branch"></i><?= e(__('common.status')) ?></span>
            <?php if (EvenementService::transitionAutorisee($statutActuel, 'MODIFICATION_DEMANDEE')): ?>
                <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modifModal">
                    <i class="mdi mdi-pencil-outline me-1"></i><?= $isAr ? 'طلب تعديل' : 'Demander modifications' ?>
                </button>
            <?php endif; ?>
        </div>
        <div class="ev-card-body">
            <form method="post" action="<?= url('wilaya/evenements/' . (int) $event['id'] . '/statut') ?>" class="row g-2 align-items-end">
                <?= csrf_field() ?>
                <div class="col-md-3">
                    <label class="form-label fw-semibold" style="font-size:.78rem"><?= e(__('common.status')) ?></label>
                    <select class="form-select form-select-sm" name="statut" required>
                        <?php foreach ($statuts as $s): ?>
                            <?php if (EvenementService::transitionAutorisee($statutActuel, $s)): ?>
                                <option value="<?= e($s) ?>"><?= e(statut_label($s)) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" style="font-size:.78rem"><?= e(__('evenements.motif_refus')) ?></label>
                    <input type="text" class="form-control form-control-sm" name="motif" placeholder="<?= e(__('evenements.motif_refus')) ?>">
                </div>
                <div class="col-md-3 d-grid">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="mdi mdi-check me-1"></i><?= e(__('common.validate')) ?></button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal Demander modifications -->
    <?php if (! $isDeleted && EvenementService::transitionAutorisee($statutActuel, 'MODIFICATION_DEMANDEE')): ?>
    <div class="modal fade" id="modifModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post" action="<?= url('wilaya/evenements/' . (int) $event['id'] . '/statut') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="statut" value="MODIFICATION_DEMANDEE">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="mdi mdi-pencil-outline me-2"></i><?= $isAr ? 'طلب تعديلات' : 'Demander des modifications' ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label fw-semibold small"><?= $isAr ? 'اختر نموذجاً' : 'Modèles rapides' ?></label>
                        <div class="d-flex flex-wrap gap-1 mb-3" id="modifTemplates">
                            <?php $tpls = $isAr ? ['معلومات ناقصة','صور مفقودة','عنوان غير دقيق','سعة غير صحيحة','تفاصيل الشذوذ ناقصة'] : ['Informations incomplètes','Photos manquantes','Adresse imprécise','Capacité à corriger','Détails anomalie à préciser']; ?>
                            <?php foreach ($tpls as $tpl): ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary modif-tpl" data-text="<?= e($tpl) ?> — "><?= e($tpl) ?></button>
                            <?php endforeach; ?>
                        </div>
                        <label for="modifMotif" class="form-label fw-semibold small"><?= e(__('evenements.motif_refus')) ?> <span class="text-danger">*</span></label>
                        <textarea id="modifMotif" name="motif" class="form-control" rows="4" maxlength="500" required placeholder="<?= $isAr ? 'اكتب سبب طلب التعديل...' : 'Décrivez précisément les corrections attendues...' ?>"></textarea>
                        <div class="d-flex justify-content-between mt-1">
                            <small class="text-muted" style="font-size:.7rem"><?= $isAr ? 'سيتم إشعار الجمعية فوراً' : 'L’association sera notifiée immédiatement' ?></small>
                            <small class="text-muted" style="font-size:.7rem"><span id="modifCounter">0</span>/500</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?= e(__('common.cancel')) ?></button>
                        <button type="submit" class="btn btn-warning btn-sm"><i class="mdi mdi-send me-1"></i><?= $isAr ? 'إرسال الطلب' : 'Envoyer la demande' ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded',function(){
        var ta=document.getElementById('modifMotif'); var cnt=document.getElementById('modifCounter');
        if(ta&&cnt){ ta.addEventListener('input',function(){ cnt.textContent=ta.value.length; cnt.style.color=ta.value.length>450?'#dc3545':''; }); }
        document.querySelectorAll('.modif-tpl').forEach(function(b){ b.addEventListener('click',function(){ if(!ta) return; var t=b.getAttribute('data-text'); ta.value = ta.value ? ta.value + "\n" + t : t; ta.dispatchEvent(new Event('input')); ta.focus(); }); });
    });
    </script>
    <?php endif; ?>

    <!-- ═══ VALIDATION & ASSIGNATION ═══ -->
    <?php if ($statutActuel === 'EN_ATTENTE'): ?>
    <div class="ev-card ev-validate-card mb-4">
        <div class="ev-card-head font-heading">
            <span><i class="mdi mdi-account-multiple-check"></i><?= $isAr ? 'التحقق والتعيين' : 'Validation et affectation' ?></span>
            <?php if ($association): ?>
                <span class="text-muted" style="font-size:.78rem;font-weight:400">
                    <a href="<?= url('wilaya/associations/' . (int) ($association['id'] ?? 0)) ?>" class="text-decoration-none fw-bold"><?= e($association['nom'] ?? '-') ?></a>
                    <?php if ($association['email'] ?? ''): ?>
                        <a href="mailto:<?= e($association['email']) ?>" title="<?= e($association['email']) ?>" class="ms-1"><i class="mdi mdi-email-outline"></i></a>
                    <?php endif; ?>
                    <?php if ($association['telephone'] ?? ''): ?>
                        <a href="tel:<?= e($association['telephone']) ?>" title="<?= e($association['telephone']) ?>" class="ms-1"><i class="mdi mdi-phone"></i></a>
                    <?php endif; ?>
                </span>
            <?php endif; ?>
        </div>
        <div class="ev-card-body">
            <form method="post" action="<?= url('wilaya/evenements/' . (int) $event['id'] . '/validate') ?>" class="row g-3 align-items-end">
                <?= csrf_field() ?>
                <div class="col-md-3">
                    <label class="form-label fw-semibold" style="font-size:.78rem"><?= e(__('common.association')) ?></label>
                    <input type="text" class="form-control form-control-sm" value="<?= e($association['nom'] ?? '-') ?>" readonly>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold" style="font-size:.78rem"><?= e(__('evenements.date_proposee')) ?></label>
                    <input type="text" class="form-control form-control-sm" value="<?= e($event['date_evenement'] ?? '-') ?>" readonly>
                </div>
                <div class="col-md-1">
                    <label class="form-label fw-semibold" style="font-size:.78rem"><?= e(__('evenements.heure')) ?></label>
                    <input type="text" class="form-control form-control-sm" value="<?= e($event['heure'] ? substr((string) $event['heure'], 0, 5) : '-') ?>" readonly>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold" style="font-size:.78rem" for="date_evenement"><?= e(__('common.date')) ?></label>
                    <input type="date" class="form-control form-control-sm" id="date_evenement" name="date_evenement" required>
                </div>
                <div class="col-md-1">
                    <label class="form-label fw-semibold" style="font-size:.78rem" for="heure"><?= e(__('evenements.program.heure')) ?></label>
                    <input type="time" class="form-control form-control-sm" id="heure" name="heure" value="09:00" required>
                </div>
                <?php $epicIdsLies = array_column($epics ?? [], 'id'); ?>
                <div class="col-md-3">
                    <label class="form-label fw-semibold" style="font-size:.78rem" for="epics"><?= e(__('evenements.epics_assigned')) ?></label>
                    <select class="form-select form-select-sm" id="epics" name="epics[]" multiple size="4" required>
                        <?php foreach ($epicsListe as $ep): ?>
                            <option value="<?= e($ep['id']) ?>" <?= in_array((int) $ep['id'], $epicIdsLies, true) ? 'selected' : '' ?>>
                                <?= e($ep['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text" style="font-size:.7rem">Ctrl+clic pour plusieurs EPICs</div>
                </div>
                <div class="col-12 d-grid d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="mdi mdi-check-circle me-1"></i>Valider et affecter
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- ═══ MAIN CONTENT — TABS === -->
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-infos" type="button"><i class="mdi mdi-information-outline me-1"></i><?= $isAr ? 'معلومات' : 'Infos' ?></button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-anomalies" type="button"><i class="mdi mdi-alert-octagon me-1"></i><?= e(__('evenements.anomalies')) ?> <span class="badge bg-danger ms-1"><?= count($anomalies) ?></span></button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-discuss" type="button"><i class="mdi mdi-message-text-outline me-1"></i><?= $isAr ? 'نقاش' : 'Discussion' ?></button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-historique" type="button"><i class="mdi mdi-history me-1"></i><?= e(__('common.historique')) ?></button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-documents" type="button"><i class="mdi mdi-paperclip me-1"></i><?= e(__('documents.title')) ?></button></li>
            </ul>
            <div class="tab-content">
            <div class="tab-pane fade show active" id="tab-infos">
            <!-- Informations -->
            <div class="ev-card mb-4">
                <div class="ev-card-head font-heading">
                    <span><i class="mdi mdi-information-outline"></i><?= e(__('common.informations')) ?></span>
                </div>
                <div class="ev-card-body">
                    <dl class="row ev-info-dl mb-0">
                        <dt class="col-sm-4"><?= e(__('common.commune')) ?></dt>
                        <dd class="col-sm-8"><?= e($commune['nom'] ?? '-') ?></dd>

                        <dt class="col-sm-4"><?= e(__('common.association')) ?></dt>
                        <dd class="col-sm-8">
                            <a href="<?= url('wilaya/associations/' . (int) ($association['id'] ?? 0)) ?>" class="text-decoration-none fw-medium">
                                <?= e($association['nom'] ?? '-') ?>
                            </a>
                            <?php if ($association): ?>
                                <span class="text-muted" style="font-size:.8rem">(<?= e($association['email'] ?? '') ?>)</span>
                            <?php endif; ?>
                        </dd>

                        <dt class="col-sm-4"><?= e(__('evenements.program.heure')) ?></dt>
                        <dd class="col-sm-8"><?= $event['heure'] ? '<i class="mdi mdi-clock-outline me-1"></i>' . e(substr((string) $event['heure'], 0, 5)) : '—' ?></dd>

                        <?php if (!empty($event['start_at'])): ?>
                            <dt class="col-sm-4"><?= $isAr ? 'البداية' : 'Début' ?></dt>
                            <dd class="col-sm-8"><i class="mdi mdi-calendar-start me-1" style="color:var(--wh-green)"></i><?= e(date('d/m/Y H:i', strtotime((string) $event['start_at']))) ?></dd>
                        <?php endif; ?>
                        <?php if (!empty($event['end_at'])): ?>
                            <dt class="col-sm-4"><?= $isAr ? 'النهاية' : 'Fin' ?></dt>
                            <dd class="col-sm-8"><i class="mdi mdi-calendar-end me-1" style="color:var(--wh-red)"></i><?= e(date('d/m/Y H:i', strtotime((string) $event['end_at']))) ?></dd>
                        <?php endif; ?>

                        <dt class="col-sm-4"><?= e(__('common.description')) ?></dt>
                        <dd class="col-sm-8" style="line-height:1.6"><?= nl2br(e($event['description'])) ?></dd>

                        <?php if ($event['informations_complementaires']): ?>
                            <dt class="col-sm-4"><?= e(__('evenements.complementaires')) ?></dt>
                            <dd class="col-sm-8" style="line-height:1.6"><?= nl2br(e($event['informations_complementaires'])) ?></dd>
                        <?php endif; ?>

                        <?php if ($event['motif_refus']): ?>
                            <dt class="col-sm-4 text-danger fw-bold"><?= e(__('evenements.motif_refus')) ?></dt>
                            <dd class="col-sm-8 text-danger" style="background:#f8d7da;padding:.4rem .6rem;border-radius:.35rem"><i class="mdi mdi-alert-circle me-1"></i><?= e($event['motif_refus']) ?></dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>
            <?php if(!empty($related)): ?>
            <div class="ev-card mb-3"><div class="ev-card-head font-heading"><span><i class="mdi mdi-link-variant"></i><?= $isAr ? 'فعاليات مشابهة' : 'Événements liés (même commune)' ?></span></div><div class="ev-card-body d-flex flex-wrap gap-2"><?php foreach($related as $r): ?><a href="<?= url('wilaya/evenements/'.(int)$r['id']) ?>" class="btn btn-sm btn-outline-secondary"><span class="badge <?= $badgeColor($r['statut']) ?> me-1" style="font-size:.6rem"><?= e(statut_label($r['statut'])) ?></span><?= e(mb_strimwidth($r['adresse'],0,28,'…')) ?></a><?php endforeach; ?></div></div>
            <?php endif; ?>
            </div><!-- /tab-infos -->
            <div class="tab-pane fade" id="tab-anomalies">
            <!-- Anomalies -->
            <div class="ev-card mb-4">
                <div class="ev-card-head font-heading">
                    <span><i class="mdi mdi-alert-octagon"></i><?= e(__('evenements.anomalies')) ?></span>
                    <?php if (count($anomalies) > 0): ?>
                        <span class="badge bg-danger" style="font-size:.65rem"><?= count($anomalies) ?></span>
                    <?php endif; ?>
                </div>
                <div class="ev-card-body">
                    <?php
                    $anomalyAssignments = Database::all(
                        'SELECT aa.*, an.nom AS anomalie_nom, ep.nom AS epic_nom
                         FROM anomaly_assignments aa
                         JOIN anomalies an ON an.id = aa.anomalie_id
                         LEFT JOIN epic ep ON ep.id = aa.epic_id
                         WHERE aa.evenement_id = ?',
                        [(int) $event['id']]
                    );
                    $anomalyDetails = Database::all(
                        'SELECT ae.*, an.nom AS anomalie_nom
                         FROM anomalies_evenement ae
                         JOIN anomalies an ON an.id = ae.anomalie_id
                         WHERE ae.evenement_id = ?',
                        [(int) $event['id']]
                    );
                    ?>
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <div class="flex-grow-1"><input class="form-control form-control-sm" id="anomalySearch" placeholder="<?= $isAr?'فلترة الشذوذ':'Filtrer anomalies…' ?>"></div>
                        <select class="form-select form-select-sm" id="bulkAnomalyStatus" style="width:auto"><option value=""><?= $isAr?'تغيير جماعي':'Bulk statut' ?></option><option>DETECTEE</option><option>EN_COURS</option><option>RESOLUE</option><option>REJETEE</option><option>EN_ATTENTE</option></select>
                        <button class="btn btn-sm btn-outline-primary" id="bulkAnomalyApply"><i class="mdi mdi-check-all"></i></button>
                    </div>
                    <?php if ($anomalyDetails): ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0 ev-anomaly-table" id="anomalyTable">
                                <thead>
                                <tr>
                                    <th><?= $isAr ? 'الشذوذ' : 'Anomalie' ?></th>
                                    <th><?= e(__('common.status')) ?></th>
                                    <th>GPS</th>
                                    <th><?= $isAr ? 'ال EPIC المعيّن' : 'EPIC assignée' ?></th>
                                    <th><?= $isAr ? 'الوضع' : 'Mode' ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($anomalyDetails as $ad): ?>
                                    <?php
                                    $assign = null;
                                    foreach ($anomalyAssignments as $aa) {
                                        if ((int) $aa['anomalie_id'] === (int) $ad['anomalie_id']) { $assign = $aa; break; }
                                    }
                                    $statusColors = [
                                        'DETECTEE' => 'warning', 'EN_COURS' => 'primary',
                                        'RESOLUE' => 'success', 'REJETEE' => 'danger', 'EN_ATTENTE' => 'secondary',
                                    ];
                                    $st = (string) ($ad['statut'] ?? 'DETECTEE');
                                    ?>
                                    <tr>
                                        <td class="fw-semibold"><i class="mdi mdi-alert-circle me-1" style="color:var(--wh-red)"></i><?= e((string) $ad['anomalie_nom']) ?></td>
                                        <td>
                                            <select class="form-select form-select-sm anomaly-status" data-evenement="<?= (int) $event['id'] ?>" data-anomalie="<?= (int) $ad['anomalie_id'] ?>" style="width:auto;display:inline-block;min-width:120px">
                                                <?php foreach (['DETECTEE','EN_COURS','RESOLUE','REJETEE','EN_ATTENTE'] as $opt): ?>
                                                    <option value="<?= e($opt) ?>" <?= $st===$opt?'selected':'' ?>><?= e($opt) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <?php if ($ad['latitude'] && $ad['longitude']): ?>
                                                <a href="https://www.openstreetmap.org/?mlat=<?= e((string) $ad['latitude']) ?>&mlon=<?= e((string) $ad['longitude']) ?>#map=15" target="_blank" class="text-decoration-none" style="font-size:.8rem">
                                                    <i class="mdi mdi-map-marker" style="color:var(--wh-red)"></i> <?= e($ad['latitude']) ?>, <?= e($ad['longitude']) ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($assign): ?>
                                                <span class="wh-pill badge badge-blue"><i class="mdi mdi-satellite-variant"></i> <?= e((string) $assign['epic_nom']) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($assign): ?>
                                                <span class="wh-pill badge bg-<?= $assign['auto_routed'] ? 'success' : 'warning text-dark' ?>"><?= $assign['auto_routed'] ? '<i class="mdi mdirobot"></i> Auto' : '<i class="mdi mdi-hand"></i> Manuel' ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($anomalies as $an): ?>
                                <span class="wh-pill badge badge-red"><i class="mdi mdi-alert-octagon"></i> <?= e($an['nom']) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- GPS Map -->
            <?php if (!empty($event['latitude']) && !empty($event['longitude'])): ?>
            <div class="ev-card mb-4">
                <div class="ev-card-head font-heading">
                    <span><i class="mdi mdi-map-marker-radius"></i><?= $isAr ? 'الموقع GPS' : 'Position GPS' ?></span>
                </div>
                <div class="ev-card-body">
                    <div id="eventMap" style="height:260px" class="rounded-xl overflow-hidden border border-gray-200 shadow-sm"></div>
                    <div class="d-flex align-items-center justify-content-between mt-2">
                        <span class="text-muted" style="font-size:.78rem"><i class="mdi mdi-crosshairs-gps me-1"></i><?= e((string) $event['latitude']) ?>, <?= e((string) $event['longitude']) ?></span>
                        <a href="https://www.openstreetmap.org/?mlat=<?= e((string) $event['latitude']) ?>&mlon=<?= e((string) $event['longitude']) ?>#map=15" target="_blank" class="btn btn-outline-primary btn-sm" style="font-size:.78rem">
                            <i class="mdi mdi-map me-1"></i>OpenStreetMap
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- EPICs assignés -->
            <div class="ev-card mb-4">
                <div class="ev-card-head font-heading">
                    <span><i class="mdi mdi-satellite-variant"></i><?= e(__('evenements.epics_assigned')) ?></span>
                    <?php if ($epics): ?>
                        <span class="badge bg-warning text-dark" style="font-size:.65rem"><?= count($epics) ?></span>
                    <?php endif; ?>
                </div>
                <div class="ev-card-body">
                    <?php if ($epics): ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0" style="font-size:.85rem">
                                <thead>
                                <tr>
                                    <th style="font-size:.72rem;text-transform:uppercase;letter-spacing:.03em;color:var(--wh-text-muted)"><?= e(__('common.epic')) ?></th>
                                    <th style="font-size:.72rem;text-transform:uppercase;letter-spacing:.03em;color:var(--wh-text-muted)"><?= e(__('common.date')) ?></th>
                                    <th style="font-size:.72rem;text-transform:uppercase;letter-spacing:.03em;color:var(--wh-text-muted)"><?= e(__('evenements.complementaires')) ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($epics as $ep): ?>
                                    <tr class="ev-epic-row">
                                        <td>
                                            <div class="fw-semibold"><?= e($ep['nom']) ?></div>
                                            <?php if ($ep['description'] ?? ''): ?>
                                                <small class="text-muted"><?= e($ep['description']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td style="white-space:nowrap"><?= $ep['date_affectation'] ? '<i class="mdi mdi-calendar-outline me-1"></i>' . e(date('d/m/Y', strtotime((string) $ep['date_affectation']))) : '—' ?></td>
                                        <td class="text-muted"><?= e($ep['observation'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-3" style="color:var(--wh-text-muted)">
                            <i class="mdi mdi-satellite-variant" style="font-size:2rem;opacity:.3;display:block;margin-bottom:.5rem"></i>
                            <?= e(__('common.no_data')) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            </div><!-- /tab-anomalies -->
            <div class="tab-pane fade" id="tab-discuss">
            <!-- Messages & Checklist -->
            <?php $eventId = (int) $event['id']; ?>
            <?php include __DIR__ . '/../../partials/event_messages.php'; ?>
            <?php include __DIR__ . '/../../partials/event_checklist.php'; ?>
            </div>
            <div class="tab-pane fade" id="tab-historique">
            <!-- Historique -->
            <div class="ev-card mb-4">
                <div class="ev-card-head font-heading">
                    <span><i class="mdi mdi-clock-outline"></i><?= e(__('common.historique')) ?></span>
                    <?php if ($transitions): ?>
                        <span class="badge bg-secondary" style="font-size:.65rem"><?= count($transitions) ?></span>
                    <?php endif; ?>
                </div>
                <div class="ev-card-body">
                    <?php if ($transitions): ?>
                        <div class="ev-timeline">
                            <?php foreach ($transitions as $idx => $t): ?>
                                <?php
                                $dotColor = match (statut_key((string) $t['statut_apres'])) {
                                    'termine' => 'green',
                                    'refuse' => 'red',
                                    'en_attente', 'modification_demandee' => 'amber',
                                    default => '',
                                };
                                $initials = mb_substr((string)($t['user_prenom'] ?? ''),0,1) . mb_substr((string)($t['user_nom'] ?? ''),0,1);
                                $initials = trim($initials) !== '' ? mb_strtoupper($initials) : '•';
                                $duration = '';
                                if (isset($transitions[$idx+1])) {
                                    $a = strtotime((string) $t['created_at']); $b = strtotime((string) $transitions[$idx+1]['created_at']);
                                    $diff = abs($a-$b);
                                    if ($diff < 3600) $duration = floor($diff/60) . ' min';
                                    elseif ($diff < 86400) $duration = floor($diff/3600) . ' h';
                                    else $duration = floor($diff/86400) . ' j';
                                }
                                ?>
                                <div class="ev-timeline-item">
                                    <div class="ev-timeline-dot <?= $dotColor ?>"></div>
                                    <div class="ev-timeline-content">
                                        <div class="d-flex align-items-center gap-2">
                                            <span style="width:26px;height:26px;border-radius:50%;background:var(--wh-blue);color:#fff;display:grid;place-items:center;font-size:.65rem;font-weight:700;flex-shrink:0"><?= e($initials) ?></span>
                                            <span class="ev-pill badge badge-gray"><?= e(statut_label((string) $t['statut_avant'])) ?></span>
                                            <i class="mdi mdi-arrow-right" style="font-size:.8rem;color:var(--wh-text-muted)"></i>
                                            <span class="ev-pill badge <?= $badgeColor($t['statut_apres']) ?>"><?= e(statut_label((string) $t['statut_apres'])) ?></span>
                                            <?php if ($duration !== ''): ?><span class="badge bg-light border text-muted" style="font-size:.6rem">+<?= e($duration) ?></span><?php endif; ?>
                                        </div>
                                        <div class="ev-timeline-meta">
                                            <i class="mdi mdi-account me-1"></i><?= e(trim((string) ($t['user_prenom'] ?? '') . ' ' . ($t['user_nom'] ?? '')) ?: 'Système') ?>
                                            <span class="mx-1">·</span>
                                            <i class="mdi mdi-clock-outline me-1"></i><?= e(date('d/m/Y H:i', strtotime((string) $t['created_at']))) ?>
                                        </div>
                                        <?php if ($t['motif']): ?>
                                            <div class="ev-audit-note"><i class="mdi mdi-comment-outline me-1"></i><?= e($t['motif']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-3" style="color:var(--wh-text-muted)">
                            <i class="mdi mdi-history" style="font-size:2rem;opacity:.3;display:block;margin-bottom:.5rem"></i>
                            <?= e(__('common.no_data')) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            </div><!-- /tab-historique -->

            <!-- ═══ TAB: PIÈCES JOINTES (dossier) ═══ -->
            <div class="tab-pane fade" id="tab-documents">
                <?php
                $evDocs = Database::all(
                    'SELECT d.*, u.nom AS user_nom, u.prenom AS user_prenom
                     FROM event_documents d
                     LEFT JOIN users u ON u.id = d.uploaded_by
                     WHERE d.evenement_id = ?
                     ORDER BY d.created_at DESC, d.id DESC',
                    [(int) $event['id']]
                );
                echo view('partials.event_documents_list', ['documents' => $evDocs, 'evenement' => $event]);
                ?>
            </div>
            </div><!-- /tab-content -->
        </div>

        <!-- ═══ SIDEBAR ═══ -->
        <div class="col-lg-4">

            <!-- QR Code -->
            <?php if ($qr): ?>
            <div class="ev-card ev-qr-card mb-4">
                <div class="ev-card-head font-heading">
                    <span><i class="mdi mdi-qrcode"></i><?= e(__('common.qrcode')) ?></span>
                </div>
                <div class="ev-card-body">
                    <img src="<?= $qrStreamUrl ? $qrStreamUrl : QrCodeGenerator::pngDataUri(network_url('checkin/' . $qr['token_qr']), 220) ?>"
                         alt="QR Code" loading="lazy" class="ev-qr-img wh-qr-print">
                    <div class="ev-qr-info">
                        <strong><?= e(substr((string) ($event['description'] ?? ''), 0, 50) ?: 'Événement') ?></strong>
                        <div><i class="mdi mdi-map-marker me-1"></i><?= e($event['adresse'] ?? '-') ?></div>
                        <div><i class="mdi mdi-calendar me-1"></i><?= e($event['date_evenement'] ? date('d/m/Y', strtotime((string) $event['date_evenement'])) : '-') ?>
                             à <?= e($event['heure'] ? substr((string) $event['heure'], 0, 5) : '-') ?></div>
                        <?php if ($epics): ?>
                            <div><i class="mdi mdi-account-multiple me-1"></i>
                                <?= e(implode(', ', array_map(fn($ep) => (string) $ep['nom'], $epics))) ?></div>
                        <?php endif; ?>
                    </div>
                    <div style="margin:.75rem 0">
                        <a href="<?= url('checkin/' . $qr['token_qr']) ?>" class="text-decoration-none" style="font-size:.78rem;color:var(--wh-blue)" target="_blank" rel="noopener">
                            <?= e(url('checkin/' . $qr['token_qr'])) ?>
                        </a>
                        <?php if ($qr['date_expiration']): ?>
                            <div style="font-size:.72rem;color:var(--wh-text-muted);margin-top:.2rem">
                                <i class="mdi mdi-clock-outline me-1"></i><?= e(date('d/m/Y H:i', strtotime((string) $qr['date_expiration']))) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="qrCopyBtn" data-url="<?= e(url('checkin/' . $qr['token_qr'])) ?>">
                            <i class="mdi mdi-content-copy me-1"></i>Copier lien
                        </button>
                        <?php if ($qrDownloadUrl): ?>
                            <a href="<?= $qrDownloadUrl ?>" class="btn btn-outline-primary btn-sm" download>
                                <i class="mdi mdi-download me-1"></i>Télécharger
                            </a>
                        <?php endif; ?>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                            <i class="mdi mdi-printer me-1"></i>Imprimer
                        </button>
                        <?php if (! $isDeleted && $permission('qrcode.generate')): ?>
                            <form method="post" action="<?= url('wilaya/evenements/' . (int) $event['id'] . '/regen-qr') ?>" class="d-inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-outline-primary btn-sm"><i class="mdi mdi-refresh me-1"></i><?= e(__('common.regenerate')) ?></button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- EPIC Assignment (single source of truth) -->
            <?php if (! $isDeleted && $permission('epic.assign')): ?>
            <div class="ev-card mb-4">
                <div class="ev-card-head font-heading">
                    <span><i class="mdi mdi-satellite-variant"></i><?= e(__('evenements.epics_assigned')) ?></span>
                    <?php
                    $orgNom = '';
                    foreach ($epicsListe as $epicItem) {
                        if ((int) $epicItem['id'] === (int) ($event['assigned_org_id'] ?? 0)) {
                            $orgNom = $epicItem['nom'];
                            break;
                        }
                    }
                    ?>
                    <span class="wh-pill <?= ($event['assigned_org_id'] ?? null) ? 'badge-blue' : 'badge-gray' ?>">
                        <?= ($event['assigned_org_id'] ?? null) ? '<i class="mdi mdi-check-circle"></i> ' . e($orgNom ?: 'Routé') : '<i class="mdi mdi-close-circle"></i> Non routé' ?>
                    </span>
                </div>
                <div class="ev-card-body">
                    <form method="post" action="<?= url('wilaya/evenements/' . (int) $event['id'] . '/epics') ?>">
                        <?= csrf_field() ?>
                        <select class="form-select form-select-sm mb-2" name="epics[]" multiple size="5">
                            <?php foreach ($epicsListe as $ep): ?>
                                <?php $assigned = in_array((int) $ep['id'], array_column($epics, 'id'), true); ?>
                                <option value="<?= (int) $ep['id'] ?>" <?= $assigned ? 'selected' : '' ?>><?= e($ep['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text mb-2" style="font-size:.7rem">
                            <i class="mdi mdi-information-outline"></i>
                            <?= $isAr ? 'Ctrl+نقر لل่ multiple' : 'Ctrl+clic pour plusieurs EPICs' ?>
                            — <?= $isAr ? 'سيتم تحديث جميع العلاقات' : 'Sync auto evenement_epic + assigned_org_id' ?>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100"><i class="mdi mdi-content-save me-1"></i><?= e(__('common.save')) ?></button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Participants MAX -->
            <div class="ev-card mb-4">
                <div class="ev-card-head font-heading"><span><i class="mdi mdi-account-group"></i>Participants <span class="badge bg-primary ms-1" style="font-size:.65rem"><?= (int)$participants ?></span></span><a href="<?= url('wilaya/evenements/'.(int)$event['id'].'/export') ?>" class="btn btn-sm btn-outline-primary" style="font-size:.65rem"><i class="mdi mdi-download"></i></a></div>
                <div class="ev-card-body p-0">
                    <div class="p-2"><input class="form-control form-control-sm" id="partSearch" placeholder="Rechercher participant…"></div>
                    <div style="max-height:240px;overflow:auto" id="partList">
                    <?php if(empty($participantsList)): ?><div class="text-center text-muted py-3 small">Aucun participant — en attente scans QR</div><?php else: foreach($participantsList as $p): ?>
                        <div class="d-flex align-items-center gap-2 p-2 border-bottom part-row"><span style="width:28px;height:28px;border-radius:50%;background:var(--wh-blue-soft);color:var(--wh-blue);display:grid;place-items:center;font-weight:700;font-size:.7rem"><?= e(mb_substr($p['prenom']??'',0,1).mb_substr($p['nom']??'',0,1)) ?></span><div class="flex-grow-1"><div class="fw-semibold" style="font-size:.82rem"><?= e(trim(($p['prenom']??'').' '.($p['nom']??''))) ?></div><div class="text-muted" style="font-size:.7rem"><?= e($p['email']??'') ?></div></div><span class="text-muted" style="font-size:.7rem"><?= $p['heure_scan']?e(date('d/m H:i',strtotime($p['heure_scan']))):'<span class="badge bg-warning text-dark">En attente</span>' ?></span></div>
                    <?php endforeach; endif; ?>
                    </div>
                    <?php if($tauxRemplissage!==null): ?><div class="p-2 small text-muted">Taux <?= (int)$tauxRemplissage ?>% — <?= (int)$participants ?>/<?= (int)($event['capacite']??'?') ?> — <?= max(0,(int)($event['capacite']??0)-(int)$participants) ?> places restantes</div><?php endif; ?>
                </div>
            </div>

            <!-- Audit -->
            <?php if ($historique): ?>
            <div class="ev-card mb-4">
                <div class="ev-card-head font-heading">
                    <span><i class="mdi mdi-clipboard-text-outline"></i><?= e(__('common.audit')) ?></span>
                    <span class="badge bg-secondary" style="font-size:.65rem"><?= count($historique) ?></span>
                </div>
                <div class="ev-card-body">
                    <?php foreach ($historique as $h): ?>
                        <div class="ev-audit-item">
                            <div class="ev-audit-action"><i class="mdi mdi-circle" style="width:5px;height:5px;border-radius:50%;background:var(--wh-blue);display:inline-block;margin-inline-end:.35rem"></i><?= e($h['action']) ?></div>
                            <div class="ev-audit-time"><?= e(date('d/m/Y H:i', strtotime((string) $h['date_action']))) ?></div>
                            <?php if ($h['observation']): ?>
                                <div class="ev-audit-note"><?= e($h['observation']) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ═══ COMMENTAIRES & NOTES INTERNES ═══ -->
    <?php
    $isAr = I18n::direction() === 'rtl';
    $isLogged = true;
    include __DIR__ . '/../../../Views/partials/event_comments.php';
    ?>
</div>

<?php if (!empty($event['latitude']) && !empty($event['longitude'])): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded',function(){
    var map=L.map('eventMap').setView([<?= e((string) $event['latitude']) ?>,<?= e((string) $event['longitude']) ?>],14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'&copy; OpenStreetMap',maxZoom:19}).addTo(map);
    var evMarker=L.marker([<?= e((string) $event['latitude']) ?>,<?= e((string) $event['longitude']) ?>],{icon:L.divIcon({html:'<div style="background:var(--wh-blue);color:#fff;width:28px;height:28px;border-radius:50%;display:grid;place-items:center;border:2px solid #fff"><i class="mdi mdi-calendar-star"></i></div>', iconSize:[28,28]})}).addTo(map).bindPopup('<b><?= e(addslashes($event['adresse'])) ?></b> — Événement');
    var anomalies=<?= json_encode($anomalyDetailsFull ?? [], JSON_UNESCAPED_UNICODE) ?>;
    var bounds=L.latLngBounds([[<?= e((string) $event['latitude']) ?>,<?= e((string) $event['longitude']) ?>]]);
    anomalies.forEach(function(a){ if(!a.latitude||!a.longitude) return; var m=L.marker([parseFloat(a.latitude),parseFloat(a.longitude)],{icon:L.divIcon({html:'<div style="background:#dc3545;color:#fff;width:22px;height:22px;border-radius:50%;display:grid;place-items:center;border:2px solid #fff;font-size:.7rem"><i class="mdi mdi-alert"></i></div>', iconSize:[22,22]})}).addTo(map).bindPopup('<b>'+a.anomalie_nom+'</b><br>'+(a.statut||'')); bounds.extend([a.latitude,a.longitude]); });
    if(anomalies.length>0) map.fitBounds(bounds.pad(.2));
    setTimeout(function(){map.invalidateSize()},200);
});
</script>
<?php endif; ?>
<script>
// QR copy
document.getElementById('qrCopyBtn')?.addEventListener('click', async function(){
    var u=this.getAttribute('data-url'); try{ await navigator.clipboard.writeText(u); if(typeof showToast==='function') showToast('Lien copié','success'); else alert('Lien copié'); }catch(e){ prompt('Copiez le lien',u); }
});
// Anomalies inline status
document.querySelectorAll('.anomaly-status').forEach(function(sel){
    sel.addEventListener('change', async function(){
        var ev=this.getAttribute('data-evenement'), an=this.getAttribute('data-anomalie'), ns=this.value;
        var fd=new FormData(); fd.append('_token', window.WH_CSRF); fd.append('evenement_id', ev); fd.append('anomalie_id', an); fd.append('new_status', ns);
        var r=await fetch('<?= url('wilaya/api/anomaly-status') ?>',{method:'POST', headers:{'X-CSRF-TOKEN': window.WH_CSRF}, body: fd});
        var j=await r.json().catch(()=>({})); if(j.ok) { if(typeof showToast==='function') showToast('Statut anomalie → '+ns,'success'); } else alert(j.error||'Erreur');
    });
});
document.getElementById('anomalySearch')?.addEventListener('input',function(){
    var q=this.value.toLowerCase(); document.querySelectorAll('#anomalyTable tbody tr').forEach(function(tr){ tr.style.display = tr.textContent.toLowerCase().indexOf(q)!==-1 ? '' : 'none'; });
});
document.getElementById('bulkAnomalyApply')?.addEventListener('click', async function(){
    var ns=document.getElementById('bulkAnomalyStatus')?.value; if(!ns) return alert('Choisissez un statut');
    var sels=document.querySelectorAll('.anomaly-status'); for(var sel of sels){ if(sel.closest('tr').style.display==='none') continue; sel.value=ns; sel.dispatchEvent(new Event('change', {bubbles:true})); await new Promise(r=>setTimeout(r,150)); }
});
document.getElementById('partSearch')?.addEventListener('input',function(){
    var q=this.value.toLowerCase(); document.querySelectorAll('.part-row').forEach(function(r){ r.style.display=r.textContent.toLowerCase().indexOf(q)!==-1?'':'none'; });
});
// QR expiry countdown
(function(){
    var exp='<?= $qr['date_expiration']??'' ?>'; if(!exp) return;
    var target=new Date(exp).getTime(); var el=document.createElement('div'); el.className='small mt-1 fw-bold'; el.style.fontSize='.7rem';
    document.querySelector('.ev-qr-info')?.appendChild(el);
    function tick(){ var diff=target-Date.now(); if(diff<=0){ el.textContent='QR expiré'; el.className='small mt-1 fw-bold text-danger'; return; } var h=Math.floor(diff/3600000), m=Math.floor(diff%3600000/60000); el.textContent='Expire dans '+h+'h '+m+'min'; el.className='small mt-1 fw-bold '+(h<2?'text-danger':'text-warning'); }
    tick(); setInterval(tick,60000);
})();
</script>
<style>.nav-tabs{position:sticky;top:58px;z-index:5;background:var(--wh-gray-soft);border-radius:.5rem;padding:.25rem}
.wh-fab{position:fixed;bottom:1.2rem;inset-inline-end:1.2rem;z-index:1040}
.wh-fab-btn{width:52px;height:52px;border-radius:50%;background:var(--wh-blue);color:#fff;border:none;box-shadow:0 8px 24px rgba(11,94,215,.35);display:grid;place-items:center;font-size:1.4rem}
.wh-fab-menu{position:absolute;bottom:60px;inset-inline-end:0;background:var(--wh-white);border:1px solid var(--wh-border);border-radius:.75rem;box-shadow:var(--wh-shadow-lg);padding:.5rem;display:none;min-width:180px}
.wh-fab.is-open .wh-fab-menu{display:block}
.wh-fab-menu a{display:flex;align-items:center;gap:.5rem;padding:.5rem .75rem;border-radius:.5rem;text-decoration:none;color:var(--wh-text);font-size:.85rem}
.wh-fab-menu a:hover{background:var(--wh-gray-soft)}</style>
<div class="wh-fab" id="whFab"><button class="wh-fab-btn" onclick="document.getElementById('whFab').classList.toggle('is-open')"><i class="mdi mdi-lightning-bolt"></i></button><div class="wh-fab-menu"><a href="<?= url('wilaya/evenements/'.(int)$event['id'].'/edit') ?>"><i class="mdi mdi-pencil"></i>Éditer</a><a href="<?= url('evenement/'.(int)$event['id'].'/ical') ?>"><i class="mdi mdi-calendar-export"></i>iCal</a><a href="#" onclick="window.print(); return false;"><i class="mdi mdi-printer"></i>Imprimer</a><a href="#" onclick="navigator.clipboard.writeText(location.href); showToast('Lien copié','success'); return false;"><i class="mdi mdi-share-variant"></i>Partager</a></div></div>
