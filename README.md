# 🏛️ Wilaya Harmonia

> **Plateforme de gestion événementielle citoyenne** — Bordeaux de la participation civique en Algérie. Les Associations soumettent des demandes d'opérations (nettoyage, plantation, réhabilitation…), la Wilaya les valide, les programme et génère un QR code de check-in ; les Citoyens participent, gagnent des points et badges, et les EPIC (ADE, NETCOM, ASROUT, EDEVAL) coordonnent les interventions.

[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](https://www.php.net)
[![MariaDB](https://img.shields.io/badge/MariaDB-10.6%2B-003545?logo=mariadb&logoColor=white)](https://mariadb.org)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind%20CSS-3-06B6D4?logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
[![Leaflet](https://img.shields.io/badge/Leaflet-OpenStreetMap-199900?logo=leaflet&logoColor=white)](https://leafletjs.com)
[![PWA](https://img.shields.io/badge/PWA-Progressives-5A0FC8?logo=pwa&logoColor=white)](https://web.dev/progressive-web-apps)
[![License](https://img.shields.io/badge/license-MIT-3DA639)](#licence)

Plateforme **100 % auto-hébergée** : PHP 8.2+ · MariaDB/MySQL · PDO préparé · RBAC 7 niveaux · i18n FR/AR **RTL** · PWA · conformité RGPD.

---

### ✨ Points forts

- **Cycle de vie complet** des opérations avec validation Wilaya, programmation, QR check-in, journal d'audit et historique immuables.
- **QR codes** sécurisés (UUID v4, fenêtre de validité, anti re-scan) & **gamification** citoyenne (points, badges, classement).
- **Coordination EPIC** (ADE, NETCOM, ASROUT, EDEVAL) et **espaces dédiés** Association / Citoyen / Membre.
- **Carte Leaflet / OpenStreetMap**, **i18n FR/AR RTL**, **PWA hors-ligne** et **CMS Landing** administrable.
- **Backup automatisé** (MySQL + uploads) vers Google Drive via `wilaya-backup.sh`.

---

## Démarrage rapide

Prérequis : PHP ≥ 8.2 (`pdo_mysql`, `mbstring`, `gd`, `openssl`), MariaDB ≥ 10.6, Composer.

```bash
composer install
cp .env.example .env          # renseigner DB_* (utilisateur dédié recommandé)
php artisan key:generate
php artisan migrate:all       # schéma + références + données démo + landing
php -S 0.0.0.0:8080 -t public # serveur de dev
```

Worker de file (notifications, SLA, QR différés) :

```bash
php queue/worker.php --watch        # daemon
# ou passe unique :
php queue/worker.php
# SLA (cron recommandé) :
php artisan sla:run
```

Avec Docker :

```bash
docker compose up -d --build   # db + app (http://localhost:8080) + worker
```

## Tests

La suite s'exécute sur une base dédiée `wilaya_harmonia_test` (créée automatiquement ; l'utilisateur `DB_USERNAME` doit disposer des droits).

```bash
composer test                   # phpunit
composer analyse                # phpstan (niveau 6)
vendor/bin/phpcs app            # conventions de code
```

## Comptes de démonstration

Mot de passe commun : `Harmonia@2026`

| Rôle | Email | Périmètre |
|------|-------|-----------|
| Wilaya | `wilaya@wilaya-harmonia.dz` | validation, programmation, QR, audit, statistiques, CMS landing |
| Association | `president@elamel.dz` | créer/modifier une demande, évaluation, récit |
| Association | `president@vert.dz` | idem + événement terminé avec album |
| Association | `president@bbo.dz` | demande en attente |
| Membre | `membre1@elamel.dz` | suivi des événements de son association |
| EPIC | `ade@epic.dz` · `netcom@epic.dz` · `asrout@epic.dz` · `edeval@epic.dz` | calendrier, interventions |
| Citoyen | `amina@citoyen.dz` … `sami@citoyen.dz` | check-in QR, points, badges, classement |

## Fonctionnalités

- **Cycle de vie des événements** : EN_ATTENTE → (validation Wilaya) → PROGRAMME → (check-in) → TERMINE ; refus et demandes de modifications motivés, avec **journal d'audit** et **historique** immuables.
- **QR Codes** (Endroid v4/v5) : token UUID v4, fenêtre de validité, anti re-scan (double check-in refusé), scan webcam + API.
- **Gamification** : 50 pts/participation, badges (`first_event`, `10_events`, `50_events`, `100_scans`, `1000_scans`), classement et profil citoyen.
- **EPIC** : affectation aux événements programmés, calendrier, interface de consultation.
- **Albums & récit officiel** : photos, récit rédigé par la Wilaya, évaluation (1–5) par l'association.
- **Géolocalisation** : carte Leaflet des opérations par commune/circonscription administrative (CA).
- **PWA** : manifeste, service worker, notifications push (VAPID optionnel), mode hors-ligne.
- **i18n** : FR/AR avec rendu **RTL**, traductions exposées via `GET /api/lang/{locale}`.
- **Landing CMS** : contenu administrable (témoignages, FAQ, partenaires) par la Wilaya.

## API

| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `/api/evenements` | Opérations (filtres `statut`, `q`, `limit`) |
| GET | `/api/evenements/{id}` | Détail d'un événement |
| GET | `/api/map` | Points géolocalisés |
| GET | `/api/stats` | Statistiques globales |
| GET | `/api/lang/{locale}` | Traductions (`fr`/`ar`) |
| GET | `/api/checkin/verify/{token}` | Validation d'un QR |
| POST | `/api/checkin/{token}` | Enregistrement d'une participation (`{"user_id": 12}`) |
| POST | `/api/push/subscribe` · `unsubscribe` | Abonnement push PWA (session requise) |

Collection Postman : `docs/WilayaHarmonia.postman_collection.json`.

## Structure

```
app/
  Config/        configuration (database, paths, security, ia, push)
  Controllers/   logique métier (web + Api/)
  Helpers/       Database, Router, Rbac, Validator, Session, QrCodeGenerator,
                 Gamification, SlaHelper, AuditLog, Notification, Queue, WebPush…
  Middleware/    AuthMiddleware, CsrfMiddleware, RoleMiddleware
  Jobs/          tâches de file (QR, notifications, SLA)
  Models/        entités
  Routes/        web.php, api.php
  Views/         vues (layouts, evenements, wilaya, associations, epic, citoyen…)
public/          index.php (CSP + en-têtes sécurité), assets, sw.js, manifest.json
sql/             001_schema → 004_landing_content (idempotents)
queue/worker.php worker de file
tests/           suite PHPUnit (base wilaya_harmonia_test)
docs/            collection Postman
```

## Sécurité & RGPD

- Toutes les requêtes SQL passent par **prepared statements** (PDO, `EMULATE_PREPARES=false`).
- Mots de passe **bcrypt** ; sessions sécurisées (HttpOnly, SameSite=Lax, régénération au login), **CSRF** sur toutes les écritures.
- **RBAC** : 7 niveaux hiérarchiques (citoyen → wilaya), permissions dynamiques et portée de données par rôle.
- En-têtes de sécurité (CSP, X-Frame-Options, Referrer-Policy) appliqués via `public/index.php`.
- Journal d'audit des actions sensibles ; **anonymisation** possible des données citoyennes (pseudonymisation `prenom` → initiales).
- `.env` hors Git ; aucune clé en dur (VAPID/Gemini optionnels, vérifiés à vide).
