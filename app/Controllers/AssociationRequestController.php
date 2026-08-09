<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuditLog;
use App\Helpers\Database;
use App\Helpers\Notification;
use App\Helpers\Session;

/**
 * Gestion des demandes d'inscription des associations (côté Wilaya).
 *
 * La soumission publique passe par AuthController@associationRegister
 * (route /auth/register-association) ; ici on traite la liste, le détail,
 * la validation et le refus.
 */
final class AssociationRequestController extends Controller
{
    private const PER_PAGE = 20;

    // ─── WILAYA — LISTE DES DEMANDES ────────────────────────

    /**
     * Liste des demandes d'inscription (admin Wilaya).
     */
    public function index(): never
    {
        $this->requirePermission('association_request.view');

        $status = (string) input('status', '');
        $q = trim((string) input('q', ''));

        $sql = 'SELECT * FROM association_requests';
        $params = [];
        $wheres = [];

        if ($status !== '' && in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $wheres[] = 'status = ?';
            $params[] = $status;
        }

        if ($q !== '') {
            $wheres[] = '(association_name LIKE ? OR email LIKE ? OR president_lastname LIKE ?)';
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if (! empty($wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $wheres);
        }

        $sql .= ' ORDER BY created_at DESC';
        $page = (int) input('page', 1);
        $result = Database::paginate($sql, $params, self::PER_PAGE, $page);

        $this->view('wilaya.association-requests.index', [
            'requests'   => $result['items'],
            'total'      => $result['total'],
            'page'       => $result['page'],
            'lastPage'   => $result['last_page'],
            'status'     => $status,
            'q'          => $q,
        ]);
    }

    /**
     * Page détail d'une demande.
     */
    public function show(string $id): never
    {
        $this->requirePermission('association_request.view');

        $request = Database::one('SELECT * FROM association_requests WHERE id = ?', [(int) $id]);
        if ($request === null) {
            abort(404, 'Demande introuvable.');
        }

        $this->view('wilaya.association-requests.show', [
            'request' => $request,
        ]);
    }

    /**
     * Valider une demande — crée le compte président + l'association.
     */
    public function approve(string $id): never
    {
        $this->requirePermission('association_request.approve');
        $this->csrfCheck();

        $request = Database::one('SELECT * FROM association_requests WHERE id = ?', [(int) $id]);
        if ($request === null) {
            abort(404, 'Demande introuvable.');
        }

        if ($request['status'] !== 'pending') {
            flash('error', 'Cette demande a déjà été traitée.', 'warning');
            $this->redirect('admin/association-requests/' . (int) $id);
        }

        $userId = null;

        Database::transaction(function () use ($request, &$userId) {
            // 1. Créer l'association
            $associationId = Database::insert('associations', [
                'nom'                => $request['association_name'],
                'caractere'          => 'association',
                'numero_agrement'    => $request['approval_number'],
                'agrement_fichier'   => $request['approval_file'],
                'nom_prenom_president' => $request['president_firstname'] . ' ' . $request['president_lastname'],
                'telephone'          => $request['phone'],
                'email'              => $request['email'],
                'date_creation'      => date('Y-m-d'),
                'commune_id'         => null,
                'valide'             => 1,
            ]);

            $associationRoleId = (int) Database::value('SELECT id FROM roles WHERE nom = ?', ['association']);

            // 2. Compte président : réutiliser celui créé à l'inscription, sinon créer (demandes legacy)
            $linkedUserId = (int) ($request['user_id'] ?? 0);
            if ($linkedUserId > 0) {
                Database::update('users', [
                    'association_id' => $associationId,
                    'is_active'      => 1,
                ], 'id = ?', [$linkedUserId]);

                if (
                    $associationRoleId > 0
                    && ! Database::exists('SELECT 1 FROM user_roles WHERE user_id = ? AND role_id = ?', [$linkedUserId, $associationRoleId])
                ) {
                    Database::insert('user_roles', [
                        'user_id' => $linkedUserId,
                        'role_id' => $associationRoleId,
                    ]);
                }

                $userId = $linkedUserId;
            } else {
                $hashedPassword = password_hash('Harmonia@2026', PASSWORD_BCRYPT);
                $userId = (int) Database::insert('users', [
                    'nom'            => $request['president_lastname'],
                    'prenom'         => $request['president_firstname'],
                    'email'          => $request['president_email'],
                    'password'       => $hashedPassword,
                    'role_user'      => 'association',
                    'telephone'      => $request['president_phone'],
                    'association_id' => $associationId,
                    'is_active'      => 1,
                ]);

                if ($associationRoleId > 0) {
                    Database::insert('user_roles', [
                        'user_id' => $userId,
                        'role_id' => $associationRoleId,
                    ]);
                }
            }

            // 3. Mettre à jour le statut de la demande
            Database::update('association_requests', [
                'status'       => 'approved',
                'processed_by' => Session::userId(),
                'processed_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [(int) $request['id']]);

            AuditLog::log('association_request_approved', 'association_requests', (int) $request['id'], null, [
                'association_id' => $associationId,
                'user_id'        => $userId,
            ]);

            AuditLog::log('association_account_created', 'users', $userId, null, [
                'email' => $request['president_email'],
            ]);
        });

        // Notifier le président de l'association
        if ($userId !== null && $userId > 0) {
            Notification::send($userId, 'Demande approuvée', 'Votre demande d\'inscription pour « ' . $request['association_name'] . ' » a été approuvée. Vous pouvez maintenant accéder à votre espace association.', 'association_request_approved', [
                'request_id' => (int) $request['id'],
            ]);
        }

        flash('success', 'Demande validée. Le compte président est actif.');
        $this->redirect('admin/association-requests/' . (int) $id);
    }

    /**
     * Refuser une demande.
     */
    public function reject(string $id): never
    {
        $this->requirePermission('association_request.reject');
        $this->csrfCheck();

        $request = Database::one('SELECT * FROM association_requests WHERE id = ?', [(int) $id]);
        if ($request === null) {
            abort(404, 'Demande introuvable.');
        }

        if ($request['status'] !== 'pending') {
            flash('error', 'Cette demande a déjà été traitée.', 'warning');
            $this->redirect('admin/association-requests/' . (int) $id);
        }

        $data = all_input();
        $reason = trim((string) ($data['rejection_reason'] ?? ''));

        if ($reason === '') {
            $this->backWithErrors(['rejection_reason' => 'Le motif du refus est obligatoire.'], $data);
        }

        Database::update('association_requests', [
            'status'           => 'rejected',
            'processed_by'     => Session::userId(),
            'processed_at'     => date('Y-m-d H:i:s'),
            'rejection_reason' => $reason,
        ], 'id = ?', [(int) $id]);

        AuditLog::log('association_request_rejected', 'association_requests', (int) $id, null, [
            'reason' => $reason,
        ]);

        // Notifier le président de l'association
        $linkedUserId = (int) ($request['user_id'] ?? 0);
        if ($linkedUserId > 0) {
            Notification::send($linkedUserId, 'Demande refusée', 'Votre demande d\'inscription pour « ' . $request['association_name'] . ' » a été refusée. Motif : ' . $reason, 'association_request_rejected', [
                'request_id' => (int) $id,
            ]);
        }

        flash('success', 'Demande refusée.');
        $this->redirect('admin/association-requests/' . (int) $id);
    }
}
