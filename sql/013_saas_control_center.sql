-- ═══════════════════════════════════════════════════════════════════
--  WILAYA HARMONIA — Migration V3.0 — Couche de Contrôle SaaS
--  Control Center • Règles métier • 2FA • Sécurité • Paramètres système
--  Workflow de validation des contenus publics
-- ═══════════════════════════════════════════════════════════════════

USE wilaya_harmonia;
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET FOREIGN_KEY_CHECKS = 0;

-- ── 1. Modules contrôlables (Control Center) ────────────────────────
DROP TABLE IF EXISTS control_modules;
CREATE TABLE control_modules (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    cle        VARCHAR(60) NOT NULL UNIQUE COMMENT 'identifiant du module',
    nom        VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    actif      BOOLEAN DEFAULT TRUE COMMENT 'activation/désactivation du module',
    verrouille BOOLEAN DEFAULT FALSE COMMENT 'évite la désactivation accidentelle',
    ordre      INT DEFAULT 0,
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB COMMENT='Supervision & activation des modules de la plateforme';

-- ── 2. Moteur de règles métier (Business Rule Engine) ───────────────
DROP TABLE IF EXISTS system_rules;
CREATE TABLE system_rules (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    cle        VARCHAR(80) NOT NULL UNIQUE COMMENT 'identifiant logique de la règle',
    nom        VARCHAR(120) NOT NULL,
    description VARCHAR(255) NULL,
    activite   VARCHAR(50) NOT NULL COMMENT 'blocage/validation/autorisation',
    portee     VARCHAR(30) DEFAULT 'global' COMMENT 'role, module, entité',
    cible      VARCHAR(80) NULL COMMENT 'role ou module ou entité ciblé(e)',
    condition_sql TEXT NULL,
    actif      BOOLEAN DEFAULT TRUE,
    version    INT DEFAULT 1,
    updated_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_actif (actif)
) ENGINE=InnoDB COMMENT='Règles métier dynamiques et versionnées';

-- ── 3. Paramètres système centralisés (SaaS) ────────────────────────
DROP TABLE IF EXISTS system_settings;
CREATE TABLE system_settings (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    groupe     VARCHAR(40) NOT NULL COMMENT 'securite, api, stockage, email, notification, langue, theme, maintenance, sauvegarde, quota',
    cle        VARCHAR(80) NOT NULL UNIQUE,
    valeur     TEXT NULL,
    type       VARCHAR(20) DEFAULT 'string',
    description VARCHAR(255) NULL,
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_groupe (groupe)
) ENGINE=InnoDB COMMENT='Paramètres SaaS centralisés (chaque modif tracée)';

-- ── 4. Événements de sécurité (2FA, tentatives, IP suspectes) ───────
DROP TABLE IF EXISTS security_events;
CREATE TABLE security_events (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    type       VARCHAR(40) NOT NULL COMMENT 'login_fail, suspicious_ip, tfa_code, blocked_action, force_logout...',
    severity   TINYINT DEFAULT 1 COMMENT '1=info 2=warning 3=critical',
    user_id    INT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    message    TEXT NULL,
    payload    TEXT NULL COMMENT 'JSON contexte',
    status     VARCHAR(20) DEFAULT 'open' COMMENT 'open/investigated/resolved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_ip (ip_address),
    INDEX idx_date (created_at DESC)
) ENGINE=InnoDB COMMENT='Journal de sécurité — tentatives suspectes et anomalies';

-- ── 5. Adresses IP bloquées (détection d'anomalies) ─────────────────
DROP TABLE IF EXISTS blocked_ips;
CREATE TABLE blocked_ips (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    ip_address  VARCHAR(45) NOT NULL UNIQUE,
    raison      VARCHAR(255) NULL,
    trigger_type VARCHAR(40) DEFAULT 'auto' COMMENT 'auto/manuel',
    expires_at  TIMESTAMP NULL,
    blocked_by  INT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB COMMENT='Blocage automatique / manuel d''adresses IP';

-- ── 6. 2FA (TOTP) ───────────────────────────────────────────────────
DROP TABLE IF EXISTS two_factor;
CREATE TABLE two_factor (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL UNIQUE,
    secret     VARCHAR(64) NULL COMMENT 'secret TOTP (optionnel, fallback email)',
    method     VARCHAR(20) DEFAULT 'email' COMMENT 'email/authenticator',
    enabled    BOOLEAN DEFAULT FALSE,
    confirmed  BOOLEAN DEFAULT FALSE,
    code       VARCHAR(10) NULL COMMENT 'code temporaire en attente',
    code_expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Authentification à deux facteurs';

-- ── 7. Workflow de validation des contenus publics ──────────────────
DROP TABLE IF EXISTS content_validations;
CREATE TABLE content_validations (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    modele       VARCHAR(40) NOT NULL COMMENT 'album, evenement, image, temoignage...',
    modele_id    INT NOT NULL,
    statut       ENUM('BROUILLON','EN_ATTENTE','PUBLIE','REJETE') DEFAULT 'EN_ATTENTE',
    proposer_par INT NULL,
    valide_par   INT NULL,
    motif        TEXT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_modele (modele, modele_id),
    INDEX idx_statut (statut)
) ENGINE=InnoDB COMMENT='État des contenus publics (draft/pending/published/rejected)';

-- ── 8. Suivi des sessions actives (contrôle multi-device) ───────────
ALTER TABLE sessions
    ADD COLUMN IF NOT EXISTS ip_address VARCHAR(45) NULL AFTER user_id,
    ADD COLUMN IF NOT EXISTS device VARCHAR(120) NULL AFTER ip_address,
    ADD COLUMN IF NOT EXISTS last_seen TIMESTAMP NULL AFTER last_activity;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS status ENUM('actif','suspendu','banni') DEFAULT 'actif' AFTER is_active,
    ADD COLUMN IF NOT EXISTS suspendu_jusqu_a TIMESTAMP NULL AFTER status;

-- ── 9. Séed : modules par défaut ────────────────────────────────────
INSERT INTO control_modules (cle, nom, description, actif, verrouille, ordre) VALUES
('users',       'Gestion des utilisateurs',       'Activation, suspension, bannissement et rôles', TRUE, TRUE, 1),
('associations','Contrôle des associations',      'Validation, suspension, événements et albums',   TRUE, FALSE, 2),
('epic',        'Contrôle des EPIC',              'Interventions, anomalies et supervision',        TRUE, FALSE, 3),
('evenements',  'Gestion des événements',         'Cycle de vie et validation des événements',      TRUE, TRUE, 4),
('albums',      'Albums photos',                  'Publication et validation des albums',           TRUE, FALSE, 5),
('agrements',   'Agréments',                      'Validation des agréments associations',          TRUE, FALSE, 6),
('permissions', 'Permissions & accès',            'RBAC/ABAC et permissions en temps réel',         TRUE, TRUE, 7),
('cms',         'Contenus publics (Landing)',     'CMS et workflow de validation publique',         TRUE, FALSE, 8),
('parametres',  'Paramètres système',             'Paramètres SaaS centralisés',                    TRUE, TRUE, 9),
('audit',       'Audit & journaux',               'Journaux immuables et exports',                  TRUE, TRUE, 10),
('securite',    'Sécurité & 2FA',                 'Tentatives, IP bloquées, sessions',              TRUE, TRUE, 11);

-- ── 10. Seed : règles métier par défaut ──────────────────────────────
INSERT INTO system_rules (cle, nom, activite, portee, cible, actif, version) VALUES
('evenement_validation_obligatoire', 'Un événement doit être validé avant publication', 'validation', 'module', 'evenements', TRUE, 1),
('epic_intervention_autorisation',   'Un EPIC ne modifie une intervention qu''avec autorisation', 'blocage', 'role', 'epic', TRUE, 1),
('utilisateur_suspendu_aucun_acces', 'Un utilisateur suspendu n''accède à aucune ressource', 'blocage', 'global', NULL, TRUE, 1),
('album_validation_avant_publication','Un album doit être validé avant publication publique', 'validation', 'module', 'albums', TRUE, 1),
('association_validation_evenement', 'Une association ne publie pas sans validation', 'validation', 'role', 'association', TRUE, 1);

-- ── 11. Seed : paramètres système par défaut ─────────────────────────
INSERT INTO system_settings (groupe, cle, valeur, type, description) VALUES
('securite',     'securite.tentatives_max',     '5',   'int',    'Tentatives de connexion avant blocage'),
('securite',     'securite.blocage_duree_min',  '10',  'int',    'Durée de blocage IP (minutes)'),
('securite',     'securite.tfa_obligatoire',    '1',   'bool',   '2FA obligatoire pour les administrateurs'),
('securite',     'securite.session_expiration', '60',  'int',    'Expiration de session (minutes)'),
('maintenance',  'maintenance.mode',            '0',   'bool',   'Mode maintenance'),
('maintenance',  'maintenance.message',         '',    'string', 'Message de maintenance'),
('quota',        'quota.upload_max_mb',         '5',   'int',    'Taille max d''upload (Mo)'),
('langue',       'langue.defaut',               'fr',  'string', 'Langue par défaut'),
('theme',        'theme.defaut',                'dark', 'string','Thème par défaut du dashboard');

SET FOREIGN_KEY_CHECKS = 1;
