<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AnnouncementService;
use App\Helpers\Database;
use App\Helpers\EvenementService;
use App\Helpers\Notification;
use App\Helpers\Queue;
use App\Helpers\SlaHelper;

/**
 * Endpoint HTTP pour les tâches périodiques.
 *
 * Protégé par un token secret (CRON_TOKEN dans .env).
 * Usage : GET /cron/tick?token=xxx
 *
 * Tâches automatisées :
 *  1. Alertes SLA (J-2, J-1, retard)
 *  2. Album en retard
 *  3. Auto-clôture événements passés
 *  4. Rappels J-1
 *  5. SLA : événement en attente trop longtemps
 *  6. SLA : modification demandée non traitée
 *  7. Invitations membres expirées
 *  8. Nettoyage anciennes notifications
 *  9. Alertes capacité événements
 * 10. Nettoyage cache expiré
 */
final class CronController extends Controller
{
    public function tick(): never
    {
        $token = $_GET['token'] ?? '';

        if ($token === '' || $token !== (string) env('CRON_TOKEN', '')) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }

        $results = [];

        // 1. Alertes SLA (J-2, J-1)
        try {
            $sla = SlaHelper::runDue();
            $results['sla'] = $sla . ' alerte(s) SLA envoyée(s)';
        } catch (\Throwable $e) {
            $results['sla'] = 'error: ' . $e->getMessage();
        }

        // 2. Vérification album en retard
        try {
            $album = SlaHelper::checkAlbumDelai();
            $results['album_check'] = $album . ' alerte(s) album en retard';
        } catch (\Throwable $e) {
            $results['album_check'] = 'error: ' . $e->getMessage();
        }

        // 3. Auto-clôture événements passés → TERMINE
        try {
            $closed = EvenementService::autoCloturer();
            $results['auto_close'] = $closed . ' event(s) terminé(s)';
        } catch (\Throwable $e) {
            $results['auto_close'] = 'error: ' . $e->getMessage();
        }

        // 4. Rappels J-1 (notifications + emails)
        try {
            $reminded = EvenementService::envoyerRappels();
            $results['reminders'] = $reminded . ' rappel(s) envoyé(s)';
        } catch (\Throwable $e) {
            $results['reminders'] = 'error: ' . $e->getMessage();
        }

        // 5. SLA : événements en attente > 48h
        try {
            $pending = self::checkPendingTooLong();
            $results['pending_sla'] = $pending . ' alerte(s) attente prolongée';
        } catch (\Throwable $e) {
            $results['pending_sla'] = 'error: ' . $e->getMessage();
        }

        // 6. SLA : modifications demandées > 7 jours
        try {
            $modif = self::checkModificationOverdue();
            $results['modification_sla'] = $modif . ' alerte(s) modification en retard';
        } catch (\Throwable $e) {
            $results['modification_sla'] = 'error: ' . $e->getMessage();
        }

        // 7. Invitations membres expirées (> 7 jours)
        try {
            $expired = self::expireInvitations();
            $results['expired_invitations'] = $expired . ' invitation(s) expirée(s)';
        } catch (\Throwable $e) {
            $results['expired_invitations'] = 'error: ' . $e->getMessage();
        }

        // 8. Nettoyage des anciennes notifications (> 90 jours)
        try {
            $cleaned = Notification::cleanup(90);
            $results['notif_cleanup'] = $cleaned . ' notification(s) nettoyée(s)';
        } catch (\Throwable $e) {
            $results['notif_cleanup'] = 'error: ' . $e->getMessage();
        }

        // 9. Alertes capacité (>= 80%)
        try {
            $capacity = self::checkCapacityAlerts();
            $results['capacity_alerts'] = $capacity . ' alerte(s) capacité';
        } catch (\Throwable $e) {
            $results['capacity_alerts'] = 'error: ' . $e->getMessage();
        }

        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode([
            'status'  => 'ok',
            'time'    => date('Y-m-d H:i:s'),
            'results' => $results,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Alerte si un événement reste EN_ATTENTE > 48h.
     */
    private static function checkPendingTooLong(): int
    {
        $events = Database::all(
            'SELECT e.id, e.created_at, e.association_id
             FROM evenements e
             WHERE e.statut = "EN_ATTENTE"
               AND e.created_at < DATE_SUB(NOW(), INTERVAL 48 HOUR)
               AND e.deleted_at IS NULL'
        );

        $sent = 0;
        foreach ($events as $event) {
            $exists = Database::exists(
                'SELECT 1 FROM sla_alertes WHERE evenement_id = ? AND type = ?',
                [$event['id'], 'attente_longue']
            );
            if ($exists) continue;

            Database::insert('sla_alertes', [
                'evenement_id' => (int) $event['id'],
                'type'         => 'attente_longue',
                'message'      => 'L\'événement est en attente de validation depuis plus de 48h.',
                'envoyee'      => 1,
            ]);

            Notification::sendToRole(
                'wilaya',
                'Événement en attente',
                'L\'événement #' . $event['id'] . ' attend une validation depuis plus de 48h.',
                'sla_retard',
                ['evenement_id' => (int) $event['id']]
            );

            $sent++;
        }

        return $sent;
    }

    /**
     * Alerte si une demande de modification n'est pas traitée > 7 jours.
     */
    private static function checkModificationOverdue(): int
    {
        $events = Database::all(
            'SELECT e.id, e.updated_at, e.association_id
             FROM evenements e
             WHERE e.statut = "MODIFICATION_DEMANDEE"
               AND e.updated_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
               AND e.deleted_at IS NULL'
        );

        $sent = 0;
        foreach ($events as $event) {
            $exists = Database::exists(
                'SELECT 1 FROM sla_alertes WHERE evenement_id = ? AND type = ?',
                [$event['id'], 'modification_retard']
            );
            if ($exists) continue;

            Database::insert('sla_alertes', [
                'evenement_id' => (int) $event['id'],
                'type'         => 'modification_retard',
                'message'      => 'La demande de modification n\'a pas été traitée depuis 7 jours.',
                'envoyee'      => 1,
            ]);

            Notification::sendToRole(
                'wilaya',
                'Modification en attente',
                'La demande de modification de l\'événement #' . $event['id'] . ' reste non traitée depuis 7 jours.',
                'sla_retard',
                ['evenement_id' => (int) $event['id']]
            );

            $sent++;
        }

        return $sent;
    }

    /**
     * Expire les invitations membres > 7 jours.
     */
    private static function expireInvitations(): int
    {
        $expired = Database::run(
            'UPDATE member_invitations SET status = "expired"
             WHERE status = "pending" AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)'
        );

        return $expired > 0 ? $expired : 0;
    }

    /**
     * Alerte si un événement programmé atteint 80%+ de sa capacité.
     */
    private static function checkCapacityAlerts(): int
    {
        $events = Database::all(
            'SELECT e.id, e.capacite, e.association_id,
                    (SELECT COUNT(*) FROM evenement_participant ep WHERE ep.evenement_id = e.id) AS participants
             FROM evenements e
             WHERE e.statut IN ("PROGRAMME", "QR_GENERE", "EN_COURS")
               AND e.capacite IS NOT NULL
               AND e.capacite > 0
               AND e.deleted_at IS NULL'
        );

        $sent = 0;
        foreach ($events as $event) {
            $pct = round(((int) $event['participants'] / (int) $event['capacite']) * 100);
            if ($pct < 80) continue;

            $exists = Database::exists(
                'SELECT 1 FROM sla_alertes WHERE evenement_id = ? AND type = ?',
                [$event['id'], 'capacite_' . ($pct >= 100 ? 'full' : 'high')]
            );
            if ($exists) continue;

            $type = $pct >= 100 ? 'capacite_full' : 'capacite_high';
            $msg = $pct >= 100
                ? 'L\'événement a atteint sa capacité maximale !'
                : 'L\'événement atteint ' . $pct . '% de sa capacité.';

            Database::insert('sla_alertes', [
                'evenement_id' => (int) $event['id'],
                'type'         => $type,
                'message'      => $msg,
                'envoyee'      => 1,
            ]);

            Notification::sendToAssociation(
                (int) $event['association_id'],
                'Capacité événement',
                $msg,
                'sla_retard',
                ['evenement_id' => (int) $event['id']]
            );

            $sent++;
        }

        return $sent;
    }
}
