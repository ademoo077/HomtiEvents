USE wilaya_harmonia;
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- =====================================================
-- Lie chaque demande d'inscription au compte président
-- créé dès l'inscription (espace association / suivi).
-- Idempotent : ADD COLUMN IF NOT EXISTS (MariaDB ≥ 10.5).
-- =====================================================
ALTER TABLE association_requests
    ADD COLUMN IF NOT EXISTS user_id INT NULL AFTER id;

CREATE INDEX IF NOT EXISTS idx_ar_user ON association_requests (user_id);
