-- ═══════════════════════════════════════════════════════════════════
--  021 — Routage automatique des réclamations vers les organisations
--  -------------------------------------------------------------------
--  Tables/colonnes ajoutées :
--    evenements.assigned_org_id   INT NULL → epic.id  (organisation routée)
--    routing_rules                règles catégorie→organisation
--                                 (anomalie_id + daira optionnelle ca_id)
--    routing_log                  journal de routage (old/new/rule)
--    routing_alertes              alertes admin (fallback = aucune règle)
--  Seeds :
--    1. Règles initiales depuis epic_anomalies (compétences existantes).
--    2. Compte utilisateur pour chaque EPIC sans compte (idempotent).
--  ═══════════════════════════════════════════════════════════════════

USE wilaya_harmonia;
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ── 1. COLONNE assigned_org_id sur evenements ─────────────────────
ALTER TABLE evenements
    ADD COLUMN IF NOT EXISTS assigned_org_id INT(11) NULL AFTER association_id;

CREATE INDEX IF NOT EXISTS idx_evenements_assigned_org ON evenements (assigned_org_id);

-- ── 2. RÈGLES DE ROUTAGE (organisation_rules adaptée) ─────────────
CREATE TABLE IF NOT EXISTS routing_rules (
    id          INT(11)      NOT NULL AUTO_INCREMENT,
    anomalie_id INT(11)      NULL COMMENT 'Type de réclamation (null = toutes)',
    ca_id       INT(11)      NULL COMMENT 'Daira optionnelle (null = toutes)',
    epic_id     INT(11)      NOT NULL COMMENT 'Organisation cible',
    priorite    INT(11)      NOT NULL DEFAULT 0,
    actif       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_routing_rules_anomalie (anomalie_id),
    KEY idx_routing_rules_ca (ca_id),
    KEY idx_routing_rules_epic (epic_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3. JOURNAL DE ROUTAGE ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS routing_log (
    id           INT(11)      NOT NULL AUTO_INCREMENT,
    evenement_id INT(11)      NOT NULL,
    old_org_id   INT(11)      NULL,
    new_org_id   INT(11)      NULL,
    rule_matched VARCHAR(50)  NOT NULL DEFAULT 'aucune'
                 COMMENT 'anomalie | daira | manuel | aucune',
    detail       VARCHAR(255) NULL,
    created_at   TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_routing_log_evenement (evenement_id),
    KEY idx_routing_log_org (new_org_id),
    KEY idx_routing_log_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 4. ALERTES ADMIN (fallback : aucune règle trouvée) ────────────
CREATE TABLE IF NOT EXISTS routing_alertes (
    id           INT(11)      NOT NULL AUTO_INCREMENT,
    evenement_id INT(11)      NULL,
    motif        VARCHAR(255) NULL,
    traite       TINYINT(1)   NOT NULL DEFAULT 0,
    traite_par   INT(11)      NULL,
    traite_at    TIMESTAMP    NULL,
    created_at   TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_routing_alertes_evenement (evenement_id),
    KEY idx_routing_alertes_traite (traite)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 5. SEED : règles initiales depuis les compétences existantes ──
INSERT INTO routing_rules (anomalie_id, ca_id, epic_id, priorite, actif)
SELECT ea.anomalie_id, NULL, ea.epic_id, 10, 1
FROM epic_anomalies ea
WHERE NOT EXISTS (
    SELECT 1 FROM routing_rules r
    WHERE r.anomalie_id = ea.anomalie_id AND r.ca_id IS NULL AND r.epic_id = ea.epic_id
);

-- ── 6. SEED : compte utilisateur pour chaque EPIC sans compte ─────
-- Comptes : prenom = 'Directeur', email = <slug(nom)>@epic.dz, mdp = Harmonia@2026.
INSERT INTO users (nom, prenom, email, password, role_user, epic_id, is_active, status)
SELECT e.nom, 'Directeur',
       CONCAT(REPLACE(LOWER(e.nom), ' ', ''), '@epic.dz'),
       '$2y$12$DwBhiIAvhHO1rD.rDq68P.M94dSNtpaI3w9IFWUTVxKXb7Z5Tjb16', -- Hash bcrypt de 'Harmonia@2026'
       'epic', e.id, 1, 'actif'
FROM epic e
WHERE NOT EXISTS (
    SELECT 1 FROM users u WHERE u.epic_id = e.id AND u.role_user = 'epic' AND u.email LIKE CONCAT('%', '@epic.dz')
);

-- Lien RBAC des comptes EPIC
INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id
FROM users u
JOIN roles r ON r.nom = 'epic'
WHERE u.role_user = 'epic'
  AND NOT EXISTS (SELECT 1 FROM user_roles ur WHERE ur.user_id = u.id AND ur.role_id = r.id);

-- ── 7. BACKFILL : reflète les affectations manuelles simples ──────────
-- Les événements liés à une seule EPIC (via evenement_epic) voient leur
-- organisation assignée automatiquement. Les événements multi-EPIC restent
-- à affecter manuellement. Idempotent.
UPDATE evenements e
JOIN evenement_epic ee ON ee.evenement_id = e.id
SET e.assigned_org_id = ee.epic_id
WHERE e.assigned_org_id IS NULL
  AND e.id IN (SELECT DISTINCT evenement_id FROM evenement_epic)
  AND NOT EXISTS (SELECT 1 FROM evenement_epic ee2 WHERE ee2.evenement_id = e.id AND ee2.epic_id <> ee.epic_id);
