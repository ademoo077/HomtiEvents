-- ═══════════════════════════════════════════════════════════════════
--  WILAYA HARMONIA — Tableau de bord EPIC (v2.3)
--  - transition_history.motif existe déjà (migration 008) : aucun
--    changement de schéma requis pour les motifs d'anomalie.
--  - Seed : événements en anomalie (MODIFICATION_DEMANDEE / REFUSE)
--    affectés aux EPIC + journal de transitions, pour alimenter la
--    répartition des anomalies du dashboard EPIC.
-- ═══════════════════════════════════════════════════════════════════

USE wilaya_harmonia;
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

SET @wilaya_id := (SELECT id FROM users WHERE email = 'wilaya@wilaya-harmonia.dz');
SET @centre    := (SELECT id FROM commune WHERE nom = 'Alger-Centre');
SET @harrach   := (SELECT id FROM commune WHERE nom = 'El Harrach');
SET @birkhadem := (SELECT id FROM commune WHERE nom = 'Birkhadem');

-- ── 1. MODIFICATION_DEMANDEE (EPIC ADE) ────────────────────────────
INSERT INTO evenements
(commune_id, adresse, association_id, description, statut, motif_refus, date_evenement, heure, deadline_at)
SELECT @centre, 'Rue des Fusillés, Alger-Centre', id,
       'Campagne de sensibilisation sur la consommation d''eau.',
       'MODIFICATION_DEMANDEE',
       'Date invalide : conflit avec la fête nationale',
       DATE_ADD(CURDATE(), INTERVAL 9 DAY), '09:00:00',
       DATE_ADD(NOW(), INTERVAL 9 DAY)
FROM associations WHERE nom = 'Association El Amel'
AND NOT EXISTS (SELECT 1 FROM evenements e WHERE e.adresse = 'Rue des Fusillés, Alger-Centre' AND e.statut = 'MODIFICATION_DEMANDEE');

SET @ev_modif1 := LAST_INSERT_ID();

INSERT IGNORE INTO evenement_epic (evenement_id, epic_id)
SELECT @ev_modif1, id FROM epic WHERE nom = 'ADE';

-- ── 2. MODIFICATION_DEMANDEE (EPIC NETCOM) ──────────────────────────
INSERT INTO evenements
(commune_id, adresse, association_id, description, statut, motif_refus, date_evenement, heure, deadline_at)
SELECT @harrach, 'Cité 5 Juillet, El Harrach', id,
       'Opération de tri sélectif et de collecte des encombrants.',
       'MODIFICATION_DEMANDEE',
       'Lieu inexact : adresse incomplète (bâtiment non précisé)',
       DATE_ADD(CURDATE(), INTERVAL 12 DAY), '08:00:00',
       DATE_ADD(NOW(), INTERVAL 12 DAY)
FROM associations WHERE nom = 'Association Environnement Vert'
AND NOT EXISTS (SELECT 1 FROM evenements e WHERE e.adresse = 'Cité 5 Juillet, El Harrach' AND e.statut = 'MODIFICATION_DEMANDEE');

SET @ev_modif2 := LAST_INSERT_ID();

INSERT IGNORE INTO evenement_epic (evenement_id, epic_id)
SELECT @ev_modif2, id FROM epic WHERE nom = 'NETCOM';

-- ── 3. REFUSE (EPIC ASROUT) ─────────────────────────────────────────
INSERT INTO evenements
(commune_id, adresse, association_id, description, statut, motif_refus, date_evenement, heure, deadline_at)
SELECT @centre, 'Chemin du Créneau, Alger-Centre', id,
       'Réfection du trottoir et pose de bordures.',
       'REFUSE',
       'Pièce manquante : copie de l''agrément non fournie',
       DATE_ADD(CURDATE(), INTERVAL 15 DAY), '07:30:00',
       DATE_ADD(NOW(), INTERVAL 15 DAY)
FROM associations WHERE nom = 'Association El Amel'
AND NOT EXISTS (SELECT 1 FROM evenements e WHERE e.adresse = 'Chemin du Créneau, Alger-Centre' AND e.statut = 'REFUSE');

SET @ev_refuse1 := LAST_INSERT_ID();

INSERT IGNORE INTO evenement_epic (evenement_id, epic_id)
SELECT @ev_refuse1, id FROM epic WHERE nom = 'ASROUT';

-- ── 4. REFUSE (EPIC EDEVAL) ─────────────────────────────────────────
INSERT INTO evenements
(commune_id, adresse, association_id, description, statut, motif_refus, date_evenement, heure, deadline_at)
SELECT @birkhadem, 'Allée des Platanes, Birkhadem', id,
       'Journée de plantation d''arbres sur l''avenue principale.',
       'REFUSE',
       'Dossier incomplet : statut juridique de l''association non fourni',
       DATE_ADD(CURDATE(), INTERVAL 18 DAY), '09:30:00',
       DATE_ADD(NOW(), INTERVAL 18 DAY)
FROM associations WHERE nom = 'Association Environnement Vert'
AND NOT EXISTS (SELECT 1 FROM evenements e WHERE e.adresse = 'Allée des Platanes, Birkhadem' AND e.statut = 'REFUSE');

SET @ev_refuse2 := LAST_INSERT_ID();

INSERT IGNORE INTO evenement_epic (evenement_id, epic_id)
SELECT @ev_refuse2, id FROM epic WHERE nom = 'EDEVAL';

-- ── 5. Journal des transitions (immuable) ───────────────────────────
INSERT INTO transition_history (evenement_id, statut_avant, statut_apres, user_id, motif)
VALUES
(@ev_modif1,  'EN_ATTENTE', 'MODIFICATION_DEMANDEE', @wilaya_id, 'Date invalide : conflit avec la fête nationale'),
(@ev_modif2,  'EN_ATTENTE', 'MODIFICATION_DEMANDEE', @wilaya_id, 'Lieu inexact : adresse incomplète (bâtiment non précisé)'),
(@ev_refuse1, 'EN_ATTENTE', 'REFUSE',                @wilaya_id, 'Pièce manquante : copie de l''agrément non fournie'),
(@ev_refuse2, 'EN_ATTENTE', 'REFUSE',                @wilaya_id, 'Dossier incomplet : statut juridique de l''association non fourni');
