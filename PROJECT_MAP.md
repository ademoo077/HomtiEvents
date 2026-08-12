# PROJECT_MAP — Wilaya Harmonia

> Document de mémoire externe — généré le 2026-08-12.
> Mise à jour obligatoire à chaque fin de phase.

## [TECH_STACK]

| Couche | Technologie | Version verrouillée | Statut |
|--------|-------------|--------------------:|--------|
| PHP | Runtime serveur | 8.4.23 (Debian) — requis `>=8.2` | ✔ actif |
| Base de données | MariaDB | 11.8.6 | ✔ actif |
| ORM/DB | PDO préparé maison (`App\Helpers\Database`, `EMULATE_PREPARES=false`) | — | ✔ actif |
| Frontend | Bootstrap (vendored `public/assets/vendor/bootstrap/`) | 5.3.3 | ⚠ 5.3.8 dispo (à laisser — pas de montée requise) |
| Graphiques | Chart.js (vendored) | 4.4.3 | ⚠ 4.5.1 dispo |
| Calendrier | FullCalendar (vendored) | 6.1.15 | ⚠ 7.0.2 dispo (breaking) |
| Maps | Leaflet + leaflet-heat (vendored) | vendored | ✔ actif |
| Lightbox | SimpleLightbox (vendored) | vendored | ✔ actif |
| QR Code | `endroid/qr-code` | 5.1.0 | ✔ actif |
| PDF | `dompdf/dompdf` | 3.1.6 | ✔ actif |
| Icons | Font Awesome / MDI (vendored) | vendored | ✔ actif |
| Tests | PHPUnit | 10.5.64 | ✔ actif |
| Statique | PHPStan | 1.12.34 (niveau 6) | ✔ actif |
| Lint | PHP_CodeSniffer | 3.13.5 | ✔ actif |
| Cache | Fichier (`App\Helpers\Cache`, storage/cache) | — | ✔ actif |
| Queue | Fichier (`App\Helpers\Queue`, QUEUE_DRIVER=file) | — | ✔ actif |
| Redis | Config seule (`app/Config/redis.php`) — **aucun client PHP connecté** | — | ⚠ inerte |
| PWA | `public/manifest.json` + `public/sw.js` | — | ⚠ manifest figé `lang:fr dir:ltr` |
| i18n | `app/Helpers/I18n.php`, JSON `lang/fr.json` + `lang/ar.json`, rendu RTL | — | ✔ actif |

Note versions frontend : volontairement figées en vendor/ (hors composer). Aucune mise à jour
de librairie n'est exigée par les phases : le dépôt reste sur Bootstrap 5.3.3 / Chart.js 4.4.3
/ FullCalendar 6.1.15 (versions stables de la ligne utilisée, aucune dépréciation bloquante).

## [SYSTEM_FLOW]

Flux principal (cycle de vie des événements) :

```
Association ──POST demande (create/store)──► EN_ATTENTE
   │                                            │  EvenementService::changeStatut() + transition_history + AuditLog
   ▼                                            ▼
Wilaya  ──valider──► VALIDÉ ──programmer──► PROGRAMME ──gen QR──► QR_GENERE
   │              (notif assoc)        (notif assoc + EPIC)
   │  ──refuser──► REFUSE      (notif assoc + motif_refus)
   │  ──demander modif──► MODIFICATION_DEMANDEE   (notif assoc, badge "action attendue" sur dashboard)
   ▼
Citoyen ──scan QR (evenement_participant + points/badges)──► EN_COURS ──► TERMINE
EPIC    ──affectation (evenement_epic, RoutingService)──► AFFECTE/EN_COURS/TERMINE/ANOMALIE
Wilaya  ──album photos──► publish ──► notifications assoc (+ option participants)
```

Sécurité transverse :
```
Router → AuthMiddleware → RoleMiddleware(:role) → Controller::requirePermission()
      → CsrfMiddleware (toutes écritures) → Database::* (PDO préparé) → AuditLog::log() actions sensibles
```

RBAC : 7 niveaux `citoyen(1) < membre(2)=epic(2) < association(3) < chef_section(4) < chef_unite(5) < wilaya(7)`.
Scoping ABAC via `Rbac::scope($user, $column)` → `sprintf('%s = %d', $column, association_id)`.

## [ARCHITECTURE]

```
public/index.php        Front controller (CSP, headers, bootstrap)
app/Bootstrap.php       Chargement env/config/routeur
app/Config/             app, database, paths, redis (config; redis inerte)
app/Routes/             web.php (400 lignes), api.php
app/Controllers/        Web (29) + Api/ (10)
app/Helpers/            Services sans état statiques : Database, Validator, Rbac, Abac,
                        EvenementService, StatsService, Notification, AuditLog, Csrf,
                        Session, Cache, Queue, Security, Gamification, UploadHelper,
                        RoutingService, QrCodeService, SlaHelper, ControlCenter, ...
app/Jobs/               GenerateQrJob, SendNotificationJob, SendPushJob, SlaAlertJob
app/Middleware/         Auth, Csrf, Role (+ SystemControl)
app/Views/              layouts (main, association, citoyen, landing, guest, dashboard-futur)
                        + partials + espaces (association, wilaya, admin, control, epic, citoyen, auth, qrcode, profile, landing)
sql/                    001→026 (idempotents, numérotation continue — prochain : 027)
tests/                  PHPUnit (base wilaya_harmonia_test) — Admin/Epic/Rbac/Stats/Validator/...Test.php
queue/worker.php        Worker de file (daemon), artisan sla:run
```

Règles de conception maintenues :
- « Favoriser la composition » : Services statiques (`Helpers`) + contrôleurs fins ; pas de couche Service/Orm superflue.
- **Pas de nouveaux fichiers micro** : toute nouvelle capacité s'insère dans un Contrôleur/Helper existant, sauf si le fichier dépasse ~600 lignes (règle appliquée : ControlCenterController = 704 lignes → ne plus y ajouter, nouveau contrôleur si nécessaire).
- Toute action sensible → `AuditLog::log()`. Toute écriture → CSRF global (ne pas dupliquer).
- i18n FR/AR + RTL : `dir="rtl"` piloté par `I18n::direction()` ; ne jamais casser.

## [ORPHANS & PENDING]

### Incohérences / observations (audit de conformité, 2026-08-12)

| # | Observation | Localisation | Impact |
|---|-------------|--------------|--------|
| O1 | **~4 900 lignes non commitées** (53 fichiers) depuis le dernier commit `7c1450f` | tout le tree | ⚠ Basculer vers la suite sans stabiliser = risque de régression invisible |
| O2 | `EpicController::updateStatut()` ne journalise **rien** (`AuditLog` absent) ni ne notifie la Wilaya sur `ANOMALIE` | app/Controllers/EpicController.php:107-138 | ⚠ incohérent avec le reste ; signal opérationnel critique silencieux |
| O3 | `epic/show.php` n'expose que `association_nom` — pas les coordonnées du contact sur place | app/Views/epic/show.php | manque Phase 6 §4 |
| O4 | `public/manifest.json` figé `"lang":"fr","dir":"ltr"` — casse l'install PWA en arabe | public/manifest.json | manque Phase 5 §1 |
| O5 | Centre de notifications : liste plate, pas de regroupement par type (le « Tout marquer comme lu » existe) | app/Helpers/Notification.php, Views/layouts/main.php | manque Phase 5 §5 |
| O6 | **Aucune gestion de membres d'association** (`membre` seedé en RBAC mais rien ne crée ce type de compte) | AssociationController/Dashboard, sql | manque Phase 7 (nécessite migration `027_member_invitations`) |
| O7 | Redis configuré (`app/Config/redis.php`) mais **inerte** : Cache et Queue sont fichiers, aucun client (predis) dans composer.lock | app/Helpers/Cache.php, Queue.php | écart avec l'énoncé Phase 4 §5 |
| O8 | `transition_history` alimenté au niveau `evenements` ; temps EPIC (affectation→clôture) nécessite croisement `evenement_epic.date_affectation` × `transition_history` (statut TERMINE) | sql/008, sql/020 | pour Phase 4 §4 si non couvert |
| O9 | Validation = `backWithErrors()` (redirect complet) : pas de scroll-to-error ni validation client progressive | app/Controllers/Controller.php | manque Phase 5 §2 (progressif) |
| O10 | Squelettes de chargement : partiels (`wh-empty` présents partout) ; skeletons réels absents | Views/wilaya/dashboard.php | manque Phase 5 §4 (optionnel) |
| O11 | Notification « participants » à la publication d'album non implémentée (seule l'association est notifiée) | app/Controllers/EventGalleryController.php:285-296 | Phase 8 §3 (option à valider) |
| O12 | Confirmation destructive `data-confirm` en usage mais pas de **modal unique réutilisable** vérifiée partout (certains `confirm()` JS natifs restent possibles) | Views/wilaya/**, control/**, admin/** | Phase 5 §6 (audit + unifier) |
| O13 | Fichiers SQL non commités 021-026 + nouveaux contrôleurs non commités (Profile, Routing, AssociationGallery…) | — | partie de O1 |

### Actions bloquantes avant la Phase suivante
1. **M0 — stabiliser la base** : passer en revue + commiter le tree en travaillé (O1/O13) avec commits par thème ; `composer test && composer analyse && vendor/bin/phpcs app` verts.
2. Valider les choix d'interprétation signalés (voir briefing).
3. Les chemins de migration `sql/0XX_*.sql` doivent suivre la numérotation existante (prochain libre : `027`).

### Suivi des phases
| Phase | Statut | Commit |
|-------|--------|--------|
| 1 — UI/UX compte association | ✔ livrée | `406bef5` |
| 2 — Gestion comptes panel admin | ✔ livrée | `d5ed364` |
| 3 — CMS landing + galerie | ✔ livrée (+3.1) | `d941911`, `9bd7b41` |
| 4 — Statistiques | ✔ livrée | `ba99ed6` |
| 5 — UI/UX transverse | ◐ partielle | dark mode + toasts + data-confirm + wh-empty + read-all ✔ ; manifest, regroupement, validation client, skeletons restants |
| 6 — Espace EPIC | ◐ partielle | dashboard ✔ (`7c1450f`) ; `updateStatut` audit/notif + contact assoc restants |
| 7 — Membres association | ✖ non démarrée | — |
| 8 — Notifications & réponses | ◐ partielle | refus ✔, QR ✔, album-assoc ✔, toasts ✔ ; participants-album + feedback JSON à vérifier |
