-- Contrôles CMS : navbar, footer et carte de la landing.
-- INSERT IGNORE : ne pas écraser une valeur déjà enregistrée par l'admin.
INSERT IGNORE INTO landing_settings (cle, valeur, type, groupe) VALUES
('navbar_visible',        '1',    'text', 'navbar'),
('navbar_cta_visible',    '1',    'text', 'navbar'),
('footer_show_titles',    '1',    'text', 'footer'),
('footer_show_navigation','1',    'text', 'footer'),
('footer_show_liens',     '1',    'text', 'footer'),
('footer_show_contact',   '1',    'text', 'footer'),
('footer_titre_navigation', '',   'text', 'footer'),
('footer_titre_liens',    '',     'text', 'footer'),
('footer_titre_contact',  '',     'text', 'footer'),
('map_visible',           '1',    'text', 'map'),
('map_heatmap',           '1',    'text', 'map'),
('map_style',             'light','text', 'map'),
('map_zoom',              '',     'text', 'map'),
('map_center_lat',        '',     'text', 'map'),
('map_center_lng',        '',     'text', 'map');
