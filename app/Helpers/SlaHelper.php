<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * SLA — rappels J-2, J-1 et alertes retard pour les événements.
 */
final class SlaHelper
{
    /**
     * Calcule et insère les alertes SLA pour un événement programmé.
     */
    public static function scheduleForEvenement(int $evenementId, string $dateEvenement, string $heure = '00:00:00'): void
    {
        $timestamp = strtotime($dateEvenement . ' ' . $heure);

        $alerts = [
            'j-2' => $timestamp - 2 * 86400,
            'j-1' => $timestamp - 1 * 86400,
        ];

        foreach ($alerts as $type => $at) {
            if ($at < time()) {
                continue;
            }

            Database::run(
                'INSERT IGNORE INTO sla_alertes (evenement_id, type, message, envoyee)
                 VALUES (?, ?, ?, 0)',
                [
                    $evenementId,
                    $type,
                    sprintf("Rappel : l'événement est dans %s.", $type === 'j-2' ? '2 jours' : '1 jour'),
                ]
            );
        }
    }

    /**
     * Exécute les alertes dues (appelé par le worker/cron).
     */
    public static function runDue(): int
    {
        $alerts = Database::all(
            'SELECT sa.*, e.date_evenement, e.heure, e.association_id, e.id AS ev_id
             FROM sla_alertes sa
             JOIN evenements e ON e.id = sa.evenement_id
             WHERE sa.envoyee = 0'
        );

        $sent = 0;
        foreach ($alerts as $alert) {
            $dueAt = match ($alert['type']) {
                'j-2'   => strtotime((string) $alert['date_evenement']) - 2 * 86400,
                'j-1'   => strtotime((string) $alert['date_evenement']) - 1 * 86400,
                'retard'=> time(),
                default => time(),
            };

            if ($dueAt > time()) {
                continue;
            }

            $associationId = (int) ($alert['association_id'] ?? 0);
            if ($associationId > 0) {
                Notification::sendToAssociation(
                    $associationId,
                    __('sla.rappel_titre'),
                    __('sla.rappel_message', ['date' => $alert['date_evenement']]),
                    'rappel',
                    ['evenement_id' => $alert['ev_id']]
                );
            }

            Database::run('UPDATE sla_alertes SET envoyee = 1 WHERE id = ?', [$alert['id']]);
            $sent++;
        }

        return $sent;
    }

    /**
     * Détecte les événements terminés sans album après 48h → alerte retard Wilaya.
     */
    public static function checkAlbumDelai(): int
    {
        $events = Database::all(
            'SELECT e.id, e.date_evenement FROM evenements e
             WHERE e.statut = ? AND e.date_evenement IS NOT NULL
               AND e.date_evenement < DATE_SUB(CURDATE(), INTERVAL 1 DAY)
               AND NOT EXISTS (SELECT 1 FROM albums a WHERE a.evenement_id = e.id)',
            ['TERMINE']
        );

        $sent = 0;
        foreach ($events as $event) {
            $existing = Database::exists(
                'SELECT 1 FROM sla_alertes WHERE evenement_id = ? AND type = ?',
                [$event['id'], 'retard']
            );

            if ($existing) {
                continue;
            }

            Database::insert('sla_alertes', [
                'evenement_id' => (int) $event['id'],
                'type'         => 'retard',
                'message'      => "Album officiel non créé dans les 48h après la fin de l'événement.",
                'envoyee'      => 1,
            ]);

            Notification::sendToRole(
                'wilaya',
                __('sla.album_retard_titre'),
                __('sla.album_retard_message', ['id' => $event['id']]),
                'sla_retard',
                ['evenement_id' => (int) $event['id']]
            );

            $sent++;
        }

        return $sent;
    }
}
