-- ═══════════════════════════════════════════════════════════════
-- 037 — Commentaires événements, notes internes Wilaya, enhances notifications
-- Rejouable : CREATE IF NOT EXISTS + ALTER TABLE IF NOT EXISTS (MariaDB 10.0.5+)
-- ═══════════════════════════════════════════════════════════════

-- 1) Commentaires sur les événements (discussion publique)
CREATE TABLE IF NOT EXISTS event_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evenement_id INT NOT NULL,
    user_id INT NOT NULL,
    body TEXT NOT NULL,
    parent_id INT NULL DEFAULT NULL,
    edited_at DATETIME NULL DEFAULT NULL,
    deleted_at DATETIME NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evenement_id) REFERENCES evenements(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES event_comments(id) ON DELETE CASCADE,
    INDEX idx_evenement (evenement_id, created_at),
    INDEX idx_parent (parent_id)
) ENGINE=InnoDB;

-- 2) Notes internes Wilaya
CREATE TABLE IF NOT EXISTS event_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evenement_id INT NOT NULL,
    user_id INT NOT NULL,
    body TEXT NOT NULL,
    is_internal BOOLEAN DEFAULT TRUE,
    edited_at DATETIME NULL DEFAULT NULL,
    deleted_at DATETIME NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evenement_id) REFERENCES evenements(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_evenement (evenement_id, created_at)
) ENGINE=InnoDB;

-- 3) sender_id sur notifications (idempotent)
ALTER TABLE notifications ADD COLUMN IF NOT EXISTS sender_id INT NULL DEFAULT NULL AFTER user_id;

-- 4) FK sender_id (idempotent)
SET @has_fk = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND CONSTRAINT_TYPE = 'FOREIGN KEY' AND CONSTRAINT_NAME = 'fk_notif_sender');
SET @ddl = IF(@has_fk = 0, 'ALTER TABLE notifications ADD CONSTRAINT fk_notif_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE SET NULL', 'SELECT 1');
PREPARE st FROM @ddl;
EXECUTE st;
DEALLOCATE PREPARE st;

-- 5) Index sender_id (idempotent)
ALTER TABLE notifications ADD INDEX IF NOT EXISTS idx_notif_sender (sender_id);

-- 6) Table annonces / bulletin board
CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(150) NOT NULL,
    body TEXT NOT NULL,
    target_role VARCHAR(50) NULL DEFAULT NULL,
    target_association_id INT NULL DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    published_at DATETIME NULL DEFAULT NULL,
    expires_at DATETIME NULL DEFAULT NULL,
    created_by INT NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (target_association_id) REFERENCES associations(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_active (is_active, published_at),
    INDEX idx_target (target_role, target_association_id)
) ENGINE=InnoDB;
