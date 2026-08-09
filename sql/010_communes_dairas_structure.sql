-- ═══════════════════════════════════════════════════════════════════
-- 010 — Structure territoriale inspirée de Balagh
-- Dairas (Circonscriptions Administratives) enrichies + Communes
-- rattachement territorial des associations (ca_id, commune_id)
-- ═══════════════════════════════════════════════════════════════════

-- ---------------------------------------------------------------------
-- 1. ENRICHISSEMENT TABLE ca (dairas)
-- ---------------------------------------------------------------------
ALTER TABLE ca
    ADD COLUMN nom_ar    VARCHAR(100) NULL AFTER nom,
    ADD COLUMN code      VARCHAR(20)  NULL AFTER nom_ar,
    ADD COLUMN is_active TINYINT(1)   NOT NULL DEFAULT 1 AFTER code;

-- ---------------------------------------------------------------------
-- 2. ENRICHISSEMENT TABLE commune
-- ---------------------------------------------------------------------
ALTER TABLE commune
    ADD COLUMN nom_ar      VARCHAR(100) NULL AFTER nom,
    ADD COLUMN code        VARCHAR(20)  NULL AFTER ca_id,
    ADD COLUMN code_postal VARCHAR(10)  NULL AFTER code,
    ADD COLUMN is_active   TINYINT(1)   NOT NULL DEFAULT 1 AFTER code_postal;

-- ---------------------------------------------------------------------
-- 3. RATTACHEMENT TERRITORIAL DES ASSOCIATIONS
-- ---------------------------------------------------------------------
ALTER TABLE associations
    ADD COLUMN ca_id      INT(11) NULL AFTER date_creation,
    ADD COLUMN commune_id INT(11) NULL AFTER ca_id;

CREATE INDEX idx_associations_ca ON associations (ca_id);
CREATE INDEX idx_associations_commune ON associations (commune_id);

-- ---------------------------------------------------------------------
-- 4. SEED : 13 DAIRAS DE LA WILAYA D'ALGER (Balagh : name_ar, code, is_active)
-- ---------------------------------------------------------------------
UPDATE ca SET nom_ar = 'الجزائر الوسطى',  code = '16-01' WHERE id = 1;
UPDATE ca SET nom_ar = 'باب الوادي',      code = '16-02' WHERE id = 2;
UPDATE ca SET nom_ar = 'الحراش',          code = '16-03' WHERE id = 3;
UPDATE ca SET nom_ar = 'حسين داي',        code = '16-04' WHERE id = 4;

INSERT INTO ca (nom, nom_ar, code, is_active) VALUES
('Sidi M''Hamed',  'سيدي امحمد',    '16-05', 1),
('Bir Mourad Raïs','بير مراد رايس',  '16-06', 1),
('Draria',         'درارية',        '16-07', 1),
('Birtouta',       'بئر توتة',      '16-08', 1),
('Zéralda',        'زرالدة',        '16-09', 1),
('Dar El Beïda',   'الدار البيضاء', '16-10', 1),
('Rouïba',         'الرويبة',       '16-11', 1),
('Baraki',         'براقي',         '16-12', 1),
('Aïn Taya',       'عين طاية',      '16-13', 1);

-- ---------------------------------------------------------------------
-- 5. SEED : COMMUNES (code, code_postal, coords) — enrichissement
-- ---------------------------------------------------------------------
UPDATE commune SET nom_ar = 'الجزائر الوسطى', code = '1601', code_postal = '16000' WHERE id = 1;
UPDATE commune SET nom_ar = 'باب الوادي',     code = '1602', code_postal = '16006' WHERE id = 2;
UPDATE commune SET nom_ar = 'الحراش',         code = '1603', code_postal = '16200' WHERE id = 3;
UPDATE commune SET nom_ar = 'حسين داي',       code = '1604', code_postal = '16004' WHERE id = 4;
UPDATE commune SET nom_ar = 'بئر خادم',       code = '1605', code_postal = '16330' WHERE id = 5;
UPDATE commune SET nom_ar = 'بئر قاسيم عبد الصمد' , code = '1606', code_postal = '16100' WHERE id = 6;
UPDATE commune SET nom_ar = 'حيدرة',          code = '1607', code_postal = '16035' WHERE id = 7;
UPDATE commune SET nom_ar = 'القبة',          code = '1608', code_postal = '16050' WHERE id = 8;

INSERT INTO commune (nom, nom_ar, ca_id, code, code_postal, latitude, longitude, is_active) VALUES
('Sidi M''Hamed',  'سيدي امحمد',    5,  '1609', '16080', 36.75810000, 3.05270000, 1),
('El Madania',     'المدنية',        5,  '1610', '16075', 36.74820000, 3.06520000, 1),
('El Mouradia',    'المرادية',       5,  '1611', '16070', 36.75020000, 3.04590000, 1),
('Belouizdad',     'بلوزداد',        5,  '1612', '16015', 36.74460000, 3.07880000, 1),
('Casbah',         'القصبة',         2,  '1613', '16025', 36.78500000, 3.06060000, 1),
('Bologhine',      'بولوغين',        2,  '1614', '16090', 36.80220000, 3.04130000, 1),
('Oued Koriche',   'وادي قريش',      2,  '1615', '16005', 36.78220000, 3.04340000, 1),
('Raïs Hamidou',   'الرايس حميدو',   2,  '1616', '16045', 36.81060000, 3.01580000, 1),
('Bir Mourad Raïs','بير مراد رايس',  6,  '1617', '16300', 36.72940000, 3.05060000, 1),
('El Biar',        'الابيار',        6,  '1618', '16030', 36.76460000, 3.03710000, 1),
('Djasr Kasentina','جسر قسنطينة',    6,  '1619', '16013', 36.71690000, 3.05590000, 1),
('Gué de Constantine','قصر القسنطيني',6,  '1620', '16061', 36.73520000, 3.06120000, 1),
('Saoula',         'السحاولة',       6,  '1621', '16350', 36.70960000, 3.02250000, 1),
('Draria',         'درارية',         7,  '1622', '16310', 36.71580000, 2.99740000, 1),
('El Achour',      'العاشور',        7,  '1623', '16340', 36.72050000, 3.02440000, 1),
('Baba Hassen',    'بابا حسن',       7,  '1624', '16300', 36.69050000, 3.02050000, 1),
('Douéra',         'الدويرة',        7,  '1625', '16320', 36.67780000, 3.04580000, 1),
('Khraicia',       'الخرايسية',      7,  '1626', '16360', 36.70530000, 3.00440000, 1),
('Birtouta',       'بئر توتة',       8,  '1627', '16400', 36.64890000, 3.05930000, 1),
('Tessala El Merdja','تسالة المرجة',  8,  '1628', '16401', 36.61070000, 3.04530000, 1),
('Ouled Chebel',   'أولاد شبل',      8,  '1629', '16402', 36.57360000, 3.08360000, 1),
('Zéralda',        'زرالدة',         9,  '1630', '16411', 36.71660000, 2.84210000, 1),
('Staouéli',       'سطاوالي',        9,  '1631', '16412', 36.75250000, 2.90640000, 1),
('Mahelma',        'المحالمة',       9,  '1632', '16413', 36.69150000, 2.87910000, 1),
('Dar El Beïda',   'الدار البيضاء', 10,  '1633', '16033', 36.71330000, 3.20970000, 1),
('Bab Ezzouar',    'باب الزوار',    10,  '1634', '16024', 36.72080000, 3.18220000, 1),
('Bordj El Bahri', 'برج البحري',    10,  '1635', '16160', 36.78530000, 3.23530000, 1),
('Rouïba',         'الرويبة',       11,  '1636', '16012', 36.73830000, 3.27830000, 1),
('Réghaïa',        'الرغاية',       11,  '1637', '16043', 36.73780000, 3.35030000, 1),
('H''raoua',       'هراوة',         11,  '1638', '16044', 36.75470000, 3.34950000, 1),
('Baraki',         'براقي',         12,  '1639', '16027', 36.69970000, 3.08520000, 1),
('Les Eucalyptus', 'الكاليتوس',     12,  '1640', '16028', 36.67670000, 3.10010000, 1),
('Aïn Taya',       'عين طاية',      13,  '1641', '16022', 36.79470000, 3.28720000, 1),
('El Marsa',       'المرسى',        13,  '1642', '16023', 36.81140000, 3.25470000, 1),
('Oued Smar',      'وادي السمار',    3,  '1643', '16270', 36.70640000, 3.14190000, 1),
('Bourouba',       'بوروبة',         3,  '1644', '16260', 36.70860000, 3.10920000, 1),
('Bachdjerrah',    'باش جراح',       3,  '1645', '16250', 36.72220000, 3.10560000, 1);
