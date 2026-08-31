-- ═══════════════════════════════════════════════════════════════
-- 036 — QR invité (capacité), rappels 24h, vignettes photos
-- Rejouable : ALTER conditionnels uniquement.
-- ═══════════════════════════════════════════════════════════════

-- 1) Capacité (quota de passages) par événement
SET @has_cap := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'evenements' AND COLUMN_NAME = 'capacite'
);
SET @ddl := IF(@has_cap = 0,
    'ALTER TABLE evenements ADD COLUMN capacite INT UNSIGNED NULL DEFAULT NULL AFTER heure',
    'SELECT 1');
PREPARE st FROM @ddl;
EXECUTE st;
DEALLOCATE PREPARE st;

-- 2) Marqueur de rappel 24h envoyé
SET @has_rap := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'evenements' AND COLUMN_NAME = 'rappel_envoye_at'
);
SET @ddl := IF(@has_rap = 0,
    'ALTER TABLE evenements ADD COLUMN rappel_envoye_at DATETIME NULL DEFAULT NULL AFTER capacite',
    'SELECT 1');
PREPARE st FROM @ddl;
EXECUTE st;
DEALLOCATE PREPARE st;

-- 3) Vignette (miniature) par photo
SET @has_th := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'photos' AND COLUMN_NAME = 'thumbnail'
);
SET @ddl := IF(@has_th = 0,
    'ALTER TABLE photos ADD COLUMN thumbnail VARCHAR(255) NULL DEFAULT NULL AFTER image',
    'SELECT 1');
PREPARE st FROM @ddl;
EXECUTE st;
DEALLOCATE PREPARE st;
