USE wilaya_harmonia;
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- =====================================================
-- Table des demandes d'inscription association (complète)
-- =====================================================
CREATE TABLE IF NOT EXISTS association_requests (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    -- Infos association
    association_name VARCHAR(150) NOT NULL,
    approval_number VARCHAR(50) NULL,
    activity_domain VARCHAR(100) NULL,
    description TEXT NULL,
    address VARCHAR(255) NULL,
    commune VARCHAR(100) NULL,
    wilaya VARCHAR(100) NULL,
    phone VARCHAR(20) NULL,
    email VARCHAR(100) NULL,
    website VARCHAR(255) NULL,
    -- Infos président
    president_lastname VARCHAR(100) NOT NULL,
    president_firstname VARCHAR(100) NOT NULL,
    president_birthdate DATE NULL,
    president_phone VARCHAR(20) NULL,
    president_email VARCHAR(100) NULL,
    president_address VARCHAR(255) NULL,
    president_id_type VARCHAR(50) NULL COMMENT 'CNI, Passeport, etc.',
    president_id_number VARCHAR(50) NULL,
    -- Documents
    approval_file VARCHAR(255) NULL COMMENT 'Chemin fichier agrément',
    identity_file VARCHAR(255) NULL COMMENT 'Chemin pièce d\'identité',
    -- Statut
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    processed_by INT UNSIGNED NULL COMMENT 'ID utilisateur Wilaya ayant traité',
    processed_at TIMESTAMP NULL,
    rejection_reason TEXT NULL,
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_status (status),
    INDEX idx_created (created_at),
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- =====================================================
-- Permissions pour le module demandes associations
-- =====================================================
INSERT INTO permissions (nom, module, description) VALUES
('association_request.view', 'association', 'Consulter les demandes d\'inscription'),
('association_request.approve', 'association', 'Valider une demande d\'inscription'),
('association_request.reject', 'association', 'Refuser une demande d\'inscription')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Accorder au rôle wilaya
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r, permissions p
WHERE r.niveau = 7
  AND p.nom IN ('association_request.view', 'association_request.approve', 'association_request.reject');
