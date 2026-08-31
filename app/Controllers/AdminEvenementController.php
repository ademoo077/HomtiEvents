<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuditLog;
use App\Helpers\Database;
use App\Helpers\EvenementService;
use App\Helpers\I18n;
use App\Helpers\Notification;
use App\Helpers\QrCodeService;
use App\Helpers\EpicDashboardService;
use App\Helpers\RoutingService;
use App\Helpers\Session;
use App\Helpers\StatsService;
use App\Helpers\Validator;

/**
 * Gestion administrative des événements (centre de commandement Wilaya).
 *
 * Liste filtrée, création directe, édition complète, workflow, EPIC, QR,
 * archivage et actions groupées.
 */
final class AdminEvenementController extends Controller
{
    private const PER_PAGE = 20;

    public function index(): never
    {
        $this->requirePermission('evenement.view_all');
        $filters = [
            'q'            => trim((string) input('q', '')),
            'statut'        => input('statut'),
            'commune_id'    => input('commune_id'),
            'association_id' => input('association_id'),
            'epic_id'       => input('epic_id'),
            'anomalie_id'   => input('anomalie_id'),
            'du'            => input('du'),
            'au'            => input('au'),
            'deleted'       => input('deleted') !== null,
            'sans_epic'     => input('sans_epic') !== null && input('sans_epic') !== '' && input('sans_epic') !== '0',
            'retard'        => input('retard') !== null && input('retard') !== '' && input('retard') !== '0',
            'sort'          => strtolower((string) input('sort', 'created_at')),
            'dir'           => strtolower((string) input('dir', 'desc')),
        ];

        [$sql, $params] = EvenementService::queryFiltres($filters);
        $page = (int) input('page', 1);
        $result = Database::paginate($sql, $params, self::PER_PAGE, $page);

        // Statistiques globales par statut (hors événements archivés).
        $statutCounts = array_fill_keys(EvenementService::STATUTS, 0);
        foreach (Database::all(
            'SELECT statut, COUNT(*) AS nb FROM evenements WHERE deleted_at IS NULL GROUP BY statut'
        ) as $row) {
            if (array_key_exists((string) $row['statut'], $statutCounts)) {
                $statutCounts[(string) $row['statut']] = (int) $row['nb'];
            }
        }

        $this->view('wilaya.evenements.index', [
            'evenements'  => $result['items'],
            'filters'     => $filters,
            'page'        => $result['page'],
            'lastPage'    => $result['last_page'],
            'total'       => $result['total'],
            'totalEvenements'   => array_sum($statutCounts),
            'statutCounts'      => $statutCounts,
            'pendingRequests'   => $statutCounts['EN_ATTENTE'],
            'activeRequests'    => $statutCounts['EN_COURS'],
            'completedRequests' => $statutCounts['TERMINE'],
            'rejectedRequests'  => $statutCounts['REFUSE'],
            'communes'    => Database::all('SELECT id, nom FROM commune ORDER BY nom'),
            'associations' => Database::all('SELECT id, nom FROM associations WHERE valide = 1 ORDER BY nom'),
            'epics'       => Database::all('SELECT id, nom FROM epic ORDER BY nom'),
            'anomalies'   => Database::all('SELECT id, nom FROM anomalies ORDER BY nom'),
        ]);
    }

    /**
     * Carte de suivi temps réel des événements EN_COURS (polling API 15 s).
     */
    public function suivi(): never
    {
        $this->requirePermission('evenement.view_all');

        $this->view('wilaya/suivi', [
            'page_title' => 'Suivi en direct',
        ], 'main');
    }

    public function dashboard(): never
    {
        $this->requirePermission('evenement.view_all');
        $kpis = [
            'total'       => (int) Database::value('SELECT COUNT(*) FROM evenements WHERE deleted_at IS NULL'),
            'en_attente'  => (int) Database::value("SELECT COUNT(*) FROM evenements WHERE statut = 'EN_ATTENTE' AND deleted_at IS NULL"),
            'programmes'  => (int) Database::value("SELECT COUNT(*) FROM evenements WHERE statut IN ('PROGRAMME', 'QR_GENERE') AND deleted_at IS NULL"),
            'en_cours'    => (int) Database::value("SELECT COUNT(*) FROM evenements WHERE statut = 'EN_COURS' AND deleted_at IS NULL"),
            'termines'    => (int) Database::value("SELECT COUNT(*) FROM evenements WHERE statut = 'TERMINE' AND deleted_at IS NULL"),
            'associations' => (int) Database::value('SELECT COUNT(*) FROM associations WHERE valide = 1'),
            'epics'       => (int) Database::value('SELECT COUNT(*) FROM epic'),
            'communes'    => (int) Database::value('SELECT COUNT(*) FROM commune WHERE is_active = 1'),
            // Galerie KPIs
            'total_photos' => (int) Database::value('SELECT COUNT(*) FROM photos'),
            'total_albums' => (int) Database::value('SELECT COUNT(*) FROM albums'),
            // Association requests KPIs
            'total_requests'  => (int) Database::value('SELECT COUNT(*) FROM association_requests'),
            'pending_requests' => (int) Database::value("SELECT COUNT(*) FROM association_requests WHERE status = 'pending'"),
            'approved_requests' => (int) Database::value("SELECT COUNT(*) FROM association_requests WHERE status = 'approved'"),
            'rejected_requests' => (int) Database::value("SELECT COUNT(*) FROM association_requests WHERE status = 'rejected'"),
        ];

        $parStatut = Database::all("SELECT statut, COUNT(*) AS nb FROM evenements WHERE deleted_at IS NULL GROUP BY statut");
        $parMois = Database::all("SELECT DATE_FORMAT(created_at, '%Y-%m') AS mois, COUNT(*) AS nb FROM evenements WHERE deleted_at IS NULL AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY mois ORDER BY mois");
        $prochains = Database::all("SELECT e.id, e.adresse, e.statut, e.date_evenement, e.heure, c.nom AS commune_nom
            FROM evenements e
            LEFT JOIN commune c ON c.id = e.commune_id
            WHERE e.deleted_at IS NULL AND e.statut NOT IN ('TERMINE', 'REFUSE')
            ORDER BY e.date_evenement ASC LIMIT 6");

        // Activité récente
        $recentActivity = Database::all(
            "SELECT a.action, a.created_at, a.modele, u.nom, u.prenom
             FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.created_at DESC LIMIT 8"
        );

        // Photos récentes
        $recentPhotos = Database::all(
            "SELECT p.image, p.uploaded_at, a.titre AS album_titre, e.adresse
             FROM photos p
             JOIN albums a ON a.id = p.album_id
             JOIN evenements e ON e.id = a.evenement_id
             ORDER BY p.uploaded_at DESC LIMIT 6"
        );

        // Taux de complétion
        $tauxComplet = $kpis['total'] > 0
            ? round(($kpis['termines'] / $kpis['total']) * 100)
            : 0;

        // ── Idées & conseils contextuels (actions recommandées) ──
        $validesSansDate = Database::all(
            "SELECT e.id, e.adresse, e.description
             FROM evenements e
             WHERE e.statut = 'VALIDÉ' AND e.deleted_at IS NULL AND e.date_evenement IS NULL
             ORDER BY e.created_at ASC LIMIT 5"
        );
        $programmesSansQr = Database::all(
            "SELECT e.id, e.adresse, e.description
             FROM evenements e
             WHERE e.statut IN ('PROGRAMME', 'QR_GENERE') AND e.deleted_at IS NULL
               AND NOT EXISTS (SELECT 1 FROM qr_event q WHERE q.evenement_id = e.id)
             ORDER BY e.created_at ASC LIMIT 5"
        );
        $slaRetard = (int) Database::value(
            "SELECT COUNT(*) FROM sla_alertes WHERE envoyee = 1 AND type = 'retard'"
        );
        $evenementsSansEpic = (int) Database::value(
            "SELECT COUNT(*) FROM evenements e
             WHERE e.deleted_at IS NULL AND e.statut NOT IN ('TERMINE', 'REFUSE', 'ANNULE')
               AND NOT EXISTS (SELECT 1 FROM evenement_epic ee WHERE ee.evenement_id = e.id)"
        );
        $qrsActifs = (int) Database::value(
            'SELECT COUNT(*) FROM qr_event WHERE date_expiration >= NOW() OR date_expiration IS NULL'
        );

        $isRtl = I18n::direction() === 'rtl';

        // Événements prévus aujourd'hui (rappel contextuel)
        $todayUpcoming = Database::all(
            "SELECT e.id, e.adresse, e.description, e.heure, c.nom AS commune_nom
             FROM evenements e
             LEFT JOIN commune c ON c.id = e.commune_id
             WHERE e.deleted_at IS NULL
               AND DATE(e.date_evenement) = CURDATE()
               AND e.statut IN ('PROGRAMME','QR_GENERE','EN_COURS')
             ORDER BY e.heure ASC"
        );

        $suggestions = [];
        if ($kpis['en_attente'] > 0) {
            $suggestions[] = ['icon' => 'mdi-clock-outline', 'color' => 'amber',
                'titre' => $kpis['en_attente'] . ' demande(s) en attente',
                'texte' => 'Des demandes d\'événements attendent une décision. Validez, demandez des modifications ou refusez-les pour débloquer le circuit.',
                'lien'  => url('wilaya/evenements?statut=EN_ATTENTE')];
        }
        if ($validesSansDate !== []) {
            $suggestions[] = ['icon' => 'mdi-calendar-blank-outline', 'color' => 'violet',
                'titre' => count($validesSansDate) . ' événement(s) validé(s) sans date',
                'texte' => 'Programmez-les pour fixer une date/heure et générer le QR code automatiquement.',
                'lien'  => url('wilaya/evenements?statut=VALIDÉ')];
        }
        if ($programmesSansQr !== []) {
            $suggestions[] = ['icon' => 'mdi-qrcode-remove', 'color' => 'red',
                'titre' => count($programmesSansQr) . ' événement(s) programmé(s) sans QR',
                'texte' => 'Régénérez le QR code de ces événements pour permettre le contrôle d\'accès.',
                'lien'  => url('wilaya/evenements?statut=PROGRAMME')];
        }
        if ($slaRetard > 0) {
            $suggestions[] = ['icon' => 'mdi-image-off-outline', 'color' => 'red',
                'titre' => $slaRetard . ' alerte(s) album en retard',
                'texte' => 'Des événements terminés n\'ont toujours pas d\'album officiel. Relancez les associations concernées.',
                'lien'  => url('wilaya/gallery')];
        }
        if ($evenementsSansEpic > 0) {
            $suggestions[] = ['icon' => 'mdi-folder-account-outline', 'color' => 'info',
                'titre' => $evenementsSansEpic . ' événement(s) sans EPIC',
                'texte' => 'Affectez une EPIC compétente pour assurer le suivi des travaux sur le terrain.',
                'lien'  => url('wilaya/evenements')];
        }
        if ($kpis['pending_requests'] > 0) {
            $suggestions[] = ['icon' => 'mdi-account-check-outline', 'color' => 'green',
                'titre' => $kpis['pending_requests'] . ' demande(s) d\'inscription en attente',
                'texte' => 'Examinez les nouvelles demandes d\'associations pour agrandir le réseau.',
                'lien'  => url('admin/association-requests?status=pending')];
        }
        if ($suggestions === []) {
            $suggestions[] = ['icon' => 'mdi-lightbulb-on-outline', 'color' => 'primary',
                'titre' => 'Tout est à jour',
                'texte' => 'Aucune action urgente. Le circuit des demandes est fluide. Consultez les statistiques pour piloter votre territoire.',
                'lien'  => null];
        }

        // Demandes d'inscription récentes + ancienneté
        $latestRequests = Database::all(
            "SELECT r.*, DATEDIFF(CURDATE(), DATE(r.created_at)) AS age_jours
             FROM association_requests r
             ORDER BY r.id DESC LIMIT 5"
        );
        $agingPending = (int) Database::value(
            "SELECT COUNT(*) FROM association_requests
             WHERE status = 'pending' AND DATEDIFF(CURDATE(), DATE(created_at)) >= 7"
        );

        // Notifications de l'utilisateur connecté
        $notifFeed = [];
        $unreadNotifs = 0;
        $currentUserId = Session::userId();
        if ($currentUserId !== null) {
            $unreadNotifs = Notification::unreadCount($currentUserId);
            $notifFeed = Notification::recent($currentUserId, 6);
        }

        // Évolution du volume d'événements (mois courant vs mois précédent) — KPI "total"
        $trendEvents = null;
        try {
            $evol = StatsService::evolutionMensuelle();
            $n = count($evol);
            if ($n >= 2) {
                $prev = (int) ($evol[$n - 2]['evenements'] ?? 0);
                $curr = (int) ($evol[$n - 1]['evenements'] ?? 0);
                if ($prev > 0) {
                    $trendEvents = round((($curr - $prev) / $prev) * 100, 1);
                }
            }
        } catch (\Throwable $e) {
            $trendEvents = null;
        }

        $this->view('wilaya.dashboard', [
            'kpis'             => $kpis,
            'parStatut'        => $parStatut,
            'parMois'          => $parMois,
            'prochains'        => $prochains,
            'recentActivity'   => $recentActivity,
            'recentPhotos'     => $recentPhotos,
            'tauxComplet'      => $tauxComplet,
            'latestRequests'   => $latestRequests,
            'agingPending'     => $agingPending,
            'notifFeed'        => $notifFeed,
            'unreadNotifs'     => $unreadNotifs,
            'trendEvents'      => $trendEvents,
            'repartitionOrg'   => RoutingService::repartition(),
            'routing_alertes_non_traitees' => RoutingService::alertesNonTraitees(),
            'suggestions'      => $suggestions,
            'qrsActifs'        => $qrsActifs,
            'slaRetard'        => $slaRetard,
        ]);
    }

    /**
     * Export PDF du dashboard via dompdf — KPIs + répartition + graphiques base64 si fournis.
     */
    public function dashboardPdf(): never
    {
        $this->requirePermission('evenement.view_all');
        $kpis = [
            'total'       => (int) Database::value('SELECT COUNT(*) FROM evenements WHERE deleted_at IS NULL'),
            'en_attente'  => (int) Database::value("SELECT COUNT(*) FROM evenements WHERE statut = 'EN_ATTENTE' AND deleted_at IS NULL"),
            'termines'    => (int) Database::value("SELECT COUNT(*) FROM evenements WHERE statut = 'TERMINE' AND deleted_at IS NULL"),
            'associations' => (int) Database::value('SELECT COUNT(*) FROM associations WHERE valide = 1'),
        ];
        $parStatut = Database::all("SELECT statut, COUNT(*) AS nb FROM evenements WHERE deleted_at IS NULL GROUP BY statut");
        $parMois = Database::all("SELECT DATE_FORMAT(created_at, '%Y-%m') AS mois, COUNT(*) AS nb FROM evenements WHERE deleted_at IS NULL AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY mois ORDER BY mois");
        $repartitionOrg = RoutingService::repartition();
        $chartStatuts = input('chart_statuts'); // base64 from frontend
        $chartMois = input('chart_mois');
        $chartOrg = input('chart_org');

        $html = '<html><head><meta charset="utf-8"><style>
            body{font-family: DejaVu Sans, sans-serif; color:#1a2332; font-size:11px;}
            h1{color:#0B5ED7; font-size:18px; border-bottom:2px solid #0B5ED7; padding-bottom:6px;}
            h2{color:#0B5ED7; font-size:13px; margin-top:18px;}
            table{width:100%; border-collapse:collapse; margin:8px 0;}
            th{background:#0B5ED7; color:#fff; padding:6px; text-align:left;}
            td{padding:5px 6px; border-bottom:1px solid #dee2e6;}
            .kpi{display:inline-block; width:22%; text-align:center; border:1px solid #dee2e6; border-radius:8px; padding:10px; margin:2px;}
            .kpi b{font-size:18px; color:#0B5ED7; display:block;}
            .footer{margin-top:20px; font-size:9px; color:#6b7280; text-align:center; border-top:1px solid #dee2e6; padding-top:6px;}
            img.chart{max-width:100%; height:auto; border:1px solid #dee2e6; border-radius:6px; margin:6px 0;}
        </style></head><body>';
        $html .= '<h1>Wilaya Harmonia — Rapport Dashboard ' . date('d/m/Y H:i') . '</h1>';
        $html .= '<div>';
        $html .= '<div class="kpi"><b>' . $kpis['total'] . '</b>Événements totaux</div>';
        $html .= '<div class="kpi"><b>' . $kpis['en_attente'] . '</b>En attente</div>';
        $html .= '<div class="kpi"><b>' . $kpis['termines'] . '</b>Terminés</div>';
        $html .= '<div class="kpi"><b>' . $kpis['associations'] . '</b>Associations</div>';
        $html .= '</div>';
        if ($chartStatuts && str_starts_with((string) $chartStatuts, 'data:image')) {
            $html .= '<h2>Répartition par statut</h2><img class="chart" src="' . e((string) $chartStatuts) . '">';
        } else {
            $html .= '<h2>Répartition par statut</h2><table><tr><th>Statut</th><th>Nombre</th></tr>';
            foreach ($parStatut as $ps) { $html .= '<tr><td>' . e(statut_label((string) $ps['statut'])) . '</td><td>' . (int) $ps['nb'] . '</td></tr>'; }
            $html .= '</table>';
        }
        if ($chartMois && str_starts_with((string) $chartMois, 'data:image')) {
            $html .= '<h2>Évolution mensuelle</h2><img class="chart" src="' . e((string) $chartMois) . '">';
        } else {
            $html .= '<h2>Évolution 6 mois</h2><table><tr><th>Mois</th><th>Événements</th></tr>';
            foreach ($parMois as $pm) { $html .= '<tr><td>' . e((string) $pm['mois']) . '</td><td>' . (int) $pm['nb'] . '</td></tr>'; }
            $html .= '</table>';
        }
        if ($chartOrg && str_starts_with((string) $chartOrg, 'data:image')) {
            $html .= '<h2>Répartition par organisation</h2><img class="chart" src="' . e((string) $chartOrg) . '">';
        } elseif ($repartitionOrg !== []) {
            $html .= '<h2>Répartition par organisation</h2><table><tr><th>Organisation</th><th>Nombre</th></tr>';
            foreach ($repartitionOrg as $r) { $html .= '<tr><td>' . e((string) ($r['org'] ?? '')) . '</td><td>' . (int) ($r['nb'] ?? 0) . '</td></tr>'; }
            $html .= '</table>';
        }
        $html .= '<div class="footer">Généré par Wilaya Harmonia — ' . e(settings('app.name') ?: 'Plateforme officielle') . ' — ' . date('Y-m-d H:i:s') . '</div>';
        $html .= '</body></html>';

        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="rapport-wilaya-' . date('Ymd_His') . '.pdf"');
        echo $dompdf->output();
        exit;
    }

    /**
     * Espace notifications Wilaya : historique complet, paginé,
     * avec lien contextuel vers l'événement / la demande concernée.
     */
    public function notifications(): never
    {
        $this->requirePermission('evenement.view_all');

        $currentUserId = Session::userId();
        $page   = (int) input('page', 1);
        $result = Notification::center((int) $currentUserId, 20, $page);

        $this->view('wilaya/notifications', [
            'notifications' => $result['items'],
            'page'          => $result['page'],
            'lastPage'      => $result['last_page'],
            'total'         => $result['total'],
            'unread'        => Notification::unreadCount((int) $currentUserId),
        ]);
    }

    public function show(string $id): never
    {
        $this->requirePermission('evenement.view_all');
        $event = $this->find($id);

        $commune = Database::one('SELECT nom, nom_ar FROM commune WHERE id = ?', [(int) $event['commune_id']]);
        $association = Database::one('SELECT id, nom, email, telephone FROM associations WHERE id = ?', [(int) ($event['association_id'] ?? 0)]);
        $anomalies = Database::all('SELECT an.id, an.nom FROM anomalies_evenement ae JOIN anomalies an ON an.id = ae.anomalie_id WHERE ae.evenement_id = ? ORDER BY an.nom', [(int) $id]);
        $epics = Database::all('SELECT ep.id, ep.nom, ep.description, ee.date_affectation, ee.observation FROM evenement_epic ee JOIN epic ep ON ep.id = ee.epic_id WHERE ee.evenement_id = ? ORDER BY ee.date_affectation DESC', [(int) $id]);
        $participants = (int) Database::value('SELECT COUNT(*) FROM evenement_participant WHERE evenement_id = ?', [(int) $id]);
        $qr = Database::one('SELECT * FROM qr_event WHERE evenement_id = ? ORDER BY id DESC LIMIT 1', [(int) $id]);
        $historique = AuditLog::historiqueEvenement((int) $id);
        $transitions = Database::all('SELECT t.*, u.nom AS user_nom, u.prenom AS user_prenom
            FROM transition_history t
            LEFT JOIN users u ON u.id = t.user_id
            WHERE t.evenement_id = ? ORDER BY t.id DESC', [(int) $id]);

        // Galerie photos
        $album = Database::one('SELECT * FROM albums WHERE evenement_id = ? ORDER BY id DESC LIMIT 1', [(int) $id]);
        $photos = [];
        if ($album !== null) {
            $photos = Database::all('SELECT * FROM photos WHERE album_id = ? ORDER BY uploaded_at DESC', [(int) $album['id']]);
        }

         $statutActuel = (string) ($event['statut'] ?? 'EN_ATTENTE');
        // MAX: participants détaillés + related + SLA
        $participantsList = Database::all('SELECT u.id, u.nom, u.prenom, u.email, ep.heure_scan FROM evenement_participant ep JOIN users u ON u.id=ep.user_id WHERE ep.evenement_id=? ORDER BY ep.heure_scan DESC LIMIT 50', [(int)$id]);
        $related = Database::all('SELECT id, adresse, statut, date_evenement FROM evenements WHERE commune_id=? AND id<>? AND deleted_at IS NULL ORDER BY created_at DESC LIMIT 4', [(int)($event['commune_id']??0),(int)$id]);
        $tauxRemplissage = (!empty($event['capacite']) && (int)$event['capacite']>0) ? round(((int)$participants/(int)$event['capacite'])*100) : null;
        $slaCountdown = null; if(!empty($event['deadline_at'])){ $diff=strtotime((string)$event['deadline_at'])-time(); $slaCountdown=['sec'=>$diff,'label'=> $diff<0 ? 'Dépassé de '.ceil(abs($diff)/86400).'j' : ($diff<86400 ? ceil($diff/3600).'h restantes' : ceil($diff/86400).'j restantes'), 'overdue'=>$diff<0]; }
        $anomalyDetailsFull = Database::all('SELECT ae.*, an.nom AS anomalie_nom FROM anomalies_evenement ae JOIN anomalies an ON an.id=ae.anomalie_id WHERE ae.evenement_id=?', [(int)$id]);
        $prochaineAction = EvenementService::nextAction($event, [
            'epics_count'       => count($epics),
            'nb_participants'   => $participants,
            'album_existe'      => $album !== null,
            'anomalies_ouvertes'=> count(array_filter($anomalyDetailsFull, static fn($a) => ($a['statut'] ?? 'DETECTEE') !== 'RESOLUE')),
            'qrcode_existe'     => $qr !== null,
        ]);
        $ctxDossier = [
            'epics_count'       => count($epics),
            'nb_participants'   => $participants,
            'album_existe'      => $album !== null,
            'anomalies_ouvertes'=> count(array_filter($anomalyDetailsFull, static fn($a) => ($a['statut'] ?? 'DETECTEE') !== 'RESOLUE')),
            'qrcode_existe'     => $qr !== null,
        ];
        $completude = EvenementService::completudeEvent($event, $ctxDossier);
        $priorite   = EvenementService::prioriteDossier($event, $ctxDossier);
        $suggestions= EvenementService::suggestionsAdmin($event, $ctxDossier);
        $estimation = EvenementService::estimDelaiTraitement($event, $ctxDossier);
        $escaladesDossier = array_values(array_filter(
            EvenementService::relancesEscalades(40),
            static fn($e) => (int) ($e['evenement_id'] ?? 0) === (int) $id
        ));
        $this->view('wilaya.evenements.show', [
            'event'       => $event,
            'commune'     => $commune,
            'association' => $association,
            'anomalies'   => $anomalies,
            'epics'       => $epics,
            'participants' => $participants,
            'qr'          => $qr,
            'qrUrl'       => QrCodeService::getQrCodeUrl((int) $id),
            'qrStreamUrl' => QrCodeService::has((int) $id) ? url('event/qr/stream/' . (int) $id) : null,
            'qrDownloadUrl'=> QrCodeService::has((int) $id) ? url('event/qr/download/' . (int) $id) : null,
            'historique'  => $historique,
            'transitions' => $transitions,
            'statuts'     => EvenementService::STATUTS,
            'statutActuel' => $statutActuel,
            'epicsListe'  => Database::all('SELECT id, nom FROM epic ORDER BY nom'),
            'album'       => $album,
            'photos'      => $photos,
            'errors'      => $this->errors(),
            'participantsList'=> $participantsList,
            'related'=> $related,
            'tauxRemplissage'=> $tauxRemplissage,
            'slaCountdown'=> $slaCountdown,
            'anomalyDetailsFull'=> $anomalyDetailsFull,
            'prochaineAction'=> $prochaineAction,
            'completude'    => $completude,
            'priorite'      => $priorite,
            'suggestions'   => $suggestions,
            'estimation'    => $estimation,
            'escalades'     => $escaladesDossier,
        ]);
    }

    public function create(): never
    {
        $this->view('wilaya.evenements.create', [
            'communes'    => Database::all('SELECT id, nom, ca_id, latitude, longitude FROM commune WHERE is_active = 1 ORDER BY nom'),
            'dairas'      => Database::all('SELECT id, nom, nom_ar FROM ca WHERE is_active = 1 ORDER BY id'),
            'associations' => Database::all('SELECT id, nom FROM associations WHERE valide = 1 ORDER BY nom'),
            'anomalies'   => Database::all('SELECT * FROM anomalies ORDER BY nom'),
            'categories'  => Database::all('SELECT * FROM anomalie_categories WHERE actif = 1 ORDER BY nom'),
            'epics'       => Database::all('SELECT id, nom FROM epic ORDER BY nom'),
            'selectedAnomalies' => [],
            'assignedEpics' => [],
            'event'       => null,
            'errors'      => $this->errors(),
            'old'         => $_SESSION['_old'] ?? [],
        ]);
    }

    public function store(): never
    {
        $this->requirePermission('evenement.create');
        $data = all_input();
        $validator = Validator::make($data, [
            'commune_id'   => 'required|integer',
            'adresse'      => 'required|string|min:5|max:255',
            'description'  => 'required|string|min:10',
            'anomalies'    => 'required|array|distinct',
            'capacite'     => 'nullable|integer|between:1,100000',
        ], ['anomalies.required' => 'Sélectionnez au moins une anomalie.']);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data);
        }

        // Build anomaly detail array with per-anomaly GPS from form
        $anomalyDetail = [];
        foreach ((array) ($data['anomalies'] ?? []) as $aId) {
            $aId = (int) $aId;
            $lat = $data["anomaly_lat[$aId]"] ?? $data["anomaly_lat_{$aId}"] ?? null;
            $lng = $data["anomaly_lng[$aId]"] ?? $data["anomaly_lng_{$aId}"] ?? null;
            $anomalyDetail[] = [
                'anomalie_id' => $aId,
                'latitude'    => ($lat !== null && $lat !== '') ? (float) $lat : null,
                'longitude'   => ($lng !== null && $lng !== '') ? (float) $lng : null,
            ];
        }
        $data['anomalies_detail'] = $anomalyDetail;

        $statut = in_array($data['statut'] ?? '', ['EN_ATTENTE', 'VALIDÉ', 'PROGRAMME'], true) ? (string) $data['statut'] : 'EN_ATTENTE';
        $eventId = EvenementService::create(
            $data,
            ! empty($data['association_id']) ? (int) $data['association_id'] : null,
            (array) ($data['anomalies'] ?? []),
            $statut
        );

        // Save per-anomaly GPS after insert (need event ID)
        if ($anomalyDetail !== []) {
            EvenementService::syncAnomaliesWithGps($eventId, $anomalyDetail);
        }

        // Création directe programmée : date + EPIC + QR immédiats
        if ($statut === 'PROGRAMME' && ! empty($data['date_evenement']) && ! empty($data['epics'])) {
            EvenementService::programmer(
                $eventId,
                (string) $data['date_evenement'],
                (string) ($data['heure'] ?? '00:00:00'),
                (array) $data['epics'],
                (int) ($data['association_id'] ?? 0)
            );
        }

        flash('success', 'Événement créé par la Wilaya.');
        redirect('wilaya/evenements/' . $eventId);
    }

    public function edit(string $id): never
    {
        $this->requirePermission('evenement.edit');
        $event = $this->find($id);

        $this->view('wilaya.evenements.edit', [
            'event'       => $event,
            'communes'    => Database::all('SELECT id, nom, ca_id, latitude, longitude FROM commune WHERE is_active = 1 ORDER BY nom'),
            'dairas'      => Database::all('SELECT id, nom, nom_ar FROM ca WHERE is_active = 1 ORDER BY id'),
            'associations' => Database::all('SELECT id, nom FROM associations WHERE valide = 1 ORDER BY nom'),
            'anomalies'   => Database::all('SELECT * FROM anomalies ORDER BY nom'),
            'categories'  => Database::all('SELECT * FROM anomalie_categories WHERE actif = 1 ORDER BY nom'),
            'selectedAnomalies' => array_column(Database::all('SELECT anomalie_id FROM anomalies_evenement WHERE evenement_id = ?', [(int) $id]), 'anomalie_id'),
            'epics'       => Database::all('SELECT id, nom FROM epic ORDER BY nom'),
            'assignedEpics' => array_column(Database::all('SELECT epic_id FROM evenement_epic WHERE evenement_id = ?', [(int) $id]), 'epic_id'),
            'anomalyDetails' => Database::all('SELECT ae.*, an.nom AS anomalie_nom FROM anomalies_evenement ae JOIN anomalies an ON an.id = ae.anomalie_id WHERE ae.evenement_id = ?', [(int) $id]),
            'assignments' => Database::all('SELECT aa.*, an.nom AS anomalie_nom, ep.nom AS epic_nom FROM anomaly_assignments aa JOIN anomalies an ON an.id = aa.anomalie_id JOIN epic ep ON ep.id = aa.epic_id WHERE aa.evenement_id = ?', [(int) $id]),
            'errors'      => $this->errors(),
        ]);
    }

    public function update(string $id): never
    {
        $this->requirePermission('evenement.edit');
        $event = $this->find($id);
        $data = all_input();

        $validator = Validator::make($data, [
            'commune_id'   => 'required|integer',
            'adresse'      => 'required|string|min:5|max:255',
            'description'  => 'required|string|min:10',
            'anomalies'    => 'required|array|distinct',
            'capacite'     => 'nullable|integer|between:1,100000',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data);
        }

        // Build anomaly detail array with per-anomaly GPS from form
        $anomalyDetail = [];
        foreach ((array) ($data['anomalies'] ?? []) as $aId) {
            $aId = (int) $aId;
            $lat = $data["anomaly_lat[$aId]"] ?? $data["anomaly_lat_{$aId}"] ?? null;
            $lng = $data["anomaly_lng[$aId]"] ?? $data["anomaly_lng_{$aId}"] ?? null;
            $status = $data["anomaly_status[$aId]"] ?? $data["anomaly_status_{$aId}"] ?? 'DETECTEE';
            $anomalyDetail[] = [
                'anomalie_id' => $aId,
                'latitude'    => ($lat !== null && $lat !== '') ? (float) $lat : null,
                'longitude'   => ($lng !== null && $lng !== '') ? (float) $lng : null,
                'statut'      => (string) $status,
            ];
        }
        $data['anomalies_detail'] = $anomalyDetail;

        EvenementService::update((int) $id, $data, $event);

        // Save per-anomaly GPS + status
        if ($anomalyDetail !== []) {
            EvenementService::syncAnomaliesWithGps((int) $id, $anomalyDetail);
            EvenementService::syncAnomalyAssignments((int) $id, array_column($anomalyDetail, 'anomalie_id'));
        }

        if (isset($data['epics']) && is_array($data['epics'])) {
            EvenementService::syncEpics((int) $id, (array) $data['epics']);
        }

        flash('success', 'Événement mis à jour.');
        redirect('wilaya/evenements/' . $id);
    }

    public function statut(string $id): never
    {
        $this->requirePermission('evenement.validate');
        $this->find($id);

        $statut = (string) input('statut', '');
        $motif = trim((string) input('motif', ''));

        EvenementService::changeStatut((int) $id, $statut, $motif ?: null);

        flash('success', 'Statut mis à jour : ' . $statut);
        redirect('wilaya/evenements/' . $id);
    }

    public function epics(string $id): never
    {
        $this->requirePermission('epic.assign');
        $this->find($id);

        $epics = (array) (input('epics', []) ?: []);
        EvenementService::syncEpics((int) $id, $epics);

        // Traçabilité de l'organisation assignée (première EPIC ou désassignée).
        RoutingService::reaffecter((int) $id, $epics, 'Affectation manuelle Wilaya');

        flash('success', 'EPIC affectées mises à jour.');
        redirect('wilaya/evenements/' . $id);
    }

    /**
     * Validation Wilaya + affectation multi-EPIC (bouton « Valider et affecter »).
     * Passe l'événement en VALIDÉ, lie les EPIC sélectionnées, notifie l'association
     * et chaque EPIC.
     */
    public function valider(string $id): never
    {
        $this->requirePermission('evenement.validate');
        $this->find($id);

        $epics = array_values(array_filter(array_map('intval', (array) input('epics', []))));
        $date  = input('date_evenement') !== null ? (string) input('date_evenement') : null;
        $heure = input('heure') !== null ? (string) input('heure') : null;

        EvenementService::validateEvent((int) $id, $date, $heure, $epics);

        flash('success', 'Événement validé et EPIC affectées.');
        redirect('wilaya/evenements/' . $id);
    }

    /**
     * Réaffectation manuelle d'une organisation (EPIC) — bouton "Réaffecter".
     */
    public function reaffecter(string $id): never
    {
        $this->requirePermission('epic.assign');
        $this->find($id);

        $epicIds = array_values(array_filter(array_map('intval', (array) input('epic_id', []))));
        $motif  = trim((string) input('motif', ''));

        // Sync both tables: evenement_epic (N↔N) + assigned_org_id (routing).
        EvenementService::syncEpics((int) $id, $epicIds);
        RoutingService::reaffecter((int) $id, $epicIds, $motif);

        flash('success', $epicIds !== [] ? 'Organisation réaffectée.' : 'Organisation désassignée.');
        redirect('wilaya/evenements/' . $id);
    }

    public function regenQr(string $id): never
    {
        $this->requirePermission('qrcode.generate');
        $this->find($id);
        EvenementService::regenQr((int) $id);

        flash('success', 'QR code régénéré.');
        redirect('wilaya/evenements/' . $id);
    }

    public function archive(string $id): never
    {
        $this->requirePermission('evenement.delete');
        $this->find($id);
        EvenementService::softDelete((int) $id);

        flash('success', 'Événement archivé.');
        redirect('wilaya/evenements');
    }

    public function restore(string $id): never
    {
        $this->requirePermission('evenement.delete');
        $event = Database::one('SELECT * FROM evenements WHERE id = ? AND deleted_at IS NOT NULL', [(int) $id]);

        if ($event === null) {
            abort(404, 'Événement introuvable');
        }

        EvenementService::restore((int) $id);
        flash('success', 'Événement restauré.');
        redirect('wilaya/evenements?deleted=1');
    }

    public function bulk(): never
    {
        $this->requirePermission('evenement.view_all');
        $action = (string) input('action', '');
        $ids = (array) (input('ids', []) ?: []);

        // support JSON ids[] or comma string
        if ($ids === [] && input('ids_csv')) { $ids = array_map('intval', explode(',', (string) input('ids_csv'))); }

        $allowed = ['valider','terminer','archiver','restaurer','modification','programmer','epic'];
        if (! in_array($action, $allowed, true) || $ids === []) {
            // AJAX call?
            if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'json') || isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                json_response(['success'=>false,'error'=>'Sélection invalide'],422);
            }
            flash('error', 'Sélection invalide.');
            redirect('wilaya/evenements');
        }

        // programmer needs date
        if ($action === 'programmer') {
            $date=(string) input('date'); $heure=(string) (input('heure')?:'09:00'); $epicIds=array_map('intval', (array) input('epics', []));
            $count=0; foreach ($ids as $id){ $ev=Database::one('SELECT * FROM evenements WHERE id=? AND deleted_at IS NULL',[(int)$id]); if(!$ev) continue; try{ EvenementService::programmer((int)$id,$date,$heure,$epicIds,(int)($ev['association_id']??0)); $count++; }catch(\Throwable $e){} }
            if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'json') || isset($_SERVER['HTTP_X_REQUESTED_WITH'])) { json_response(['success'=>true,'count'=>$count]); }
            flash('success', $count . ' événement(s) programmé(s).');
            redirect('wilaya/evenements');
        }
        if ($action === 'modification') {
            $motif=trim((string) input('motif','Modifications demandées (bulk)'));
            $count=0; foreach ($ids as $id){ try{ EvenementService::changeStatut((int)$id,'MODIFICATION_DEMANDEE',$motif); $count++; }catch(\Throwable $e){} }
            if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'json') || isset($_SERVER['HTTP_X_REQUESTED_WITH'])) { json_response(['success'=>true,'count'=>$count]); }
            flash('success', $count . ' événement(s) en demande de modifs.');
            redirect('wilaya/evenements');
        }
        if ($action === 'epic') {
            $epicIds=array_map('intval', (array) input('epics', []));
            $count=0; foreach ($ids as $id){ EvenementService::syncEpics((int)$id,$epicIds); RoutingService::reaffecter((int)$id,$epicIds,'Bulk réaffectation'); $count++; }
            if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'json') || isset($_SERVER['HTTP_X_REQUESTED_WITH'])) { json_response(['success'=>true,'count'=>$count]); }
            flash('success', $count . ' événement(s) réaffecté(s).');
            redirect('wilaya/evenements');
        }

        $count = EvenementService::bulk($action, $ids);
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'json') || isset($_SERVER['HTTP_X_REQUESTED_WITH'])) { json_response(['success'=>true,'count'=>$count]); }
        flash('success', $count . ' événement(s) traité(s).');
        redirect('wilaya/evenements');
    }

    public function export(): never
    {
        // ids param via bulk CSV export ?ids=1,2,3
        $idsParam = trim((string) input('ids',''));
        if ($idsParam !== '') {
            $ids = array_map('intval', explode(',', $idsParam));
            $ids = array_filter($ids);
            if ($ids !== []) {
                $placeholders = implode(',', array_fill(0,count($ids),'?'));
                $rows = Database::all("SELECT e.id, e.adresse, e.description, e.statut, e.date_evenement, e.heure, c.nom AS commune, a.nom AS association FROM evenements e LEFT JOIN commune c ON c.id=e.commune_id LEFT JOIN associations a ON a.id=e.association_id WHERE e.id IN ($placeholders) ORDER BY e.id DESC", $ids);
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="evenements-selection.csv"');
                $out = fopen('php://output', 'w'); fputs($out, "\xEF\xBB\xBF"); fputcsv($out, ['ID','Adresse','Description','Statut','Date','Heure','Commune','Association'], ',','"','\\');
                foreach ($rows as $row) { fputcsv($out, array_map(static fn($v)=>is_string($v)?str_replace(["\r","\n"],' ',$v):$v, array_values($row)), ',','"','\\'); }
                fclose($out); exit;
            }
        }
        [$sql, $params] = EvenementService::queryFiltres([
            'q'          => trim((string) input('q', '')),
            'statut'     => input('statut'),
            'commune_id' => input('commune_id'),
            'epic_id'    => input('epic_id'),
            'anomalie_id' => input('anomalie_id'),
        ]);
        $sql = preg_replace(
            '/^SELECT.*? FROM evenements e/s',
            'SELECT e.id, e.adresse, e.description, e.statut, e.date_evenement, e.heure, c.nom AS commune, a.nom AS association FROM evenements e',
            $sql,
            1
        );

        $rows = Database::all($sql, $params);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="evenements.csv"');

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF");
        fputcsv($out, ['ID', 'Adresse', 'Description', 'Statut', 'Date', 'Heure', 'Commune', 'Association'], ',', '"', '\\');

        foreach ($rows as $row) {
            fputcsv($out, array_map(
                static fn ($v) => is_string($v) ? str_replace(["\r", "\n"], ' ', $v) : $v,
                array_values($row)
            ), ',', '"', '\\');
        }

        fclose($out);
        exit;
    }

    /**
     * API: preview du routage multi-EPIC par anomalie (AJAX GET).
     */
    public function routingPreview(): never
    {
        $communeId = (int) input('commune_id', 0);
        $anomalieIds = array_map('intval', (array) input('anomalies', []));

        $preview = RoutingService::previewPerAnomaly($communeId, $anomalieIds);

        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'preview' => $preview]);
        exit;
    }

    /**
     * API: override d'une assignation d'anomalie (AJAX POST).
     */
    public function overrideAssignment(): never
    {
        $this->requirePermission('epic.assign');

        $assignmentId = (int) input('assignment_id', 0);
        $newEpicId = (int) input('new_epic_id', 0);
        $reason = trim((string) input('reason', ''));

        if ($assignmentId <= 0 || $newEpicId <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'Paramètres invalides.']);
            exit;
        }

        RoutingService::overrideAssignment($assignmentId, $newEpicId, $reason);

        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit;
    }

    /**
     * API: changement de statut d'anomalie (AJAX POST).
     */
    public function anomalyStatus(): never
    {
        $evenementId = (int) input('evenement_id', 0);
        $anomalieId = (int) input('anomalie_id', 0);
        $newStatus = trim((string) input('new_status', ''));
        $note = trim((string) input('note', ''));

        $validStatuses = ['DETECTEE', 'EN_COURS', 'RESOLUE', 'REJETEE', 'EN_ATTENTE'];
        if ($evenementId <= 0 || $anomalieId <= 0 || ! in_array($newStatus, $validStatuses, true)) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'Paramètres invalides.']);
            exit;
        }

        $current = Database::one(
            'SELECT statut FROM anomalies_evenement WHERE evenement_id = ? AND anomalie_id = ?',
            [$evenementId, $anomalieId]
        );
        $oldStatus = $current ? (string) $current['statut'] : null;

        Database::update('anomalies_evenement', ['statut' => $newStatus], 'evenement_id = ? AND anomalie_id = ?', [$evenementId, $anomalieId]);

        Database::insert('anomaly_status_history', [
            'evenement_id' => $evenementId,
            'anomalie_id'  => $anomalieId,
            'old_status'   => $oldStatus,
            'new_status'   => $newStatus,
            'changed_by'   => Session::userId(),
            'note'         => $note ?: null,
        ]);

        AuditLog::log('anomaly_status_change', 'anomalies_evenement', $anomalieId, ['statut' => $oldStatus], ['statut' => $newStatus]);

        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit;
    }

    /**
     * API: categories d'anomalies (AJAX GET).
     */
    public function anomalyCategories(): never
    {
        $categories = Database::all('SELECT * FROM anomalie_categories WHERE actif = 1 ORDER BY nom');
        $all = [];
        foreach ($categories as $cat) {
            $subs = Database::all('SELECT * FROM anomalie_subcategories WHERE category_id = ? AND actif = 1 ORDER BY nom', [(int) $cat['id']]);
            $cat['subcategories'] = $subs;
            $all[] = $cat;
        }

        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'categories' => $all]);
        exit;
    }

    private function find(string $id): array
    {
        $event = Database::one('SELECT * FROM evenements WHERE id = ?', [(int) $id]);

        if ($event === null) {
            abort(404, 'Événement introuvable');
        }

        return $event;
    }
}
