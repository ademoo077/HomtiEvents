USE wilaya_harmonia;
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- =====================================================
-- Complète la structure de `association_requests`
-- (récupère les bases où la table avait été créée avec
--  un schéma minimal ne contenant que 4 colonnes).
-- Idempotent : ADD COLUMN IF NOT EXISTS (MariaDB ≥ 10.5).
-- =====================================================
ALTER TABLE association_requests
    ADD COLUMN IF NOT EXISTS approval_number VARCHAR(50) NULL AFTER association_name,
    ADD COLUMN IF NOT EXISTS activity_domain VARCHAR(100) NULL AFTER approval_number,
    ADD COLUMN IF NOT EXISTS description TEXT NULL AFTER activity_domain,
    ADD COLUMN IF NOT EXISTS address VARCHAR(255) NULL AFTER description,
    ADD COLUMN IF NOT EXISTS commune VARCHAR(100) NULL AFTER address,
    ADD COLUMN IF NOT EXISTS wilaya VARCHAR(100) NULL AFTER commune,
    ADD COLUMN IF NOT EXISTS phone VARCHAR(20) NULL AFTER wilaya,
    ADD COLUMN IF NOT EXISTS email VARCHAR(100) NULL AFTER phone,
    ADD COLUMN IF NOT EXISTS website VARCHAR(255) NULL AFTER email,
    ADD COLUMN IF NOT EXISTS president_lastname VARCHAR(100) NULL AFTER website,
    ADD COLUMN IF NOT EXISTS president_firstname VARCHAR(100) NULL AFTER president_lastname,
    ADD COLUMN IF NOT EXISTS president_birthdate DATE NULL AFTER president_firstname,
    ADD COLUMN IF NOT EXISTS president_phone VARCHAR(20) NULL AFTER president_birthdate,
    ADD COLUMN IF NOT EXISTS president_email VARCHAR(100) NULL AFTER president_phone,
    ADD COLUMN IF NOT EXISTS president_address VARCHAR(255) NULL AFTER president_email,
    ADD COLUMN IF NOT EXISTS president_id_type VARCHAR(50) NULL AFTER president_address,
    ADD COLUMN IF NOT EXISTS president_id_number VARCHAR(50) NULL AFTER president_id_type,
    ADD COLUMN IF NOT EXISTS approval_file VARCHAR(255) NULL AFTER president_id_number,
    ADD COLUMN IF NOT EXISTS identity_file VARCHAR(255) NULL AFTER approval_file,
    ADD COLUMN IF NOT EXISTS processed_by INT UNSIGNED NULL AFTER status,
    ADD COLUMN IF NOT EXISTS processed_at TIMESTAMP NULL AFTER processed_by,
    ADD COLUMN IF NOT EXISTS rejection_reason TEXT NULL AFTER processed_at,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

CREATE INDEX IF NOT EXISTS idx_ar_status ON association_requests (status);
CREATE INDEX IF NOT EXISTS idx_ar_created ON association_requests (created_at);
CREATE INDEX IF NOT EXISTS idx_ar_email ON association_requests (email);
