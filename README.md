# Wilaya Harmonia — حومة إيفانت

**Plateforme de gestion événementielle citoyenne pour la Wilaya d'Alger**

<img src="HomtiEvents.jpg" alt="Wilaya Harmonia Banner" width="60%">

![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php)
![MySQL](https://img.shields.io/badge/MySQL%2FMariaDB-10.6-4479A1?logo=mysql)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap)
![Tailwind CSS](https://img.shields.io/badge/Tailwind%20CSS-3-06B6D4?logo=tailwindcss)
![PWA](https://img.shields.io/badge/PWA-Yes-5A0FC8?logo=pwa)
![i18n](https://img.shields.io/badge/i18n-FR%2FAR%20RTL-28A745)
![License](https://img.shields.io/badge/License-Proprietary-red)
![Version](https://img.shields.io/badge/Version-2.0-blue)

---

## Table des matières

1. [Présentation](#1-présentation)
2. [Fonctionnalités](#2-fonctionnalités)
3. [Architecture technique](#3-architecture-technique)
4. [Stack technologique](#4-stack-technologique)
5. [Installation](#5-installation)
6. [Configuration](#6-configuration)
7. [Base de données](#7-base-de-données)
8. [Système RBAC](#8-système-rbac)
9. [Workflow des événements](#9-workflow-des-événements)
10. [Système de file d'attente (Queue)](#10-système-de-file-dattente-queue)
11. [API REST](#11-api-rest)
12. [PWA & Mode hors-ligne](#12-pwa--mode-hors-ligne)
13. [Internationalisation (i18n)](#13-internationalisation-i18n)
14. [Système de gamification](#14-système-de-gamification)
15. [Système SLA](#15-système-sla)
16. [CMS Landing Page](#16-cms-landing-page)
17. [Sécurité](#17-sécurité)
18. [Structure du projet](#18-structure-du-projet)
19. [Routing & API](#19-routing--api)
20. [Contrôleurs](#20-contrôleurs)
21. [Helpers & Bibliothèques](#21-helpers--bibliothèques)
22. [Design System](#22-design-system)
23. [Déploiement](#23-déploiement)
24. [CLI Artisan](#24-cli-artisan)
25. [Statistiques](#25-statistiques)
26. [Backup & Monitoring](#26-backup--monitoring)

---

## 1. Présentation

**Wilaya Harmonia** (حومة إيفانت — « quartier ») est une plateforme web de gestion événementielle citoyenne destinée à la Wilaya d'Alger. Les **Associations** soumettent des demandes d'opérations (nettoyage, plantation, réhabilitation, animations…), la **Wilaya** les valide, les **programme** et génère un **QR code** de check-in ; les **Citoyens** participent sur le terrain, gagnent des **points et badges**, et les **EPIC** (ADE, NETCOM, ASROUT, EDEVAL) coordonnent les interventions techniques.

### Caractéristiques principales

- **Pas de framework lourd** : MVC custom en PHP 8.2+ avec autoloading Composer (PSR-4 `App\`)
- **Bilingue** : Français / Arabe avec support RTL complet
- **PWA** : Progressive Web App avec mode hors-ligne et notifications push
- **RBAC** : 7 niveaux de rôles avec contrôle d'accès basé sur la portée des données
- **Gamification** : Points, badges, classement pour encourager la participation
- **CMS** : Page d'accueil entièrement gérable par l'administration
- **Queue** : Système de jobs asynchrones sur fichier (disque)
- **SLA** : Suivi des délais avec alertes automatiques (J-2, J-1, retard)
- **QR Codes** : check-in sécurisé (UUID v4, anti re-scan, scan webcam + API)
- **Backup automatisé** : base MySQL + uploads vers Google Drive
- **~240 fichiers PHP** | **~57 000 lignes de code** | **64 tables MySQL**

---

## 2. Fonctionnalités

### Côté Citoyen
- Inscription et authentification (session sécurisée, 2FA TOTP optionnel)
- Consultation et suivi des opérations publiques par carte interactive
- **Check-in** par QR code (token UUID v4, fenêtre de validité, anti re-scan)
- Participation aux opérations, points, badges et classement
- Favoris et notifications de publication
- Profil avec statistiques personnelles d'impact
- Recherche de participants invités (mode invité)

### Côté Association
- Déposer une demande d'opération détaillée
- Suivi du statut (EN_ATTENTE → VALIDÉ → PROGRAMME → EN_COURS → TERMINE)
- Réponse aux demandes de modification, évaluation (1–5), récit officiel
- Gestion des membres, des albums photos et du récit
- Modèles de demande réutilisables, liste de contrôle (checklist)
- Gestion de présence des participants

### Côté Administration (Wilaya)
- Dashboard avec graphiques (statuts, tendances, orgs, communes)
- Validation et refus motivés des demandes, programmation
- Génération des QR codes, contrôle central (Control Center)
- Gestion des associations, utilisateurs, EPIC, communes/daïras
- Affectation des EPIC aux opérations programmées
- CRM Landing, gestion des actualités
- Journal d'audit complet, rapports PDF, export/statistiques
- Assistant IA intelligent (chatbot, contexte base de données)

### Côté EPIC
- Calendrier des interventions assignées
- Parcours EPIC dédié (EPIC : ADE, NETCOM, ASROUT, EDEVAL)
- Interface de consultation des événements programmés

### Page d'accueil publique
- Statistiques en temps réel (AJAX)
- Présentation du service (Comment ça marche)
- Galerie Avant/Après, albums et événements à venir
- Actualités, témoignages, FAQ, partenaires
- Carte Leaflet/OpenStreetMap des opérations
- CMS administrable

---

## 3. Architecture technique

### Pattern MVC classique

```
Requête HTTP → public/index.php (Front Controller)
    → .env (chargement, putenv)
    → Bootstrap (session, i18n, helpers)
    → Router::dispatch()
        → Middleware (Auth, Csrf, Role, SystemControl)
        → Controller::action()
            → Helpers (Database, Rbac, Csrf, QrCode…)
            → View rendering (templates PHP)
            → Layout (main, citoyen, association, epic, member, public…)
        → Response HTML / JSON / Redirect
```

### Système d'autoloading

```php
// composer.json
"autoload": {
    "psr-4": { "App\\": "app/" },
    "files": ["app/Helpers/Helper.php"]
}
```

Le code applicatif est chargé par **Composer (PSR-4)** ; les helpers utilitaires sont exposés via la liste `files` pour un accès global.

### Routeur fluent

```php
$router->prefix('/wilaya')->middleware(['auth','role:wilaya'])->group(function ($r) {
    $r->get('/dashboard', [DashboardController::class, 'index'])->name('wilaya.dashboard');
    $r->get('/evenements/{id}', [AdminEvenementController::class, 'show'])->name('wilaya.evenements.show');
});
```

### Deux familles de layout

| Layout | Fichier | Public cible | Design |
|--------|---------|-------------|--------|
| **Admin / Wilaya** | `layouts/main.php` | Personnel administratif | Sidebar + top navbar, design-tokens |
| **Citoyen** | `layouts/citoyen.php` | Citoyens | Navigation bottom mobile-first |
| **Association** | `layouts/association.php` | Associations | Sidebar + KPIs |
| **EPIC** | `layouts/epic.php` | EPIC | Calendrier + interventions |
| **Membre** | `layouts/member.php` | Membres d'association | Suivi des opérations |
| **Public / Landing / Guest** | `public.php`, `landing.php`, `guest.php` | Publique | Landing CMS + pages publiques |

### Base Controller

Tous les contrôleurs étendent `App\Controllers\Controller` qui fournit :

```php
$this->view($name, $data);        // Rendu avec layout
$this->viewRaw($name, $data);     // Rendu sans layout
$this->json($data, $code);        // Réponse JSON
$this->redirect($url);            // Redirection
$this->withSuccess($msg);         // Message flash succès
$this->withError($msg);           // Message flash erreur
$this->auth();                    // Vérifie l'authentification
$this->requirePermission($perm);  // Vérifie une permission (RBAC)
$this->requireRole($role);        // Vérifie un rôle
$this->requireStaff();            // Vérifie que c'est du personnel
$this->checkCsrf($redirect);      // Vérifie le token CSRF
$this->audit($action, ...);       // Log d'audit
$this->getUser();                 // Récupère l'utilisateur courant
```

---

## 4. Stack technologique

### Backend

| Composant | Technologie | Version |
|-----------|------------|---------|
| Langage | PHP | 8.2+ |
| Base de données | MariaDB / MySQL | 10.6+ / 8 |
| Queue / Cache | Fichier (disque) | — |
| PDF | DomPDF | ^3.0 |
| QR Codes | Endroid QR Code | ^5.0 |
| Email | PHPMailer | ^7.1 |
| Auth 2FA | TOTP (custom) | — |
| Routeur | Custom (`Router.php`) | — |
| Tests | PHPUnit | ^10.5 |

### Frontend

| Composant | Technologie | Version |
|-----------|------------|---------|
| CSS Framework | Bootstrap | 5.3 (RTL) |
| CSS Utility | Tailwind CSS | 3 (additif, `resources/css`) |
| Design System | `admin.css` + `design-tokens.css` + `tailwind.css` | Custom |
| Cartes | Leaflet.js + OpenStreetMap | 1.9 (gratuit, sans clé) |
| Graphiques | Chart.js | (CDN) |
| Tables | DataTables | 1.13+ |
| Dialogues | SweetAlert2 | 11 |
| Icônes | Font Awesome + MDI | — |
| Fonts | Inter + Noto Sans Arabic + Cairo | Google Fonts |
| JS | Vanilla JavaScript | — |
| Assistant IA | Gemini API (optionnel) | — |

### Outils CLI

| Commande | Usage |
|----------|-------|
| `php artisan queue:work` | Démarrer le worker queue |
| `php artisan queue:status` | Taille / état des jobs |
| `php artisan queue:failed` | Jobs échoués |
| `php artisan queue:retry <idx>` | Relancer un job |
| `php artisan queue:flush` | Vider les jobs échoués |
| `php artisan sla:run` | Exécuter les alertes SLA |
| `php artisan migrate:all` | Appliquer les migrations SQL |
| `php artisan app:info` | Info application |
| `php vendor/bin/phpunit` | Lancer les tests |
| `php vendor/bin/phpstan analyse -l 6 app` | Analyse statique |

### Tests

| Métrique | Valeur |
|----------|--------|
| Framework | PHPUnit 10.5 |
| Couverture | Unitaires + Intégration (MySQL) + Fonctionnels (HTTP) |
| Base de test | `wilaya_harmonia_test` (auto-créée, rollback transactionnel) |

```bash
# Lancer tous les tests
php vendor/bin/phpunit

# Filtrer un domaine
php vendor/bin/phpunit --filter=QrCode
php vendor/bin/phpunit --filter=EpicFlow
php vendor/bin/phpunit tests/EvenementServiceTest.php
```

> PHPStan niveau 6 et PHP_CodeSniffer (`phpcs.xml`) sont configurés pour garantir la qualité du code.

---

## 5. Installation

### Prérequis

```bash
PHP >= 8.2 avec extensions :
  - pdo_mysql
  - mbstring
  - gd
  - openssl
  - fileinfo
  - json

MariaDB >= 10.6 (ou MySQL 8)
Node.js >= 18 + npm   (uniquement pour compiler Tailwind)
Apache2 avec modules : mod_rewrite, mod_deflate, mod_headers
Composer 2.x
```

### Étapes d'installation

```bash
# 1. Cloner le projet
cd /var/www
git clone <repository> wilaya-harmonia
cd wilaya-harmonia

# 2. Installer les dépendances PHP
composer install

# 3. Compiler le CSS Tailwind (optionnel pour le dev)
npm install
npx tailwindcss -i resources/css/tailwind.css -o public/assets/css/tailwind.css --minify

# 4. Configurer l'environnement
cp .env.example .env
nano .env  # Modifier DB_*, APP_URL

# 5. Créer la base de données
mysql -u root -p
CREATE DATABASE wilaya_harmonia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'harmonia_user'@'localhost' IDENTIFIED BY 'votre_mot_de_passe';
GRANT ALL PRIVILEGES ON wilaya_harmonia.* TO 'harmonia_user'@'localhost';
FLUSH PRIVILEGES;

# 6. Appliquer le schéma + données de référence + démo
php artisan key:generate
php artisan migrate:all   # applique sql/001 → sql/045 (idempotent)

# 7. Créer la base de test
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS wilaya_harmonia_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 8. Configurer Apache
sudo nano /etc/apache2/sites-available/wilaya-harmonia.conf
# DocumentRoot /var/www/wilaya-harmonia/public
# <Directory /var/www/wilaya-harmonia/public> AllowOverride All </Directory>
sudo a2ensite wilaya-harmonia.conf
sudo systemctl restart apache2

# 9. Démarrer le worker queue (optionnel)
php queue/worker.php --watch &

# 10. Configurer le cron SLA (optionnel)
crontab -e
# 0 * * * * cd /var/www/wilaya-harmonia && php artisan sla:run
```

### Fichiers d'upload

```bash
mkdir -p public/uploads/{evenements,albums,avatars}
chmod -R 775 public/uploads
chown -R www-data:www-data public/uploads
```

---

## 6. Configuration

### Fichier `.env`

```env
APP_NAME="Wilaya Harmonia"
APP_ENV=local
APP_URL=http://localhost:8080
APP_LOCALE=fr
APP_LOCALES=fr,ar

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wilaya_harmonia
DB_USERNAME=root
DB_PASSWORD=

QUEUE_DRIVER=file
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# PWA
PWA_ENABLED=true

# Optionnel
GEMINI_API_KEY=
VAPID_PUBLIC_KEY=
VAPID_PRIVATE_KEY=

# Email (SMTP)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=

CRON_TOKEN=
```

### Fichiers de config

| Fichier | Description |
|---------|------------|
| `app/Config/app.php` | Nom (FR/AR), URL, version, timezone (`Africa/Algiers`), locale (`fr`), debug |
| `app/Config/database.php` | PDO MySQL, `ATTR_EMULATE_PREPARES => false` |
| `app/Config/paths.php` | Constantes `ROOT_PATH`, `APP_PATH`, `VIEW_PATH` |
| `app/Config/mail.php` | Configuration SMTP (PHPMailer) |
| `app/Config/redis.php` | Connexion Redis (optionnel, cache) |

### Configuration Apache

```apache
# public/.htaccess — Redirection vers index.php
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [L]

# Compression gzip
AddOutputFilterByType DEFLATE text/html text/css application/javascript

# Cache statique (1 mois)
Header set Cache-Control "public, max-age=2592000, immutable"

# Sécurité
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set Referrer-Policy "strict-origin-when-cross-origin"
```

### Base de données

**Important** : `PDO::ATTR_EMULATE_PREPARES => false` — les paramètres `LIMIT` et `OFFSET` doivent être interpolés (castés en entier), jamais bindés (bug PDO MySQL avec les entiers).

---

## 7. Base de données

### Schéma

**64 tables** réparties en 7 groupes fonctionnels.

#### Tables cœur (RBAC & identités)

| Table | Description |
|-------|------------|
| `roles` | Rôles du système |
| `permissions` | Permissions dynamiques |
| `role_permissions` | Association rôles → permissions |
| `users` | Utilisateurs (email, mot de passe bcrypt, avatar, `role_user`, statut, soft-delete) |
| `user_roles` | Association utilisateurs → rôles |
| `epic` | Entités EPIC (ADE, NETCOM, ASROUT, EDEVAL) |
| `associations` | Associations (caractère : association / comité_de_quartier) |
| `ca` | Circonscriptions administratives |
| `commune` / `dairas` | Communes et daïras avec coordonnées GPS |
| `anomalies` / `epic_anomalies` | Types d'anomalies et rattachement aux EPIC |

#### Tables événements

| Table | Description |
|-------|------------|
| `evenements` | Opérations (statut, dates, GPS, organisation pilote, workflow) |
| `anomalies_evenement` | Anomalies traitées par l'opération |
| `evenement_epic` | Affectation des EPIC aux événements programmés |
| `qr_event` | QR codes générés (token UUID, fenêtre de validité) |
| `evenement_participant` | Participations / check-ins |
| `historique_evenement` | Historique immuable des changements |

#### Tables albums & contenu

| Table | Description |
|-------|------------|
| `albums` | Albums photo d'événement |
| `photos` | Photos (légendes, avant/après) |
| `evaluation` | Notes (1–5) et avis des associations |

#### Tables communautaires & gamification

| Table | Description |
|-------|------------|
| `badges` / `user_badges` | Badges gagnés |
| `citizen_points` | Historique de points (gamification) |
| `notifications` | Notifications in-app (type, titre, message, data JSON, lu) |

#### Tables système

| Table | Description |
|-------|------------|
| `audit_logs` | Journal d'audit (action, modèle, anciennes/nouvelles valeurs, IP, user agent) |
| `sla_alertes` | Alertes SLA (J-2, J-1, retard) — anti doublons |
| `sessions` | Sessions PHP |
| `password_resets` | Tokens de réinitialisation de mot de passe |
| `push_subscriptions` | Abonnements notifications push Web |
| `settings` | Paramètres système |

#### Tables CMS landing

| Table | Description |
|-------|------------|
| `landing_partners` | Partenaires |
| `landing_gallery` | Galerie photos |
| `landing_testimonials` | Témoignages (bilingue FR/AR) |
| `landing_faq` | Questions fréquentes (bilingue FR/AR) |
| `landing_settings` | Paramètres landing (réseaux sociaux, image hero) |

### Migrations

45 migrations idempotentes dans `sql/` (`001_schema.sql` → `045_participations_invitees.sql`) appliquées via `php artisan migrate:all`.

### Données de référence

- **13 daïras** d'Alger, **communes** avec coordonnées GPS
- **Catégories** d'anomalies rattachées aux EPIC
- Comptes de démonstration (Wilaya, Associations, EPIC, Citoyens) — voir README de démo

---

## 8. Système RBAC

### Hiérarchie des rôles (7 niveaux)

```
wilaya (niveau 7)              ← Accès total, validation, programmation, QR
    ↑
chef_unite (niveau 5)          ← Supervision d'unité
    ↑
chef_section (niveau 4)        ← Supervision de section
    ↑
association (niveau 3)         ← Dépose / gère ses demandes et membres
    ↑
membre (niveau 2)              ← Membre d'association
epic (niveau 2)                ← Coordination des interventions assignées
    ↑
citoyen (niveau 1)             ← Participation, points, badges
```

### Permissions dynamiques

Les permissions sont stockées en base (`permissions`, `role_permissions`) et chargées dynamiquement selon le rôle de l'utilisateur. Le rôle `wilaya` bénéficie d'un **bypass** (lecture de toutes les permissions).

### Résolution de portée

```php
// Rbac::scope() génère une clause SQL WHERE basée sur le rôle
//   - wilaya / chef_section / chef_unite  → portée complète ''
//   - association / membre                → 'e.association_id = ?'
//   - epic                                → 'e.id IN (SELECT evenement_id
//                                              FROM evenement_epic WHERE epic_id = ?)'
```

### Utilisation dans les contrôleurs

```php
public function index()
{
    $this->auth();
    $this->requirePermission('evenements.view');

    $scope = Rbac::scope($this->getUser(), 'e.association_id');
    $events = EvenementService::all($scope['where'], $scope['params']);
}
```

---

## 9. Workflow des événements

### Cycle de vie d'une opération

```
┌────────────┐
│ EN_ATTENTE │  ← Association dépose la demande
└─────┬──────┘
      ↓
┌─────────────────────────┐
│ MODIFICATION_DEMANDEE   │  ← (retour Wilaya) → EN_ATTENTE
└─────────────────────────┘
      ↓
┌──────────┐      ┌────────┐
│  VALIDÉ  │ ←──  │ REFUSE │  ← refus motivé
└────┬─────┘      └────────┘
     ↓
┌────────────┐
│  PROGRAMME  │  ← programmation
└─────┬──────┘
      ↓
┌───────────┐
│ QR_GENERE  │  ← QR code de check-in généré
└─────┬─────┘
      ↓
┌──────────┐
│ EN_COURS  │  ← opération sur le terrain (check-in / QR)
└─────┬────┘
      ↓
┌─────────┐
│ TERMINE  │  ← clôturée, évaluation, récit, album
└─────────┘

  Toutes les transitions sont validées par la machine à états
  (EvenementService::STATUTS_VALIDES / transitions autorisées).
  ANNULE : annulation possible avec motif (audité).
```

### Transitions autorisées

| Statut | Vers |
|--------|------|
| `EN_ATTENTE` | `MODIFICATION_DEMANDEE`, `VALIDÉ`, `PROGRAMME`, `REFUSE`, `ANNULE` |
| `MODIFICATION_DEMANDEE` | `EN_ATTENTE`, `REFUSE`, `ANNULE` |
| `VALIDÉ` | `PROGRAMME`, `MODIFICATION_DEMANDEE`, `REFUSE` |
| `PROGRAMME` | `QR_GENERE`, `EN_COURS`, `TERMINE` |
| `QR_GENERE` | `EN_COURS`, `PROGRAMME`, `TERMINE` |
| `EN_COURS` | `TERMINE`, `EN_ATTENTE` |
| `TERMINE` | `EN_ATTENTE` |
| `REFUSE` | `EN_ATTENTE`, `MODIFICATION_DEMANDEE` |

### Check-in QR

- Token **UUID v4** stocké dans `qr_event`
- Fenêtre de validité temporelle
- **Anti re-scan** : un double check-in est refusé
- Scan webcam (ZXing) + validation via API
- QR généré via **Endroid QR Code** (`QrCodeService`), fichiers dans `public/uploads`/stockage

### Historique & audit

Chaque transition est journalisée dans `historique_evenement` et `audit_logs` (qui, quoi, avant/après, IP, user agent) — historique **immutable**.

---

## 10. Système de file d'attente (Queue)

### Architecture sur fichier

Le système utilise une **file sur disque** (`QUEUE_DRIVER=file`) : les jobs sont sérialisés en JSON dans `storage/queue/` et exécutés par `queue/worker.php`. Aucune dépendance Redis requise (Redis optionnel pour le cache).

```
storage/queue/
└── <timestamp>_<priorité>_<id>.job
```

### Jobs disponibles

| Job | Max tentatives | Description |
|-----|----------------|-------------|
| `GenerateQrJob` | 2 | Génère le QR code d'un événement (différé) |
| `SendNotificationJob` | 3 | Crée une notification in-app + dispatch push |
| `SendPushJob` | 2 | Livre la notification push (WebPush). Nettoie les abonnements périmés (410/404) |
| `SlaAlertJob` | 1 | Vérifie les délais : alertes J-2, J-1, retard |

### Utilisation

```php
// Dispatch immédiat
Queue::push(SendNotificationJob::class, ['user_id' => 12, 'data' => [...]]);

// Dispatch différé (60 secondes)
Queue::push(SlaAlertJob::class, [], 60);

// Worker
php queue/worker.php --watch          # daemon
php queue/worker.php                  # passe unique
```

### Worker

```bash
# Démarrer le worker
php queue/worker.php --watch

# CLI Artisan équivalente
php artisan queue:work
php artisan queue:status                # état des files
php artisan queue:failed                # jobs échoués
php artisan queue:retry 5               # relancer le job #5
php artisan queue:flush                 # vider les échoués
```

---

## 11. API REST

### Endpoints

Toutes les routes API sont préfixées par `/api/`.

| Méthode | Route | Description |
|---------|-------|------------|
| GET | `/api/evenements` | Opérations (filtres `statut`, `q`, `limit`) |
| GET | `/api/evenements/{id}` | Détail d'un événement |
| GET | `/api/map` | Marqueurs carte (points géolocalisés) |
| GET | `/api/stats` | Statistiques globales |
| GET | `/api/lang/{locale}` | Traductions (`fr`/`ar`) |
| GET | `/api/checkin/verify/{token}` | Validation d'un QR |
| POST | `/api/checkin/{token}` | Enregistrement d'une participation (`{"user_id": 12}`) |
| POST | `/api/push/subscribe` · `unsubscribe` | Abonnement / désabonnement push |
| GET | `/api/epic/dashboard` | Données EPIC (parcours) |
| GET | `/api/calendar` | Calendrier des interventions |
| POST | `/api/ai/ask` | Assistant IA (contexte base de données) |
| GET | `/api/routing/*` | Services de routage / géolocalisation |

### Exemple de réponse

```json
GET /api/stats
{
    "total": 42,
    "en_attente": 8,
    "programmes": 4,
    "termines": 11,
    "associations": 20
}
```

Collection Postman : `docs/WilayaHarmonia.postman_collection.json`.

---

## 12. PWA & Mode hors-ligne

### Service Worker (`sw.js`)

```
Cache : wilaya-vN
Stratégie :
  - Pages HTML  : Network-first → fallback cache → /offline
  - Assets      : Cache-first → mise à jour réseau en arrière-plan
```

### Manifest PWA

```json
{
    "name": "Wilaya Harmonia",
    "short_name": "Harmonia",
    "display": "standalone",
    "theme_color": "#0B5ED7",
    "background_color": "#f8fafc",
    "start_url": "/"
}
```

### Notifications Push

```javascript
// Abonnement
POST /api/push/subscribe
{
    "endpoint": "https://fcm.googleapis.com/...",
    "keys": { "p256dh": "...", "auth": "..." }
}
```

Clés **VAPID** optionnelles (non requises si non configurées).

---

## 13. Internationalisation (i18n)

### Langues supportées

| Langue | Code | Direction | Fichier |
|--------|------|-----------|---------|
| Français | `fr` | LTR | `lang/fr.json` |
| Arabe | `ar` | RTL | `lang/ar.json` |

### Utilisation côté serveur

```php
echo __('evenements.statut_en_attente');   // "En attente"
echo __('app.name');                       // "Wilaya Harmonia"
```

### Utilisation côté client

```javascript
I18n.t('nav.dashboard');
I18n.formatNumber(1234);
I18n.timeAgo(date);
```

### Support RTL

- Balise `<html dir="rtl">` automatique en arabe
- Bootstrap RTL (`bootstrap.rtl.min.css`) + propriétés logiques (`me-*` ↔ `ms-*`)
- Tailwind : preflight désactivé, compatible RTL
- Sidebar et texte alignés à droite

---

## 14. Système de gamification

### Points

| Action | Points |
|--------|--------|
| Participation (check-in) | 50 pts |
| Création / implication d'un événement | 20 pts |
| Récompense de badge | configurable (`badges.points_recompense`) |

### Badges

| Badge | Condition |
|-------|-----------|
| `first_event` | Première participation |
| `10_events` | 10 événements |
| `50_events` | 50 événements |
| `100_scans` | 100 scans de QR |
| `1000_scans` | 1 000 scans de QR |

### Classement

`Gamification::getLeaderboard()` classe les citoyens par points totaux. Accessible via `/classement` et les pages citoyennes.

### Vues

- `/profile` — Statistiques personnelles d'impact
- Badges visibles sur le profil citoyen

---

## 15. Système SLA

### Principe

Chaque catégorie / opération peut porter un délai (`deadline_days`). Le deadline est calculé à la création :

```
deadline_at = created_at + deadline_days jours
```

### Alertes automatiques

Le job `SlaAlertJob` vérifie les délais et envoie des notifications :

| Moment | Type | Message |
|--------|------|---------|
| J-2 (2 jours avant) | `j-2` | « Attention, le délai expire dans 2 jours » |
| J-1 (1 jour avant) | `j-1` | « Dernier jour » |
| J0 (retard) | `retard` | « En retard » |

### Prévention des doublons

La table `sla_alertes` enregistre les alertes envoyées (type, référence) pour éviter les notifications en double.

### Utilisation

```bash
# Exécuter manuellement
php artisan sla:run

# Cron (toutes les heures)
0 * * * * cd /var/www/wilaya-harmonia && php artisan sla:run
```

---

## 16. CMS Landing Page

### Pages gérables

La page d'accueil publique est entièrement administrable via `/admin/landing/` et `/admin/actualites`.

| Section | Table | Contenu |
|---------|-------|---------|
| Partenaires | `landing_partners` | nom, icône, couleur, ordre, actif |
| Galerie | `landing_gallery` | image, légende, ordre |
| Témoignages | `landing_testimonials` | texte FR/AR, auteur, rôle, note |
| FAQ | `landing_faq` | question FR/AR, réponse FR/AR |
| Paramètres | `landing_settings` | image hero, réseaux sociaux |
| Actualités | `actualites` | CMS d'articles publics (`/actualites`) |
| Albums / événements à venir | `albums`, `evenements` | affichage landing |

### Upload de fichiers

- Taille max : 5 Mo (configurable `UPLOAD_MAX_SIZE`)
- Types acceptés : JPG, PNG, WebP
- Validation MIME via `finfo(FILEINFO_MIME_TYPE)` (UploadHelper)

---

## 17. Sécurité

### Mécanismes implémentés

| Mécanisme | Implémentation |
|-----------|---------------|
| **CSRF** | Double-submit (`Csrf::generate()` / `Csrf::verify()`). Meta tag + champ. `$this->checkCsrf()` dans chaque handler POST, appliqué globalement par le middleware `CsrfMiddleware` |
| **Mots de passe** | `password_hash(PASSWORD_DEFAULT)` (bcrypt) |
| **2FA** | TOTP (`Totp.php`) + codes de récupération |
| **Requêtes préparées** | PDO avec `ATTR_EMULATE_PREPARES => false`. Toutes les requêtes utilisent des prepared statements |
| **RBAC** | Vérification dans chaque action via `requirePermission()`, `requireRole()`, `requireStaff()` + middleware `RoleMiddleware` |
| **Portée de données** | `Rbac::scope()` génère des clauses SQL WHERE limitées à l'association / l'EPIC / la section |
| **Upload fichiers** | Validation MIME (`finfo`), taille max, whitelist de types, noms uniques |
| **Headers sécurité** | `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy`, CSP via `public/index.php` |
| **Audit logging** | Toutes les opérations CRUD logguées (utilisateur, action, modèle, avant/après, IP, user agent) |
| **Soft delete** | Suppression logique des utilisateurs (`users.deleted_at`) |
| **Anti mass-assignment** | Whitelist des clés mises à jour |

### Filtre XSS

```php
// Toutes les sorties sont échappées
htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

// Helper dédié
e($value);   // alias d'échappement
```

---

## 18. Structure du projet

```
/var/www/wilaya-harmonia/
├── .env                              # Configuration (hors Git)
├── .env.example                      # Gabarit de configuration
├── AGENTS.md                         # Documentation développeur
├── PROJECT_MAP.md                    # Cartographie du projet
├── composer.json                     # Dépendances PHP (PSR-4 App\)
├── artisan                           # CLI (queue, SLA, migrate, info)
├── queue/
│   └── worker.php                    # Worker de file (fichier)
├── lang/
│   ├── fr.json                       # Traductions françaises
│   └── ar.json                       # Traductions arabes
├── sql/                              # Migrations 001 → 045 (idempotentes)
├── resources/
│   └── css/tailwind.css              # Source Tailwind (additif)
├── tests/
│   ├── bootstrap.php                 # Auto-loader, env vars test DB
│   ├── DatabaseTestCase.php          # Rollback transactionnel
│   └── *Test.php                     # Suites PHPUnit
├── docs/                             # Collection Postman, mockups, registres
│
├── app/
│   ├── Config/                       # app, database, paths, mail, redis, ia, push
│   ├── Controllers/                  # web + Api/
│   ├── Helpers/                      # Database, Router, Rbac, Session, Csrf…
│   ├── Jobs/                         # GenerateQr, SendNotification, SendPush, SlaAlert
│   ├── Middleware/                   # Auth, Csrf, Role, SystemControl
│   ├── Routes/                       # web.php, api.php
│   └── Views/
│       ├── layouts/                  # main, citoyen, association, epic, member, public, guest, landing…
│       ├── wilaya/  association/  citoyen/  epic/  admin/  profile/
│       ├── auth/  actualites/  landing/  qrcode/  partials/
│       └── control/
│
└── public/
    ├── index.php                     # Front controller (CSP + en-têtes)
    ├── .htaccess                     # Apache rewrite + cache + sécurité
    ├── manifest.json                 # PWA manifest
    ├── sw.js                         # Service worker
    ├── assets/
    │   ├── css/admin.css             # Design system admin
    │   ├── css/tailwind.css          # CSS Tailwind compilé
    │   └── js/
    └── uploads/                      # événements, albums, avatars (hors Git)
```

---

## 19. Routing & API

### Système de routing

Le routeur custom (`Router.php`) utilise une API **fluent** avec middleware et préfixes :

```php
// app/Routes/web.php
$router->get('/', [LandingController::class, 'landing'])->name('home');
$router->get('/evenements/{id}', [EvenementPublicController::class, 'show'])->name('evenements.public.show');

$router->prefix('/wilaya')->middleware(['auth', 'role:wilaya'])->group(function ($r) {
    $r->get('/dashboard', [DashboardController::class, 'index'])->name('wilaya.dashboard');
    $r->get('/suivi', [RoutingController::class, 'suivi'])->name('wilaya.suivi');
});

// app/Routes/api.php
$router->prefix('/api')->group(function ($r) {
    $r->get('/evenements', [Api\EvenementController::class, 'index']);
    $r->post('/checkin/{token}', [Api\CheckinController::class, 'store']);
});
```

### Routes web principales

| Groupe | Routes |
|--------|--------|
| **Public** | `/` (landing), `/evenements/{id}`, `/actualites`, `/suivi/{code}` |
| **Auth** | `/auth/login`, `/auth/register`, `/auth/logout`, 2FA |
| **Citoyen** | `/citoyen/*` (accueil, favoris, notifications, profil), `/checkin/*` |
| **Association** | `/association/*` (dashboard, demandes, membres, albums, présence) |
| **Wilaya** | `/wilaya/*` (dashboard, suivi, événements, associations, calendrier) |
| **EPIC** | `/epic/*` (dashboard, agenda, parcours) |
| **Admin** | `/admin/*` (utilisateurs, associations, statistiques) |
| **Control Center** | `/control/*` (dashboard, users, audit, security, rules, settings) |
| **CMS** | `/admin/landing/*`, `/admin/actualites/*` |

---

## 20. Contrôleurs

### Contrôleurs web (36)

| Contrôleur | Responsabilité |
|-----------|---------------|
| `AuthController` | Login/logout, inscription, 2FA TOTP, récupération |
| `DashboardController` | Tableau de bord Wilaya (KPIs, tendances, graphiques) |
| `AdministrationController` | Gestion des utilisateurs / associations (admin) |
| `ControlCenterController` | Panneau de contrôle central (onglets : dashboard, users, audit, security, rules, settings) |
| `AdminEvenementController` | Validation, programmation, gestion des opérations |
| `AssociationController` | Espace association (demandes, statuts) |
| `AssociationRequestController` | Cycle de vie des demandes d'opération |
| `AssociationDashboardController` | Dashboard association (KPIs, tendances) |
| `AssociationGalleryController` | Albums et récit officiel |
| `AssociationPresenceController` | Gestion de présence |
| `AssociationTemplateController` | Modèles de demandes réutilisables |
| `WilayaAssociationController` | Vue Wilaya sur les associations |
| `WilayaCalendarController` | Calendrier des opérations |
| `EpicController` | Espace EPIC |
| `EpicDashboardController` | Dashboard EPIC |
| `EventGalleryController` | Galerie d'événement |
| `EventChecklistController` | Liste de contrôle d'un événement |
| `EventDocumentController` | Documents d'un événement |
| `EventMessageController` | Messages d'événement |
| `CommentController` | Commentaires |
| `CitoyenController` | Espace citoyen (check-in, favoris) |
| `ParticipationController` | Participations / invités |
| `ProfilController` / `ProfileController` / `PublicProfileController` | Profils |
| `QrCodeController` / `EnhancedQrCodeController` | QR codes (scan, génération) |
| `NotificationController` | Notifications in-app |
| `AuditController` | Journal d'audit |
| `StatsController` | Statistiques / export |
| `RoutingController` | Suivi public / géolocalisation |
| `LandingController` / `LandingAdminController` / `ActualiteController` | Landing + CMS actualités |
| `CronController` | Tâches planifiées (SLA) |

### Contrôleurs API (10)

| Contrôleur | Route | Description |
|-----------|-------|------------|
| `Api\EvenementController` | `/api/evenements` | Liste / détail opérations |
| `Api\CheckinController` | `/api/checkin/{token}` | Validation + check-in |
| `Api\MapController` | `/api/map` | Points géolocalisés |
| `Api\StatsController` | `/api/stats` | Statistiques globales |
| `Api\LangController` | `/api/lang/{locale}` | Traductions |
| `Api\PushController` | `/api/push/*` | Notifications push |
| `Api\CalendarController` | `/api/calendar` | Calendrier |
| `Api\EpicDashboardApi` | `/api/epic/dashboard` | Parcours EPIC |
| `Api\ChatbotController` | `/api/ai/ask` | Assistant IA |
| `Api\RoutingDebugApi` | `/api/routing/*` | Routage / géolocalisation |

---

## 21. Helpers & Bibliothèques

| Helper | Fichier | Description |
|--------|---------|------------|
| **Router** | `Router.php` | Routeur fluent : get/post/prefix/middleware, noms de routes, extraction `{id}`, dispatch |
| **Database** | `Database.php` | Singleton PDO, DB::all/value/insert/update/run, prepared statements |
| **Session** | `Session.php` | Démarrage session, get/set, flash, état auth, **persistance DB des sessions** |
| **Csrf** | `Csrf.php` | Génération / vérification de tokens |
| **Validator** | `Validator.php` | Validation des entrées |
| **Helper** | `Helper.php` | `e()`, `slugify()`, `timeAgo()`, helpers fichiers, `url()` |
| **I18n** | `I18n.php` | Chargement `lang/fr.json` / `ar.json`, `__()`, détection RTL |
| **Rbac** | `Rbac.php` | Hiérarchie 7 niveaux, permissions, résolution de portée |
| **Abac** | `Abac.php` | Contrôle d'accès avancé (attributs) |
| **AuditLog** | `AuditLog.php` | Journal immuable (avant/après, IP, user agent) |
| **Notification** | `Notification.php` | Notifications in-app + compteurs |
| **Queue** | `Queue.php` | File sur disque : push (avec délai), worker, failed, retry |
| **Cache** | `Cache.php` | Cache applicatif |
| **Gamification** | `Gamification.php` | Points, badges, classement |
| **Badge** | `Badge.php` | Définitions et attribution de badges |
| **QrCodeGenerator** / **QrCodeService** | | Génération / service de QR codes (Endroid) |
| **SlaHelper** | `SlaHelper.php` | Calcul des délais et alertes SLA |
| **WebPush** | `WebPush.php` | Notifications push (VAPID) |
| **Mailer** | `Mailer.php` | Envoi d'emails (PHPMailer/SMTP) |
| **PdfHelper** | `PdfHelper.php` | Génération de PDF (DomPDF) |
| **Totp** | `Totp.php` | Authentification à deux facteurs |
| **GeoHelper** | `GeoHelper.php` | Calculs GPS (Haversine), géolocalisation |
| **Security** | `Security.php` | Durcissement, headers, rate-limiting |
| **UploadHelper** | `UploadHelper.php` | Upload sécurisé (MIME, taille, noms uniques) |
| **ControlCenter** / **ControlAction** / **BusinessRules** | | Règles métier et panneau de contrôle |
| **StatsService** / **EpicDashboardService** / **EvenementService** / **LandingService** / **ActualiteService** / **AnnouncementService** / **CommentService** | | Services métier |

---

## 22. Design System

### Design tokens (`design-tokens.css`)

```css
:root {
    --wh-blue: #0B5ED7;          /* Bleu de marque */
    --wh-green: #198754;
    --wh-amber: #f59e0b;
    --wh-red: #dc3545;
    --wh-radius: .75rem;
    --wh-shadow: 0 1px 3px rgba(16,24,40,.08);
    --wh-font-heading: 'Inter', 'Noto Sans Arabic', sans-serif;
    --wh-font-body: 'Inter', 'Noto Sans Arabic', sans-serif;
}
```

### CSS Admin (`admin.css`)

- Design system complet avec variables CSS
- Sidebar sombre + top navbar
- Composants réutilisables : `wh-hero-panel` (hero unifié), `wh-kpi-card`, `wh-dash-card`, `wh-stat-*`
- Graphiques (Chart.js), tables de données, pagination
- Thème dark/light basculable

### CSS Citoyen (`citoyen.css`)

- Mobile-first avec `touch-action: manipulation`
- Glassmorphisme (`backdrop-filter: blur`)
- Navigation bottom bar
- Bannière d'installation PWA

### Tailwind CSS (additif)

- **Preflight désactivé** (n'écrase pas Bootstrap)
- Content scan : `app/Views/**/*.php`, `public/assets/js/**/*.js`, `resources/css/**/*.css`
- Build : `npx tailwindcss -i resources/css/tailwind.css -o public/assets/css/tailwind.css --minify`
- Utilities : design tokens (`--wh-*`), `font-heading`, hover/shadow cohérents

### JS

- `admin.js` : gestion thème, sidebar, rayon de commande, graphiques, AJAX
- `citoyen.js` : toasts, langue, animations, PWA
- `i18n.js` : traductions côté client, `timeAgo()`
- `sw.js` : service worker (cache, hors-ligne)

---

## 23. Déploiement

### Prérequis serveur

```
OS : Ubuntu/Debian
PHP : 8.2+ (extensions pdo_mysql, mbstring, gd, openssl, fileinfo, json)
MariaDB : 10.6+ / MySQL 8
Apache : mod_rewrite, mod_deflate, mod_headers
Composer : 2.x
Node.js 18+ (build Tailwind)
```

### Installation (production)

```bash
# Cloner
cd /var/www
git clone <repo> wilaya-harmonia && cd wilaya-harmonia

# Dépendances & assets
composer install --no-dev --optimize-autoloader
npm install && npx tailwindcss -i resources/css/tailwind.css -o public/assets/css/tailwind.css --minify

# Configuration
cp .env.example .env
nano .env   # APP_ENV=production, DB_*, APP_URL

# Base de données
mysql -u root -e "CREATE DATABASE wilaya_harmonia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate:all

# Permissions
chmod -R 775 public/uploads storage
chown -R www-data:www-data public/uploads storage

# Apache
nano /etc/apache2/sites-available/wilaya-harmonia.conf
# DocumentRoot /var/www/wilaya-harmonia/public
a2ensite wilaya-harmonia.conf && systemctl restart apache2
```

### Production

```bash
# .env : APP_DEBUG=false, SESSION_SECURE=true (si HTTPS)

# Worker queue (systemd ou nohup)
nohup php queue/worker.php --watch >> /var/log/wilaya-worker.log 2>&1 &

# Cron SLA + backup
crontab -e
# 0 * * * * /usr/local/bin/wilaya-backup.sh >> /var/log/wilaya-backup.log 2>&1
# 30 3 * * * cd /var/www/wilaya-harmonia && php artisan sla:run
```

### Site déjà en service

Le site est déployé sur Apache (ports 80/443) — serveurs `php -S` réservés au développement.

---

## 24. CLI Artisan

```bash
php artisan app:info                        # Info application

# === MIGRATIONS ===
php artisan migrate:all                     # Applique sql/001 → 045

# === QUEUE ===
php artisan queue:work                       # Démarrer le worker
php artisan queue:status                     # État des files
php artisan queue:failed                     # Jobs échoués
php artisan queue:retry 5                    # Relancer job #5
php artisan queue:retry all                  # Relancer tous les échoués
php artisan queue:flush                      # Vider les échoués

# === SLA ===
php artisan sla:run                          # Exécuter les alertes SLA
```

---

## 25. Statistiques

### Métriques du projet

| Métrique | Valeur |
|----------|--------|
| Fichiers PHP | ~240 |
| Lignes de code (app) | ~57 000 |
| Tables MySQL | 64 |
| Contrôleurs | 36 web + 10 API |
| Helpers | 36 |
| Jobs | 4 |
| Vues | 50+ |
| Layouts | 10+ |
| Migrations SQL | 45 |
| Niveaux RBAC | 7 |
| Langues | FR / AR (RTL) |

### Écosystème

| Acteur | Rôle |
|--------|------|
| Associations | Déposent les demandes d'opérations |
| Wilaya | Valide, programme, génère les QR, audite |
| EPIC | Coordonnent les interventions techniques |
| Citoyens | Participent, check-in, points, badges |
| Membres | Suivent les opérations de leur association |

### Points de gamification

| Action | Points |
|--------|--------|
| Participation (check-in) | 50 |
| Implication événement | 20 |

---

## 26. Backup & Monitoring

### Backup automatisé (`/usr/local/bin/wilaya-backup.sh`)

Le script assemble dans une **archive horodatée** : dump MySQL + uploads + storage + config + `.env`, puis envoie vers un **stockage cloud** (Google Drive) via rclone.

```
Archive : /tmp/wilaya-harmonia_YYYY-MM-DD_HHMMSS.tar.gz
Remote  : gdrive:wilaya-harmonia/backups
```

```bash
# Backup local uniquement
/usr/local/bin/wilaya-backup.sh --local

# Backup + envoi cloud
/usr/local/bin/wilaya-backup.sh

# Restauration
tar -xzf wilaya-harmonia_<ts>.tar.gz
mysql -u root -p wilaya_harmonia < wilaya_harmonia.sql
```

### Planification (cron)

| Horaire | Action |
|---------|--------|
| 03:30 | Backup complet + envoi Google Drive (`/usr/local/bin/wilaya-backup.sh`) |
| horaire | SLA (`php artisan sla:run`) |

### Rétention

| Élément | Rétention |
|---------|-----------|
| Archives locales | 14 jours (nettoyage automatique) |
| Backups cloud | archivés dans `wilaya-harmonia/backups` |

### Monitoring

Le guide de contrôle central (`/control`) offre un tableau de bord de supervision (dashboard, users, audit, security, rules, settings).

---

*Développé pour la Wilaya d'Alger — 2026*
