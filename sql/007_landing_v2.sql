-- ═══════════════════════════════════════════════════════════════════
--  WILAYA HARMONIA — Landing V2 (CMS complet)
--  Étapes "Comment ça marche", ordre/visibilité des sections,
--  réseaux sociaux du footer.
-- ═══════════════════════════════════════════════════════════════════

INSERT INTO landing_settings (cle, valeur, type, groupe) VALUES
('fonctionnement_etapes', '[
  {"icone":"mdi-magnify","titre_fr":"Signalez","titre_ar":"بلّغ","texte_fr":"Décrivez l''anomalie ou proposez une action citoyenne.","texte_ar":"صف الخلل أو اقترح عملاً مواطنياً."},
  {"icone":"mdi-calendar-check","titre_fr":"Organisez","titre_ar":"نظّم","texte_fr":"La Wilaya programme l''opération et affecte les EPIC compétentes.","texte_ar":"تستقبل الولاية وتبرمج العملية وتكلف المؤسسات العمومية المختصة."},
  {"icone":"mdi-hand-heart","titre_fr":"Participez","titre_ar":"شارك","texte_fr":"Scannez le QR code, participez et gagnez des points.","texte_ar":"امسح رمز QR، شارك واكسب النقاط."}
]', 'json', 'fonctionnement'),
    ('sections_order', '["actualites","apropos","fonctionnement","anomalies","albums","temoignages","partenaires","galerie","before_after","faq"]', 'json', 'general'),
    ('section_actualites_visible', '1', 'text', 'general'),
    ('section_apropos_visible', '1', 'text', 'general'),
    ('section_fonctionnement_visible', '1', 'text', 'general'),
    ('section_anomalies_visible', '1', 'text', 'general'),
    ('section_albums_visible', '1', 'text', 'general'),
    ('section_temoignages_visible', '1', 'text', 'general'),
    ('section_partenaires_visible', '1', 'text', 'general'),
    ('section_galerie_visible', '1', 'text', 'general'),
    ('section_before_after_visible', '1', 'text', 'general'),
    ('section_faq_visible', '1', 'text', 'general'),
('social_facebook', 'https://facebook.com/wilayaharmonia', 'text', 'footer'),
('social_instagram', 'https://instagram.com/wilayaharmonia', 'text', 'footer'),
('social_youtube', 'https://youtube.com/@wilayaharmonia', 'text', 'footer'),
('social_x', 'https://x.com/wilayaharmonia', 'text', 'footer')
ON DUPLICATE KEY UPDATE valeur = VALUES(valeur);
