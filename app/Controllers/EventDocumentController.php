<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuditLog;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Helpers\UploadHelper;

/**
 * Pièces jointes du dossier événement (documents).
 * Vision "dossier événement" : permet à la Wilaya d'attacher, consulter et
 * supprimer des documents liés à un événement.
 */
class EventDocumentController extends Controller
{
    /**
     * GET /wilaya/evenements/{id}/documents  → liste (fragment HTML pour onglet)
     */
    public function index(string $evenementId): never
    {
        $this->requirePermission('evenement.view_all');
        $id = (int) $evenementId;

        $evenement = Database::one('SELECT id FROM evenements WHERE id = ?', [$id]);
        if ($evenement === null) {
            abort(404);
        }

        $documents = Database::all(
            'SELECT d.*, u.nom AS user_nom, u.prenom AS user_prenom
             FROM event_documents d
             LEFT JOIN users u ON u.id = d.uploaded_by
             WHERE d.evenement_id = ?
             ORDER BY d.created_at DESC, d.id DESC',
            [$id]
        );

        echo view('partials.event_documents_list', [
            'documents' => $documents,
            'evenement' => $evenement,
        ]);
        exit;
    }

    /**
     * POST /wilaya/evenements/{id}/documents
     */
    public function store(string $evenementId): never
    {
        $this->requirePermission('evenement.edit');
        $id = (int) $evenementId;

        $evenement = Database::one('SELECT id FROM evenements WHERE id = ?', [$id]);
        if ($evenement === null) {
            abort(404);
        }

        $files = $_FILES['documents'] ?? null;
        if ($files === null || ($files['error'][0] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            flash('error', __('documents.no_file'), 'danger');
            $this->redirect('wilaya/evenements/' . $id . '#tab-documents');
        }

        $uploadDir   = config('paths.uploads.documents', public_path('uploads/documents'));
        $maxSize     = (int) config('security.upload_max', 5242880);
        $result      = UploadHelper::uploadMultiple($files, $uploadDir, $maxSize);

        $inserted = 0;
        foreach ($result['successes'] as $path) {
            $idx = array_search($path, $result['successes'], true);
            $origName = $files['name'][$idx] ?? 'document';
            Database::insert('event_documents', [
                'evenement_id' => $id,
                'nom'          => mb_substr((string) $origName, 0, 191),
                'fichier'      => $path,
                'type_mime'    => $files['type'][$idx] ?? null,
                'taille'       => isset($files['size'][$idx]) ? (int) $files['size'][$idx] : null,
                'uploaded_by'  => Session::userId(),
            ]);
            $inserted++;
        }

        AuditLog::log('document_uploaded', 'evenements', $id, null, ['count' => $inserted]);

        if ($inserted > 0) {
            flash('success', __('documents.added', ['count' => $inserted]));
        }
        if (! empty($result['errors'])) {
            flash('error', count($result['errors']) . ' fichier(s) rejeté(s).', 'warning');
        }

        $this->redirect('wilaya/evenements/' . $id . '#tab-documents');
    }

    /**
     * DELETE /wilaya/evenements/documents/{id}
     */
    public function destroy(string $documentId): never
    {
        $this->requirePermission('evenement.edit');

        $doc = Database::one('SELECT * FROM event_documents WHERE id = ?', [(int) $documentId]);
        if ($doc === null) {
            abort(404);
        }

        if (! empty($doc['fichier'])) {
            UploadHelper::delete($doc['fichier']);
        }
        Database::delete('event_documents', 'id = ?', [(int) $documentId]);

        AuditLog::log('document_deleted', 'evenements', (int) $doc['evenement_id'], ['fichier' => $doc['fichier']]);

        flash('success', __('documents.deleted'));
        $this->redirect('wilaya/evenements/' . (int) $doc['evenement_id'] . '#tab-documents');
    }

    /**
     * GET /wilaya/evenements/documents/{id}/download — téléchargement sécurisé.
     */
    public function download(string $documentId): never
    {
        $this->requirePermission('evenement.view_all');

        $doc = Database::one('SELECT * FROM event_documents WHERE id = ?', [(int) $documentId]);
        if ($doc === null) {
            abort(404);
        }

        $publicPath = rtrim((string) config('paths.public', ''), '/');
        $fullPath   = $publicPath . '/' . ltrim((string) $doc['fichier'], '/');
        if (! is_file($fullPath)) {
            abort(404, 'Fichier introuvable.');
        }

        $nom = basename((string) $doc['nom']);
        $mime = $doc['type_mime'] ?: 'application/octet-stream';

        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $nom) . '"');
        header('Content-Length: ' . (string) filesize($fullPath));
        header('X-Content-Type-Options: nosniff');
        readfile($fullPath);
        exit;
    }
}
