-- ═══════════════════════════════════════════════════════════════════
--  WILAYA HARMONIA — Données de démonstration
--  Comptes, associations, événements, participations, albums, badges
-- ═══════════════════════════════════════════════════════════════════

USE wilaya_harmonia;
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════════════════════════
-- MOT DE PASSE COMMUN DES COMPTES DÉMO :  "Harmonia@2026"
-- Hash bcrypt généré ci-dessous (password_hash)
-- ═══════════════════════════════════════════════════════════════════

SET @pwd := '$2y$12$XUJHnstEigTSnXphOZzNbeN7arSJMZ1jzN5vAvjm7nSTGth9mN7ZW';

-- =====================================================
-- ASSOCIATIONS
-- =====================================================

INSERT INTO associations
(nom, caractere, numero_agrement, agrement_fichier, nom_prenom_president, telephone, email, date_creation, valide)
VALUES
('Association El Amel',       'association',    'AGR-2023-001', NULL, 'Ahmed Benali',       '0550 11 22 33', 'contact@elamel.dz',  '2021-04-12', TRUE),
('Comité de Quartier El Djazair', 'comite_quartier', 'AGR-2024-002', NULL, 'Fatima Zohra',    '0550 44 55 66', 'comite.djazair@mail.dz', '2022-06-01', TRUE),
('Association Environnement Vert', 'association', 'AGR-2023-003', NULL, 'Karim Mansouri',   '0550 77 88 99', 'asso.vert@mail.dz', '2020-01-20', TRUE),
('Comité Bab El Oued',        'comite_quartier', 'AGR-2024-004', NULL, 'Slimane Cherif',    '0551 00 11 22', 'comite.bbo@mail.dz', '2023-11-15', TRUE),
('Association Non Validée',   'association',    'AGR-2025-005', NULL, 'Larbi Meziane',     '0551 33 44 55', 'nonvalidee@mail.dz', '2025-01-10', FALSE)
ON DUPLICATE KEY UPDATE nom = nom;

-- =====================================================
-- UTILISATEURS
-- =====================================================

-- Wilaya
INSERT INTO users (nom, prenom, email, password, role_user, telephone, is_active)
VALUES ('Kheddam', 'Sofiane', 'wilaya@wilaya-harmonia.dz', @pwd, 'wilaya', '0660 00 00 01', TRUE)
ON DUPLICATE KEY UPDATE users.email = users.email;

-- Responsables associations (rôle association, liés à leur structure)
INSERT INTO users (nom, prenom, email, password, role_user, telephone, association_id, is_active)
SELECT 'Benali', 'Ahmed',  'president@elamel.dz', @pwd, 'association', '0550 11 22 33', id, TRUE
FROM associations WHERE nom = 'Association El Amel'
ON DUPLICATE KEY UPDATE users.email = users.email;

INSERT INTO users (nom, prenom, email, password, role_user, telephone, association_id, is_active)
SELECT 'Mansouri', 'Karim', 'president@vert.dz', @pwd, 'association', '0550 77 88 99', id, TRUE
FROM associations WHERE nom = 'Association Environnement Vert'
ON DUPLICATE KEY UPDATE users.email = users.email;

INSERT INTO users (nom, prenom, email, password, role_user, telephone, association_id, is_active)
SELECT 'Cherif', 'Slimane', 'president@bbo.dz', @pwd, 'association', '0551 00 11 22', id, TRUE
FROM associations WHERE nom = 'Comité Bab El Oued'
ON DUPLICATE KEY UPDATE users.email = users.email;

-- Membres d'association
INSERT INTO users (nom, prenom, email, password, role_user, telephone, association_id, is_active)
SELECT 'Hamidi', 'Nadia', 'membre1@elamel.dz', @pwd, 'membre', '0550 55 66 77', id, TRUE
FROM associations WHERE nom = 'Association El Amel'
ON DUPLICATE KEY UPDATE users.email = users.email;

INSERT INTO users (nom, prenom, email, password, role_user, telephone, association_id, is_active)
SELECT 'Bouzid', 'Yacine', 'membre1@vert.dz', @pwd, 'membre', '0550 66 77 88', id, TRUE
FROM associations WHERE nom = 'Association Environnement Vert'
ON DUPLICATE KEY UPDATE users.email = users.email;

-- Comptes EPIC (un par entreprise)
INSERT INTO users (nom, prenom, email, password, role_user, telephone, epic_id, is_active)
SELECT 'Directeur', 'ADE',    'ade@epic.dz',   @pwd, 'epic', '0660 20 00 01', id, TRUE FROM epic WHERE nom = 'ADE'
ON DUPLICATE KEY UPDATE users.email = users.email;

INSERT INTO users (nom, prenom, email, password, role_user, telephone, epic_id, is_active)
SELECT 'Directeur', 'NETCOM', 'netcom@epic.dz', @pwd, 'epic', '0660 20 00 02', id, TRUE FROM epic WHERE nom = 'NETCOM'
ON DUPLICATE KEY UPDATE users.email = users.email;

INSERT INTO users (nom, prenom, email, password, role_user, telephone, epic_id, is_active)
SELECT 'Directeur', 'ASROUT', 'asrout@epic.dz', @pwd, 'epic', '0660 20 00 03', id, TRUE FROM epic WHERE nom = 'ASROUT'
ON DUPLICATE KEY UPDATE users.email = users.email;

INSERT INTO users (nom, prenom, email, password, role_user, telephone, epic_id, is_active)
SELECT 'Directeur', 'EDEVAL', 'edeval@epic.dz', @pwd, 'epic', '0660 20 00 04', id, TRUE FROM epic WHERE nom = 'EDEVAL'
ON DUPLICATE KEY UPDATE users.email = users.email;

-- Citoyens
INSERT INTO users (nom, prenom, email, password, role_user, telephone, points, is_active)
VALUES
('Meziane', 'Amina',  'amina@citoyen.dz',   @pwd, 'citoyen', '0770 00 00 01', 1250, TRUE),
('Salah',   'Riad',   'riad@citoyen.dz',    @pwd, 'citoyen', '0770 00 00 02', 870,  TRUE),
('Belaid',  'Sofiane','sofiane@citoyen.dz', @pwd, 'citoyen', '0770 00 00 03', 540,  TRUE),
('Haddad',  'Lina',   'lina@citoyen.dz',    @pwd, 'citoyen', '0770 00 00 04', 310,  TRUE),
('Ouali',   'Sami',   'sami@citoyen.dz',    @pwd, 'citoyen', '0770 00 00 05', 0,    TRUE)
ON DUPLICATE KEY UPDATE users.email = users.email;

-- =====================================================
-- USER ROLES (RBAC)
-- =====================================================

INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT u.id, r.id FROM users u JOIN roles r ON r.nom = u.role_user;

-- =====================================================
-- ÉVÉNEMENTS DE DÉMONSTRATION
-- =====================================================

SET @commune_centre := (SELECT id FROM commune WHERE nom = 'Alger Centre');
SET @commune_bbo    := (SELECT id FROM commune WHERE nom = 'Bab El Oued');
SET @commune_harrach:= (SELECT id FROM commune WHERE nom = 'El Harrach');

SET @asso_elamel := (SELECT id FROM associations WHERE nom = 'Association El Amel');
SET @asso_vert   := (SELECT id FROM associations WHERE nom = 'Association Environnement Vert');
SET @asso_bbo    := (SELECT id FROM associations WHERE nom = 'Comité Bab El Oued');

-- Événement 1 : PROGRAMME, avec QR, EPIC, participations, album, évaluation
INSERT INTO evenements
(commune_id, adresse, association_id, description, informations_complementaires,
 statut, date_evenement, heure, deadline_at)
VALUES
(@commune_centre, 'Rue Didouche Mourad, Alger Centre', @asso_elamel,
 'Grande opération de nettoyage du quartier et de sensibilisation au tri des déchets.',
 'Prévoir des gants et sacs poubelle. Rassemblement place Maurice Audin à 8h00.',
 'PROGRAMME', DATE_ADD(CURDATE(), INTERVAL 7 DAY), '08:30:00',
 DATE_ADD(NOW(), INTERVAL 7 DAY));

SET @ev1 := LAST_INSERT_ID();

INSERT IGNORE INTO anomalies_evenement (anomalie_id, evenement_id)
SELECT id, @ev1 FROM anomalies WHERE nom IN ('Déchets', 'Manque de bacs', 'Gravats');

INSERT IGNORE INTO evenement_epic (evenement_id, epic_id)
SELECT @ev1, id FROM epic WHERE nom = 'NETCOM';

INSERT INTO qr_event (evenement_id, token_qr, date_debut, date_expiration)
VALUES (@ev1, UUID(), NOW(), DATE_ADD(NOW(), INTERVAL 8 DAY));

-- Événement 2 : EN_ATTENTE (en attente de validation)
INSERT INTO evenements
(commune_id, adresse, association_id, description, informations_complementaires,
 statut, deadline_at)
VALUES
(@commune_bbo, 'Boulevard Ourida Meddad, Bab El Oued', @asso_bbo,
 'Réhabilitation et embellissement de la place du quartier.',
 'Demande de replantation et réparation de l''éclairage.',
 'EN_ATTENTE', DATE_ADD(NOW(), INTERVAL 5 DAY));

SET @ev2 := LAST_INSERT_ID();

INSERT IGNORE INTO anomalies_evenement (anomalie_id, evenement_id)
SELECT id, @ev2 FROM anomalies WHERE nom IN ('Éclairage public', 'Espaces verts');

-- Événement 3 : TERMINE, avec album, évaluation, récit officiel
INSERT INTO evenements
(commune_id, adresse, association_id, description, informations_complementaires,
 statut, date_evenement, heure, deadline_at)
VALUES
(@commune_harrach, 'Cité 5 Juillet, El Harrach', @asso_vert,
 'Journée de plantation d''arbres le long de l''avenue principale.',
 'Sensibilisation des riverains à la protection des espaces verts.',
 'TERMINE', DATE_SUB(CURDATE(), INTERVAL 12 DAY), '09:00:00',
 DATE_SUB(NOW(), INTERVAL 10 DAY));

SET @ev3 := LAST_INSERT_ID();

INSERT IGNORE INTO anomalies_evenement (anomalie_id, evenement_id)
SELECT id, @ev3 FROM anomalies WHERE nom IN ('Espaces verts', 'Élagage');

INSERT IGNORE INTO evenement_epic (evenement_id, epic_id)
SELECT @ev3, id FROM epic WHERE nom = 'EDEVAL';

INSERT INTO qr_event (evenement_id, token_qr, date_debut, date_expiration)
VALUES (@ev3, UUID(), DATE_SUB(NOW(), INTERVAL 13 DAY), DATE_SUB(NOW(), INTERVAL 11 DAY));

INSERT INTO albums (evenement_id, titre, recit)
VALUES (@ev3, 'Plantation du boulevard 5 Juillet',
 'Grâce à la mobilisation exceptionnelle des citoyens et de l''association Environnement Vert, 120 arbres ont été plantés le long du boulevard. La Wilaya remercie les équipes EDEVAl pour leur accompagnement professionnel.');

SET @album1 := LAST_INSERT_ID();

INSERT INTO evaluation (evenement_id, association_id, note, description)
VALUES (@ev3, @asso_vert, 5, 'Coordination exemplaire entre la Wilaya, EDEVAL et les bénévoles. Matériel fourni, planning respecté.');

-- Événement 4 : PROGRAMME (avec EPIC ADE + ASROUT)
INSERT INTO evenements
(commune_id, adresse, association_id, description, informations_complementaires,
 statut, date_evenement, heure, deadline_at)
VALUES
(@commune_centre, 'Chemin du Créneau, Alger Centre', @asso_elamel,
 'Réparation d''une fuite d''eau majeure et reprise de la chaussée endommagée.',
 'Travaux conjoints ADE et ASROUT. Fermeture partielle de la circulation.',
 'PROGRAMME', DATE_ADD(CURDATE(), INTERVAL 14 DAY), '07:00:00',
 DATE_ADD(NOW(), INTERVAL 14 DAY));

SET @ev4 := LAST_INSERT_ID();

INSERT IGNORE INTO anomalies_evenement (anomalie_id, evenement_id)
SELECT id, @ev4 FROM anomalies WHERE nom IN ('Fuite d''eau', 'Chaussée dégradée', 'Assainissement');

INSERT IGNORE INTO evenement_epic (evenement_id, epic_id)
SELECT @ev4, id FROM epic WHERE nom IN ('ADE', 'ASROUT');

INSERT INTO qr_event (evenement_id, token_qr, date_debut, date_expiration)
VALUES (@ev4, UUID(), NOW(), DATE_ADD(NOW(), INTERVAL 15 DAY));

-- =====================================================
-- PARTICIPATIONS (événement terminé + événement programmé)
-- =====================================================

INSERT IGNORE INTO evenement_participant (evenement_id, user_id)
SELECT @ev3, id FROM users WHERE email IN ('amina@citoyen.dz', 'riad@citoyen.dz', 'sofiane@citoyen.dz', 'lina@citoyen.dz', 'membre1@elamel.dz');

INSERT IGNORE INTO evenement_participant (evenement_id, user_id)
SELECT @ev1, id FROM users WHERE email IN ('amina@citoyen.dz', 'riad@citoyen.dz');

-- =====================================================
-- HISTORIQUE DES ÉVÉNEMENTS
-- =====================================================

SET @wilaya_id := (SELECT id FROM users WHERE email = 'wilaya@wilaya-harmonia.dz');

INSERT INTO historique_evenement (evenement_id, user_id, action, observation, ip_address)
SELECT id, @wilaya_id, 'programme', 'Événement programmé et publié par la Wilaya', '127.0.0.1'
FROM evenements WHERE id IN (@ev1, @ev4);

INSERT INTO historique_evenement (evenement_id, user_id, action, observation, ip_address)
VALUES
(@ev1, (SELECT id FROM users WHERE email = 'president@elamel.dz'), 'creation', 'Demande créée par l''association', '127.0.0.1'),
(@ev3, (SELECT id FROM users WHERE email = 'president@vert.dz'), 'creation', 'Demande créée par l''association', '127.0.0.1'),
(@ev3, @wilaya_id, 'album_cree', 'Album officiel créé avec récit', '127.0.0.1'),
(@ev3, (SELECT id FROM users WHERE email = 'president@vert.dz'), 'evaluation', 'Évaluation rédigée (5/5)', '127.0.0.1');

-- =====================================================
-- POINTS ET BADGES (gamification)
-- =====================================================

INSERT INTO citizen_points (user_id, points, raison, evenement_id)
SELECT id, 250, 'Participation à l''événement de plantation (x5)', @ev3 FROM users WHERE email = 'amina@citoyen.dz';

INSERT INTO citizen_points (user_id, points, raison, evenement_id)
SELECT id, 200, 'Participation à l''événement de plantation', @ev3 FROM users WHERE email = 'riad@citoyen.dz';

INSERT IGNORE INTO user_badges (user_id, badge_id)
SELECT u.id, b.id FROM users u, badges b
WHERE u.email IN ('amina@citoyen.dz', 'riad@citoyen.dz')
  AND b.condition_type = 'first_event';
