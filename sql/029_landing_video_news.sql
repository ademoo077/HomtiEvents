-- ═══════════════════════════════════════════════════════════════════
--  WILAYA HARMONIA — Landing Video & News/Events
--  URL vidéo hero + section Actualités & événements à venir
--  ═══════════════════════════════════════════════════════════════════

-- URL de la vidéo hero
INSERT INTO landing_settings (cle, valeur, type, groupe) VALUES
('hero_video_url', '/assets/video/hero.mp4', 'text', 'hero')
ON DUPLICATE KEY UPDATE valeur = VALUES(valeur);

-- Table des actualités / événements à venir pour la landing page
CREATE TABLE IF NOT EXISTS landing_news (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titre_fr VARCHAR(255) NOT NULL,
    titre_ar VARCHAR(255) NULL,
    description_fr TEXT NULL,
    description_ar TEXT NULL,
    image VARCHAR(500) NULL,
    date_event DATE NULL,
    lieu VARCHAR(255) NULL,
    lieu_ar VARCHAR(255) NULL,
    type ENUM('actualite', 'evenement') DEFAULT 'actualite',
    url_externe VARCHAR(500) NULL,
    actif TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_actif (actif),
    INDEX idx_date (date_event)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Section visible par défaut
INSERT INTO landing_settings (cle, valeur, type, groupe) VALUES
('section_actualites_upcoming_visible', '1', 'text', 'general')
ON DUPLICATE KEY UPDATE valeur = VALUES(valeur);
