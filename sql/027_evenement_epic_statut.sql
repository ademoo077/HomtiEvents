-- ============================================================
-- 027 — Statut d'intervention EPIC + identifiant sur evenement_epic
--
-- Les interventions EPIC (evenement_epic) n'avaient ni clé id ni statut
-- alors que EpicController::updateStatut() et ControlCenter::epicValidate()
-- référençaient ee.id / ee.statut (erreur SQLSTATE 42S22 au runtime).
-- Ajout d'un identifiant AUTO_INCREMENT (PK) et d'un statut d'intervention :
--   AFFECTE → EN_COURS → TERMINE | ANOMALIE  (défaut : AFFECTE)
-- Idempotent (MariaDB 10.6+ / MySQL : ADD COLUMN IF NOT EXISTS MariaDB).
-- ============================================================

ALTER TABLE evenement_epic
    DROP PRIMARY KEY,
    ADD COLUMN IF NOT EXISTS id INT UNSIGNED NOT NULL AUTO_INCREMENT FIRST,
    ADD PRIMARY KEY (id);

ALTER TABLE evenement_epic
    ADD COLUMN IF NOT EXISTS statut ENUM('AFFECTE', 'EN_COURS', 'TERMINE', 'ANOMALIE')
        NOT NULL DEFAULT 'AFFECTE' AFTER epic_id;

-- Unicité de l'affectation conservée (nécessaire aussi aux FK existantes).
ALTER TABLE evenement_epic ADD UNIQUE KEY IF NOT EXISTS uq_evenement_epic (evenement_id, epic_id);
