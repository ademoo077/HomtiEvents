-- ═══════════════════════════════════════════════════════════════════
--  WILAYA HARMONIA — Landing Gallery & Before/After
--  Complète le module CMS avec galeries visuelles et comparaisons
--  avant/après pour le site public.
-- ═══════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS landing_gallery (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    titre_fr   VARCHAR(255) NOT NULL,
    titre_ar   VARCHAR(255) DEFAULT NULL,
    image      VARCHAR(255) NOT NULL,
    lien       VARCHAR(255) DEFAULT NULL,
    type       ENUM('album', 'evenement', 'actualite') NOT NULL DEFAULT 'album',
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    actif      TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_sort (sort_order),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS landing_before_after (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    titre_fr       VARCHAR(255) NOT NULL,
    titre_ar       VARCHAR(255) DEFAULT NULL,
    image_before   VARCHAR(255) NOT NULL,
    image_after    VARCHAR(255) NOT NULL,
    description_fr TEXT DEFAULT NULL,
    description_ar TEXT DEFAULT NULL,
    statut         ENUM('publie', 'brouillon') NOT NULL DEFAULT 'publie',
    sort_order     INT UNSIGNED NOT NULL DEFAULT 0,
    actif          TINYINT(1) NOT NULL DEFAULT 1,
    created_at     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_sort (sort_order),
    INDEX idx_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO landing_settings (cle, valeur, type, groupe) VALUES
    ('section_galerie_visible', '1', 'text', 'general'),
    ('section_before_after_visible', '1', 'text', 'general')
ON DUPLICATE KEY UPDATE valeur = VALUES(valeur);
