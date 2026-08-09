-- ═══════════════════════════════════════════════════════════════════
--  WILAYA HARMONIA — Base de données v1.0.0
--  Fusion des concepts Balagh Alger × Gestion Événementielle Citoyenne
--  PHP 8.2+ • MySQL 8.0+ / MariaDB 10.6+ • utf8mb4
-- ═══════════════════════════════════════════════════════════════════

CREATE DATABASE IF NOT EXISTS wilaya_harmonia
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE wilaya_harmonia;

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- TABLES D'ACTEURS
-- =====================================================

DROP TABLE IF EXISTS roles;
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL UNIQUE,
    niveau INT NOT NULL COMMENT '1-7, hiérarchie',
    description VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

DROP TABLE IF EXISTS permissions;
CREATE TABLE permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL UNIQUE,
    module VARCHAR(50) NOT NULL,
    description VARCHAR(255) NULL
) ENGINE=InnoDB;

DROP TABLE IF EXISTS role_permissions;
CREATE TABLE role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

DROP TABLE IF EXISTS epic;
CREATE TABLE epic (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

DROP TABLE IF EXISTS associations;
CREATE TABLE associations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    caractere ENUM('association', 'comite_quartier') NOT NULL,
    numero_agrement VARCHAR(50) NULL UNIQUE,
    agrement_fichier VARCHAR(255) NULL COMMENT 'Chemin du document uploadé',
    nom_prenom_president VARCHAR(100) NULL,
    telephone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NULL UNIQUE,
    date_creation DATE NOT NULL,
    valide BOOLEAN DEFAULT FALSE COMMENT 'Validé par la Wilaya',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_valide (valide),
    INDEX idx_caractere (caractere)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role_user ENUM('wilaya', 'association', 'membre', 'epic', 'citoyen') NOT NULL,
    telephone VARCHAR(20) NULL,
    avatar VARCHAR(255) NULL,
    association_id INT NULL,
    epic_id INT NULL,
    points INT DEFAULT 0 COMMENT 'Gamification',
    remember_token VARCHAR(100) NULL,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (association_id) REFERENCES associations(id) ON DELETE SET NULL,
    FOREIGN KEY (epic_id) REFERENCES epic(id) ON DELETE SET NULL,
    INDEX idx_role (role_user),
    INDEX idx_association (association_id),
    INDEX idx_epic (epic_id),
    INDEX idx_points (points DESC)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS user_roles;
CREATE TABLE user_roles (
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABLES GÉOGRAPHIQUES
-- =====================================================

DROP TABLE IF EXISTS ca;
CREATE TABLE ca (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

DROP TABLE IF EXISTS commune;
CREATE TABLE commune (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    ca_id INT NULL,
    latitude DECIMAL(10, 8) NULL,
    longitude DECIMAL(11, 8) NULL,
    FOREIGN KEY (ca_id) REFERENCES ca(id) ON DELETE SET NULL,
    INDEX idx_ca (ca_id),
    INDEX idx_coords (latitude, longitude)
) ENGINE=InnoDB;

-- =====================================================
-- TABLES D'ANOMALIES
-- =====================================================

DROP TABLE IF EXISTS anomalies;
CREATE TABLE anomalies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    icone VARCHAR(50) NULL COMMENT 'Classe Font Awesome ou MDI',
    couleur VARCHAR(7) NULL COMMENT 'Code hex',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

DROP TABLE IF EXISTS epic_anomalies;
CREATE TABLE epic_anomalies (
    epic_id INT NOT NULL,
    anomalie_id INT NOT NULL,
    PRIMARY KEY (epic_id, anomalie_id),
    FOREIGN KEY (epic_id) REFERENCES epic(id) ON DELETE CASCADE,
    FOREIGN KEY (anomalie_id) REFERENCES anomalies(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABLES D'ÉVÉNEMENTS
-- =====================================================

DROP TABLE IF EXISTS evenements;
CREATE TABLE evenements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commune_id INT NULL,
    adresse VARCHAR(255) NOT NULL,
    association_id INT NULL,
    description TEXT NULL,
    informations_complementaires TEXT NULL,
    statut ENUM('EN_ATTENTE', 'PROGRAMME', 'TERMINE') NOT NULL DEFAULT 'EN_ATTENTE',
    motif_refus VARCHAR(255) NULL COMMENT 'Motif de refus / demande de modification',
    date_evenement DATE NULL,
    heure TIME NULL,
    deadline_at TIMESTAMP NULL COMMENT 'Date limite SLA',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (commune_id) REFERENCES commune(id) ON DELETE SET NULL,
    FOREIGN KEY (association_id) REFERENCES associations(id) ON DELETE SET NULL,
    INDEX idx_statut (statut),
    INDEX idx_date (date_evenement),
    INDEX idx_association (association_id),
    INDEX idx_deadline (deadline_at)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS anomalies_evenement;
CREATE TABLE anomalies_evenement (
    anomalie_id INT NOT NULL,
    evenement_id INT NOT NULL,
    PRIMARY KEY (anomalie_id, evenement_id),
    FOREIGN KEY (anomalie_id) REFERENCES anomalies(id) ON DELETE CASCADE,
    FOREIGN KEY (evenement_id) REFERENCES evenements(id) ON DELETE CASCADE
) ENGINE=InnoDB;

DROP TABLE IF EXISTS evenement_epic;
CREATE TABLE evenement_epic (
    evenement_id INT NOT NULL,
    epic_id INT NOT NULL,
    date_affectation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    observation TEXT NULL,
    PRIMARY KEY (evenement_id, epic_id),
    FOREIGN KEY (evenement_id) REFERENCES evenements(id) ON DELETE CASCADE,
    FOREIGN KEY (epic_id) REFERENCES epic(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- QR CODE & PARTICIPATION
-- =====================================================

DROP TABLE IF EXISTS qr_event;
CREATE TABLE qr_event (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evenement_id INT NOT NULL UNIQUE,
    token_qr CHAR(36) NOT NULL UNIQUE COMMENT 'UUID v4',
    date_debut TIMESTAMP NULL,
    date_expiration TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evenement_id) REFERENCES evenements(id) ON DELETE CASCADE,
    INDEX idx_token (token_qr),
    INDEX idx_expiration (date_expiration)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS evenement_participant;
CREATE TABLE evenement_participant (
    evenement_id INT NOT NULL,
    user_id INT NOT NULL,
    heure_scan TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    PRIMARY KEY (evenement_id, user_id),
    FOREIGN KEY (evenement_id) REFERENCES evenements(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- ALBUMS & PHOTOS
-- =====================================================

DROP TABLE IF EXISTS albums;
CREATE TABLE albums (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evenement_id INT NOT NULL,
    titre VARCHAR(255) NOT NULL,
    recit TEXT NULL COMMENT 'Récit officiel rédigé par la Wilaya',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evenement_id) REFERENCES evenements(id) ON DELETE CASCADE
) ENGINE=InnoDB;

DROP TABLE IF EXISTS photos;
CREATE TABLE photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    album_id INT NOT NULL,
    image VARCHAR(255) NOT NULL COMMENT 'Chemin du fichier',
    legende VARCHAR(255) NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- ÉVALUATIONS
-- =====================================================

DROP TABLE IF EXISTS evaluation;
CREATE TABLE evaluation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evenement_id INT NOT NULL,
    association_id INT NOT NULL,
    note TINYINT NOT NULL CHECK (note BETWEEN 1 AND 5),
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evenement_id) REFERENCES evenements(id) ON DELETE CASCADE,
    FOREIGN KEY (association_id) REFERENCES associations(id) ON DELETE CASCADE,
    UNIQUE KEY unique_evaluation (evenement_id, association_id)
) ENGINE=InnoDB;

-- =====================================================
-- NOTIFICATIONS
-- =====================================================

DROP TABLE IF EXISTS notifications;
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    titre VARCHAR(100) NOT NULL,
    message_notif VARCHAR(255) NOT NULL,
    type VARCHAR(50) NULL COMMENT 'evenement_valide, evenement_refuse, qr_genere, rappel, album, etc.',
    data_json TEXT NULL COMMENT 'Données additionnelles (ID événement, etc.)',
    lu BOOLEAN DEFAULT FALSE,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_lu (user_id, lu),
    INDEX idx_date (date_creation DESC)
) ENGINE=InnoDB;

-- =====================================================
-- HISTORIQUE IMMUABLE
-- =====================================================

DROP TABLE IF EXISTS historique_evenement;
CREATE TABLE historique_evenement (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evenement_id INT NOT NULL,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    observation TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    date_action TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evenement_id) REFERENCES evenements(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_evenement (evenement_id),
    INDEX idx_date (date_action DESC),
    INDEX idx_action (action)
) ENGINE=InnoDB;

-- =====================================================
-- GAMIFICATION
-- =====================================================

DROP TABLE IF EXISTS badges;
CREATE TABLE badges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    icone VARCHAR(50) NULL,
    condition_type VARCHAR(50) NOT NULL COMMENT 'first_event, 10_events, 50_scans, etc.',
    points_recompense INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

DROP TABLE IF EXISTS user_badges;
CREATE TABLE user_badges (
    user_id INT NOT NULL,
    badge_id INT NOT NULL,
    date_obtention TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, badge_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
) ENGINE=InnoDB;

DROP TABLE IF EXISTS citizen_points;
CREATE TABLE citizen_points (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    points INT NOT NULL,
    raison VARCHAR(255) NOT NULL,
    evenement_id INT NULL,
    date_attribution TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (evenement_id) REFERENCES evenements(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_date (date_attribution DESC)
) ENGINE=InnoDB;

-- =====================================================
-- SLA & ALERTES
-- =====================================================

DROP TABLE IF EXISTS sla_alertes;
CREATE TABLE sla_alertes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evenement_id INT NOT NULL,
    type ENUM('j-2', 'j-1', 'retard') NOT NULL,
    message TEXT NOT NULL,
    envoyee BOOLEAN DEFAULT FALSE,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evenement_id) REFERENCES evenements(id) ON DELETE CASCADE,
    UNIQUE KEY unique_alerte (evenement_id, type),
    INDEX idx_envoyee (envoyee)
) ENGINE=InnoDB;

-- =====================================================
-- CMS LANDING PAGE
-- =====================================================

DROP TABLE IF EXISTS landing_settings;
CREATE TABLE landing_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cle VARCHAR(100) NOT NULL UNIQUE,
    valeur TEXT NULL,
    type VARCHAR(50) DEFAULT 'text' COMMENT 'text, image, url, json',
    groupe VARCHAR(50) DEFAULT 'general',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

DROP TABLE IF EXISTS landing_faq;
CREATE TABLE landing_faq (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_fr VARCHAR(255) NOT NULL,
    question_ar VARCHAR(255) NULL,
    reponse_fr TEXT NOT NULL,
    reponse_ar TEXT NULL,
    ordre INT DEFAULT 0,
    actif BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

DROP TABLE IF EXISTS landing_testimonials;
CREATE TABLE landing_testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    auteur VARCHAR(100) NOT NULL,
    role VARCHAR(100) NULL,
    avatar VARCHAR(255) NULL,
    texte_fr TEXT NOT NULL,
    texte_ar TEXT NULL,
    note TINYINT DEFAULT 5 CHECK (note BETWEEN 1 AND 5),
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    actif BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

DROP TABLE IF EXISTS landing_partners;
CREATE TABLE landing_partners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    logo VARCHAR(255) NULL,
    url VARCHAR(255) NULL,
    ordre INT DEFAULT 0,
    actif BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- AUDIT LOG (immuable)
-- =====================================================

DROP TABLE IF EXISTS audit_logs;
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    modele VARCHAR(100) NOT NULL COMMENT 'evenement, association, album, etc.',
    modele_id INT NULL,
    anciennes_valeurs TEXT NULL COMMENT 'JSON ancien état',
    nouvelles_valeurs TEXT NULL COMMENT 'JSON nouvel état',
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_modele (modele, modele_id),
    INDEX idx_date (created_at DESC)
) ENGINE=InnoDB;

-- =====================================================
-- PUSH NOTIFICATIONS (PWA)
-- =====================================================

DROP TABLE IF EXISTS push_subscriptions;
CREATE TABLE push_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    endpoint TEXT NOT NULL,
    p256dh VARCHAR(255) NOT NULL,
    auth VARCHAR(100) NOT NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- =====================================================
-- SESSIONS (PHP native / base de données)
-- =====================================================

DROP TABLE IF EXISTS sessions;
CREATE TABLE sessions (
    id VARCHAR(128) NOT NULL PRIMARY KEY,
    user_id INT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    payload TEXT NOT NULL,
    last_activity INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB;

-- =====================================================
-- Mots de passe oubliés
-- =====================================================

DROP TABLE IF EXISTS password_resets;
CREATE TABLE password_resets (
    email VARCHAR(100) NOT NULL,
    token VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_token (token)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
