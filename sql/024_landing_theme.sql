-- ═══════════════════════════════════════════════════════════════════
--  WILAYA HARMONIA — Landing Theme Personalization
--  Thèmes prédéfinis + personnalisation avancée des couleurs
--  ═══════════════════════════════════════════════════════════════════

INSERT INTO landing_settings (cle, valeur, type, groupe) VALUES
('theme_name',           'vert',          'text',  'theme'),
('theme_primary',        '#16a34a',        'color', 'theme'),
('theme_primary_hover',  '#15803d',        'color', 'theme'),
('theme_secondary',      '#22c55e',        'color', 'theme'),
('theme_tertiary',       '#0ea5e9',        'color', 'theme'),
('theme_accent_glow',    '#22c55e',        'color', 'theme'),
('theme_hero_gradient_1','rgba(22,163,74,0.16)','color', 'theme'),
('theme_hero_gradient_2','rgba(34,197,94,0.08)','color', 'theme'),
('theme_hero_gradient_3','rgba(16,185,129,0.10)','color', 'theme'),
('theme_navbar_bg',      'rgba(255,255,255,0.65)','color', 'theme'),
('theme_navbar_bg_scrolled','rgba(255,255,255,0.85)','color', 'theme'),
('theme_footer_bg',      '#ffffff',        'color', 'theme'),
('theme_footer_text',    '#475569',        'color', 'theme'),
('theme_border_radius',  '18px',           'text',  'theme'),
('theme_font_sans',      'Inter, system-ui, -apple-system, sans-serif', 'text', 'theme'),
('theme_font_heading',   'Inter, system-ui, -apple-system, sans-serif', 'text', 'theme'),
('theme_rounded_corners', '1',              'text',  'theme')
ON DUPLICATE KEY UPDATE valeur = VALUES(valeur);

-- Thèmes prédéfinis (stockés en JSON pour un rappel rapide)
INSERT INTO landing_settings (cle, valeur, type, groupe) VALUES
('theme_presets', '[{"name":"vert","label":"Vert environnement","colors":{"primary":"#16a34a","primary_hover":"#15803d","secondary":"#22c55e","tertiary":"#0ea5e9","accent_glow":"#22c55e","hero_gradient_1":"rgba(22,163,74,0.16)","hero_gradient_2":"rgba(34,197,94,0.08)","hero_gradient_3":"rgba(16,185,129,0.10)"}},{"name":"océan","label":"Bleu océan","colors":{"primary":"#0ea5e9","primary_hover":"#0283ca","secondary":"#22d3ee","tertiary":"#6366f1","accent_glow":"#22d3ee","hero_gradient_1":"rgba(14,165,233,0.16)","hero_gradient_2":"rgba(34,211,238,0.08)","hero_gradient_3":"rgba(99,102,241,0.10)"}},{"name":"pivoine","label":"Pivoine (rose/violet)","colors":{"primary":"#db27b2","primary_hover":"#a21caf","secondary":"#ec4899","tertiary":"#a855f7","accent_glow":"#ec4899","hero_gradient_1":"rgba(219,39,178,0.16)","hero_gradient_2":"rgba(236,72,153,0.08)","hero_gradient_3":"rgba(168,85,247,0.10)"}}]', 'json', 'theme')
ON DUPLICATE KEY UPDATE valeur = VALUES(valeur);
