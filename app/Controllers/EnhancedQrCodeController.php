<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\QrCodeGenerator;
use App\Helpers\Rbac;
use App\Helpers\Session;

final class EnhancedQrCodeController extends Controller
{
    /**
     * Page principale du scan — interface caméra + galerie.
     * Accessible depuis le FAB sur n'importe quel écran citoyen.
     */
    public function scan(): never
    {
        $this->requireAuth();

        $evenements = Database::all(
            'SELECT e.id, e.description, e.adresse, e.date_evenement, e.statut, e.heure,
                    q.token_qr, q.date_expiration
             FROM evenements e
             LEFT JOIN qr_event q ON q.evenement_id = e.id
             WHERE e.deleted_at IS NULL
               AND e.statut IN (\'PROGRAMME\', \'QR_GENERE\', \'EN_COURS\')
               AND q.token_qr IS NOT NULL
             ORDER BY e.date_evenement DESC LIMIT 10'
        );

        $this->view('qrcode/scan_optimized', [
            'evenements' => $evenements,
        ], 'citoyen');
    }

    /**
     * API : valider un token QR et enregistrer la participation.
     * Gestion complète des erreurs avec messages clairs.
     */
    public function validateScan(): never
    {
        $this->requireAuth();

        $token = trim((string) ($_POST['token'] ?? '') ?: (string) ($_GET['token'] ?? ''));
        $token = preg_replace('/[^\w-]/', '', $token);

        if ($token === '') {
            json_response(['success' => false, 'error' => "Code QR invalide."]);
        }

        $qr = QrCodeGenerator::findByToken($token);

        if ($qr === null) {
            json_response(['success' => false, 'error' => "Ce code QR n'est pas reconnu."]);
        }

        if (!QrCodeGenerator::isValid($qr, true)) {
            $errorMsg = "Ce code QR n'est plus valide.";
            if (($qr['date_expiration'] ?? null) !== null && strtotime((string) $qr['date_expiration']) < time()) {
                $errorMsg = "Ce code QR a expiré après la fin de l'événement.";
            } elseif (! in_array(($qr['statut'] ?? ''), ['PROGRAMME', 'QR_GENERE', 'EN_COURS'], true)) {
                $errorMsg = "Cet événement n'est plus ouvert à la participation.";
            }
            json_response(['success' => false, 'error' => $errorMsg, 'expired' => true]);
        }

        $userId = Session::userId();
        if (QrCodeGenerator::hasParticipated((int) $qr['evenement_id'], (int) $userId)) {
            json_response(['success' => false, 'error' => "Vous avez déjà participé à cet événement."]);
        }

        if (QrCodeGenerator::estComplet((int) $qr['evenement_id'])) {
            json_response(['success' => false, 'error' => "Désolé, la capacité maximale de cet événement est atteinte."]);
        }

        $ok = QrCodeGenerator::registerParticipation((int) $qr['evenement_id'], (int) $userId);

        if (!$ok) {
            json_response(['success' => false, 'error' => "Erreur lors de l'enregistrement. Veuillez réessayer."]);
        }

        $event = Database::one(
            'SELECT e.adresse, e.date_evenement, e.heure, e.description, e.adresse AS lieu
             FROM evenements e WHERE e.id = ? AND e.deleted_at IS NULL',
            [(int) $qr['evenement_id']]
        );

        $newBadge = Database::one(
            'SELECT b.id, b.nom, b.description, b.icone, b.couleur
             FROM user_badges ub
             JOIN badges b ON b.id = ub.badge_id
             WHERE ub.user_id = ? AND ub.date_obtention >= DATE_SUB(NOW(), INTERVAL 2 MINUTE)
             ORDER BY ub.date_obtention DESC LIMIT 1',
            [(int) $userId]
        );

        json_response([
            'success' => true,
            'event' => $event,
            'message' => 'Participation enregistrée avec succès !',
            'points_gagnes' => \App\Helpers\Gamification::POINTS_PARTICIPATION,
            'new_badge' => $newBadge,
        ]);
    }

    /**
     * Lister les participations d'un citoyen (historique avec preuve de scan).
     * Adaptatif : utilise le layout membre si l'utilisateur est membre.
     */
    public function participations(): never
    {
        $this->requireAuth();

        $user   = Session::user();
        $userId = Session::userId();
        $role   = Rbac::role($user);

        $participations = Database::all(
            'SELECT e.id AS evenement_id, e.adresse, e.date_evenement, e.heure, e.statut AS event_statut,
                    ep.heure_scan, ep.ip_address,
                    c.nom AS commune_nom,
                    a.id AS album_id, a.titre AS album_titre,
                    (SELECT COUNT(*) FROM photos p WHERE p.album_id = a.id AND p.status = ?) AS nb_photos
             FROM evenement_participant ep
             JOIN evenements e ON e.id = ep.evenement_id AND e.deleted_at IS NULL
             LEFT JOIN commune c ON c.id = e.commune_id
             LEFT JOIN albums a ON a.evenement_id = e.id AND a.statut = ?
             WHERE ep.user_id = ?
             ORDER BY ep.heure_scan DESC',
            ['publie', 'active', $userId]
        );

        $layout = $role === 'membre' ? 'member' : 'citoyen';

        $this->view('citoyen/participations', [
            'participations' => $participations,
            'role'           => $role,
        ], $layout);
    }

    /**
     * Détail d'un événement pour la vue carte/liste.
     */
    public function eventDetail(string $id): never
    {
        $this->requireAuth();

        $event = Database::one(
            'SELECT e.*,
                    c.nom AS commune_nom, c.latitude, c.longitude,
                    a.nom AS association_nom,
                    q.token_qr, q.date_expiration, q.date_debut,
                    (SELECT COUNT(*) FROM evenement_participant ep WHERE ep.evenement_id = e.id) AS participants_count,
                    (SELECT GROUP_CONCAT(an.nom SEPARATOR ", ") FROM anomalies_evenement ae
                     JOIN anomalies an ON an.id = ae.anomalie_id WHERE ae.evenement_id = e.id) AS anomalies
             FROM evenements e
             LEFT JOIN commune c ON c.id = e.commune_id
             LEFT JOIN associations a ON a.id = e.association_id
             LEFT JOIN qr_event q ON q.evenement_id = e.id
             WHERE e.id = ? AND e.deleted_at IS NULL',
            [(int) $id]
        );

        if ($event === null) {
            abort(404, "Événement introuvable.");
        }

        $photos = [];
        $album = Database::one('SELECT id, titre, recit, statut FROM albums WHERE evenement_id = ? AND statut = ?', [(int) $id, 'publie']);
        if ($album !== null) {
            $photos = Database::all('SELECT * FROM photos WHERE album_id = ? AND status = ? ORDER BY sort_order ASC, uploaded_at DESC', [(int) $album['id'], 'active']);
        }

        $hasParticipated = Database::exists(
            'SELECT 1 FROM evenement_participant WHERE evenement_id = ? AND user_id = ?',
            [(int) $id, Session::userId()]
        );

        $isFavori = Database::exists(
            'SELECT 1 FROM citoyen_favoris WHERE evenement_id = ? AND user_id = ?',
            [(int) $id, Session::userId()]
        );

        $this->view('citoyen/event-detail', [
            'event'          => $event,
            'photos'         => $photos,
            'album'          => $album,
            'hasParticipated' => $hasParticipated,
            'isFavori'       => $isFavori,
        ], 'citoyen');
    }

    /**
     * Liste dynamique des événements (explorateur).
     *
     * En JSON quand la requête est en AJAX, sinon affiche la vue explorateur
     * avec les données initiales et les référentiels (communes, anomalies).
     */
    public function listEvents(): never
    {
        $this->requireAuth();

        $filters = [
            'date'     => $_GET['date'] ?? 'all',
            'commune'  => $_GET['commune'] ?? null,
            'anomalie' => $_GET['anomalie'] ?? ($_GET['category_id'] ?? null),
            'q'        => $_GET['q'] ?? null,
        ];

        $where = [
            'e.deleted_at IS NULL',
            "e.statut IN ('PROGRAMME', 'QR_GENERE', 'EN_COURS', 'TERMINE', 'VALIDÉ')",
        ];
        $params = [];

        if ($filters['date'] === 'today') {
            $where[] = 'DATE(e.date_evenement) = CURDATE()';
        } elseif ($filters['date'] === 'week') {
            $where[] = 'e.date_evenement BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)';
        } elseif ($filters['date'] === 'upcoming') {
            $where[] = 'e.date_evenement >= CURDATE()';
        } elseif ($filters['date'] === 'past') {
            $where[] = 'e.date_evenement < CURDATE()';
        }

        if ($filters['commune']) {
            $where[] = 'e.commune_id = ?';
            $params[] = (int) $filters['commune'];
        }

        if ($filters['anomalie']) {
            $where[] = 'EXISTS (SELECT 1 FROM anomalies_evenement ae
                        WHERE ae.evenement_id = e.id AND ae.anomalie_id = ?)';
            $params[] = (int) $filters['anomalie'];
        }

        if ($filters['q']) {
            $where[] = '(e.adresse LIKE ? OR e.description LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            $params[] = $like;
            $params[] = $like;
        }

        // Tri : géolocalisation si fournie, sinon par date.
        $orderBy = 'e.date_evenement DESC';
        $lat = $_GET['lat'] ?? null;
        $lon = $_GET['lon'] ?? null;
        if ($lat !== null && $lon !== null && is_numeric($lat) && is_numeric($lon)) {
            $orderBy = '(6371 * 2 * ASIN(SQRT(POWER(SIN(RADIANS(? - c.latitude) / 2), 2)
                        + COS(RADIANS(?)) * COS(RADIANS(c.latitude)) * POWER(SIN(RADIANS(? - c.longitude) / 2), 2)))) ASC';
            $params[] = (float) $lat;
            $params[] = (float) $lat;
            $params[] = (float) $lon;
        }

        $sql = 'SELECT e.*, c.id AS commune_id, c.nom AS commune_nom, c.latitude, c.longitude,
                       (SELECT COUNT(*) FROM evenement_participant ep WHERE ep.evenement_id = e.id) AS participants,
                       (SELECT GROUP_CONCAT(an.nom SEPARATOR ", ") FROM anomalies_evenement ae
                        JOIN anomalies an ON an.id = ae.anomalie_id WHERE ae.evenement_id = e.id) AS anomalies
                FROM evenements e
                LEFT JOIN commune c ON c.id = e.commune_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY ' . $orderBy . '
                LIMIT 50';

        $events = Database::all($sql, $params);

        // Marquer les événements déjà en favori (pour les boutons cœur).
        $favoriIds = array_map(
            static fn (array $r): int => (int) $r['evenement_id'],
            Database::all('SELECT evenement_id FROM citoyen_favoris WHERE user_id = ?', [Session::userId()])
        );
        $isFavori = array_flip($favoriIds);
        foreach ($events as &$ev) {
            $ev['is_favori'] = isset($isFavori[(int) $ev['id']]);
        }
        unset($ev);

        $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || (($_GET['ajax'] ?? null) === '1');

        if ($isAjax) {
            json_response(['events' => $events]);
        }

        $communes = Database::all(
            'SELECT c.id, c.nom FROM commune c WHERE c.is_active = 1 ORDER BY c.nom ASC'
        );
        $anomalies = Database::all(
            'SELECT a.id, a.nom, a.icone, a.couleur FROM anomalies a ORDER BY a.nom ASC'
        );

        $this->view('citoyen/explorer', [
            'events'    => $events,
            'communes'  => $communes,
            'anomalies' => $anomalies,
            'filters'   => $filters,
        ], 'citoyen');
    }
}
