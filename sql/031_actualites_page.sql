-- ============================================================
-- 031 — Page "Actualités & événements à venir" (publique)
--
-- Enrichit landing_news pour la page dédiée /actualites :
--   - statut  brouillon / publie  (préparation d'annonce sans
--     publication immédiate sur le site public),
--   - evenement_id  (lien optionnel vers un événement structuré
--     de la table evenements — approche hybride : événements
--     auto-synchronisés depuis evenements + saisie libre),
--   - deleted_at  (suppression douce, cohérente avec users/evenements).
-- Idempotent (ADD COLUMN IF NOT EXISTS / UPDATE gardé, MariaDB 10.6+).
-- ============================================================

ALTER TABLE landing_news
    ADD COLUMN IF NOT EXISTS statut ENUM('brouillon', 'publie') NOT NULL DEFAULT 'publie' AFTER actif;

ALTER TABLE landing_news
    ADD COLUMN IF NOT EXISTS evenement_id INT(11) NULL DEFAULT NULL AFTER type;

ALTER TABLE landing_news
    ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at;

CREATE INDEX IF NOT EXISTS idx_landing_news_statut ON landing_news (statut);
CREATE INDEX IF NOT EXISTS idx_landing_news_deleted_at ON landing_news (deleted_at);
CREATE UNIQUE INDEX IF NOT EXISTS uq_landing_news_evenement ON landing_news (evenement_id);

-- Migration de l'existant : un élément inactif devient un brouillon.
UPDATE landing_news SET statut = 'brouillon' WHERE actif = 0 AND statut = 'publie';

-- ============================================================
-- Données démo (garde sur date_event IS NULL pour idempotence) :
-- dates et lieux manquants pour le rendu de la page publique.
-- ============================================================
UPDATE landing_news SET
    date_event     = '2026-08-14',
    lieu           = 'Maison de la Culture Mouloud Mammeri, Tizi Ouzou',
    lieu_ar        = 'دار الثقافة مولود معمري، تيزي وزو',
    description_fr = 'Conférence citoyenne sur la préservation de l''environnement urbain : enjeux, actions menées et perspectives de la wilaya.',
    description_ar = 'ندوة مواطنية حول الحفاظ على البيئة الحضرية: الرهانات والإجراءات المتخذة وآفاق الولاية.'
WHERE id = 1 AND date_event IS NULL;

UPDATE landing_news SET
    date_event     = '2026-08-16',
    lieu           = 'Salle des fêtes, Birkhadem',
    lieu_ar        = 'قاعة الحفلات، بئر مراد رايس',
    description_fr = 'Le forum annuel des associations de la wilaya : bilan des initiatives, partage d''expériences et ateliers de coopération.',
    description_ar = 'المنتدى السنوي لجمعيات الولاية: حصيلة المبادرات وتبادل التجارب وورشات التعاون.'
WHERE id = 2 AND date_event IS NULL;

UPDATE landing_news SET
    date_event     = '2026-08-18',
    lieu           = 'Centre culturel, El Harrach',
    lieu_ar        = 'المركز الثقافي، الحراش',
    description_fr = 'Atelier de sensibilisation au tri sélectif et au recyclage, ouvert aux familles et aux écoles de la wilaya.',
    description_ar = 'ورشة تحسيسية حول الفرز الانتقائي وإعادة التدوير، مفتوحة للعائلات ومدارس الولاية.'
WHERE id = 3 AND date_event IS NULL;

UPDATE landing_news SET
    date_event     = '2026-08-25',
    lieu           = 'Esplanade de la Grande Poste, Alger',
    lieu_ar        = 'ساحة البريد المركزي، الجزائر',
    description_fr = 'Festival culturel des arts populaires algériens : musique, artisanat et spectacles de rue.',
    description_ar = 'مهرجان ثقافي للفنون الشعبية الجزائرية: موسيقى وحرف وعروض الشارع.'
WHERE id = 4 AND date_event IS NULL;
