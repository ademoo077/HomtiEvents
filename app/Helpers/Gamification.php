<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Gamification : points et badges citoyens.
 */
final class Gamification
{
    public const POINTS_PARTICIPATION = 50;
    public const POINTS_EVENEMENT     = 20;

    public static function awardPoints(int $userId, int $points, string $raison, ?int $evenementId = null): void
    {
        Database::insert('citizen_points', [
            'user_id'    => $userId,
            'points'     => $points,
            'raison'     => $raison,
            'evenement_id' => $evenementId,
        ]);

        Database::run('UPDATE users SET points = points + ? WHERE id = ?', [$points, $userId]);

        self::checkBadges($userId);
    }

    public static function participation(int $userId, int $evenementId): void
    {
        self::awardPoints($userId, self::POINTS_PARTICIPATION, __('gamification.participation'), $evenementId);
    }

    public static function checkBadges(int $userId): void
    {
        $badges = Database::all('SELECT * FROM badges');

        foreach ($badges as $badge) {
            if (self::earned($userId, (int) $badge['id'])) {
                continue;
            }

            if (! self::conditionSatisfied($userId, $badge['condition_type'])) {
                continue;
            }

            Database::insert('user_badges', [
                'user_id'  => $userId,
                'badge_id' => (int) $badge['id'],
            ]);

            if ((int) $badge['points_recompense'] > 0) {
                self::awardPoints(
                    $userId,
                    (int) $badge['points_recompense'],
                    __('gamification.badge_gagne') . ' : ' . $badge['nom']
                );
            }

            Notification::send(
                $userId,
                __('gamification.badge_gagne') . ' : ' . $badge['nom'],
                $badge['description'] ?? '',
                'badge',
                ['badge_id' => (int) $badge['id']]
            );
        }
    }

    private static function earned(int $userId, int $badgeId): bool
    {
        return Database::exists('SELECT 1 FROM user_badges WHERE user_id = ? AND badge_id = ?', [$userId, $badgeId]);
    }

    private static function conditionSatisfied(int $userId, string $condition): bool
    {
        return match ($condition) {
            'first_event'          => self::eventsForAssociation($userId) >= 1,
            '10_events'            => self::eventsForAssociation($userId) >= 10,
            '50_events'            => self::eventsForAssociation($userId) >= 50,
            'first_participation'  => self::scans($userId) >= 1,
            '5_participations'     => self::scans($userId) >= 5,
            '25_participations'    => self::scans($userId) >= 25,
            '50_participations'    => self::scans($userId) >= 50,
            '100_scans'            => self::scans($userId) >= 100,
            '1000_scans'           => self::scans($userId) >= 1000,
            default                => false,
        };
    }

    private static function eventsForAssociation(int $userId): int
    {
        $associationId = Database::value('SELECT association_id FROM users WHERE id = ?', [$userId]);

        if ($associationId === null) {
            return 0;
        }

        return (int) Database::value(
            'SELECT COUNT(*) FROM evenements WHERE association_id = ?',
            [(int) $associationId]
        );
    }

    public static function scans(int $userId): int
    {
        return (int) Database::value(
            'SELECT COUNT(*) FROM evenement_participant WHERE user_id = ?',
            [$userId]
        );
    }

    public static function badgesOf(int $userId): array
    {
        return Database::all(
            'SELECT b.*, ub.date_obtention FROM badges b
             JOIN user_badges ub ON ub.badge_id = b.id
             WHERE ub.user_id = ? ORDER BY ub.date_obtention DESC',
            [$userId]
        );
    }

    public static function pointsHistory(int $userId, int $limit = 20): array
    {
        return Database::all(
            'SELECT * FROM citizen_points WHERE user_id = ? ORDER BY date_attribution DESC LIMIT ' . (int) $limit,
            [$userId]
        );
    }

    public static function leaderboard(int $limit = 10): array
    {
        return Database::all(
            'SELECT id, nom, prenom, avatar, points FROM users
             WHERE role_user = ? AND is_active = 1
             ORDER BY points DESC, updated_at ASC LIMIT ' . (int) $limit,
            ['citoyen']
        );
    }

    public static function rank(int $userId): int
    {
        $points = (int) Database::value('SELECT points FROM users WHERE id = ?', [$userId]);

        return (int) Database::value(
            'SELECT COUNT(*) + 1 FROM users WHERE role_user = ? AND points > ?',
            ['citoyen', $points]
        );
    }
}
