USE wilaya_harmonia;
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE audit_logs
    ADD COLUMN IF NOT EXISTS statut ENUM('succes','echec') NOT NULL DEFAULT 'succes' AFTER user_agent;

ALTER TABLE audit_logs
    ADD COLUMN IF NOT EXISTS device VARCHAR(120) NULL AFTER user_agent;

ALTER TABLE audit_logs
    ADD INDEX idx_statut (statut),
    ADD INDEX idx_action_statut (action, statut);

SET FOREIGN_KEY_CHECKS = 1;