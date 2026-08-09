USE wilaya_harmonia;
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- =====================================================
-- Étendre la table `albums`
-- =====================================================
ALTER TABLE albums
    ADD COLUMN description_fr TEXT NULL AFTER recit,
    ADD COLUMN description_ar TEXT NULL AFTER description_fr,
    ADD COLUMN couverture VARCHAR(255) NULL AFTER description_ar,
    ADD COLUMN statut ENUM('brouillon', 'publie', 'masque') NOT NULL DEFAULT 'brouillon' AFTER couverture,
    ADD COLUMN date_publication TIMESTAMP NULL AFTER statut,
    ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER date_creation,
    ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- Migrer les données existantes : `date_creation` -> `created_at`
UPDATE albums SET created_at = date_creation WHERE created_at IS NULL;

-- Index pour les requêtes Landing Page
CREATE INDEX idx_albums_statut ON albums (statut);
CREATE INDEX idx_albums_evenement ON albums (evenement_id);

-- =====================================================
-- Étendre la table `photos`
-- =====================================================
ALTER TABLE photos
    ADD COLUMN title VARCHAR(255) NULL AFTER image,
    ADD COLUMN description_fr TEXT NULL AFTER legende,
    ADD COLUMN description_ar TEXT NULL AFTER description_fr,
    ADD COLUMN sort_order INT UNSIGNED NOT NULL DEFAULT 0 AFTER description_ar,
    ADD COLUMN status ENUM('active', 'hidden') NOT NULL DEFAULT 'active' AFTER sort_order,
    ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER uploaded_at;

-- Index pour optimisation
CREATE INDEX idx_photos_album ON photos (album_id);
CREATE INDEX idx_photos_sort ON photos (sort_order);
CREATE INDEX idx_photos_status ON photos (status);

-- =====================================================
-- Permissions galerie
-- =====================================================
INSERT INTO permissions (nom, module, description) VALUES
('gallery.create', 'gallery', 'Créer des galeries photo'),
('gallery.edit', 'gallery', 'Modifier une galerie photo'),
('gallery.delete', 'gallery', 'Supprimer des photos'),
('gallery.upload', 'gallery', 'Uploader des photos'),
('gallery.publish', 'gallery', 'Publier / masquer un album')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Accorder les permissions au rôle wilaya (niveau 7)
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r, permissions p
WHERE r.niveau = 7
  AND p.nom IN ('gallery.create', 'gallery.edit', 'gallery.delete', 'gallery.upload', 'gallery.publish');

