-- LOT 5 : parcours EPIC complet (acceptation / refus, agenda, preuves, cloture)
-- Etend la table evenement_epic pour couvrir le cycle de vie d'une intervention.

ALTER TABLE evenement_epic
  ADD COLUMN accepte enum('EN_ATTENTE','ACCEPTE','REFUSE') NOT NULL DEFAULT 'EN_ATTENTE' AFTER statut,
  ADD COLUMN date_acceptation timestamp NULL DEFAULT NULL AFTER accepte,
  ADD COLUMN motif_refus varchar(255) DEFAULT NULL AFTER date_acceptation,
  ADD COLUMN date_debut_reel timestamp NULL DEFAULT NULL AFTER motif_refus,
  ADD COLUMN date_fin_reel timestamp NULL DEFAULT NULL AFTER date_debut_reel,
  ADD COLUMN cloture enum('OUVERTE','CLOTUREE') NOT NULL DEFAULT 'OUVERTE' AFTER date_fin_reel,
  ADD COLUMN date_cloture timestamp NULL DEFAULT NULL AFTER cloture,
  ADD COLUMN rapport text DEFAULT NULL AFTER date_cloture;

-- Preuves avant / apres d'une intervention (photos).
CREATE TABLE IF NOT EXISTS epic_preuves (
  id int unsigned NOT NULL AUTO_INCREMENT,
  evenement_epic_id int(10) unsigned NOT NULL,
  type enum('AVANT','APRES') NOT NULL,
  fichier varchar(255) NOT NULL,
  type_mime varchar(120) DEFAULT NULL,
  taille int(10) unsigned DEFAULT NULL,
  uploaded_by int(11) DEFAULT NULL,
  created_at timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  KEY idx_epic_preuves_ee (evenement_epic_id),
  CONSTRAINT fk_epic_preuves_ee FOREIGN KEY (evenement_epic_id) REFERENCES evenement_epic (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Les interventions deja assumees (encours / terminees / anomalie) sont considerees acceptees.
UPDATE evenement_epic
SET accepte = 'ACCEPTE',
    date_acceptation = COALESCE(date_affectation, CURRENT_TIMESTAMP)
WHERE statut IN ('EN_COURS', 'TERMINE', 'ANOMALIE');
