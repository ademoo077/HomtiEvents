<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\Rbac;

/**
 * Modèles de demande réutilisables pour les associations (Lot 4).
 *
 * Permet à une association d'enregistrer une demande d'événement type
 * (commune, adresse, capacité, informations, anomalies) et de la réutiliser
 * pour pré-remplir le formulaire de création.
 */
final class AssociationTemplateController extends Controller
{
    private function guard(): int
    {
        $this->requireAuth();
        $user = $this->user();
        if ($user === null || Rbac::role($user) !== 'association') {
            abort(403, 'Accès refusé.');
        }

        return (int) ($user['association_id'] ?? 0);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function templatesOf(int $associationId): array
    {
        return Database::all(
            'SELECT mt.id, mt.nom, mt.description, mt.commune_id, mt.adresse,
                    mt.capacite, mt.informations, mt.anomalies, mt.updated_at,
                    c.nom AS commune_nom
               FROM demande_modeles mt
               LEFT JOIN commune c ON c.id = mt.commune_id
              WHERE mt.association_id = ?
              ORDER BY mt.updated_at DESC',
            [$associationId]
        );
    }

    public function index(): never
    {
        $associationId = $this->guard();

        json_response([
            'success'   => true,
            'templates' => array_map(static function (array $t): array {
                $t['anomalies'] = ! empty($t['anomalies'])
                    ? array_map('intval', json_decode((string) $t['anomalies'], true) ?: [])
                    : [];

                return $t;
            }, $this->templatesOf($associationId)),
        ]);
    }

    public function store(): never
    {
        $associationId = $this->guard();

        $nom = trim((string) (all_input()['nom'] ?? ''));
        if ($nom !== '') {
            $nom = mb_strimwidth($nom, 0, 110, '…');
        } else {
            $nom = 'Modèle ' . date('d/m/Y');
        }

        $anomalies = all_input()['anomalies'] ?? [];
        $anomalies = is_array($anomalies) ? array_values(array_map('intval', $anomalies)) : [];

        Database::run(
            'INSERT INTO demande_modeles
                (association_id, nom, description, commune_id, adresse, capacite, informations, anomalies)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $associationId,
                $nom,
                trim((string) (all_input()['description'] ?? '')),
                ! empty(all_input()['commune_id']) ? (int) all_input()['commune_id'] : null,
                trim((string) (all_input()['adresse'] ?? '')),
                ! empty(all_input()['capacite']) ? (int) all_input()['capacite'] : null,
                trim((string) (all_input()['informations'] ?? '')),
                $anomalies !== [] ? json_encode($anomalies) : null,
            ]
        );

        json_response(['success' => true, 'message' => 'Modèle enregistré.']);
    }

    public function destroy(string $id): never
    {
        $associationId = $this->guard();

        Database::run(
            'DELETE FROM demande_modeles WHERE id = ? AND association_id = ?',
            [(int) $id, $associationId]
        );

        json_response(['success' => true]);
    }
}
