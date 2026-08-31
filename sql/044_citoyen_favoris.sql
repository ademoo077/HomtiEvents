-- LOT 6 : parcours citoyen - favoris d'evenements.
-- Le citoyen peut sauvegarder des evenements pour les retrouver plus tard.

CREATE TABLE IF NOT EXISTS citoyen_favoris (
  user_id int(11) NOT NULL,
  evenement_id int(11) NOT NULL,
  created_at timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (user_id, evenement_id),
  KEY idx_citoyen_favoris_ev (evenement_id),
  KEY idx_citoyen_favoris_user (user_id),
  CONSTRAINT fk_citoyen_favoris_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_citoyen_favoris_ev FOREIGN KEY (evenement_id) REFERENCES evenements (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
