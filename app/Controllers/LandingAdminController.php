<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\I18n;
use App\Helpers\LandingService;
use App\Helpers\UploadHelper;
use App\Helpers\Validator;

final class LandingAdminController extends Controller
{
    /**
     * Aperçu en direct de la landing page avec les données CMS actuelles.
     */
    public function preview(): never
    {
        $this->requirePermission('landing.manage');

        $data = LandingService::data();
        $data['previewMode'] = true;
        $data['previewBackUrl'] = url('admin/landing');

        $this->view('landing.index', $data, 'landing');
    }

    /**
     * Retourne le contenu CMS en JSON (pour le dashboard futuriste).
     */
    public function indexJson(): never
    {
        $this->requirePermission('landing.manage');

        $rows = Database::all('SELECT * FROM landing_settings ORDER BY groupe, cle');
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['cle']] = $row['type'] === 'json'
                ? (json_decode((string) $row['valeur'], true) ?? $row['valeur'])
                : $row['valeur'];
        }

        $media = Database::all('SELECT id, titre_fr, image AS path FROM landing_gallery WHERE actif = 1 ORDER BY sort_order');
        $galleryItems = array_map(static function (array $m): array {
            return ['path' => $m['path'], 'titre' => $m['titre_fr']];
        }, $media);

        json_response([
            'success'      => true,
            'settings'     => $settings,
            'faq'          => Database::all('SELECT * FROM landing_faq ORDER BY ordre'),
            'testimonials' => Database::all('SELECT * FROM landing_testimonials ORDER BY sort_order, created_at DESC'),
            'partners'     => Database::all('SELECT * FROM landing_partners ORDER BY ordre'),
            'media'        => $galleryItems,
        ]);
    }

    public function index(): never
    {
        $rows = Database::all('SELECT * FROM landing_settings ORDER BY groupe, cle');
        $settings = [];

        foreach ($rows as $row) {
            $settings[$row['cle']] = $row['type'] === 'json'
                ? (json_decode((string) $row['valeur'], true) ?? $row['valeur'])
                : $row['valeur'];
        }

        $sections = ['actualites', 'apropos', 'fonctionnement', 'anomalies', 'albums', 'temoignages', 'partenaires', 'galerie', 'before_after', 'faq'];

        $this->view('admin.landing.index', [
            'settings'     => $settings,
            'sections'     => $sections,
            'faq'          => Database::all('SELECT * FROM landing_faq ORDER BY ordre'),
            'testimonials' => Database::all('SELECT * FROM landing_testimonials ORDER BY created_at DESC'),
            'partners'     => Database::all('SELECT * FROM landing_partners ORDER BY ordre'),
            'errors'       => $this->errors(),
        ]);
    }

    public function saveSettings(): never
    {
        $data = all_input();
        $cles = (array) ($data['cle'] ?? []);
        $valeurs = (array) ($data['valeur'] ?? []);

        if (count($cles) !== count($valeurs)) {
            $this->backWithErrors(['cle' => 'Champs invalides.'], $data);
        }

        foreach ($cles as $i => $cle) {
            $cle = trim((string) $cle);
            if ($cle === '') {
                continue;
            }
            $valeur = (string) ($valeurs[$i] ?? '');

            if (in_array($cle, ['fonctionnement_etapes', 'sections_order'], true)) {
                $decoded = json_decode($valeur, true);
                if ($decoded === null && $valeur !== '') {
                    $this->backWithErrors(['cle' => "Le champ « $cle » doit être un JSON valide."], $data);
                }
            }

            $existing = Database::one('SELECT type, groupe FROM landing_settings WHERE cle = ?', [$cle]);
            $type = (string) ($existing['type'] ?? 'text');
            $groupe = (string) ($existing['groupe'] ?? 'general');

            Database::run(
                'INSERT INTO landing_settings (cle, valeur, type, groupe) VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE valeur = VALUES(valeur)',
                [$cle, $valeur, $type, $groupe]
            );
        }

        flash('success', 'Contenu enregistré.');
        redirect('admin/landing');
    }

    public function saveFaq(): never
    {
        $data = all_input();
        $validator = Validator::make($data, [
            'question_fr' => 'required|string',
            'reponse_fr'  => 'required|string',
            'question_ar' => 'nullable|string',
            'reponse_ar'  => 'nullable|string',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data);
        }

        Database::insert('landing_faq', [
            'question_fr' => trim((string) $data['question_fr']),
            'question_ar' => trim((string) ($data['question_ar'] ?? '')) ?: null,
            'reponse_fr'  => trim((string) $data['reponse_fr']),
            'reponse_ar'  => trim((string) ($data['reponse_ar'] ?? '')) ?: null,
            'ordre'       => (int) ($data['ordre'] ?? 0),
            'actif'       => isset($data['actif']) ? 1 : 0,
        ]);

        flash('success', 'FAQ ajoutée.');
        redirect('admin/landing');
    }

    public function deleteFaq(string $id): never
    {
        Database::run('DELETE FROM landing_faq WHERE id = ?', [(int) $id]);
        flash('success', 'FAQ supprimée.');
        redirect('admin/landing');
    }

    public function saveTestimonial(): never
    {
        $data = all_input();
        $validator = Validator::make($data, [
            'auteur'    => 'required|string|max:100',
            'texte_fr'  => 'required|string',
            'texte_ar'  => 'nullable|string',
            'role'      => 'nullable|string|max:100',
            'note'      => 'integer',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data);
        }

        Database::insert('landing_testimonials', [
            'auteur'   => trim((string) $data['auteur']),
            'role'     => trim((string) ($data['role'] ?? '')) ?: null,
            'texte_fr' => trim((string) $data['texte_fr']),
            'texte_ar' => trim((string) ($data['texte_ar'] ?? '')) ?: null,
            'note'     => min(5, max(1, (int) ($data['note'] ?? 5))),
            'actif'    => isset($data['actif']) ? 1 : 0,
        ]);

        flash('success', 'Témoignage ajouté.');
        redirect('admin/landing');
    }

    public function deleteTestimonial(string $id): never
    {
        Database::run('DELETE FROM landing_testimonials WHERE id = ?', [(int) $id]);
        flash('success', 'Témoignage supprimé.');
        redirect('admin/landing');
    }

    public function savePartner(): never
    {
        $data = all_input();
        $validator = Validator::make($data, [
            'nom'  => 'required|string|max:100',
            'url'  => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data);
        }

        Database::insert('landing_partners', [
            'nom'   => trim((string) $data['nom']),
            'url'   => trim((string) ($data['url'] ?? '')) ?: null,
            'ordre' => (int) ($data['ordre'] ?? 0),
            'actif' => isset($data['actif']) ? 1 : 0,
        ]);

        flash('success', 'Partenaire ajouté.');
        redirect('admin/landing');
    }

    public function deletePartner(string $id): never
    {
        Database::run('DELETE FROM landing_partners WHERE id = ?', [(int) $id]);
        flash('success', 'Partenaire supprimé.');
        redirect('admin/landing');
    }

    public function saveGallery(): never
    {
        $data = all_input();
        $hasFile = ! empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE;
        $validator = Validator::make($data, [
            'titre_fr' => 'required|string|max:255',
            'image'    => $hasFile ? 'nullable|string|max:255' : 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data);
        }

        $image = trim((string) ($data['image'] ?? ''));
        if ($hasFile) {
            $uploadDir = config('paths.uploads.landing', public_path('uploads/landing'));
            $result = UploadHelper::uploadImage($_FILES['image_file'], $uploadDir, (int) config('security.upload_max', 5242880));
            if (! $result['success']) {
                $this->backWithErrors(['image_file' => $result['error']], $data);
            }
            $image = $result['path'];
        }
        if ($image === '') {
            $this->backWithErrors(['image' => 'Une image est requise (fichier ou URL).'], $data);
        }

        Database::insert('landing_gallery', [
            'titre_fr'   => trim((string) $data['titre_fr']),
            'titre_ar'   => trim((string) ($data['titre_ar'] ?? '')) ?: null,
            'image'      => $image,
            'lien'       => trim((string) ($data['lien'] ?? '')) ?: null,
            'type'       => (string) ($data['type'] ?? 'album'),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'actif'      => isset($data['actif']) ? 1 : 0,
        ]);

        flash('success', 'Élément de galerie ajouté.');
        redirect('admin/landing/gallery');
    }

    public function editGallery(string $id): never
    {
        $item = Database::one('SELECT * FROM landing_gallery WHERE id = ?', [(int) $id]);

        if ($item === null) {
            abort(404, 'Élément introuvable');
        }

        $this->view('admin.landing.gallery_form', ['item' => $item]);
    }

    public function createGallery(): never
    {
        $this->view('admin.landing.gallery_form', ['item' => []]);
    }

    public function updateGallery(string $id): never
    {
        $item = Database::one('SELECT * FROM landing_gallery WHERE id = ?', [(int) $id]);
        if ($item === null) {
            abort(404, 'Élément introuvable');
        }

        $data = all_input();
        $hasFile = ! empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE;
        $validator = Validator::make($data, [
            'titre_fr' => 'required|string|max:255',
            'image'    => $hasFile ? 'nullable|string|max:255' : 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data);
        }

        $image = trim((string) ($data['image'] ?? ''));
        if ($hasFile) {
            $uploadDir = config('paths.uploads.landing', public_path('uploads/landing'));
            $result = UploadHelper::uploadImage($_FILES['image_file'], $uploadDir, (int) config('security.upload_max', 5242880));
            if (! $result['success']) {
                $this->backWithErrors(['image_file' => $result['error']], $data);
            }
            $image = $result['path'];
        }
        if ($image === '') {
            $this->backWithErrors(['image' => 'Une image est requise (fichier ou URL).'], $data);
        }

        Database::update('landing_gallery', [
            'titre_fr'   => trim((string) $data['titre_fr']),
            'titre_ar'   => trim((string) ($data['titre_ar'] ?? '')) ?: null,
            'image'      => $image,
            'lien'       => trim((string) ($data['lien'] ?? '')) ?: null,
            'type'       => (string) ($data['type'] ?? 'album'),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'actif'      => isset($data['actif']) ? 1 : 0,
        ], 'id = ?', [(int) $id]);

        // Supprime l'ancien fichier uploadé (jamais un chemin public partagé)
        if ($hasFile && str_starts_with((string) ($item['image'] ?? ''), '/uploads/')) {
            UploadHelper::delete((string) $item['image']);
        }

        flash('success', 'Élément de galerie mis à jour.');
        redirect('admin/landing/gallery');
    }

    public function deleteGallery(string $id): never
    {
        Database::run('DELETE FROM landing_gallery WHERE id = ?', [(int) $id]);
        flash('success', 'Élément de galerie supprimé.');
        redirect('admin/landing/gallery');
    }

    public function gallery(): never
    {
        $items = Database::all('SELECT * FROM landing_gallery ORDER BY sort_order, titre_fr');

        $this->view('admin.landing.gallery', ['items' => $items]);
    }

    public function saveBeforeAfter(): never
    {
        $data = all_input();
        $validator = Validator::make($data, [
            'titre_fr'      => 'required|string|max:255',
            'image_before'  => 'required|string|max:255',
            'image_after'   => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data);
        }

        Database::insert('landing_before_after', [
            'titre_fr'       => trim((string) $data['titre_fr']),
            'titre_ar'       => trim((string) ($data['titre_ar'] ?? '')) ?: null,
            'image_before'   => trim((string) $data['image_before']),
            'image_after'    => trim((string) $data['image_after']),
            'description_fr' => trim((string) ($data['description_fr'] ?? '')) ?: null,
            'description_ar' => trim((string) ($data['description_ar'] ?? '')) ?: null,
            'statut'         => (string) ($data['statut'] ?? 'publie'),
            'sort_order'     => (int) ($data['sort_order'] ?? 0),
            'actif'          => isset($data['actif']) ? 1 : 0,
        ]);

        flash('success', 'Avant/Après ajouté.');
        redirect('admin/landing/before-after');
    }

    public function editBeforeAfter(string $id): never
    {
        $item = Database::one('SELECT * FROM landing_before_after WHERE id = ?', [(int) $id]);

        if ($item === null) {
            abort(404, 'Élément introuvable');
        }

        $this->view('admin.landing.before_after_form', ['item' => $item]);
    }

    public function createBeforeAfter(): never
    {
        $this->view('admin.landing.before_after_form', ['item' => []]);
    }

    public function updateBeforeAfter(string $id): never
    {
        $data = all_input();
        $validator = Validator::make($data, [
            'titre_fr'     => 'required|string|max:255',
            'image_before' => 'required|string|max:255',
            'image_after'  => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data);
        }

        Database::update('landing_before_after', [
            'titre_fr'       => trim((string) $data['titre_fr']),
            'titre_ar'       => trim((string) ($data['titre_ar'] ?? '')) ?: null,
            'image_before'   => trim((string) $data['image_before']),
            'image_after'    => trim((string) $data['image_after']),
            'description_fr' => trim((string) ($data['description_fr'] ?? '')) ?: null,
            'description_ar' => trim((string) ($data['description_ar'] ?? '')) ?: null,
            'statut'         => (string) ($data['statut'] ?? 'publie'),
            'sort_order'     => (int) ($data['sort_order'] ?? 0),
            'actif'          => isset($data['actif']) ? 1 : 0,
        ], 'id = ?', [(int) $id]);

        flash('success', 'Avant/Après mis à jour.');
        redirect('admin/landing/before-after');
    }

    public function deleteBeforeAfter(string $id): never
    {
        Database::run('DELETE FROM landing_before_after WHERE id = ?', [(int) $id]);
        flash('success', 'Avant/Après supprimé.');
        redirect('admin/landing/before-after');
    }

    public function beforeAfter(): never
    {
        $items = Database::all('SELECT * FROM landing_before_after ORDER BY sort_order, titre_fr');

        $this->view('admin.landing.before_after', ['items' => $items]);
    }


    public function saveOrdre(): never
    {
        $data = all_input();
        $ordre = (string) ($data['ordre'] ?? '');

        if (json_decode($ordre, true) === null) {
            $this->backWithErrors(['ordre' => 'Ordre JSON invalide.'], $data);
        }

        Database::run(
            'INSERT INTO landing_settings (cle, valeur, type, groupe) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE valeur = VALUES(valeur)',
            ['sections_order', $ordre, 'json', 'general']
        );

        foreach ((array) ($data['visibles'] ?? []) as $section) {
            Database::run(
                'INSERT INTO landing_settings (cle, valeur, type, groupe) VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE valeur = VALUES(valeur)',
                ['section_' . $section . '_visible', '1', 'text', 'general']
            );
        }

        $visibles = array_map('strval', (array) ($data['visibles'] ?? []));
        foreach ((array) ($data['all_sections'] ?? []) as $section) {
            if (in_array((string) $section, $visibles, true)) {
                continue;
            }
            Database::run(
                'INSERT INTO landing_settings (cle, valeur, type, groupe) VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE valeur = VALUES(valeur)',
                ['section_' . $section . '_visible', '0', 'text', 'general']
            );
        }

        flash('success', 'Ordre des sections mis à jour.');
        redirect('admin/landing');
    }

    public function setLocale(string $locale): never
    {
        I18n::set($locale);
        redirect($_SERVER['HTTP_REFERER'] ?? '/');
    }
}
