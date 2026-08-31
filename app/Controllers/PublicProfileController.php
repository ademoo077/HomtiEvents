<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\I18n;

/**
 * Fiches publiques — association / EPIC.
 *
 * Consultables sans authentification (grand public, citoyens non connectés).
 * Intègre la plateforme citoyen (layout citoyen) pour une expérience uniforme.
 * Ne transmet JAMAIS de données sensibles (email, téléphone, adresses
 * exactes, notes internes, historique de transition).
 */
final class PublicProfileController extends Controller
{
    public function association(string $id): never
    {
        $isAr = I18n::direction() === 'rtl';
        $associationId = (int) $id;

        $association = Database::one(
            'SELECT id, nom, caractere, numero_agrement,
                    commune_id, created_at
             FROM associations WHERE id = ? AND valide = 1',
            [$associationId]
        );

        if ($association === null) {
            abort(404, 'Association introuvable.');
        }

        $commune = null;
        if (! empty($association['commune_id'])) {
            $commune = Database::one(
                'SELECT id, nom FROM commune WHERE id = ?',
                [(int) $association['commune_id']]
            );
        }

        $stats = [
            'evenements_realises' => (int) Database::value(
                'SELECT COUNT(*) FROM evenements
                 WHERE association_id = ? AND statut IN (\'PROGRAMME\', \'EN_COURS\', \'TERMINE\')
                   AND deleted_at IS NULL',
                [$associationId]
            ),
            'participants' => (int) Database::value(
                'SELECT COUNT(*) FROM evenement_participant ep
                 JOIN evenements e ON e.id = ep.evenement_id
                 WHERE e.association_id = ? AND e.deleted_at IS NULL',
                [$associationId]
            ),
            'note_moyenne' => Database::value(
                'SELECT ROUND(AVG(ev.note), 1) FROM evaluation ev
                 WHERE ev.association_id = ?',
                [$associationId]
            ),
        ];

        $events = Database::all(
            "SELECT e.id, e.adresse, e.description, e.statut, e.date_evenement, e.heure,
                    c.nom AS commune_nom
             FROM evenements e
             LEFT JOIN commune c ON c.id = e.commune_id
             WHERE e.association_id = ? AND e.deleted_at IS NULL
               AND e.statut IN ('PROGRAMME', 'EN_COURS', 'TERMINE')
             ORDER BY e.date_evenement DESC
             LIMIT 12",
            [$associationId]
        );

        $album = Database::one(
            'SELECT a.id, a.titre, a.recit
             FROM albums a
             JOIN evenements e ON e.id = a.evenement_id
             WHERE e.association_id = ? AND e.deleted_at IS NULL
             ORDER BY a.id DESC LIMIT 1',
            [$associationId]
        );

        $photos = [];
        if ($album !== null) {
            $photos = Database::all(
                'SELECT image, legende FROM photos
                 WHERE album_id = ? AND status = ? AND image IS NOT NULL
                 ORDER BY sort_order ASC, uploaded_at DESC
                 LIMIT 20',
                [(int) $album['id'], 'active']
            );
        }

        $og = [
            'title'       => (string) ($association['nom'] ?? ''),
            'description' => mb_substr(trim('Fiche publique — ' . ($association['nom'] ?? '')), 0, 160),
            'image'       => asset('/assets/img/icon-192.png'),
        ];

        $this->view('public/association', [
            'title'       => $association['nom'] ?? '',
            'og'          => $og,
            'association' => $association,
            'commune'     => $commune,
            'stats'       => $stats,
            'events'      => $events,
            'photos'      => $photos,
        ], 'public');
    }

    public function epic(string $id): never
    {
        $isAr = I18n::direction() === 'rtl';
        $epicId = (int) $id;

        $epic = Database::one(
            'SELECT id, nom, description, couleur, created_at
             FROM epic WHERE id = ?',
            [$epicId]
        );

        if ($epic === null) {
            abort(404, 'EPIC introuvable.');
        }

        $stats = [
            'interventions_terminees' => (int) Database::value(
                "SELECT COUNT(*) FROM evenement_epic
                 WHERE epic_id = ? AND statut = 'TERMINE'",
                [$epicId]
            ),
            'interventions_en_cours' => (int) Database::value(
                "SELECT COUNT(*) FROM evenement_epic
                 WHERE epic_id = ? AND statut IN ('AFFECTE', 'EN_COURS')",
                [$epicId]
            ),
            'anomalies_traitees' => (int) Database::value(
                "SELECT COUNT(DISTINCT ea.evenement_id)
                 FROM evenements e
                 JOIN anomalies_evenement ea ON ea.evenement_id = e.id
                 JOIN evenement_epic ee ON ee.evenement_id = e.id
                 WHERE ee.epic_id = ? AND e.deleted_at IS NULL",
                [$epicId]
            ),
            'delai_moyen_jours' => Database::value(
                "SELECT ROUND(AVG(DATEDIFF(e.date_evenement, ee.date_affectation)), 1)
                 FROM evenement_epic ee
                 JOIN evenements e ON e.id = ee.evenement_id
                 WHERE ee.epic_id = ? AND ee.statut = 'TERMINE' AND ee.date_affectation IS NOT NULL AND e.date_evenement IS NOT NULL",
                [$epicId]
            ),
        ];

        $anomalies = Database::all(
            'SELECT DISTINCT a.nom
             FROM anomalies a
             JOIN epic_anomalies ea ON ea.anomalie_id = a.id
             WHERE ea.epic_id = ?
             ORDER BY a.nom',
            [$epicId]
        );

        $recentEvents = Database::all(
            "SELECT e.id, e.adresse, e.date_evenement, e.statut,
                    ee.statut AS intervention_statut,
                    c.nom AS commune_nom
             FROM evenement_epic ee
             JOIN evenements e ON e.id = ee.evenement_id
             LEFT JOIN commune c ON c.id = e.commune_id
             WHERE ee.epic_id = ? AND e.deleted_at IS NULL
             ORDER BY e.date_evenement DESC
             LIMIT 10",
            [$epicId]
        );

        $og = [
            'title'       => (string) ($epic['nom'] ?? ''),
            'description' => mb_substr((string) ($epic['description'] ?? ''), 0, 160),
            'image'       => asset('/assets/img/icon-192.png'),
        ];

        $this->view('public/epic', [
            'title'        => $epic['nom'] ?? '',
            'og'           => $og,
            'epic'         => $epic,
            'stats'        => $stats,
            'anomalies'    => $anomalies,
            'recentEvents' => $recentEvents,
        ], 'public');
    }
}
