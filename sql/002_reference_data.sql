-- ═══════════════════════════════════════════════════════════════════
--  WILAYA HARMONIA — Données de référence
--  Rôles, permissions, EPIC, anomalies, compétences
-- ═══════════════════════════════════════════════════════════════════

USE wilaya_harmonia;
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- =====================================================
-- RÔLES RBAC (7 niveaux)
-- =====================================================

INSERT INTO roles (nom, niveau, description) VALUES
('citoyen',      1, 'Citoyen simple, participation aux événements'),
('membre',       2, 'Membre d''association'),
('epic',         2, 'Entreprise publique, consultation'),
('association',  3, 'Association validée, création d''événements'),
('chef_section', 4, 'Superviseur de section Wilaya'),
('chef_unite',   5, 'Chef d''unité Wilaya'),
('wilaya',       7, 'Administrateur suprême, tous les droits')
ON DUPLICATE KEY UPDATE niveau = VALUES(niveau);

-- =====================================================
-- PERMISSIONS
-- =====================================================

INSERT INTO permissions (nom, module, description) VALUES
-- Dashboard
('dashboard.view', 'dashboard', 'Consulter son tableau de bord'),
('dashboard.global', 'dashboard', 'Consulter les statistiques globales'),
-- Evenements
('evenement.create', 'evenements', 'Créer une demande d''événement'),
('evenement.edit', 'evenements', 'Modifier un événement EN_ATTENTE'),
('evenement.view', 'evenements', 'Consulter un événement'),
('evenement.view_all', 'evenements', 'Consulter tous les événements'),
('evenement.validate', 'evenements', 'Accepter / refuser une demande'),
('evenement.program', 'evenements', 'Programmer date, heure et EPIC'),
('evenement.delete', 'evenements', 'Supprimer un événement'),
('evenement.export', 'evenements', 'Exporter les événements'),
-- Associations
('association.view', 'associations', 'Consulter les associations'),
('association.validate', 'associations', 'Valider / rejeter les associations'),
-- EPIC
('epic.view', 'epic', 'Consulter les EPIC'),
('epic.assign', 'epic', 'Affecter les EPIC aux événements'),
-- QR Codes
('qrcode.generate', 'qrcode', 'Générer un QR code'),
('qrcode.scan', 'qrcode', 'Scanner / vérifier un QR code'),
-- Albums
('album.create', 'albums', 'Créer un album photo officiel'),
('album.view', 'albums', 'Consulter les albums'),
('album.upload', 'albums', 'Uploader des photos'),
-- Evaluations
('evaluation.create', 'evaluations', 'Rédiger une évaluation'),
('evaluation.view', 'evaluations', 'Consulter les évaluations'),
-- Participations
('participation.view', 'participations', 'Consulter les participations'),
('participation.export', 'participations', 'Exporter les participations'),
-- Historique
('historique.view', 'historique', 'Consulter l''historique d''un événement'),
-- Notifications
('notification.view', 'notifications', 'Consulter ses notifications'),
('notification.push', 'notifications', 'Envoyer des notifications push'),
-- CMS Landing
('landing.edit', 'landing', 'Administrer la page d''accueil'),
('landing.view', 'landing', 'Voir la page d''accueil'),
-- Audit
('audit.view', 'audit', 'Consulter le journal d''audit'),
-- Users
('user.manage', 'users', 'Gérer les comptes utilisateurs'),
-- Gamification
('gamification.view', 'gamification', 'Consulter points, badges et classement')
ON DUPLICATE KEY UPDATE module = VALUES(module);

-- =====================================================
-- ROLE ↔ PERMISSIONS
-- =====================================================

SET @role_citoyen    := (SELECT id FROM roles WHERE nom = 'citoyen');
SET @role_membre     := (SELECT id FROM roles WHERE nom = 'membre');
SET @role_epic       := (SELECT id FROM roles WHERE nom = 'epic');
SET @role_association:= (SELECT id FROM roles WHERE nom = 'association');
SET @role_wilaya     := (SELECT id FROM roles WHERE nom = 'wilaya');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p WHERE 1 = 0;

-- Citoyen
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT @role_citoyen, id FROM permissions WHERE nom IN
('dashboard.view', 'evenement.view', 'qrcode.scan', 'album.view', 'gamification.view', 'landing.view');

-- Membre d'association
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT @role_membre, id FROM permissions WHERE nom IN
('dashboard.view', 'evenement.view', 'qrcode.scan', 'album.view', 'gamification.view', 'landing.view');

-- EPIC (consultation)
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT @role_epic, id FROM permissions WHERE nom IN
('dashboard.view', 'evenement.view', 'epic.view', 'album.view', 'landing.view');

-- Association
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT @role_association, id FROM permissions WHERE nom IN
('dashboard.view', 'evenement.create', 'evenement.edit', 'evenement.view',
 'evaluation.create', 'participation.view', 'notification.view', 'album.view',
 'historique.view', 'gamification.view', 'landing.view');

-- Wilaya (tout)
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT @role_wilaya, id FROM permissions;

-- =====================================================
-- EPIC (Entreprises Publiques)
-- =====================================================

INSERT INTO epic (nom, description) VALUES
('ADE',     'Algérienne Des Eaux — gestion du réseau hydraulique'),
('NETCOM',  'Collecte et traitement des déchets ménagers'),
('ASROUT',  'Aménagement et entretien de la voirie publique'),
('EDEVAL',  'Entretien des espaces verts, arbres et élagage')
ON DUPLICATE KEY UPDATE nom = nom;

-- =====================================================
-- ANOMALIES STANDARDS
-- =====================================================

INSERT INTO anomalies (nom, description, icone, couleur) VALUES
('Déchets',            'Accumulation de déchets ménagers ou encombrants', 'fa-trash', '#ef4444'),
('Gravats',            'Débris de construction, gravats', 'fa-hard-hat', '#f59e0b'),
('Fuite d''eau',       'Fuite sur réseau public', 'fa-water', '#06b6d4'),
('Manque de bacs',     'Insuffisance de bacs à ordures', 'fa-dumpster', '#8b5cf6'),
('Trottoir dégradé',   'Trottoir endommagé nécessitant réparation', 'fa-road', '#ec4899'),
('Chaussée dégradée',  'Nids de poule, fissures de chaussée', 'fa-car-burst', '#dc2626'),
('Éclairage public',   'Lampadaires hors service', 'fa-lightbulb', '#eab308'),
('Espaces verts',      'Entretien des espaces verts', 'fa-tree', '#22c55e'),
('Élagage',            'Élagage d''arbres', 'fa-tree-city', '#15803d'),
('Assainissement',     'Problème d''assainissement', 'fa-water', '#2563eb')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- =====================================================
-- COMPÉTENCES EPIC ↔ ANOMALIES
-- =====================================================

INSERT IGNORE INTO epic_anomalies (epic_id, anomalie_id)
SELECT e.id, a.id FROM epic e
JOIN anomalies a ON
  (e.nom = 'ADE'     AND a.nom IN ('Fuite d''eau', 'Canalisation', 'Assainissement'))
  OR
  (e.nom = 'NETCOM'  AND a.nom IN ('Déchets', 'Gravats', 'Manque de bacs'))
  OR
  (e.nom = 'ASROUT'  AND a.nom IN ('Trottoir dégradé', 'Chaussée dégradée', 'Éclairage public'))
  OR
  (e.nom = 'EDEVAL'  AND a.nom IN ('Espaces verts', 'Élagage'));

-- =====================================================
-- BADGES GAMIFICATION
-- =====================================================

INSERT INTO badges (nom, description, icone, condition_type, points_recompense) VALUES
('Premier Pas',    'Premier événement créé',           'fa-star',    'first_event',   50),
('Contributeur',   '10 événements créés',              'fa-medal',   '10_events',    100),
('Pilier',         '50 événements créés',              'fa-crown',   '50_events',    500),
('Scanner Pro',    '100 participations scannées',      'fa-qrcode',  '100_scans',    200),
('Super Citoyen',  '1000 participations',              'fa-trophy',  '1000_scans',  1000)
ON DUPLICATE KEY UPDATE points_recompense = VALUES(points_recompense);

-- =====================================================
-- COMMUNES ET CIRCONSCRIPTIONS (démo Alger centre)
-- =====================================================

INSERT INTO ca (nom) VALUES
('Alger Centre'), ('Bab El Oued'), ('El Harrach'), ('Hussein Dey')
ON DUPLICATE KEY UPDATE nom = nom;

INSERT IGNORE INTO commune (nom, ca_id, latitude, longitude)
SELECT c.nom, ca.id, c.lat, c.lng
FROM (
  SELECT 'Alger Centre' AS nom, 3.0588 AS lng, 36.7538 AS lat
  UNION SELECT 'Bab El Oued', 3.0495, 36.7900
  UNION SELECT 'El Harrach', 3.1337, 36.7143
  UNION SELECT 'Hussein Dey', 3.0939, 36.7390
  UNION SELECT 'Birkhadem', 3.0250, 36.7149
  UNION SELECT 'Bordj El Kiffan', 3.1930, 36.7488
  UNION SELECT 'Hydra', 3.0367, 36.7354
  UNION SELECT 'Kouba', 3.0850, 36.7285
) c
JOIN ca ON ca.nom = CASE
  WHEN c.nom IN ('Alger Centre', 'Hydra') THEN 'Alger Centre'
  WHEN c.nom IN ('Bab El Oued') THEN 'Bab El Oued'
  WHEN c.nom IN ('El Harrach', 'Bordj El Kiffan') THEN 'El Harrach'
  ELSE 'Hussein Dey'
END;
