-- Modèles de demande réutilisables pour les associations.
-- Permettent de pré-remplir un formulaire de demande d'événement (Lot 4).
CREATE TABLE IF NOT EXISTS demande_modeles (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    association_id INT NOT NULL,
    nom            VARCHAR(120) NOT NULL,
    description    TEXT NULL,
    commune_id     INT NULL,
    adresse        VARCHAR(255) NULL,
    capacite       INT NULL,
    informations   TEXT NULL,
    anomalies      TEXT NULL COMMENT 'JSON: [anomalie_id, ...]',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (association_id) REFERENCES associations(id) ON DELETE CASCADE,
    KEY idx_demande_modeles_assoc (association_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
