-- ═══════════════════════════════════════════════════════════════
-- MIGRATION V2.0 — Centre de commandement Wilaya
-- ═══════════════════════════════════════════════════════════════

-- Soft delete des événements (régime d'archivage)
ALTER TABLE evenements
    ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at,
    ADD INDEX idx_deleted (deleted_at);

-- Colonne couleur pour le calendrier (statut → couleur)
ALTER TABLE epic
    ADD COLUMN couleur VARCHAR(7) NULL AFTER description;

-- Slug / libellé pour les rôles métier (facultatif, pour la palette)
ALTER TABLE roles
    ADD COLUMN couleur VARCHAR(7) NULL DEFAULT NULL AFTER nom;
