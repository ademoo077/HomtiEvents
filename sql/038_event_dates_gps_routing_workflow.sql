-- ═══════════════════════════════════════════════════════════════════
--  038 — Dates événement, GPS, routing par anomalie, workflow statuts
-- ═══════════════════════════════════════════════════════════════════

USE wilaya_harmonia;
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ── 1. start_at / end_at sur evenements ──────────────────────────
ALTER TABLE evenements
    ADD COLUMN IF NOT EXISTS start_at DATETIME NULL AFTER heure,
    ADD COLUMN IF NOT EXISTS end_at DATETIME NULL AFTER start_at,
    ADD COLUMN IF NOT EXISTS latitude DECIMAL(10,7) NULL AFTER end_at,
    ADD COLUMN IF NOT EXISTS longitude DECIMAL(10,7) NULL AFTER latitude;

CREATE INDEX IF NOT EXISTS idx_evenements_start_at ON evenements (start_at);
CREATE INDEX IF NOT EXISTS idx_evenements_end_at ON evenements (end_at);

-- ── 2. GPS sur anomalies_evenement ───────────────────────────────
ALTER TABLE anomalies_evenement
    ADD COLUMN IF NOT EXISTS latitude DECIMAL(10,7) NULL AFTER evenement_id,
    ADD COLUMN IF NOT EXISTS longitude DECIMAL(10,7) NULL AFTER latitude,
    ADD COLUMN IF NOT EXISTS titre VARCHAR(255) NULL AFTER longitude,
    ADD COLUMN IF NOT EXISTS description TEXT NULL AFTER titre,
    ADD COLUMN IF NOT EXISTS priorite ENUM('basse','moyenne','haute','critique') NULL DEFAULT 'moyenne' AFTER description,
    ADD COLUMN IF NOT EXISTS statut VARCHAR(50) NULL DEFAULT 'DETECTEE' AFTER priorite;

-- ── 3. Categories d'anomalies ────────────────────────────────────
CREATE TABLE IF NOT EXISTS anomalie_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT NULL,
    icone VARCHAR(50) NULL,
    couleur VARCHAR(7) NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS anomalie_subcategories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    description TEXT NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES anomalie_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Catégorie et sous-catégorie sur anomalies
ALTER TABLE anomalies
    ADD COLUMN IF NOT EXISTS category_id INT NULL AFTER description,
    ADD COLUMN IF NOT EXISTS subcategory_id INT NULL AFTER category_id;

CREATE INDEX IF NOT EXISTS idx_anomalies_category ON anomalies (category_id);
CREATE INDEX IF NOT EXISTS idx_anomalies_subcategory ON anomalies (subcategory_id);

-- ── 4. Category/subcategory sur routing_rules ────────────────────
ALTER TABLE routing_rules
    ADD COLUMN IF NOT EXISTS category_id INT NULL AFTER anomalie_id,
    ADD COLUMN IF NOT EXISTS subcategory_id INT NULL AFTER category_id;

CREATE INDEX IF NOT EXISTS idx_routing_category ON routing_rules (category_id);
CREATE INDEX IF NOT EXISTS idx_routing_subcategory ON routing_rules (subcategory_id);

-- ── 5. Assignations par anomalie (multi-EPIC) ────────────────────
CREATE TABLE IF NOT EXISTS anomaly_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evenement_id INT NOT NULL,
    anomalie_id INT NOT NULL,
    epic_id INT NOT NULL,
    assigned_org_id INT NULL COMMENT 'Organisation manuellement assignée',
    auto_routed TINYINT(1) NOT NULL DEFAULT 1,
    override_reason TEXT NULL,
    override_by INT NULL,
    override_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (evenement_id) REFERENCES evenements(id) ON DELETE CASCADE,
    FOREIGN KEY (anomalie_id) REFERENCES anomalies(id) ON DELETE CASCADE,
    FOREIGN KEY (epic_id) REFERENCES epic(id) ON DELETE CASCADE,
    INDEX idx_assignment_evenement (evenement_id),
    INDEX idx_assignment_epic (epic_id),
    INDEX idx_assignment_anomalie (anomalie_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 6. Historique des statuts d'anomalies ────────────────────────
CREATE TABLE IF NOT EXISTS anomaly_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evenement_id INT NOT NULL,
    anomalie_id INT NOT NULL,
    old_status VARCHAR(50) NULL,
    new_status VARCHAR(50) NOT NULL,
    changed_by INT NULL,
    note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evenement_id) REFERENCES evenements(id) ON DELETE CASCADE,
    FOREIGN KEY (anomalie_id) REFERENCES anomalies(id) ON DELETE CASCADE,
    INDEX idx_status_hist_evenement (evenement_id),
    INDEX idx_status_hist_anomalie (anomalie_id),
    INDEX idx_status_hist_status (new_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 7. Rétrocompat : peupler start_at à partir de date_evenement + heure ──
UPDATE evenements
SET start_at = CONCAT(date_evenement, ' ', COALESCE(heure, '09:00:00'))
WHERE start_at IS NULL AND date_evenement IS NOT NULL;
