# Maquette haute-fidélité — Landing page « Wilaya Harmonia »

> Livrables de la conception UI/UX de la page d'accueil (phase maquette).
> Format : **web desktop 1440 × 900 px** (responsif mobile inclus côté code).

## Fichiers livrés

| Fichier | Rôle |
|---|---|
| `landing.html` | Maquette HTML/CSS/JS **autonome** (aucune dépendance serveur). Icônes Font Awesome 6 (vendored dans `public/assets/vendor/fontawesome/`), illustrations 100 % SVG inline (libres de droits). |
| `landing-hero-1440x900.png` | Capture du **viewport desktop** (héro + navbar) à 1440 × 900 px. |
| `landing-full.png` | Capture **page entière** (1440 × 5000 px) pour une relecture de bout en bout. |
| `COMPOSANTS.md` | Description des composants interactifs (animations, comportements, accessibilité). |
| `CHECKLIST.md` | Checklist de relecture par l'équipe projet. |

## Afficher la maquette

Ouvrir `landing.html` dans un navigateur (double-clic ou via le serveur de dev) :

```bash
# Option 1 — visualisation directe (animations actives)
xdg-open docs/mockups/landing.html

# Option 2 — re-générer les captures PNG (nécessite Chromium headless)
chromium --headless=new --disable-gpu --no-sandbox --hide-scrollbars \
  --screenshot=landing-hero-1440x900.png --window-size=1440,900 \
  "file://$PWD/docs/mockups/landing.html?render=1"
```

> Le paramètre `?render=1` fige l'état final (compteurs terminés, barres de
> progression pleines, éléments révélés) : c'est le mode utilisé pour les
> captures. Sans ce paramètre, toutes les animations sont actives.

## Charte graphique appliquée

| Rôle | Valeur |
|---|---|
| Vert forêt | `#1A4D3E` |
| Vert émeraude (dégradés) | `#14745C` · `#27604E` |
| Or | `#D4AF37` · dérivés `#E8CD7E` / `#F6EDD4` |
| Blanc cassé | `#FAF6EC` |
| Police | Inter (400 → 900) |
| Icônes | Font Awesome 6 (`fa-solid` / `fa-brands`) |

## Structure de la page (ordre)

1. Navbar fixe (dégradé vert forêt, liens + 2 CTA)
2. Héro (dégradé vert profond → émeraude, titre 48 px, compteurs animés, 2 CTA, illustration des volontaires)
3. Cartes statistiques glassmorphism (3 cartes + barres de progression)
4. « Comment ça fonctionne ? » (parcours en 3 étapes)
5. Témoignages (mini-carrousel auto)
6. Avant / après (2 curseurs de comparaison)
7. Footer (liens utiles, adresse, réseaux sociaux)
8. Widget d'assistance (chat flottant + popup au survol)
