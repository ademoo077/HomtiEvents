<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuditLog;
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

        // Handle section visibility from the main form
        $allSections = (array) ($data['all_sections'] ?? []);
        $visibles = (array) ($data['visibles'] ?? []);
        $visiblesStr = array_map('strval', $visibles);
        foreach ($allSections as $section) {
            $section = (string) $section;
            $visible = in_array($section, $visiblesStr, true) ? '1' : '0';
            Database::run(
                'INSERT INTO landing_settings (cle, valeur, type, groupe) VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE valeur = VALUES(valeur)',
                ['section_' . $section . '_visible', $visible, 'text', 'general']
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

    public function updateFaq(string $id): never
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
        Database::run('UPDATE landing_faq SET question_fr = ?, question_ar = ?, reponse_fr = ?, reponse_ar = ?, ordre = ?, actif = ? WHERE id = ?', [
            trim((string) $data['question_fr']),
            trim((string) ($data['question_ar'] ?? '')) ?: null,
            trim((string) $data['reponse_fr']),
            trim((string) ($data['reponse_ar'] ?? '')) ?: null,
            (int) ($data['ordre'] ?? 0),
            isset($data['actif']) ? 1 : 0,
            (int) $id,
        ]);
        flash('success', 'FAQ mise à jour.');
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

    public function updateTestimonial(string $id): never
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
        Database::run('UPDATE landing_testimonials SET auteur = ?, role = ?, texte_fr = ?, texte_ar = ?, note = ?, actif = ? WHERE id = ?', [
            trim((string) $data['auteur']),
            trim((string) ($data['role'] ?? '')) ?: null,
            trim((string) $data['texte_fr']),
            trim((string) ($data['texte_ar'] ?? '')) ?: null,
            min(5, max(1, (int) ($data['note'] ?? 5))),
            isset($data['actif']) ? 1 : 0,
            (int) $id,
        ]);
        flash('success', 'Témoignage mis à jour.');
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

        $partnerData = [
            'nom'   => trim((string) $data['nom']),
            'url'   => trim((string) ($data['url'] ?? '')) ?: null,
            'ordre' => (int) ($data['ordre'] ?? 0),
            'actif' => isset($data['actif']) ? 1 : 0,
        ];

        if (!empty($_FILES['logo_file']['tmp_name'])) {
            // RNSI §9 — SVG bloqué (XSS stored) — OWASP A08
            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['logo_file']['tmp_name']);
            finfo_close($finfo);
            if (in_array($mime, $allowed, true)) {
                $ext = match ($mime) {
                    'image/jpeg'     => '.jpg',
                    'image/png'      => '.png',
                    'image/webp'     => '.webp',
                    default          => '.jpg',
                };
                $name = 'partner_' . time() . '_' . bin2hex(random_bytes(4)) . $ext;
                $dest = UPLOAD_DIR . '/landing/' . $name;
                if (!is_dir(UPLOAD_DIR . '/landing')) {
                    mkdir(UPLOAD_DIR . '/landing', 0755, true);
                }
                if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $dest)) {
                    $partnerData['logo'] = '/uploads/landing/' . $name;
                }
            }
        }

        Database::insert('landing_partners', $partnerData);

        flash('success', 'Partenaire ajouté.');
        redirect('admin/landing');
    }

    public function deletePartner(string $id): never
    {
        Database::run('DELETE FROM landing_partners WHERE id = ?', [(int) $id]);
        flash('success', 'Partenaire supprimé.');
        redirect('admin/landing');
    }

    public function updatePartner(string $id): never
    {
        $data = all_input();
        $validator = Validator::make($data, [
            'nom'  => 'required|string|max:100',
            'url'  => 'nullable|string|max:255',
        ]);
        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data);
        }

        $update = [
            'nom'   => trim((string) $data['nom']),
            'url'   => trim((string) ($data['url'] ?? '')) ?: null,
            'ordre' => (int) ($data['ordre'] ?? 0),
            'actif' => isset($data['actif']) ? 1 : 0,
        ];

        if (!empty($_FILES['logo_file']['tmp_name'])) {
            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['logo_file']['tmp_name']);
            finfo_close($finfo);
            if (in_array($mime, $allowed, true)) {
                $ext = match ($mime) {
                    'image/jpeg'     => '.jpg',
                    'image/png'      => '.png',
                    'image/webp'     => '.webp',
                    default          => '.jpg',
                };
                $name = 'partner_' . time() . '_' . bin2hex(random_bytes(4)) . $ext;
                $dest = UPLOAD_DIR . '/landing/' . $name;
                if (!is_dir(UPLOAD_DIR . '/landing')) {
                    mkdir(UPLOAD_DIR . '/landing', 0755, true);
                }
                if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $dest)) {
                    $update['logo'] = '/uploads/landing/' . $name;
                }
            }
        } elseif (!empty($data['logo_url'])) {
            $update['logo'] = trim((string) $data['logo_url']);
        }

        $sets = [];
        $params = [];
        foreach ($update as $k => $v) {
            $sets[] = "$k = ?";
            $params[] = $v;
        }
        $params[] = (int) $id;
        Database::run('UPDATE landing_partners SET ' . implode(', ', $sets) . ' WHERE id = ?', $params);

        flash('success', 'Partenaire mis à jour.');
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
        $hasBefore = ! empty($_FILES['image_before_file']['name']) && $_FILES['image_before_file']['error'] !== UPLOAD_ERR_NO_FILE;
        $hasAfter  = ! empty($_FILES['image_after_file']['name']) && $_FILES['image_after_file']['error'] !== UPLOAD_ERR_NO_FILE;

        $validator = Validator::make($data, [
            'titre_fr'     => 'required|string|max:255',
            'image_before' => ($hasBefore ? 'nullable' : 'required') . '|string|max:255',
            'image_after'  => ($hasAfter ? 'nullable' : 'required') . '|string|max:255',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data);
        }

        $imageBefore = trim((string) ($data['image_before'] ?? ''));
        $imageAfter  = trim((string) ($data['image_after'] ?? ''));

        $uploadDir = config('paths.uploads.landing', public_path('uploads/landing'));
        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if ($hasBefore) {
            $result = UploadHelper::uploadImage($_FILES['image_before_file'], $uploadDir, (int) config('security.upload_max', 5242880));
            if (! $result['success']) {
                $this->backWithErrors(['image_before_file' => $result['error']], $data);
            }
            $imageBefore = $result['path'];
        }
        if ($hasAfter) {
            $result = UploadHelper::uploadImage($_FILES['image_after_file'], $uploadDir, (int) config('security.upload_max', 5242880));
            if (! $result['success']) {
                $this->backWithErrors(['image_after_file' => $result['error']], $data);
            }
            $imageAfter = $result['path'];
        }

        if ($imageBefore === '' || $imageAfter === '') {
            $this->backWithErrors(['image' => 'Les deux images sont requises (fichier ou URL).'], $data);
        }

        Database::insert('landing_before_after', [
            'titre_fr'       => trim((string) $data['titre_fr']),
            'titre_ar'       => trim((string) ($data['titre_ar'] ?? '')) ?: null,
            'image_before'   => $imageBefore,
            'image_after'    => $imageAfter,
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
        $hasBefore = ! empty($_FILES['image_before_file']['name']) && $_FILES['image_before_file']['error'] !== UPLOAD_ERR_NO_FILE;
        $hasAfter  = ! empty($_FILES['image_after_file']['name']) && $_FILES['image_after_file']['error'] !== UPLOAD_ERR_NO_FILE;

        $validator = Validator::make($data, [
            'titre_fr'     => 'required|string|max:255',
            'image_before' => ($hasBefore ? 'nullable' : 'required') . '|string|max:255',
            'image_after'  => ($hasAfter ? 'nullable' : 'required') . '|string|max:255',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data);
        }

        $imageBefore = trim((string) ($data['image_before'] ?? ''));
        $imageAfter  = trim((string) ($data['image_after'] ?? ''));

        $uploadDir = config('paths.uploads.landing', public_path('uploads/landing'));
        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if ($hasBefore) {
            $result = UploadHelper::uploadImage($_FILES['image_before_file'], $uploadDir, (int) config('security.upload_max', 5242880));
            if (! $result['success']) {
                $this->backWithErrors(['image_before_file' => $result['error']], $data);
            }
            $imageBefore = $result['path'];
        }
        if ($hasAfter) {
            $result = UploadHelper::uploadImage($_FILES['image_after_file'], $uploadDir, (int) config('security.upload_max', 5242880));
            if (! $result['success']) {
                $this->backWithErrors(['image_after_file' => $result['error']], $data);
            }
            $imageAfter = $result['path'];
        }

        if ($imageBefore === '' || $imageAfter === '') {
            $this->backWithErrors(['image' => 'Les deux images sont requises (fichier ou URL).'], $data);
        }

        Database::update('landing_before_after', [
            'titre_fr'       => trim((string) $data['titre_fr']),
            'titre_ar'       => trim((string) ($data['titre_ar'] ?? '')) ?: null,
            'image_before'   => $imageBefore,
            'image_after'    => $imageAfter,
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

        // Derive order from all_sections if ordre not explicitly provided
        $allSections = (array) ($data['all_sections'] ?? []);
        $visibles = (array) ($data['visibles'] ?? []);

        if (isset($data['ordre']) && $data['ordre'] !== '') {
            $ordre = (string) $data['ordre'];
        } else {
            $ordre = json_encode(array_map('strval', $allSections), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (json_decode($ordre, true) === null) {
            $this->backWithErrors(['ordre' => 'Ordre JSON invalide.'], $data);
        }

        Database::run(
            'INSERT INTO landing_settings (cle, valeur, type, groupe) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE valeur = VALUES(valeur)',
            ['sections_order', $ordre, 'json', 'general']
        );

        // Mark visible sections
        $visiblesStr = array_map('strval', $visibles);
        foreach ($visiblesStr as $section) {
            Database::run(
                'INSERT INTO landing_settings (cle, valeur, type, groupe) VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE valeur = VALUES(valeur)',
                ['section_' . $section . '_visible', '1', 'text', 'general']
            );
        }

        // Mark hidden sections
        foreach ($allSections as $section) {
            $section = (string) $section;
            if (in_array($section, $visiblesStr, true)) {
                continue;
            }
            Database::run(
                'INSERT INTO landing_settings (cle, valeur, type, groupe) VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE valeur = VALUES(valeur)',
                ['section_' . $section . '_visible', '0', 'text', 'general']
            );
        }

        flash('success', 'Ordre et visibilité des sections mis à jour.');
        redirect('admin/landing');
    }

    /**
     * Réordonne les éléments d'une liste (FAQ, témoignages, partenaires, galerie, avant/après)
     * via l'identifiant fourni dans l'ordre souhaité.
     */
    public function reorderItems(): never
    {
        $data = all_input();
        $type = (string) ($data['type'] ?? '');
        $ids  = array_values(array_filter(array_map('intval', (array) ($data['ids'] ?? []))));

        $map = [
            'faq'          => ['table' => 'landing_faq',          'col' => 'ordre'],
            'partenaires'  => ['table' => 'landing_partners',     'col' => 'ordre'],
            'temoignages'  => ['table' => 'landing_testimonials', 'col' => 'sort_order'],
            'gallery'      => ['table' => 'landing_gallery',      'col' => 'sort_order'],
            'before_after' => ['table' => 'landing_before_after', 'col' => 'sort_order'],
        ];

        if (! isset($map[$type]) || $ids === []) {
            json_response(['success' => false, 'error' => 'Ordre invalide.'], 422);
        }

        $table = $map[$type]['table'];
        $col   = $map[$type]['col'];

        foreach ($ids as $i => $id) {
            Database::run("UPDATE {$table} SET {$col} = ? WHERE id = ?", [$i + 1, $id]);
        }

        json_response(['success' => true, 'type' => $type, 'ids' => $ids]);
    }

    public function setLocale(string $locale): never
    {
        I18n::set($locale);
        redirect($_SERVER['HTTP_REFERER'] ?? '/');
    }

    /**
     * Sauvegarde les paramètres de thème couleur depuis le CMS.
     * Supporte le changement rapide de thème prédéfini ou l'édition manuelle.
     */
    public function saveTheme(): never
    {
        $data = all_input();

        // ── Preset thème rapide ──
        if (! empty($data['preset']) && $data['preset'] !== 'custom') {
            $presets = json_decode((string) settings('theme_presets', '[]'), true);
            $found = null;

            foreach ((array) $presets as $p) {
                if (($p['name'] ?? '') === $data['preset']) {
                    $found = $p;
                    break;
                }
            }

            if ($found !== null && isset($found['colors'])) {
                foreach ($found['colors'] as $subKey => $colorValue) {
                    $cle = 'theme_' . $subKey;
                    Database::run(
                        'INSERT INTO landing_settings (cle, valeur, type, groupe) VALUES (?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE valeur = VALUES(valeur)',
                        [$cle, (string) $colorValue, 'color', 'theme']
                    );
                }
                Database::run(
                    'INSERT INTO landing_settings (cle, valeur, type, groupe) VALUES (?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE valeur = VALUES(valeur)',
                    ['theme_name', (string) $data['preset'], 'text', 'theme']
                );

                flash('success', 'Thème appliqué : ' . e($found['label'] ?? $data['preset']));
                redirect('admin/landing');
            }
        }

        // ── Édition manuelle des couleurs ──
        $colorKeys = [
            'theme_primary',
            'theme_primary_hover',
            'theme_secondary',
            'theme_tertiary',
            'theme_accent_glow',
            'theme_hero_gradient_1',
            'theme_hero_gradient_2',
            'theme_hero_gradient_3',
            'theme_navbar_bg',
            'theme_navbar_bg_scrolled',
            'theme_footer_bg',
            'theme_footer_text',
        ];

        foreach ($colorKeys as $cle) {
            if (array_key_exists($cle, $data)) {
                $value = trim((string) $data[$cle]);
                Database::run(
                    'INSERT INTO landing_settings (cle, valeur, type, groupe) VALUES (?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE valeur = VALUES(valeur)',
                    [$cle, $value, 'color', 'theme']
                );
            }
        }

        // Thème custom
        Database::run(
            'INSERT INTO landing_settings (cle, valeur, type, groupe) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE valeur = VALUES(valeur)',
            ['theme_name', 'custom', 'text', 'theme']
        );

        flash('success', 'Thème personnalisé enregistré.');
        redirect('admin/landing');
    }

    // ── Actualités & événements à venir ──────────────────────────

    public function news(): never
    {
        $this->requirePermission('landing.manage');

        $items = Database::all(
            'SELECT * FROM landing_news WHERE deleted_at IS NULL ORDER BY date_event DESC, sort_order ASC'
        );

        $this->view('admin.landing.news', [
            'items'  => $items,
            'errors' => $this->errors(),
        ]);
    }

    public function newsCreate(): never
    {
        $this->requirePermission('landing.manage');

        $this->view('admin.landing.news_form', [
            'item'       => null,
            'errors'     => $this->errors(),
            'evenements' => $this->evenementsSelect(),
        ]);
    }

    public function newsStore(): never
    {
        $this->requirePermission('landing.manage');

        $data = all_input();
        $hasFile = !empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE;

        $validator = Validator::make($data, [
            'titre_fr'   => 'required|string|max:255',
            'titre_ar'   => 'nullable|string|max:255',
            'type'       => 'required|in:actualite,evenement',
            'statut'     => 'required|in:brouillon,publie',
            'date_event' => 'nullable|date',
            'lieu'       => 'nullable|string|max:255',
            'lieu_ar'    => 'nullable|string|max:255',
            'description_fr' => 'nullable|string',
            'description_ar' => 'nullable|string',
            'url_externe'    => 'nullable|url|max:500',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data);
        }

        $evenementId = $this->validerEvenementLiaison($data);

        $image = trim((string) ($data['image'] ?? ''));
        if ($hasFile) {
            $uploadDir = config('paths.uploads.landing', public_path('uploads/landing'));
            $result = UploadHelper::uploadImage($_FILES['image_file'], $uploadDir, (int) config('security.upload_max', 5242880));
            if (!$result['success']) {
                $this->backWithErrors(['image_file' => $result['error']], $data);
            }
            $image = $result['path'];
        }

        $statut = (string) $data['statut'] === 'publie' ? 'publie' : 'brouillon';
        $nouvelles = $this->newsValeurs($data, $image, $statut, $evenementId);

        $id = Database::insert('landing_news', $nouvelles);

        AuditLog::log('actualite_created', 'landing_news', $id, null, $nouvelles);

        flash('success', 'Élément ajouté avec succès.');
        redirect('admin/landing/news');
    }

    public function newsEdit(string $id): never
    {
        $this->requirePermission('landing.manage');

        $item = Database::one('SELECT * FROM landing_news WHERE id = ?', [(int) $id]);
        if ($item === null) {
            abort(404, 'Élément introuvable');
        }

        $this->view('admin.landing.news_form', [
            'item'       => $item,
            'errors'     => $this->errors(),
            'evenements' => $this->evenementsSelect(),
        ]);
    }

    public function newsUpdate(string $id): never
    {
        $this->requirePermission('landing.manage');

        $item = Database::one('SELECT * FROM landing_news WHERE id = ?', [(int) $id]);
        if ($item === null) {
            abort(404, 'Élément introuvable');
        }

        $data = all_input();
        $hasFile = !empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE;

        $validator = Validator::make($data, [
            'titre_fr'   => 'required|string|max:255',
            'titre_ar'   => 'nullable|string|max:255',
            'type'       => 'required|in:actualite,evenement',
            'statut'     => 'required|in:brouillon,publie',
            'date_event' => 'nullable|date',
            'lieu'       => 'nullable|string|max:255',
            'lieu_ar'    => 'nullable|string|max:255',
            'description_fr' => 'nullable|string',
            'description_ar' => 'nullable|string',
            'url_externe'    => 'nullable|url|max:500',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data);
        }

        $evenementId = $this->validerEvenementLiaison($data, (int) $id);

        $image = trim((string) ($data['image'] ?? $item['image'] ?? ''));
        if ($hasFile) {
            $uploadDir = config('paths.uploads.landing', public_path('uploads/landing'));
            $result = UploadHelper::uploadImage($_FILES['image_file'], $uploadDir, (int) config('security.upload_max', 5242880));
            if (!$result['success']) {
                $this->backWithErrors(['image_file' => $result['error']], $data);
            }
            $image = $result['path'];
            // Supprime l'ancien fichier
            if (!empty($item['image']) && str_starts_with((string) $item['image'], '/uploads/')) {
                UploadHelper::delete((string) $item['image']);
            }
        }

        $statut = (string) $data['statut'] === 'publie' ? 'publie' : 'brouillon';
        $nouvelles = $this->newsValeurs($data, $image, $statut, $evenementId);
        $anciennes = $this->newsValeurs($item, (string) ($item['image'] ?? ''), (string) ($item['statut'] ?? 'publie'), $item['evenement_id'] !== null ? (int) $item['evenement_id'] : null);

        Database::update('landing_news', $nouvelles, 'id = ?', [(int) $id]);

        AuditLog::log('actualite_updated', 'landing_news', (int) $id, $anciennes, $nouvelles);

        flash('success', 'Élément mis à jour.');
        redirect('admin/landing/news');
    }

    public function newsDelete(string $id): never
    {
        $this->requirePermission('landing.manage');

        $item = Database::one('SELECT * FROM landing_news WHERE id = ?', [(int) $id]);
        if ($item !== null) {
            $anciennes = $this->newsValeurs($item, (string) ($item['image'] ?? ''), (string) ($item['statut'] ?? 'publie'), $item['evenement_id'] !== null ? (int) $item['evenement_id'] : null);

            // Suppression douce : l'élément disparaît du site public sans être détruit.
            Database::update('landing_news', [
                'deleted_at' => date('Y-m-d H:i:s'),
                'actif'      => 0,
                'statut'     => 'brouillon',
            ], 'id = ?', [(int) $id]);

            AuditLog::log('actualite_deleted', 'landing_news', (int) $id, $anciennes, null);
        }

        flash('success', 'Élément supprimé.');
        redirect('admin/landing/news');
    }

    /**
     * Toggle rapide publie/brouillon pour une actualité (AJAX ou redirect).
     */
    public function newsToggle(string $id): never
    {
        $this->requirePermission('landing.manage');

        $item = Database::one('SELECT statut, actif FROM landing_news WHERE id = ? AND deleted_at IS NULL', [(int) $id]);
        if ($item === null) {
            abort(404, 'Élément introuvable.');
        }

        $newStatut = ($item['statut'] ?? 'publie') === 'publie' ? 'brouillon' : 'publie';
        $newActif = $newStatut === 'publie' ? 1 : 0;

        Database::update('landing_news', [
            'statut' => $newStatut,
            'actif'  => $newActif,
        ], 'id = ?', [(int) $id]);

        AuditLog::log('actualite_toggle', 'landing_news', (int) $id, ['statut' => $item['statut']], ['statut' => $newStatut]);

        if (headers_sent()) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => true, 'statut' => $newStatut, 'actif' => $newActif]);
            exit;
        }

        flash('success', $newStatut === 'publie' ? 'Élément publié.' : 'Élément mis en brouillon.');
        redirect('admin/landing/news');
    }

    /**
     * Options du sélecteur « lier un événement structuré ».
     *
     * @return array<int, array<string, mixed>>
     */
    private function evenementsSelect(): array
    {
        return Database::all(
            "SELECT e.id, e.adresse, e.date_evenement, e.heure, c.nom AS commune_nom
             FROM evenements e
             LEFT JOIN commune c ON c.id = e.commune_id
             WHERE e.statut IN ('PROGRAMME', 'QR_GENERE') AND e.deleted_at IS NULL AND e.date_evenement >= CURDATE()
             ORDER BY e.date_evenement ASC, e.heure ASC
             LIMIT 100"
        );
    }

    /**
     * Valide le champ evenement_id (facultatif, doit exister, type événement,
     * et ne pas déjà être lié à un autre élément — contrainte UNIQUE).
     */
    private function validerEvenementLiaison(array $data, ?int $excludeId = null): ?int
    {
        if ((string) $data['type'] !== 'evenement') {
            return null;
        }

        $evenementId = (int) ($data['evenement_id'] ?? 0);
        if ($evenementId <= 0) {
            return null;
        }

        $exists = Database::value('SELECT COUNT(*) FROM evenements WHERE id = ?', [$evenementId]);
        if ((int) $exists !== 1) {
            $this->backWithErrors(['evenement_id' => "L'événement lié est introuvable."], $data);
        }

        $dejaLie = Database::value(
            'SELECT COUNT(*) FROM landing_news WHERE evenement_id = ? AND id <> ?',
            [$evenementId, (int) $excludeId]
        );
        if ((int) $dejaLie > 0) {
            $this->backWithErrors(['evenement_id' => 'Cet événement est déjà lié à un autre élément.'], $data);
        }

        return $evenementId;
    }

    /**
     * Normalise une ligne landing_news en valeurs persistées.
     *
     * @return array<string, mixed>
     */
    private function newsValeurs(array $data, string $image, string $statut, ?int $evenementId): array
    {
        return [
            'titre_fr'       => trim((string) $data['titre_fr']),
            'titre_ar'       => trim((string) ($data['titre_ar'] ?? '')) ?: null,
            'description_fr' => trim((string) ($data['description_fr'] ?? '')) ?: null,
            'description_ar' => trim((string) ($data['description_ar'] ?? '')) ?: null,
            'image'          => $image ?: null,
            'date_event'     => !empty($data['date_event']) ? $data['date_event'] : null,
            'lieu'           => trim((string) ($data['lieu'] ?? '')) ?: null,
            'lieu_ar'        => trim((string) ($data['lieu_ar'] ?? '')) ?: null,
            'type'           => (string) $data['type'],
            'evenement_id'   => $evenementId,
            'url_externe'    => trim((string) ($data['url_externe'] ?? '')) ?: null,
            'actif'          => $statut === 'publie' ? 1 : 0,
            'statut'         => $statut,
            'sort_order'     => (int) ($data['sort_order'] ?? 0),
        ];
    }
}
