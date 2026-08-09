-- ═══════════════════════════════════════════════════════════════════
-- 019 — Correction et complément des daïras/communes d'Alger (Wilaya 16)
--   • 13 daïras officiels avec codes 16-01 à 16-13
--   • 57 communes correctement rattachées
--   • Coordonnées géographiques des daïras
--
-- Idempotent : UPDATE par ID, INSERT ... WHERE NOT EXISTS
-- ═══════════════════════════════════════════════════════════════════

-- ---------------------------------------------------------------------
-- 1. Coordonnées géographiques des daïras (colonnes)
-- ---------------------------------------------------------------------
ALTER TABLE ca
    ADD COLUMN IF NOT EXISTS latitude  DECIMAL(10, 8) NULL AFTER is_active,
    ADD COLUMN IF NOT EXISTS longitude DECIMAL(11, 8) NULL AFTER latitude;

-- ---------------------------------------------------------------------
-- 2. Correction des noms/codes/noms_ar des 13 daïras d'Alger
--    Migration 010 a mal nommé les daïras (ordre différent).
--    On corrige par ID car les IDs sont stables.
-- ---------------------------------------------------------------------

-- id=1 Alger Centre → Bab El Oued (16-01)
UPDATE ca SET nom = 'Bab El Oued', nom_ar = 'باب الوادي', code = '16-01',
    latitude = 36.77550, longitude = 3.05830 WHERE id = 1;

-- id=2 Bab El Oued → Baraki (16-02)
UPDATE ca SET nom = 'Baraki', nom_ar = 'بركي', code = '16-02',
    latitude = 36.79000, longitude = 3.05000 WHERE id = 2;

-- id=3 El Harrach → Bir Mourad Raïs (16-03)
UPDATE ca SET nom = 'Bir Mourad Raïs', nom_ar = 'بIR موراد رايس', code = '16-03',
    latitude = 36.70640, longitude = 3.14190 WHERE id = 3;

-- id=4 Hussein Dey → Birtouta (16-04)
UPDATE ca SET nom = 'Birtouta', nom_ar = 'بيرتوتا', code = '16-04',
    latitude = 36.74000, longitude = 3.08000 WHERE id = 4;

-- id=5 Sidi M'Hamed → Bouzareah (16-05)
UPDATE ca SET nom = 'Bouzareah', nom_ar = 'بوزارعة', code = '16-05',
    latitude = 36.75810, longitude = 3.05270 WHERE id = 5;

-- id=6 Bir Mourad Raïs → Chéraga (16-06)
UPDATE ca SET nom = 'Chéraga', nom_ar = 'شراقة', code = '16-06',
    latitude = 36.72940, longitude = 3.05060 WHERE id = 6;

-- id=7 Draria → Dar El Beïda (16-07)
UPDATE ca SET nom = 'Dar El Beïda', nom_ar = 'دار البعيدة', code = '16-07',
    latitude = 36.71580, longitude = 2.99740 WHERE id = 7;

-- id=8 Birtouta → Draria (16-08)
UPDATE ca SET nom = 'Draria', nom_ar = 'الدرعية', code = '16-08',
    latitude = 36.64890, longitude = 3.05930 WHERE id = 8;

-- id=9 Zéralda → El Harrach (16-09)
UPDATE ca SET nom = 'El Harrach', nom_ar = 'الحراش', code = '16-09',
    latitude = 36.71660, longitude = 2.84210 WHERE id = 9;

-- id=10 Dar El Beïda → Hussein Dey (16-10)
UPDATE ca SET nom = 'Hussein Dey', nom_ar = 'حسين داي', code = '16-10',
    latitude = 36.71330, longitude = 3.20970 WHERE id = 10;

-- id=11 Rouïba → Rouïba (16-11) — nom OK, corriger coords
UPDATE ca SET nom = 'Rouïba', nom_ar = 'الرويبة', code = '16-11',
    latitude = 36.73830, longitude = 3.27830 WHERE id = 11;

-- id=12 Baraki → Sidi M'Hamed (16-12)
UPDATE ca SET nom = 'Sidi M''Hamed', nom_ar = 'سيدي امحمد', code = '16-12',
    latitude = 36.69970, longitude = 3.08520 WHERE id = 12;

-- id=13 Aïn Taya → Zéralda (16-13)
UPDATE ca SET nom = 'Zéralda', nom_ar = 'زرالدة', code = '16-13',
    latitude = 36.79470, longitude = 3.28720 WHERE id = 13;

-- ---------------------------------------------------------------------
-- 3. Correction des ca_id des communes EXISTANTES (migration 010)
--    On corrige par ID pour être sûr
-- ---------------------------------------------------------------------

-- id=1 : Alger Centre → Alger-Centre → Sidi M'Hamed (id=12)
UPDATE commune SET ca_id = 12, nom = 'Alger-Centre', nom_ar = 'الجزائر العاصمة' WHERE id = 1;

-- id=2 : Bab El Oued → Bab El Oued (id=1) ✓
UPDATE commune SET ca_id = 1 WHERE id = 2;

-- id=3 : El Harrach → El Harrach (id=9)
UPDATE commune SET ca_id = 9 WHERE id = 3;

-- id=4 : Hussein Dey → Hussein Dey (id=10)
UPDATE commune SET ca_id = 10 WHERE id = 4;

-- id=5 : Birkhadem → Bir Mourad Raïs (id=3)
UPDATE commune SET ca_id = 3 WHERE id = 5;

-- id=6 : Bordj El Kiffan → Dar El Beïda (id=7)
UPDATE commune SET ca_id = 7 WHERE id = 6;

-- id=7 : Hydra → Bir Mourad Raïs (id=3)
UPDATE commune SET ca_id = 3 WHERE id = 7;

-- id=8 : Kouba → Hussein Dey (id=10)
UPDATE commune SET ca_id = 10 WHERE id = 8;

-- id=19 : Belouizdad → Hussein Dey (id=10)
UPDATE commune SET ca_id = 10 WHERE id = 19;

-- id=20 : Casbah → Bab El Oued (id=1)
UPDATE commune SET ca_id = 1 WHERE id = 20;

-- id=21 : Bologhine → Bab El Oued (id=1)
UPDATE commune SET ca_id = 1 WHERE id = 21;

-- id=22 : Oued Koriche → Bab El Oued (id=1)
UPDATE commune SET ca_id = 1 WHERE id = 22;

-- id=23 : Raïs Hamidou → Bab El Oued (id=1)
UPDATE commune SET ca_id = 1 WHERE id = 23;

-- id=24 : Bir Mourad Raïs → Bir Mourad Raïs (id=3)
UPDATE commune SET ca_id = 3 WHERE id = 24;

-- id=25 : El Biar → Bouzareah (id=5) — renommer en El-Biar
UPDATE commune SET ca_id = 5, nom = 'El-Biar' WHERE id = 25;

-- id=26 : Djasr Kasentina → supprimer (pas dans la liste corrigée)
DELETE FROM commune WHERE id = 26;

-- id=27 : Gué de Constantine → Bir Mourad Raïs (id=3)
UPDATE commune SET ca_id = 3 WHERE id = 27;

-- id=28 : Saoula → Bir Mourad Raïs (id=3)
UPDATE commune SET ca_id = 3 WHERE id = 28;

-- id=29 : Draria → Draria (id=8)
UPDATE commune SET ca_id = 8 WHERE id = 29;

-- id=30 : El Achour → Draria (id=8)
UPDATE commune SET ca_id = 8 WHERE id = 30;

-- id=31 : Baba Hassen → Draria (id=8)
UPDATE commune SET ca_id = 8 WHERE id = 31;

-- id=32 : Douéra → Draria (id=8)
UPDATE commune SET ca_id = 8 WHERE id = 32;

-- id=33 : Khraicia → Draria (id=8) — renommer Khraïssia
UPDATE commune SET ca_id = 8, nom = 'Khraïssia', nom_ar = 'الخريسية' WHERE id = 33;

-- id=34 : Birtouta → Birtouta (id=4)
UPDATE commune SET ca_id = 4 WHERE id = 34;

-- id=35 : Tessala El Merdja → Birtouta (id=4)
UPDATE commune SET ca_id = 4 WHERE id = 35;

-- id=36 : Ouled Chebel → Birtouta (id=4)
UPDATE commune SET ca_id = 4 WHERE id = 36;

-- id=37 : Zéralda → Zéralda (id=13)
UPDATE commune SET ca_id = 13 WHERE id = 37;

-- id=38 : Staouéli → Zéralda (id=13)
UPDATE commune SET ca_id = 13 WHERE id = 38;

-- id=39 : Mahelma → Zéralda (id=13)
UPDATE commune SET ca_id = 13 WHERE id = 39;

-- id=40 : Dar El Beïda → Dar El Beïda (id=7)
UPDATE commune SET ca_id = 7 WHERE id = 40;

-- id=41 : Bab Ezzouar → Dar El Beïda (id=7)
UPDATE commune SET ca_id = 7 WHERE id = 41;

-- id=42 : Bordj El Bahri → Dar El Beïda (id=7)
UPDATE commune SET ca_id = 7 WHERE id = 42;

-- id=43 : Rouïba → Rouïba (id=11)
UPDATE commune SET ca_id = 11 WHERE id = 43;

-- id=44 : Réghaïa → Rouïba (id=11)
UPDATE commune SET ca_id = 11 WHERE id = 44;

-- id=45 : H'raoua → Rouïba (id=11)
UPDATE commune SET ca_id = 11 WHERE id = 45;

-- id=46 : Baraki → Baraki (id=2)
UPDATE commune SET ca_id = 2 WHERE id = 46;

-- id=47 : Les Eucalyptus → Baraki (id=2)
UPDATE commune SET ca_id = 2 WHERE id = 47;

-- id=48 : Aïn Taya → Dar El Beïda (id=7)
UPDATE commune SET ca_id = 7 WHERE id = 48;

-- id=49 : El Marsa → Dar El Beïda (id=7)
UPDATE commune SET ca_id = 7 WHERE id = 49;

-- id=50 : Oued Smar → El Harrach (id=9)
UPDATE commune SET ca_id = 9 WHERE id = 50;

-- id=51 : Bourouba → El Harrach (id=9)
UPDATE commune SET ca_id = 9 WHERE id = 51;

-- id=52 : Bachdjerrah → El Harrach (id=9)
UPDATE commune SET ca_id = 9 WHERE id = 52;

-- ---------------------------------------------------------------------
-- 4. Communes à ajouter (n'existent pas dans migration 010)
-- ---------------------------------------------------------------------

-- id=53 : Sidi Moussa → Baraki (id=2) [créé par migration 010/019]
-- Vérifions si elle existe et a bon ca_id
UPDATE commune SET ca_id = 2 WHERE nom = 'Sidi Moussa' AND ca_id != 2;

-- id=54 : Beni Messous → Bouzareah (id=5)
INSERT INTO commune (nom, nom_ar, ca_id, code, code_postal, is_active)
SELECT 'Beni Messous', 'بني مسعود', id, '16-05-02', '16000', 1
FROM ca WHERE code = '16-05' AND NOT EXISTS (SELECT 1 FROM commune WHERE nom = 'Beni Messous');

-- id=55 : Bouzareah → Bouzareah (id=5)
INSERT INTO commune (nom, nom_ar, ca_id, code, code_postal, is_active)
SELECT 'Bouzareah', 'بوزارعة', id, '16-05-03', '16000', 1
FROM ca WHERE code = '16-05' AND NOT EXISTS (SELECT 1 FROM commune WHERE nom = 'Bouzareah' AND ca_id = id);

-- id=56 : Ben Aknoun → Bouzareah (id=5)
INSERT INTO commune (nom, nom_ar, ca_id, code, code_postal, is_active)
SELECT 'Ben Aknoun', 'بن عكون', id, '16-05-01', '16000', 1
FROM ca WHERE code = '16-05' AND NOT EXISTS (SELECT 1 FROM commune WHERE nom = 'Ben Aknoun');

-- Aïn Benian, Chéraga, Dely Ibrahim, Ouled Fayet, El Hammamet → Chéraga (id=6)
INSERT INTO commune (nom, nom_ar, ca_id, code, code_postal, is_active)
SELECT 'Aïn Benian', 'عين بنيان', id, '16-06-01', '16000', 1
FROM ca WHERE code = '16-06' AND NOT EXISTS (SELECT 1 FROM commune WHERE nom = 'Aïn Benian');
INSERT INTO commune (nom, nom_ar, ca_id, code, code_postal, is_active)
SELECT 'Aïn Bénian', 'عين بنيان', id, '16-06-01', '16000', 1
FROM ca WHERE code = '16-06' AND NOT EXISTS (SELECT 1 FROM commune WHERE nom = 'Aïn Bénian');
INSERT INTO commune (nom, nom_ar, ca_id, code, code_postal, is_active)
SELECT 'Chéraga', 'شراقة', id, '16-06-02', '16000', 1
FROM ca WHERE code = '16-06' AND NOT EXISTS (SELECT 1 FROM commune WHERE nom = 'Chéraga');
INSERT INTO commune (nom, nom_ar, ca_id, code, code_postal, is_active)
SELECT 'Dely Ibrahim', 'دالي إبراهيم', id, '16-06-03', '16000', 1
FROM ca WHERE code = '16-06' AND NOT EXISTS (SELECT 1 FROM commune WHERE nom = 'Dely Ibrahim');
INSERT INTO commune (nom, nom_ar, ca_id, code, code_postal, is_active)
SELECT 'Ouled Fayet', 'أولاد فايت', id, '16-06-04', '16000', 1
FROM ca WHERE code = '16-06' AND NOT EXISTS (SELECT 1 FROM commune WHERE nom = 'Ouled Fayet');
INSERT INTO commune (nom, nom_ar, ca_id, code, code_postal, is_active)
SELECT 'El Hammamet', 'الحمامة', id, '16-06-05', '16000', 1
FROM ca WHERE code = '16-06' AND NOT EXISTS (SELECT 1 FROM commune WHERE nom = 'El Hammamet');

-- Mohammadia → Dar El Beïda (id=7)
INSERT INTO commune (nom, nom_ar, ca_id, code, code_postal, is_active)
SELECT 'Mohammadia', 'المحمدية', id, '16-07-07', '16000', 1
FROM ca WHERE code = '16-07' AND NOT EXISTS (SELECT 1 FROM commune WHERE nom = 'Mohammadia');

-- El Magharia → Hussein Dey (id=10)
INSERT INTO commune (nom, nom_ar, ca_id, code, code_postal, is_active)
SELECT 'El Magharia', 'المغارية', id, '16-10-02', '16000', 1
FROM ca WHERE code = '16-10' AND NOT EXISTS (SELECT 1 FROM commune WHERE nom = 'El Magharia');

-- Rahmania → Zéralda (id=13)
INSERT INTO commune (nom, nom_ar, ca_id, code, code_postal, is_active)
SELECT 'Rahmania', 'الرحمانية', id, '16-13-02', '16000', 1
FROM ca WHERE code = '16-13' AND NOT EXISTS (SELECT 1 FROM commune WHERE nom = 'Rahmania');

-- Souidania → Zéralda (id=13)
INSERT INTO commune (nom, nom_ar, ca_id, code, code_postal, is_active)
SELECT 'Souidania', 'السعيدية', id, '16-13-05', '16000', 1
FROM ca WHERE code = '16-13' AND NOT EXISTS (SELECT 1 FROM commune WHERE nom = 'Souidania');

-- ---------------------------------------------------------------------
-- 5. Nettoyer : supprimer les daïras obsolètes (16-14, 16-15, 16-16)
--    (si présents — créés par versions anciennes de cette migration)
-- ---------------------------------------------------------------------
DELETE c FROM commune c
JOIN ca old ON c.ca_id = old.id
WHERE old.code IN ('16-14', '16-15', '16-16');

DELETE FROM ca WHERE code IN ('16-14', '16-15', '16-16');

-- ---------------------------------------------------------------------
-- 6. Nettoyer les doublons (garder l'ID le plus ancien pour chaque nom+ca_id)
-- ---------------------------------------------------------------------
DELETE c1 FROM commune c1
INNER JOIN commune c2
WHERE c1.id > c2.id
  AND c1.nom = c2.nom
  AND c1.ca_id = c2.ca_id;

-- ---------------------------------------------------------------------
-- 7. Synchroniser les albums et la galerie de la landing page
--    Objectif : la galerie affiche automatiquement les couvertures d'albums publiés
--    Idempotent : INSERT ... WHERE NOT EXISTS
-- ---------------------------------------------------------------------

-- 7a. Créer un index pour optimiser la synchronisation
CREATE INDEX IF NOT EXISTS idx_landing_gallery_image_type ON landing_gallery (image, type);

-- 7b. Sync : insérer les albums publiés dans la galerie s'ils sont absents
INSERT INTO landing_gallery (image, titre_fr, titre_ar, type, lien, sort_order, actif)
SELECT
    a.couverture AS image,
    a.titre AS titre_fr,
    a.titre AS titre_ar,
    'album' AS type,
    NULL AS lien,
    a.id AS sort_order,
    1 AS actif
FROM albums a
WHERE a.statut = 'publie'
  AND a.couverture IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM landing_gallery lg
    WHERE lg.image = a.couverture
  );

-- 7c. Nettoyer : supprimer les entrées de galerie orphelines
DELETE lg FROM landing_gallery lg
LEFT JOIN albums a ON a.couverture = lg.image
WHERE lg.type = 'album' AND a.id IS NULL;

-- 7d. Mettre à jour les statistiques de synchronisation
UPDATE system_settings SET valeur = (SELECT COUNT(*) FROM albums WHERE statut = 'publie') 
WHERE cle = 'stats_albums_publies';

UPDATE system_settings SET valeur = (SELECT COUNT(*) FROM landing_gallery WHERE actif = 1) 
WHERE cle = 'stats_galerie_active';
