-- ═══════════════════════════════════════════════════════════════════
--  WILAYA HARMONIA — Migration V2.6 — Galerie associative
--  Soumission de photos par l'association + validation Wilaya.
--
--  Les photos soumises par une association passent en statut 'pending'.
--  La Wilaya valide ('active') ou rejette ('rejected' + motif).
--  Seules les photos 'active' sont affichées publiquement.
-- ═══════════════════════════════════════════════════════════════════

USE wilaya_harmonia;
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ── 1. Étendre le statut des photos ─────────────────────────────────
ALTER TABLE photos
    MODIFY COLUMN status ENUM('pending', 'active', 'hidden', 'rejected')
        NOT NULL DEFAULT 'active'
        COMMENT 'pending = en attente de validation Wilaya';

-- ── 2. Traçabilité de la soumission ─────────────────────────────────
ALTER TABLE photos
    ADD COLUMN IF NOT EXISTS uploaded_by INT NULL COMMENT 'Utilisateur qui a soumis la photo' AFTER status,
    ADD COLUMN IF NOT EXISTS motif_rejet VARCHAR(255) NULL AFTER uploaded_by;

-- ── 3. Permission de validation ─────────────────────────────────────
INSERT INTO permissions (nom, module, description) VALUES
('gallery.validate', 'gallery', 'Valider / rejeter les photos soumises par les associations')
ON DUPLICATE KEY UPDATE description = VALUES(description);

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r, permissions p
WHERE r.niveau = 7
  AND p.nom = 'gallery.validate';
