-- ═══════════════════════════════════════════════════════════════════
--  WILAYA HARMONIA — Migration V2.4 — Statut ANNULE
--  Ajoute le statut ANNULE aux ENUM (événements + journal des transitions),
--  utilisé par l'annulation d'une demande par l'association
--  (EvenementService::changerStatutAnnule, transitions EN_ATTENTE /
--  MODIFICATION_DEMANDEE → ANNULE).
-- ═══════════════════════════════════════════════════════════════════

USE wilaya_harmonia;
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ── 1. Étendre l'ENUM du statut des événements ─────────────────────
ALTER TABLE evenements
    MODIFY COLUMN statut ENUM(
        'EN_ATTENTE',
        'MODIFICATION_DEMANDEE',
        'VALIDÉ',
        'PROGRAMME',
        'QR_GENERE',
        'EN_COURS',
        'TERMINE',
        'REFUSE',
        'ANNULE'
    ) NOT NULL DEFAULT 'EN_ATTENTE';

-- ── 2. Étendre l'ENUM du journal des transitions (immuable) ─────────
ALTER TABLE transition_history
    MODIFY COLUMN statut_avant ENUM(
        'EN_ATTENTE',
        'MODIFICATION_DEMANDEE',
        'VALIDÉ',
        'PROGRAMME',
        'QR_GENERE',
        'EN_COURS',
        'TERMINE',
        'REFUSE',
        'ANNULE'
    ) NOT NULL;

ALTER TABLE transition_history
    MODIFY COLUMN statut_apres ENUM(
        'EN_ATTENTE',
        'MODIFICATION_DEMANDEE',
        'VALIDÉ',
        'PROGRAMME',
        'QR_GENERE',
        'EN_COURS',
        'TERMINE',
        'REFUSE',
        'ANNULE'
    ) NOT NULL;
