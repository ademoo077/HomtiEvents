-- 040 : Demande de modification pour les demandes d'inscription association
-- Permet à la Wilaya de demander des corrections avant refus définitif

-- 1. Ajouter le statut 'modification_requested' à l'ENUM
ALTER TABLE association_requests
    MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'modification_requested') NOT NULL DEFAULT 'pending';

-- 2. Ajouter le champ motif de modification
ALTER TABLE association_requests
    ADD COLUMN IF NOT EXISTS modification_reason TEXT NULL AFTER rejection_reason;

-- 3. Ajouter la date de demande de modification
ALTER TABLE association_requests
    ADD COLUMN IF NOT EXISTS modification_requested_at TIMESTAMP NULL AFTER processed_at;

-- 4. Permission pour demander des modifications
INSERT INTO permissions (nom, module, description) VALUES
    ('association_request.request_modification', 'association', 'Demander des modifications sur une demande d''inscription')
ON DUPLICATE KEY UPDATE description = VALUES(description);
