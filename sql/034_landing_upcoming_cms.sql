-- Contrôles CMS : section « Prochains événements » de la landing.
-- INSERT IGNORE : ne pas écraser une valeur déjà enregistrée par l'admin.
INSERT IGNORE INTO landing_settings (cle, valeur, type, groupe) VALUES
('general_upcoming_visible', '1', 'text', 'general'),
('general_upcoming_max',     '3', 'text', 'general');
