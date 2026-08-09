<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuditLog;
use App\Helpers\Database;
use App\Helpers\Notification;
use App\Helpers\Session;
use App\Helpers\UploadHelper;
use App\Helpers\Validator;

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
     * Formulaire de modification d'une demande.
     */
    public function edit(string $id): never
    {
        $this->requirePermission('association_request.edit');

        $request = Database::one('SELECT * FROM association_requests WHERE id = ?', [(int) $id]);
        if ($request === null) {
            abort(404, 'Demande introuvable.');
        }

        $this->view('wilaya.association-requests.edit', [
            'request' => $request,
            'errors'  => $this->errors(),
            'old'     => $_SESSION['_old'] ?? [],
        ]);
    }

    /**
     * Enregistre les modifications d'une demande.
     */
    public function update(string $id): never
    {
        $this->requirePermission('association_request.edit');
        $this->csrfCheck();

        $request = Database::one('SELECT * FROM association_requests WHERE id = ?', [(int) $id]);
        if ($request === null) {
            abort(404, 'Demande introuvable.');
        }

        $data = all_input();

        // Les champs nullable vides doivent être null (le validateur gère null, pas '')
        foreach (['approval_number', 'activity_domain', 'description', 'address', 'commune', 'wilaya', 'website', 'president_birthdate', 'president_phone', 'president_email', 'president_address', 'president_id_type', 'president_id_number'] as $champ) {
            $data[$champ] = trim((string) ($data[$champ] ?? '')) ?: null;
        }
        $data['email'] = mb_strtolower(trim((string) ($data['email'] ?? ''))) ?: null;
        $data['phone'] = trim((string) ($data['phone'] ?? '')) ?: null;

        $validator = Validator::make($data, [
            'association_name'    => 'required|string|max:150',
            'approval_number'     => 'nullable|string|max:50',
            'activity_domain'     => 'nullable|string|max:100',
            'description'         => 'nullable|string|max:2000',
            'address'             => 'nullable|string|max:255',
            'commune'             => 'nullable|string|max:100',
            'wilaya'              => 'nullable|string|max:100',
            'phone'               => 'nullable|string|max:20',
            'email'               => 'nullable|email|max:100',
            'website'             => 'nullable|string|max:255',
            'president_lastname'  => 'required|string|max:100',
            'president_firstname' => 'required|string|max:100',
            'president_birthdate' => 'nullable|date',
            'president_phone'     => 'nullable|string|max:20',
            'president_email'     => 'nullable|email|max:100',
            'president_address'   => 'nullable|string|max:255',
            'president_id_type'   => 'nullable|string|max:50',
            'president_id_number' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data);
        }

        $approvalFile = $request['approval_file'];
        if (! empty($_FILES['approval_file']['name']) && $_FILES['approval_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadDir = config('paths.uploads.agrements', public_path('uploads/agrements'));
            $result    = UploadHelper::uploadDocument($_FILES['approval_file'], $uploadDir, (int) config('security.upload_max', 5242880));
            if (! $result['success']) {
                $this->backWithErrors(['approval_file' => $result['error']], $data);
            }
            $approvalFile = $result['path'];
        }

        Database::update('association_requests', [
            'association_name'    => trim((string) $data['association_name']),
            'approval_number'     => $data['approval_number'],
            'activity_domain'     => $data['activity_domain'],
            'description'         => $data['description'],
            'address'             => $data['address'],
            'commune'             => $data['commune'],
            'wilaya'              => $data['wilaya'],
            'phone'               => $data['phone'],
            'email'               => $data['email'],
            'website'             => $data['website'],
            'president_lastname'  => trim((string) $data['president_lastname']),
            'president_firstname' => trim((string) $data['president_firstname']),
            'president_birthdate' => $data['president_birthdate'],
            'president_phone'     => $data['president_phone'],
            'president_email'     => $data['president_email'],
            'president_address'   => $data['president_address'],
            'president_id_type'   => $data['president_id_type'],
            'president_id_number' => $data['president_id_number'],
            'approval_file'       => $approvalFile,
        ], 'id = ?', [(int) $id]);

        // Supprime l'ancien fichier agrément si remplacé
        if ($approvalFile !== $request['approval_file'] && str_starts_with((string) ($request['approval_file'] ?? ''), '/uploads/')) {
            UploadHelper::delete((string) $request['approval_file']);
        }

        AuditLog::log('association_request_updated', 'association_requests', (int) $id, null, [
            'association_name' => trim((string) $data['association_name']),
        ]);

        flash('success', 'Demande mise à jour.');
        $this->redirect('admin/association-requests/' . (int) $id);
    }

    /**
     * Suppression définitive d'une demande (fichiers joints compris).
     */
    public function destroy(string $id): never
    {
        $this->requirePermission('association_request.delete');
        $this->csrfCheck();

        $request = Database::one('SELECT * FROM association_requests WHERE id = ?', [(int) $id]);
        if ($request === null) {
            abort(404, 'Demande introuvable.');
        }

        foreach (['approval_file', 'identity_file'] as $champ) {
            $path = (string) ($request[$champ] ?? '');
            if ($path !== '' && str_starts_with($path, '/uploads/')) {
                UploadHelper::delete($path);
            }
        }

        Database::delete('association_requests', 'id = ?', [(int) $id]);

        AuditLog::log('association_request_deleted', 'association_requests', (int) $id, null, [
            'association_name' => $request['association_name'],
            'status'           => $request['status'],
        ]);

        flash('success', 'Demande supprimée.');
        $this->redirect('admin/association-requests');
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
