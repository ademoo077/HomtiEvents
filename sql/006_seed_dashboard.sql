-- ═══════════════════════════════════════════════════════════════════
--  WILAYA HARMONIA — Seed Centre de commandement (V2)
--  Enrichit le jeu de données pour les dashboards, cartes et graphiques.
--  Idempotent : n'ajoute rien si l'événement existe déjà.
-- ═══════════════════════════════════════════════════════════════════

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

SET @commune_centre  := (SELECT id FROM commune WHERE nom = 'Alger Centre');
SET @commune_bbo     := (SELECT id FROM commune WHERE nom = 'Bab El Oued');
SET @commune_harrach := (SELECT id FROM commune WHERE nom = 'El Harrach');
SET @commune_dey     := (SELECT id FROM commune WHERE nom = 'Hussein Dey');
SET @commune_birkhadem := (SELECT id FROM commune WHERE nom = 'Birkhadem');
SET @commune_bkf     := (SELECT id FROM commune WHERE nom = 'Bordj El Kiffan');
SET @commune_hydra   := (SELECT id FROM commune WHERE nom = 'Hydra');
SET @commune_kouba   := (SELECT id FROM commune WHERE nom = 'Kouba');

SET @asso_elamel := (SELECT id FROM associations WHERE nom = 'Association El Amel');
SET @asso_vert   := (SELECT id FROM associations WHERE nom = 'Association Environnement Vert');
SET @asso_bbo    := (SELECT id FROM associations WHERE nom = 'Comité Bab El Oued');

SET @epic_ade    := (SELECT id FROM epic WHERE nom = 'ADE');
SET @epic_netcom := (SELECT id FROM epic WHERE nom = 'NETCOM');
SET @epic_asrout := (SELECT id FROM epic WHERE nom = 'ASROUT');
SET @epic_edeval := (SELECT id FROM epic WHERE nom = 'EDEVAL');

SET @wilaya_id := (SELECT id FROM users WHERE email = 'wilaya@wilaya-harmonia.dz');

-- ═══════════════════════════════════════════════════════════════════
-- ÉVÉNEMENTS EN ATTENTE (file de validation)
-- ═══════════════════════════════════════════════════════════════════

INSERT INTO evenements
(commune_id, adresse, association_id, description, informations_complementaires,
 statut, deadline_at, created_at)
SELECT @commune_dey, 'Cité AADL, Hussein Dey', @asso_elamel,
       'Opération de désencombrement des caves et abords des immeubles.',
       'Enlèvement d''encombrants et de gravats.', 'EN_ATTENTE',
       DATE_ADD(NOW(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)
WHERE NOT EXISTS (SELECT 1 FROM evenements WHERE adresse = 'Cité AADL, Hussein Dey');

INSERT INTO evenements
(commune_id, adresse, association_id, description, informations_complementaires,
 statut, deadline_at, created_at)
SELECT @commune_hydra, 'Rue des Sources, Hydra', @asso_vert,
       'Réhabilitation du trottoir et remplacement de lampadaires.',
       'Section de 300 mètres, trottoir fortement dégradé.', 'EN_ATTENTE',
       DATE_ADD(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)
WHERE NOT EXISTS (SELECT 1 FROM evenements WHERE adresse = 'Rue des Sources, Hydra');

INSERT INTO evenements
(commune_id, adresse, association_id, description, informations_complementaires,
 statut, deadline_at, created_at)
SELECT @commune_kouba, 'Rue Boubazine, Kouba', @asso_bbo,
       'Nettoyage du marché hebdomadaire et collecte des déchets.',
       'Besoin de bacs supplémentaires.', 'EN_ATTENTE',
       DATE_ADD(NOW(), INTERVAL 6 DAY), NOW()
WHERE NOT EXISTS (SELECT 1 FROM evenements WHERE adresse = 'Rue Boubazine, Kouba');

INSERT IGNORE INTO anomalies_evenement (anomalie_id, evenement_id)
SELECT a.id, e.id FROM anomalies a JOIN evenements e
WHERE e.adresse = 'Cité AADL, Hussein Dey' AND a.nom IN ('Gravats', 'Encombrants');
INSERT IGNORE INTO anomalies_evenement (anomalie_id, evenement_id)
SELECT a.id, e.id FROM anomalies a JOIN evenements e
WHERE e.adresse = 'Rue des Sources, Hydra' AND a.nom IN ('Trottoir dégradé', 'Éclairage public');
INSERT IGNORE INTO anomalies_evenement (anomalie_id, evenement_id)
SELECT a.id, e.id FROM anomalies a JOIN evenements e
WHERE e.adresse = 'Rue Boubazine, Kouba' AND a.nom IN ('Déchets', 'Manque de bacs');

-- ═══════════════════════════════════════════════════════════════════
-- ÉVÉNEMENTS PROGRAMMÉS (prochains)
-- ═══════════════════════════════════════════════════════════════════

INSERT INTO evenements
(commune_id, adresse, association_id, description, informations_complementaires,
 statut, date_evenement, heure, deadline_at, created_at)
SELECT @commune_birkhadem, 'Cité 2000 logements, Birkhadem', @asso_elamel,
       'Grande collecte des déchets verts et élagage des arbres.',
       'Événement prioritaire, école à proximité.', 'PROGRAMME',
       DATE_ADD(CURDATE(), INTERVAL 5 DAY), '09:00:00',
       DATE_ADD(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 12 DAY)
WHERE NOT EXISTS (SELECT 1 FROM evenements WHERE adresse = 'Cité 2000 logements, Birkhadem');

INSERT INTO evenements
(commune_id, adresse, association_id, description, informations_complementaires,
 statut, date_evenement, heure, deadline_at, created_at)
SELECT @commune_bkf, 'Avenue du Stade, Bordj El Kiffan', @asso_vert,
       'Réfection de la chaussée et marquage au sol.',
       'Fermeture partielle de la circulation prévue.', 'PROGRAMME',
       DATE_ADD(CURDATE(), INTERVAL 12 DAY), '14:00:00',
       DATE_ADD(NOW(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 20 DAY)
WHERE NOT EXISTS (SELECT 1 FROM evenements WHERE adresse = 'Avenue du Stade, Bordj El Kiffan');

INSERT INTO evenements
(commune_id, adresse, association_id, description, informations_complementaires,
 statut, date_evenement, heure, deadline_at, created_at)
SELECT @commune_centre, 'Place des Martyrs, Alger Centre', NULL,
       'Sensibilisation à la propreté urbaine avec les écoles.',
       'Opération portée par la Wilaya.', 'PROGRAMME',
       DATE_ADD(CURDATE(), INTERVAL 2 DAY), '08:30:00',
       DATE_ADD(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 30 DAY)
WHERE NOT EXISTS (SELECT 1 FROM evenements WHERE adresse = 'Place des Martyrs, Alger Centre');

INSERT IGNORE INTO anomalies_evenement (anomalie_id, evenement_id)
SELECT a.id, e.id FROM anomalies a JOIN evenements e
WHERE e.adresse = 'Cité 2000 logements, Birkhadem' AND a.nom IN ('Espaces verts', 'Élagage');
INSERT IGNORE INTO anomalies_evenement (anomalie_id, evenement_id)
SELECT a.id, e.id FROM anomalies a JOIN evenements e
WHERE e.adresse = 'Avenue du Stade, Bordj El Kiffan' AND a.nom IN ('Chaussée dégradée', 'Assainissement');
INSERT IGNORE INTO anomalies_evenement (anomalie_id, evenement_id)
SELECT a.id, e.id FROM anomalies a JOIN evenements e
WHERE e.adresse = 'Place des Martyrs, Alger Centre' AND a.nom IN ('Déchets', 'Manque de bacs');

INSERT IGNORE INTO evenement_epic (evenement_id, epic_id)
SELECT e.id, @epic_ade FROM evenements e WHERE e.adresse = 'Cité 2000 logements, Birkhadem';
INSERT IGNORE INTO evenement_epic (evenement_id, epic_id)
SELECT e.id, @epic_edeval FROM evenements e WHERE e.adresse = 'Cité 2000 logements, Birkhadem';
INSERT IGNORE INTO evenement_epic (evenement_id, epic_id)
SELECT e.id, @epic_asrout FROM evenements e WHERE e.adresse = 'Avenue du Stade, Bordj El Kiffan';
INSERT IGNORE INTO evenement_epic (evenement_id, epic_id)
SELECT e.id, @epic_netcom FROM evenements e WHERE e.adresse = 'Place des Martyrs, Alger Centre';

INSERT IGNORE INTO qr_event (evenement_id, token_qr, date_debut, date_expiration)
SELECT e.id, UUID(), NOW(), DATE_ADD(NOW(), INTERVAL 6 DAY) FROM evenements e WHERE e.adresse = 'Cité 2000 logements, Birkhadem';
INSERT IGNORE INTO qr_event (evenement_id, token_qr, date_debut, date_expiration)
SELECT e.id, UUID(), NOW(), DATE_ADD(NOW(), INTERVAL 13 DAY) FROM evenements e WHERE e.adresse = 'Avenue du Stade, Bordj El Kiffan';
INSERT IGNORE INTO qr_event (evenement_id, token_qr, date_debut, date_expiration)
SELECT e.id, UUID(), NOW(), DATE_ADD(NOW(), INTERVAL 3 DAY) FROM evenements e WHERE e.adresse = 'Place des Martyrs, Alger Centre';

INSERT IGNORE INTO sla_alertes (evenement_id, type, message)
SELECT e.id, t.t, t.m FROM evenements e JOIN (SELECT 'j-1' AS t, 'Rappel : l''événement est dans 1 jour.' AS m UNION SELECT 'j-2', 'Rappel : l''événement est dans 2 jours.') t
WHERE e.adresse IN ('Place des Martyrs, Alger Centre');

-- ═══════════════════════════════════════════════════════════════════
-- ÉVÉNEMENTS TERMINÉS (répartis sur les 8 derniers mois)
-- ═══════════════════════════════════════════════════════════════════

INSERT INTO evenements
(commune_id, adresse, association_id, description, statut, date_evenement, heure, deadline_at, created_at)
SELECT @commune_harrach, 'Rue Bensakrane, El Harrach', @asso_vert,
       'Collecte de déchets plastiques et tri sélectif.', 'TERMINE',
       DATE_SUB(CURDATE(), INTERVAL 45 DAY), '09:00:00',
       DATE_SUB(NOW(), INTERVAL 43 DAY), DATE_SUB(NOW(), INTERVAL 60 DAY)
WHERE NOT EXISTS (SELECT 1 FROM evenements WHERE adresse = 'Rue Bensakrane, El Harrach');

INSERT INTO evenements
(commune_id, adresse, association_id, description, statut, date_evenement, heure, deadline_at, created_at)
SELECT @commune_bbo, 'Place du 1er Mai, Bab El Oued', @asso_bbo,
       'Nettoyage du port et des quais.', 'TERMINE',
       DATE_SUB(CURDATE(), INTERVAL 75 DAY), '08:00:00',
       DATE_SUB(NOW(), INTERVAL 73 DAY), DATE_SUB(NOW(), INTERVAL 95 DAY)
WHERE NOT EXISTS (SELECT 1 FROM evenements WHERE adresse = 'Place du 1er Mai, Bab El Oued');

INSERT INTO evenements
(commune_id, adresse, association_id, description, statut, date_evenement, heure, deadline_at, created_at)
SELECT @commune_kouba, 'Cité Bachdjarah, Kouba', @asso_elamel,
       'Réparation de l''éclairage public du lotissement.', 'TERMINE',
       DATE_SUB(CURDATE(), INTERVAL 120 DAY), '10:00:00',
       DATE_SUB(NOW(), INTERVAL 118 DAY), DATE_SUB(NOW(), INTERVAL 150 DAY)
WHERE NOT EXISTS (SELECT 1 FROM evenements WHERE adresse = 'Cité Bachdjarah, Kouba');

INSERT INTO evenements
(commune_id, adresse, association_id, description, statut, date_evenement, heure, deadline_at, created_at)
SELECT @commune_dey, 'Rue Hassiba Ben Bouali, Hussein Dey', @asso_vert,
       'Opération de végétalisation et plantation d''arbres.', 'TERMINE',
       DATE_SUB(CURDATE(), INTERVAL 200 DAY), '09:30:00',
       DATE_SUB(NOW(), INTERVAL 198 DAY), DATE_SUB(NOW(), INTERVAL 220 DAY)
WHERE NOT EXISTS (SELECT 1 FROM evenements WHERE adresse = 'Rue Hassiba Ben Bouali, Hussein Dey');

INSERT IGNORE INTO anomalies_evenement (anomalie_id, evenement_id)
SELECT a.id, e.id FROM anomalies a JOIN evenements e
WHERE e.adresse = 'Rue Bensakrane, El Harrach' AND a.nom IN ('Déchets', 'Manque de bacs');
INSERT IGNORE INTO anomalies_evenement (anomalie_id, evenement_id)
SELECT a.id, e.id FROM anomalies a JOIN evenements e
WHERE e.adresse = 'Place du 1er Mai, Bab El Oued' AND a.nom IN ('Gravats', 'Assainissement');
INSERT IGNORE INTO anomalies_evenement (anomalie_id, evenement_id)
SELECT a.id, e.id FROM anomalies a JOIN evenements e
WHERE e.adresse = 'Cité Bachdjarah, Kouba' AND a.nom IN ('Éclairage public', 'Chaussée dégradée');
INSERT IGNORE INTO anomalies_evenement (anomalie_id, evenement_id)
SELECT a.id, e.id FROM anomalies a JOIN evenements e
WHERE e.adresse = 'Rue Hassiba Ben Bouali, Hussein Dey' AND a.nom IN ('Espaces verts', 'Élagage');

INSERT IGNORE INTO evenement_epic (evenement_id, epic_id)
SELECT e.id, @epic_netcom FROM evenements e WHERE e.adresse = 'Rue Bensakrane, El Harrach';
INSERT IGNORE INTO evenement_epic (evenement_id, epic_id)
SELECT e.id, @epic_ade FROM evenements e WHERE e.adresse = 'Place du 1er Mai, Bab El Oued';
INSERT IGNORE INTO evenement_epic (evenement_id, epic_id)
SELECT e.id, @epic_asrout FROM evenements e WHERE e.adresse = 'Cité Bachdjarah, Kouba';
INSERT IGNORE INTO evenement_epic (evenement_id, epic_id)
SELECT e.id, @epic_edeval FROM evenements e WHERE e.adresse = 'Rue Hassiba Ben Bouali, Hussein Dey';

-- ═══════════════════════════════════════════════════════════════════
-- PARTICIPATIONS étalées sur les mois précédents (courbe 12 mois)
-- ═══════════════════════════════════════════════════════════════════

INSERT IGNORE INTO evenement_participant (evenement_id, user_id, heure_scan)
SELECT e.id, u.id, e.date_evenement FROM evenements e
JOIN users u ON u.email IN ('amina@citoyen.dz', 'riad@citoyen.dz', 'sofiane@citoyen.dz', 'lina@citoyen.dz')
WHERE e.statut = 'TERMINE' AND e.adresse IN ('Rue Bensakrane, El Harrach', 'Cité Bachdjarah, Kouba', 'Rue Hassiba Ben Bouali, Hussein Dey');

INSERT IGNORE INTO evenement_participant (evenement_id, user_id, heure_scan)
SELECT e.id, u.id, e.date_evenement FROM evenements e
JOIN users u ON u.email = 'membre1@elamel.dz'
WHERE e.statut = 'TERMINE';

-- ═══════════════════════════════════════════════════════════════════
-- HISTORIQUE (timeline du centre de commandement)
-- ═══════════════════════════════════════════════════════════════════

INSERT IGNORE INTO historique_evenement (evenement_id, user_id, action, observation, ip_address)
SELECT e.id, @wilaya_id, 'programme', 'Événement programmé et publié par la Wilaya', '127.0.0.1'
FROM evenements e WHERE e.statut = 'PROGRAMME'
  AND e.adresse IN ('Cité 2000 logements, Birkhadem', 'Avenue du Stade, Bordj El Kiffan', 'Place des Martyrs, Alger Centre');

INSERT IGNORE INTO historique_evenement (evenement_id, user_id, action, observation, ip_address)
SELECT e.id, @wilaya_id, 'termine', 'Opération clôturée par la Wilaya', '127.0.0.1'
FROM evenements e WHERE e.statut = 'TERMINE';

INSERT IGNORE INTO historique_evenement (evenement_id, user_id, action, observation, ip_address)
SELECT e.id, (SELECT id FROM users WHERE email = 'president@elamel.dz'), 'creation', 'Demande créée par l''association', '127.0.0.1'
FROM evenements e WHERE e.association_id = @asso_elamel AND e.adresse IN ('Cité AADL, Hussein Dey');

-- ═══════════════════════════════════════════════════════════════════
-- ALBUMS + ÉVALUATIONS pour deux opérations terminées
-- ═══════════════════════════════════════════════════════════════════

INSERT IGNORE INTO albums (evenement_id, titre, recit)
SELECT e.id, 'Collecte plastiques El Harrach', 'Grande mobilisation des habitants pour le tri sélectif.'
FROM evenements e WHERE e.adresse = 'Rue Bensakrane, El Harrach';

INSERT IGNORE INTO albums (evenement_id, titre, recit)
SELECT e.id, 'Végétalisation Hussein Dey', 'Une centaine d''arbres plantés avec les écoles.'
FROM evenements e WHERE e.adresse = 'Rue Hassiba Ben Bouali, Hussein Dey';

INSERT IGNORE INTO evaluation (evenement_id, association_id, note, description)
SELECT e.id, e.association_id, 5, 'Excellente organisation, les riverains sont ravis.'
FROM evenements e WHERE e.adresse = 'Rue Bensakrane, El Harrach';

INSERT IGNORE INTO evaluation (evenement_id, association_id, note, description)
SELECT e.id, e.association_id, 4, 'Très bon travail, à renouveler au printemps.'
FROM evenements e WHERE e.adresse = 'Rue Hassiba Ben Bouali, Hussein Dey';
