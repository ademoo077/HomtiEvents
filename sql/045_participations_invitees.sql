-- Lot 6b — Participation par scan QR sans compte (invités)
-- Table des participations « invité » (pas de compte) :

CREATE TABLE IF NOT EXISTS `participations_invitees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `evenement_id` int(11) NOT NULL,
  `qr_token` varchar(64) DEFAULT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `telephone` varchar(30) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pi_evenement` (`evenement_id`),
  CONSTRAINT `fk_participations_invitees_ev`
    FOREIGN KEY (`evenement_id`) REFERENCES `evenements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
