-- 041 : Pièces jointes du dossier événement (documents)
-- Permet d'attacher des documents au dossier d'un événement (pièces jointes).

CREATE TABLE IF NOT EXISTS event_documents (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evenement_id INT NOT NULL,
    nom         VARCHAR(191) NOT NULL,
    fichier     VARCHAR(255) NOT NULL,
    type_mime   VARCHAR(120) NULL,
    taille      INT UNSIGNED NULL,
    uploaded_by INT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_event_documents_evenement (evenement_id),
    CONSTRAINT fk_event_documents_evenement
        FOREIGN KEY (evenement_id) REFERENCES evenements (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
