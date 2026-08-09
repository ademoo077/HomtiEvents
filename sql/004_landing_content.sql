-- ═══════════════════════════════════════════════════════════════════
--  WILAYA HARMONIA — Contenu CMS de la landing page
-- ═══════════════════════════════════════════════════════════════════

USE wilaya_harmonia;
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT INTO landing_settings (cle, valeur, type, groupe) VALUES
('hero_titre_fr',       'Ensemble pour notre Wilaya',                'text',  'hero'),
('hero_titre_ar',       'معًا من أجل ولايتنا',                         'text',  'hero'),
('hero_sous_titre_fr',  'Une plateforme citoyenne qui relie la Wilaya, les associations, les EPIC et les citoyens pour des événements d''intérêt public.', 'text', 'hero'),
('hero_sous_titre_ar',  'منصة مواطنة تربط الولاية والجمعيات والمؤسسات العمومية والمواطنين لتنظيم فعاليات تخدم الصالح العام.', 'text', 'hero'),
('hero_image',          '/assets/img/hero.jpg',                      'image', 'hero'),
('cta_primary_fr',      'Découvrir les événements',                  'text',  'hero'),
('cta_primary_ar',      'اكتشف الفعاليات',                           'text',  'hero'),
('cta_secondary_fr',    'Devenir partenaire',                        'text',  'hero'),
('cta_secondary_ar',    'كن شريكًا',                                  'text',  'hero'),
('stats_evenements',    '142',                                       'text',  'stats'),
('stats_associations',  '36',                                        'text',  'stats'),
('stats_epic',          '4',                                         'text',  'stats'),
('stats_participants',  '8412',                                      'text',  'stats'),
('titre_apropos_fr',    'Qu''est-ce que Wilaya Harmonia ?',           'text',  'apropos'),
('titre_apropos_ar',    'ما هي هارمونيا الولاية؟',                     'text',  'apropos'),
('texte_apropos_fr',    'Wilaya Harmonia est la plateforme officielle qui orchestre les actions citoyennes : signalement, organisation, participation et suivi des événements d''intérêt public.', 'text', 'apropos'),
('texte_apropos_ar',    'هارمونيا الولاية هي المنصة الرسمية التي تنسق العمل المواطني: الإبلاغ، التنظيم، المشاركة ومتابعة الفعاليات ذات المصلحة العامة.', 'text', 'apropos'),
('titre_fonctionnement_fr', 'Comment ça fonctionne ?',               'text',  'fonctionnement'),
('titre_fonctionnement_ar', 'كيف يعمل النظام؟',                       'text',  'fonctionnement'),
('contact_email',        'contact@wilaya-harmonia.dz',               'text',  'contact'),
('contact_telephone',    '023 00 00 00',                             'text',  'contact'),
('contact_adresse',      'Siège de la Wilaya, Alger',                'text',  'contact'),
('contact_adresse_ar',   'مقر الولاية، الجزائر العاصمة',               'text',  'contact'),
('footer_titre_fr',      'Wilaya Harmonia — La symphonie citoyenne',  'text',  'footer'),
('footer_titre_ar',      'هارمونيا الولاية — السيمفونية المواطنة',      'text',  'footer'),
('footer_description_fr','Chaque citoyen est un héros, chaque association un catalyseur, chaque EPIC un bouclier.', 'text', 'footer'),
('footer_description_ar','كل مواطن بطل، وكل جمعية حافز، وكل مؤسسة عمومية درع.', 'text', 'footer')
ON DUPLICATE KEY UPDATE valeur = VALUES(valeur);

INSERT INTO landing_faq (question_fr, question_ar, reponse_fr, reponse_ar, ordre, actif) VALUES
('Comment participer à un événement ?',
 'كيف أشارك في فعالية؟',
 'Il suffit de scanner le QR code affiché sur le lieu de l''événement avec votre téléphone après connexion.',
 'يكفي مسح رمز الاستجابة السريعة المعروض في موقع الفعالية بهاتفك بعد تسجيل الدخول.',
 1, TRUE),
('Qui peut créer un événement ?',
 'من يمكنه إنشاء فعالية؟',
 'Seules les associations et comités de quartier validés par la Wilaya peuvent soumettre une demande d''événement.',
 'فقط الجمعيات ولجان الأحياء المعتمدة من الولاية يمكنها تقديم طلب لتنظيم فعالية.',
 2, TRUE),
('Qu''est-ce que gagne un citoyen en participant ?',
 'ماذا يكسب المواطن بالمشاركة؟',
 'Des points de participation, des badges honorifiques et un classement communautaire visible sur la plateforme.',
 'نقاط مشاركة وأوسمة شرفية وترتيب مجتمعي ظاهر على المنصة.',
 3, TRUE),
('Comment devenir une association partenaire ?',
 'كيف أصبح جمعية شريكة؟',
 'Créez un compte association, téléversez votre document d''agrément et attendez la validation de la Wilaya.',
 'أنشئ حساب جمعية، ارفع وثيقة الاعتماد وانتظر موافقة الولاية.',
 4, TRUE),
('Les données personnelles sont-elles protégées ?',
 'هل البيانات الشخصية محمية؟',
 'Oui. Conformité RGPD, chiffrement des mots de passe, RBAC strict et journal d''audit immuable.',
 'نعم. الامتثال لـ RGPD، تشفير كلمات المرور، صلاحيات صارمة وسجل تدقيق غير قابل للتعديل.',
 5, TRUE)
ON DUPLICATE KEY UPDATE question_fr = question_fr;

INSERT INTO landing_testimonials (auteur, role, texte_fr, texte_ar, note, actif) VALUES
('Amina Meziane', 'Citoyenne', 'Grâce à la plateforme, j''ai participé à la plantation de 120 arbres dans mon quartier. Une fierté !', 'بفضل المنصة شاركت في غرس 120 شجرة في حيي. فخر حقيقي!', 5, TRUE),
('Ahmed Benali', 'Président Association El Amel', 'Le processus de validation et de programmation est d''une clarté remarquable. La Wilaya répond rapidement.', 'عملية الموافقة والبرمجة واضحة بشكل ملحوظ. الولاية تستجيب بسرعة.', 5, TRUE),
('Directeur NETCOM', 'EPIC', 'Le filtrage des interventions par compétences nous fait gagner un temps précieux.', 'تصفية التدخلات حسب الاختصاصات توفر لنا وقتًا ثمينًا.', 5, TRUE)
ON DUPLICATE KEY UPDATE auteur = auteur;

INSERT INTO landing_partners (nom, logo, url, ordre, actif) VALUES
('ADE',    '/assets/img/logo-ade.png',    'https://ade.dz',    1, TRUE),
('NETCOM', '/assets/img/logo-netcom.png', 'https://netcom.dz', 2, TRUE),
('ASROUT', '/assets/img/logo-asrout.png', 'https://asrout.dz', 3, TRUE),
('EDEVAL', '/assets/img/logo-edeval.png', 'https://edeval.dz', 4, TRUE)
ON DUPLICATE KEY UPDATE nom = nom;
