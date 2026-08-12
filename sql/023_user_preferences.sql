-- =====================================================
-- PRÉFÉRENCES UTILISATEUR (profil / notifications)
-- Migration 023 — idempotente
-- =====================================================

CREATE TABLE IF NOT EXISTS user_preferences (
    user_id     INT NOT NULL PRIMARY KEY,
    notif_email TINYINT(1) DEFAULT 1 COMMENT 'Notifications par email',
    notif_inapp TINYINT(1) DEFAULT 1 COMMENT 'Notifications in-app',
    langue      VARCHAR(5) NULL COMMENT 'Langue par défaut (fr/ar)',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
