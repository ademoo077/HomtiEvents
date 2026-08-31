-- ═══════════════════════════════════════════════════════════════════
--  039 — Fix données corrompues + messaging wilaya↔association
-- ═══════════════════════════════════════════════════════════════════

USE wilaya_harmonia;
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ── 1. Backfill GPS events depuis commune ─────────────────────────
UPDATE evenements e
JOIN commune c ON c.id = e.commune_id
SET e.latitude  = c.latitude,
    e.longitude = c.longitude
WHERE e.latitude IS NULL
  AND c.latitude IS NOT NULL
  AND e.deleted_at IS NULL;

-- ── 2. Backfill GPS anomalies depuis event GPS ────────────────────
UPDATE anomalies_evenement ae
JOIN evenements e ON e.id = ae.evenement_id
SET ae.latitude  = e.latitude,
    ae.longitude = e.longitude
WHERE ae.latitude IS NULL
  AND e.latitude IS NOT NULL;

-- ── 3. Créer anomaly_assignments pour tous les événements existants ──
INSERT IGNORE INTO anomaly_assignments (evenement_id, anomalie_id, epic_id, auto_routed)
SELECT ae.evenement_id,
       ae.anomalie_id,
       COALESCE(
           (SELECT r.epic_id FROM routing_rules r
            WHERE r.anomalie_id = ae.anomalie_id AND r.actif = 1
            ORDER BY r.priorite DESC LIMIT 1),
           e.assigned_org_id,
           0
       ) AS epic_id,
       CASE WHEN e.assigned_org_id IS NOT NULL THEN 1 ELSE 0 END
FROM anomalies_evenement ae
JOIN evenements e ON e.id = ae.evenement_id
WHERE e.deleted_at IS NULL;

-- ── 4. Table messaging wilaya↔association ─────────────────────────
CREATE TABLE IF NOT EXISTS event_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evenement_id INT NOT NULL,
    sender_id INT NULL COMMENT 'user_id de l\'expéditeur',
    sender_role VARCHAR(30) NULL COMMENT 'wilaya / association / epic',
    message TEXT NOT NULL,
    is_internal TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Note interne visible uniquement par Wilaya',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evenement_id) REFERENCES evenements(id) ON DELETE CASCADE,
    INDEX idx_msg_evenement (evenement_id),
    INDEX idx_msg_sender (sender_id),
    INDEX idx_msg_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 5. Table checklist par événement ──────────────────────────────
CREATE TABLE IF NOT EXISTS event_checklist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evenement_id INT NOT NULL,
    libelle VARCHAR(255) NOT NULL,
    fait TINYINT(1) NOT NULL DEFAULT 0,
    fait_by INT NULL,
    fait_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evenement_id) REFERENCES evenements(id) ON DELETE CASCADE,
    INDEX idx_check_evenement (evenement_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Checklist par défaut pour les événements existants PROGRAMME+
INSERT INTO event_checklist (evenement_id, libelle, fait)
SELECT e.id, item.libelle, 0
FROM evenements e
CROSS JOIN (
    SELECT 'Coordonnées GPS vérifiées' AS libelle UNION ALL
    SELECT 'Anomalies géolocalisées' UNION ALL
    SELECT 'EPIC contactée' UNION ALL
    SELECT 'QR code validé' UNION ALL
    SELECT 'Participants notifiés'
) AS item
WHERE e.statut IN ('PROGRAMME', 'QR_GENERE', 'EN_COURS')
  AND e.deleted_at IS NULL
  AND NOT EXISTS (SELECT 1 FROM event_checklist ec WHERE ec.evenement_id = e.id);

-- ── 6. SLA countdown sur evenements (deadline_at recalculé) ────────
UPDATE evenements
SET deadline_at = TIMESTAMPADD(DAY, 7, COALESCE(start_at, CONCAT(date_evenement, ' ', COALESCE(heure, '09:00:00'))))
WHERE deadline_at IS NULL
  AND (start_at IS NOT NULL OR date_evenement IS NOT NULL)
  AND deleted_at IS NULL;
