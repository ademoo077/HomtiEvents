# Wilaya Harmonia — Agent Instructions

## Project Overview
Custom PHP (no framework) + MariaDB + Bootstrap 5.3 + MDI icons. Bilingual FR/AR with RTL support.
- Dev server: `php -S localhost:8089 -t public` (MariaDB on 3306)
- DB: `wilaya_harmonia`, user `root` / `Harmonia@2026`
- Entry point: `public/index.php` → `App\Helpers\Router`
- URL: `http://localhost:8089`

## Key Architecture Facts
- **Routes**: `app/Routes/web.php` — `{param}` passed as **positional args** to controller methods
- **Controllers**: Extend `App\Controllers\Controller`; use `renderContent()` for partial renders (no layout)
- **Views**: `app/Views/` — PHP templates with `<?= e() ?>` escaping, `url()`, `csrf_field()`, `__()` i18n
- **Services**: `app/Helpers/*.php` — static classes, DB via `App\Helpers\Database`
- **Auth/RBAC**: `App\Helpers\Rbac::role($user)`, `can('permission')`, `Session::userId()`
- **CSS**: Institutional design system in `public/assets/css/admin.css` (`--wh-*` vars). Control Center uses `control-center.css` (imported after admin.css in `main.php:117`)
- **JS**: Vanilla only, no frontend frameworks. RTL = CSS logical properties only

## Critical Workflows

### Event Status State Machine (EvenementService::STATUTS)
```
EN_ATTENTE → VALIDÉ → PROGRAMME → QR_GENERE → EN_COURS → TERMINE
     ↓           ↓           ↓
  REFUSE      MODIF_DEM → EN_ATTENTE (re-soumission)
     ↓           ↓
  ANNULE      ANNULE
```
Allowed transitions in `EvenementService::transitionAutorisee()` (line 50-57).

### Modification Request Flow (Admin → Association → Admin)
**Current bug**: Association edits event in `MODIFICATION_DEMANDEE` status but `EvenementService::update()` (line 257) **does not transition back to `EN_ATTENTE`**. Status stays `MODIFICATION_DEMANDEE` after correction.

**Files involved**:
- `EvenementService::demanderModifications()` (line 691) — Admin requests mods, sets `MODIFICATION_DEMANDEE` + `motif_refus`, notifies association
- `AssociationController::update()` (line 483) — Association submits corrections, calls `EvenementService::update()`, sends "re-soumis" notification but **status unchanged**
- `AssociationController::edit()` (line 440) — Shows edit form for `EN_ATTENTE`, `MODIFICATION_DEMANDEE`, `REFUSE`
- `app/Views/association/events.php:183-186` — Shows "Corriger" button linking to `/association/{id}/edit`

**Fix needed**: Add `EvenementService::resoumettre(int $id)` to transition `MODIFICATION_DEMANDEE → EN_ATTENTE` (allowed per state machine) and call it from `AssociationController::update()` when status was `MODIFICATION_DEMANDEE` or `REFUSE`.

### Event Creation Differences
| Actor | Can set status | Can set date/heure/EPIC |
|-------|----------------|-------------------------|
| Wilaya (admin) | EN_ATTENTE, VALIDÉ, PROGRAMME | Yes (if PROGRAMME) |
| Association | EN_ATTENTE only (hidden) | **Never** — date/EPIC set by Wilaya only |

Wilaya create: `AdminEvenementController::store()` (line 330) → `EvenementService::create()` → if `PROGRAMME` calls `programmer()` immediately.
Association create: `AssociationController::store()` (line 137) → `EvenementService::create()` with forced `EN_ATTENTE`.

## Control Center Redesign (In Progress)
Phases 1-3 done. Phases 4-7 **blocked** — `control-center.css` truncated at 110 lines (second half lost). Views needing rewrite:
- Tables: `users.php`, `epics.php`, `associations.php`, `communes.php`, `rules.php` (Phase 4: avatars, kebab menus, search, empty states)
- Forms: `user-form.php`, `association-form.php`, `epic-form.php` (Phase 5: validation, password strength)
- Responsive cards, tab overflow, transitions, keyboard shortcuts (Phases 6-7)

## Common Commands
```bash
# Start dev stack
mysql -u root -pHarmonia@2026 -e "CREATE DATABASE IF NOT EXISTS wilaya_harmonia;"
php -S localhost:8089 -t public

# DB inspection
mysql -u root -pHarmonia@2026 wilaya_harmonia -e "SELECT * FROM evenements LIMIT 5;"

# Clear PHP session errors (flash messages)
rm -f /tmp/sess_*
```

## Gotchas
- `Controller::renderContent()` returns string (no layout) — used for AJAX tab fragments
- Route params are **positional**: `GET /control/tab/{tab}` → `tabFragment(string $tab)`
- `Database::one()` returns `null` if not found — always check before dereferencing
- `abort(code, message)` calls `exit()` — never use in background workers/crons
- RTL: use `margin-inline-start/end`, `padding-inline`, `border-inline`, `float: inline-start/end`
- Flash messages: `flash('success|error', 'msg')` + `redirect()` — read in view via `$this->flash()`

## High-Value Files to Know
| File | Purpose |
|------|---------|
| `app/Helpers/EvenementService.php` | Event lifecycle, state machine, notifications, QR, SLA (935 lines) |
| `app/Controllers/AdminEvenementController.php` | Wilaya event CRUD + workflow actions (577 lines) |
| `app/Controllers/AssociationController.php` | Association event CRUD + edit permissions (639 lines) |
| `app/Helpers/StatsService.php` | Chart data for dashboard (`evolutionMensuelle`, `parStatut`) |
| `app/Helpers/RoutingService.php` | EPIC assignment logic based on commune+anomalies |
| `app/Views/layouts/main.php:117` | CSS import order (admin.css → control-center.css) |

## Next Priority Tasks
1. **Fix modification re-submission**: Add `EvenementService::resoumettre()` + call in `AssociationController::update()`
2. **Complete control-center.css** (Phases 4-7 CSS)
3. **Rewrite 8 Control Center views** (5 tables + 3 forms)
4. **Improve admin "demander modifications" UX**: Modal with motif input instead of inline dropdown