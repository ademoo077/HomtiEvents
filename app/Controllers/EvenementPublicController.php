<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\I18n;
use App\Helpers\Session;


/**
 * Page publique de détail d'un événement : /evenement/{id}
 *
 * Consultable sans authentification (indexable, partageable).
 * Calquée sur EnhancedQrCodeController::eventDetail() pour la
 * requête principale ; masque l'action de scan pour les
 * visiteurs non authentifiés.
 */
final class EvenementPublicController extends Controller
{
    public function show(string $id): never
    {
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
        $album = Database::one(
            'SELECT id, titre, recit, statut FROM albums WHERE evenement_id = ? AND statut = ?',
            [(int) $id, 'publie']
        );
        if ($album !== null) {
            $photos = Database::all(
                'SELECT * FROM photos WHERE album_id = ? AND status = ? ORDER BY sort_order ASC, uploaded_at DESC',
                [(int) $album['id'], 'active']
            );
        }

        $hasParticipated = false;
        if (Session::userId()) {
            $hasParticipated = Database::exists(
                'SELECT 1 FROM evenement_participant WHERE evenement_id = ? AND user_id = ?',
                [(int) $id, (int) Session::userId()]
            );
        }

        $og = [
            'title'       => (string) ($event['adresse'] ?? 'Événement'),
            'description' => $this->ogDescription($event),
            'image'       => $this->ogImage($album, $photos),
        ];

        // Vue autonome — pas de layout pour éviter le sidebar admin
        echo view('public/event-detail', [
            'event'          => $event,
            'photos'         => $photos,
            'album'          => $album,
            'hasParticipated'=> $hasParticipated,
            'isPublic'       => true,
            'og'             => $og,
        ]);
        exit;
    }

    /** Description SEO concise bilingue pour la balise og:description. */
    private function ogDescription(array $event): string
    {
        $adresse   = (string) ($event['adresse'] ?? '');
        $commune   = (string) ($event['commune_nom'] ?? '');
        $dateStr   = (string) ($event['date_evenement'] ?? '');
        $date      = '';
        if ($dateStr !== '') {
            $dt = new \DateTimeImmutable($dateStr);
            $date = $dt->format('d/m/Y');
        }
        $parts = array_filter([$adresse, $commune, $date]);
        return implode(', ', $parts) ?: (I18n::direction() === 'rtl'
            ? 'حدثاً على منصة حومتي ايفانت'
            : 'Événement partagé sur la plateforme حومتي ايفانت');
    }

    /** Image OG : première photo de l'album sinon logo par défaut. */
    private function ogImage(?array $album, array $photos): string
    {
        foreach ($photos as $photo) {
            if (! empty($photo['image'])) {
                return asset((string) $photo['image']);
            }
        }
        return asset('/assets/img/icon-192.png');
    }
}
