-- ═══════════════════════════════════════════════════════════════════
--  WILAYA HARMONIA — Migration V2.1 — Machine à états stricte
--  Cycle de vie : EN_ATTENTE → PROGRAMME → TERMINE (+ REFUSE)
-- ═══════════════════════════════════════════════════════════════════

USE wilaya_harmonia;
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ── 1. Ajout du statut REFUSE à l'ENUM des événements ──────────────────
ALTER TABLE evenements
    MODIFY COLUMN statut ENUM('EN_ATTENTE', 'PROGRAMME', 'TERMINE', 'REFUSE')
    NOT NULL DEFAULT 'EN_ATTENTE';

-- ── 2. Historique des transitions (immuable) ──────────────────────────
CREATE TABLE IF NOT EXISTS transition_history (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    evenement_id   INT NOT NULL,
    statut_avant   ENUM('EN_ATTENTE', 'PROGRAMME', 'TERMINE', 'REFUSE') NOT NULL,
    statut_apres   ENUM('EN_ATTENTE', 'PROGRAMME', 'TERMINE', 'REFUSE') NOT NULL,
    user_id        INT NULL,
    motif          TEXT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evenement_id) REFERENCES evenements(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_evenement (evenement_id),
    INDEX idx_date (created_at DESC)
) ENGINE=InnoDB COMMENT='Journal immuable des transitions d''état des événements';
