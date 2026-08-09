USE wilaya_harmonia;
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- =====================================================
-- Permissions de gestion (modification / suppression)
-- des demandes d'inscription association.
-- =====================================================
INSERT INTO permissions (nom, module, description) VALUES
('association_request.edit', 'association', 'Modifier une demande d\'inscription'),
('association_request.delete', 'association', 'Supprimer une demande d\'inscription')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Accorder au rôle wilaya (niveau 7)
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r, permissions p
WHERE r.niveau = 7
  AND p.nom IN ('association_request.edit', 'association_request.delete');
