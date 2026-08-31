-- ═══════════════════════════════════════════════════════════════════
--  WILAYA HARMONIA — Albums & détails d'événements
--  La section "avant/après" est remplacée par la section albums
--  (album de photos + détails des événements).
--  Actualités & événements à venir : sections actives par défaut.
--  Thème appliqué : vert forêt / or (charte "Wilaya Harmonia").
--  Idempotent : relançable sans effet de bord.
-- ═══════════════════════════════════════════════════════════════════

-- Ordre des sections : before_after retiré, albums/actualités conservés
UPDATE landing_settings SET valeur = '["actualites","apropos","fonctionnement","anomalies","albums","temoignages","partenaires","galerie","faq"]'
 WHERE cle = 'sections_order';

-- Visibilité des sections
UPDATE landing_settings SET valeur = '0' WHERE cle = 'section_before_after_visible';
UPDATE landing_settings SET valeur = '1' WHERE cle = 'section_albums_visible';
UPDATE landing_settings SET valeur = '1' WHERE cle = 'section_actualites_visible';
UPDATE landing_settings SET valeur = '1' WHERE cle = 'section_galerie_visible';

-- Thème vert forêt / or
UPDATE landing_settings SET valeur = 'foret_or'    WHERE cle = 'theme_name';
UPDATE landing_settings SET valeur = '#1A4D3E'     WHERE cle = 'theme_primary';
UPDATE landing_settings SET valeur = '#14392E'     WHERE cle = 'theme_primary_hover';
UPDATE landing_settings SET valeur = '#D4AF37'     WHERE cle = 'theme_secondary';
UPDATE landing_settings SET valeur = '#2E6E5C'     WHERE cle = 'theme_tertiary';
UPDATE landing_settings SET valeur = '#D4AF37'     WHERE cle = 'theme_accent_glow';
UPDATE landing_settings SET valeur = 'rgba(26,77,62,0.20)'  WHERE cle = 'theme_hero_gradient_1';
UPDATE landing_settings SET valeur = 'rgba(212,175,55,0.08)' WHERE cle = 'theme_hero_gradient_2';
UPDATE landing_settings SET valeur = 'rgba(46,110,92,0.12)'  WHERE cle = 'theme_hero_gradient_3';
UPDATE landing_settings SET valeur = 'rgba(15,43,34,0.92)'   WHERE cle = 'theme_navbar_bg';
UPDATE landing_settings SET valeur = 'rgba(10,30,24,0.97)'   WHERE cle = 'theme_navbar_bg_scrolled';
UPDATE landing_settings SET valeur = '#0F2B22'      WHERE cle = 'theme_footer_bg';
UPDATE landing_settings SET valeur = '#C9D6CE'      WHERE cle = 'theme_footer_text';
