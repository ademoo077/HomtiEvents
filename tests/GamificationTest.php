<?php

declare(strict_types=1);

namespace Tests;

use App\Helpers\Database;
use App\Helpers\Gamification;

final class GamificationTest extends DatabaseTestCase
{
    public function testAwardPoints(): void
    {
        $citoyen = $this->userByEmail('sami@citoyen.dz');

        Gamification::awardPoints((int) $citoyen['id'], 100, 'test');

        $this->assertSame((int) $citoyen['points'] + 100, (int) Database::value('SELECT points FROM users WHERE id = ?', [(int) $citoyen['id']]));

        $history = Gamification::pointsHistory((int) $citoyen['id']);
        $this->assertCount(1, $history);
        $this->assertSame(100, (int) $history[0]['points']);
    }

    public function testParticipationAward(): void
    {
        $citoyen = $this->userByEmail('sami@citoyen.dz');
        $event = $this->eventByStatus('PROGRAMME');

        Gamification::participation((int) $citoyen['id'], (int) $event['id']);

        $this->assertSame(
            (int) $citoyen['points'] + Gamification::POINTS_PARTICIPATION,
            (int) Database::value('SELECT points FROM users WHERE id = ?', [(int) $citoyen['id']])
        );
    }

    public function testBadgeAwardedOnCondition(): void
    {
        $citoyen = $this->userByEmail('sami@citoyen.dz');

        $badgeId = Database::insert('badges', [
            'nom'               => 'Premier pas',
            'description'       => 'Participer à un premier événement',
            'icone'             => 'star',
            'condition_type'    => 'first_event',
            'points_recompense' => 10,
        ]);

        Gamification::checkBadges((int) $citoyen['id']);
        $this->assertCount(0, Gamification::badgesOf((int) $citoyen['id']), 'Le badge first_event concerne les associations');

        $president = $this->userByEmail('president@elamel.dz');
        Gamification::checkBadges((int) $president['id']);
        $this->assertGreaterThanOrEqual(1, count(Gamification::badgesOf((int) $president['id'])), 'Le président a au moins 1 événement → badge accordé');

        Database::delete('badges', 'id = ?', [$badgeId]);
    }

    public function testScansCount(): void
    {
        $citoyen = $this->userByEmail('amina@citoyen.dz');

        $this->assertGreaterThanOrEqual(1, Gamification::scans((int) $citoyen['id']));
    }

    public function testLeaderboard(): void
    {
        $rows = Gamification::leaderboard(5);

        $this->assertNotEmpty($rows);

        for ($i = 1; $i < count($rows); $i++) {
            $this->assertGreaterThanOrEqual((int) $rows[$i]['points'], (int) $rows[$i - 1]['points']);
        }
    }
}
