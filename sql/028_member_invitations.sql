-- ═══════════════════════════════════════════════════════════════════
--  WILAYA HARMONIA — Migration V2.7 — Membres d'association
--  Gestion des membres (rôle `membre`) par les associations :
--    • table association_invitations (invitation par email + token),
--    • permission association.members (rôles association + membre),
--    • rattachement des membres via users.association_id (déjà présent).
--  Flux : association invite → lien partagé → acceptation (création de
--  compte membre ou rattachement d'un compte existant) → notification.
-- ═══════════════════════════════════════════════════════════════════

USE wilaya_harmonia;
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ── 1. Table des invitations ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS association_invitations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    association_id INT NOT NULL,
    email VARCHAR(100) NOT NULL,
    token CHAR(64) NOT NULL UNIQUE,
    statut ENUM('pending', 'accepted', 'revoked', 'expired') NOT NULL DEFAULT 'pending',
    created_by INT NULL,
    accepted_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    accepted_at TIMESTAMP NULL,
    FOREIGN KEY (association_id) REFERENCES associations(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (accepted_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_invitation_assoc_email (association_id, email),
    INDEX idx_invitation_token (token),
    INDEX idx_invitation_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. Permission de gestion des membres ────────────────────────────
INSERT INTO permissions (nom, module, description) VALUES
('association.members', 'associations', 'Gérer les membres et les invitations de son association')
ON DUPLICATE KEY UPDATE nom = nom;

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r, permissions p
WHERE p.nom = 'association.members'
  AND r.nom IN ('association', 'membre');
