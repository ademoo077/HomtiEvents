# Dashboard Futuriste — Documentation technique

## Accès

- **URL** : `/wilaya/dashboard-futur`
- **Route nommée** : `wilaya.dashboard.futur`
- **Middleware** : `AuthMiddleware` + `RoleMiddleware:wilaya`
- **Redirection** : `/wilaya` redirige automatiquement vers le nouveau dashboard

## Architecture

Le dashboard est construit selon un modèle **SPA-like asynchrone** :
- La page principale (`dashboard_futur.php`) charge instantanément la structure HTML.
- Chaque widget est récupéré via des appels AJAX indépendants (`dashboard-futur.js`).
- Le layout (`layouts/dashboard-futur.php`) fournit la sidebar, le header et les scripts globaux.

### Fichiers livrés

| Fichier | Rôle |
|---------|------|
| `app/Controllers/DashboardFuturController.php` | Contrôleur API — toutes les endpoints AJAX |
| `app/Views/layouts/dashboard-futur.php` | Layout HTML global |
| `app/Views/wilaya/dashboard_futur.php` | Vue principale (structure des sections) |
| `public/assets/css/dashboard-futur.css` | Design néomorphique + glassmorphisme |
| `public/assets/js/dashboard-futur.js` | Logique frontend (AJAX, Chart.js, Leaflet, animations) |

## Endpoints AJAX

Tous les endpoints sont sous le préfixe `/api/dashboard/` — authentifiés via `RoleMiddleware:wilaya`.

| Méthode | Endpoint | Description | Cache |
|---------|----------|-------------|-------|
| GET | `/api/dashboard/kpi` | KPI globaux + évolutions (30j glissants) | 120s |
| GET | `/api/dashboard/charts` | Graphiques (statuts, évolution, top EPIC/communes/anomalies) | 120s |
| GET | `/api/dashboard/carte` | Carte Leaflet — événements aujourd'hui + heatmap | 60s |
| GET | `/api/dashboard/demandes?page=` | File d'attente EN_ATTENTE (paginé) | 60s |
| GET | `/api/dashboard/timeline` | Flux d'activité (audit_logs) | 60s |
| GET | `/api/dashboard/sla` | Alertes SLA (deadline j-2, j-1, retard) | 60s |
| GET | `/api/dashboard/calendrier?statuts=&commune_id=&epic_id=` | Événements pour FullCalendar | 60s |
| GET | `/api/dashboard/associations?page=&filtre=` | Gestion associations (attente/validees/toutes) | 60s |
| GET | `/api/dashboard/epic-anomalies` | Matrice EPIC ↔ anomalies | 60s |
| GET | `/api/dashboard/statistiques?periode=&date_from=&date_to=` | Graphiques avancés (barres, radar, treemap) | 60s |
| GET | `/api/dashboard/notifications` | Notifications non lues + récentes | 60s |
| POST | `/api/dashboard/epic-anomalies/link` | Lien/délien EPIC↔anomalie (toggle) | — |
| POST | `/wilaya/demandes/{id}/accept` | Accepter une demande d'événement | — |
| POST | `/wilaya/demandes/{id}/refuse` | Refuser une demande (motif) | — |
| GET | `/admin/landing/json` | Données CMS (settings, FAQ, témoignages, partenaires, médias) | — |

### Sécurité
- Tous les endpoints AJAX sont protégés par `AuthMiddleware` + `RoleMiddleware`.
- Les mutations (POST) passent par le `CsrfMiddleware` global (token `_token` ou header `X-CSRF-TOKEN`).
- Toutes les valeurs sont échappées côté serveur (`e()`).
- Les requêtes SQL utilisent des prepared statements (PDO).

## Design — Néomorphisme & Glassmorphisme

### Palette de couleurs
```css
--accent-cyan:   #00f0ff  (cyan néon)
--accent-indigo: #6c63ff  (indigo violet)
--accent-gold:   #ffd700  (or)
--accent-green:  #22c55e  (vert validation)
--accent-amber:  #f59e0b  (ambre/attente)
--accent-red:    #ef4444  (rouge urgence)
--bg-primary:    #0b0f19  (fond sombre)
```

### Effets visuels
- **Glassmorphisme** : `backdrop-filter: blur(20px) saturate(180%)` sur sidebar, cards, modals.
- **Néomorphisme** : ombres internes/externes (`--shadow-neo` / `--shadow-neo-inset`) sur les cartes.
- **Bordures lumineuses** : glow sur cards au survol + indicateurs pulsants.
- **Canvas particules** : toile de fond animée (`<canvas id="bg-particles">`).
- **Compteurs animés** : `Number.toLocaleString()` + `Intl.NumberFormat`.
- **Respiration** : `@keyframes breathe` sur les indicateurs critiques.

## Thèmes

Le dashboard supporte le mode sombre (défaut) et clair :
- Le bouton de bascule est dans le header (`#themeToggle`).
- Le thème est persisté dans `localStorage`.
- Le thème clair s'active via `html[data-theme='light']`.

## Langues

- **FR** (default) et **AR** (RTL) via `I18n::locale()`.
- Les dates/heures s'adaptent automatiquement (`toLocaleTimeString`, `toLocaleDateString`).
- Le layout s'adapte au RTL (sidebar, recherche, etc.).

## Navigation

Le dashboard utilise une navigation SPA-like :
- Les sections (`#section-*`) sont cachées/afficheées via `data-section`.
- Les appels AJAX sont gérés par `ajax()` avec token CSRF.
- Les transitions sont fluides (fade-in staggered).

## Carte interactive (Leaflet)

- Centré sur Alger (36.7538, 3.0588) par défaut.
- Heatmap des communes (leaflet-heat).
- Marqueurs colorés par statut (cercle + popup détaillé).
- Tuiles adaptées au thème (dark/light).

## Calendrier (FullCalendar)

- Vues : mois / semaine / jour.
- Couleurs par statut.
- Cliquez sur un événement pour voir le détail.

## CMS Intégré

Depuis l'onglet "CMS Landing", vous pouvez :
- Modifier la section Héro (titre, image, boutons).
- Gérer les statistiques dynamiques.
- Lister/ajouter/supprimer les témoignages.
- Gérer les partenaires (logos, ordre, liens).
- CRUD complet des FAQ.
- Gérer la bibliothèque d'images.
- Prévisualiser en direct via iframe.

## Compte de test

```
Email : wilaya@wilaya-harmonia.dz
Rôle  : wilaya (niveau 7 — toutes permissions)
```

---
*Dashboard conçu pour la Wilaya d'Alger — © Wilaya Harmonia*
