<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Attributions de badges aux utilisateurs.
 */
final class Badge
{
    public static function award(int $userId, int $badgeId): bool
    {
        if (Database::exists('SELECT 1 FROM user_badges WHERE user_id = ? AND badge_id = ?', [$userId, $badgeId])) {
            return false;
        }

        Database::insert('user_badges', [
            'user_id' => $userId,
            'badge_id' => $badgeId,
        ]);

        $points = (int) Database::value('SELECT points_recompense FROM badges WHERE id = ?', [$badgeId]);
        if ($points > 0) {
            Database::run('UPDATE users SET points = points + ? WHERE id = ?', [$points, $userId]);
        }

        return true;
    }

    public static function all(): array
    {
        return Database::all('SELECT * FROM badges ORDER BY points_recompense ASC');
    }

    public static function users(): array
    {
        return Database::all(
            'SELECT u.id, u.nom, u.prenom, u.email, u.avatar, b.nom AS badge_nom, b.icone, ub.date_obtention
             FROM user_badges ub
             JOIN users u ON u.id = ub.user_id
             JOIN badges b ON b.id = ub.badge_id
             ORDER BY ub.date_obtention DESC'
        );
    }
}
