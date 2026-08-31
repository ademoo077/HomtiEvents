-- ═══════════════════════════════════════════════════════════════
-- 035 — Badges : colonne couleur + badges de participation citoyenne
-- Rejouable : ALTER conditionnel, UPDATE/INSERT avec garde-fous.
-- ═══════════════════════════════════════════════════════════════

-- 1) Colonne couleur (rejouable)
SET @has_col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'badges' AND COLUMN_NAME = 'couleur'
);
SET @ddl := IF(@has_col = 0,
    'ALTER TABLE badges ADD COLUMN couleur VARCHAR(50) NULL DEFAULT NULL AFTER points_recompense',
    'SELECT 1');
PREPARE st FROM @ddl;
EXECUTE st;
DEALLOCATE PREPARE st;

-- 2) Couleurs des badges existants (ne modifie pas une valeur déjà définie)
UPDATE badges SET couleur = COALESCE(couleur, '#2E6E5C') WHERE condition_type = 'first_event';
UPDATE badges SET couleur = COALESCE(couleur, '#1A4D3E') WHERE condition_type = '10_events';
UPDATE badges SET couleur = COALESCE(couleur, '#0F2B22') WHERE condition_type = '50_events';
UPDATE badges SET couleur = COALESCE(couleur, '#D4AF37') WHERE condition_type = '100_scans';
UPDATE badges SET couleur = COALESCE(couleur, '#B8932C') WHERE condition_type = '1000_scans';

-- 3) Badges de participation citoyenne (rejouable)
INSERT INTO badges (nom, description, icone, condition_type, points_recompense, couleur)
SELECT 'Première Participation', 'Participer à son premier événement de la wilaya', 'fa-user-plus', 'first_participation', 25, '#2E6E5C'
WHERE NOT EXISTS (SELECT 1 FROM badges WHERE condition_type = 'first_participation');

INSERT INTO badges (nom, description, icone, condition_type, points_recompense, couleur)
SELECT 'Citoyen Engagé', 'Participer à 5 événements', 'fa-heart', '5_participations', 50, '#1A4D3E'
WHERE NOT EXISTS (SELECT 1 FROM badges WHERE condition_type = '5_participations');

INSERT INTO badges (nom, description, icone, condition_type, points_recompense, couleur)
SELECT 'Ambassadeur de la Wilaya', 'Participer à 25 événements', 'fa-certificate', '25_participations', 150, '#D4AF37'
WHERE NOT EXISTS (SELECT 1 FROM badges WHERE condition_type = '25_participations');

INSERT INTO badges (nom, description, icone, condition_type, points_recompense, couleur)
SELECT 'Citoyen d''Élite', 'Participer à 50 événements', 'fa-gem', '50_participations', 300, '#B8932C'
WHERE NOT EXISTS (SELECT 1 FROM badges WHERE condition_type = '50_participations');
