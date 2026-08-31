# Description des composants interactifs — Landing « Wilaya Harmonia »

Ce document décrit chaque composant de `landing.html`, ses animations,
comportements et choix d'accessibilité. Il sert de référence pour le
développement d'intégration (Bootstrap 5 / Tailwind acceptable, cohérence
avec les composants existants du projet).

---

## 1. Barre de navigation (Navbar)

**Structure** : `header.site-nav` fixe (`position: fixed`), hauteur 84 px,
fond `linear-gradient(180deg, #1A4D3E, #27604E)` (vert forêt → vert plus clair
en bas, conformément au brief) + liseré or de 2 px.

- **Logo** : SVG inline « blason stylisé » (bouclier vert forêt bordé d'or,
  arbre doré au centre) + libellé « Wilaya Harmonia » avec le mot-clé
  « Harmonia » en doré.
- **Liens** : Accueil · Carte des interventions · Événements · Anomalies ·
  Contact. Survol : texte vers blanc + **soulignement doré animé**
  (`::after` en `scaleX(0 → 1)`). Lien actif (scrollspy) : même effet,
  maintenu.
- **Boutons** :
  - « Se connecter » : fond blanc cassé, texte vert forêt, élévation au
    survol (`translateY(-2px)`).
  - « S'inscrire » : contour or `#D4AF37`, fond transparent, texte or ;
    au survol le fond passe à l'or avec texte vert forêt.
- **Comportement responsive** : sous 1100 px, les liens se replient derrière
  un bouton hamburger (toggle `aria-expanded`).
- **Accessibilité** : navigation `nav[aria-label]`, focus visible en or
  (`:focus-visible`).

---

## 2. Section héroïque (Hero)

**Structure** : `section.hero` avec dégradé `#0E3A2E → #1A4D3E → #1F6A52 →
#14745C`, texture « grain » en points discrets et deux halos radiaux or/vert
(pur décor, `pointer-events: none`).

- **Titre** : « ENSEMBLE pour notre Wilaya : Agissons pour l'environnement. »
  48 px, blanc, `font-weight: 900`, ombre portée douce ; « Agissons pour
  l'environnement. » en or.
- **Sous-titre** : le texte du brief, blanc translucide.
- **CTA** : « Je participe » (fond or, texte vert forêt, `btn-lg`) et
  « Signaler une anomalie » (contour or, transparent). Transition 0,3 s,
  élévation + léger scale au survol.
- **Compteurs dynamiques** : 3 blocs (arbres plantés **1 200+** · événements
  organisés **55** · volontaires mobilisés **150+**). Chaque nombre est
  animé par **Intersection Observer + `requestAnimationFrame`**
  (`easeOutCubic`, ~2,2 s), avec séparateur de milliers au format français
  (« 1 200 »).
- **Illustration** : SVG inline (620 × 460) — scène de communauté active :
  jeune femme plantant un arbre, homme collectant des déchets, personne âgée
  arrosant, jeune réhabilitant un trottoir, soleil, collines, arbres.
  Légende sous l'illustration : Plantation · Nettoyage · Réhabilitation.
  Panel en verre translucide (`backdrop-filter`).
- **Responsive** : sous 1100 px, colonnes empilées, texte centré.

---

## 3. Cartes statistiques (glassmorphism)

**Structure** : `section.stats` sur fond blanc cassé + halos radiaux or/vert
(pour rendre le flou perceptible) ; grille de 3 cartes.

- **Verre dépoli** : `background: rgba(255,255,255,.5)` +
  `backdrop-filter: blur(16px)`, **bordure fine or**, coin supérieur gauche
  souligné d'un filet or (détail signature).
- **Contenu par carte** : icône Font Awesome dans un carré dégradé vert,
  chiffre clé + libellé, description, **barre de progression stylisée**
  (piste arrondie, remplissage `linear-gradient(90deg, vert forêt →
  émeraude → or)`).
- **Exemples** : « Anomalies signalées » (312 — taux de résolution 78 %) ·
  « Événements à venir » (14 — places restantes 63 %) · « Associations
  partenaires » (28 — taux de participation 85 %).
- **Interactions** : chaque carte est **cliquable** (lien fictif vers la
  section dédiée, `href="#..."`), flèche or apparaissant au survol ;
  élévation + ombre portée or (`translateY(-8px)`).
- **Animation** : les barres de progression passent de 0 à leur valeur via
  Intersection Observer (transition `width` 1,6 s).
- **Accessibilité** : `role="progressbar"` avec `aria-valuenow`.

---

## 4. « Comment ça fonctionne ? »

**Structure** : `section.how`, 3 cartes reliées par un connecteur en pointillés
or (filigrane).

1. **Je signale ou je crée** — icône plume/édition (vert). Tag « Citoyen ·
   Association ».
2. **La Wilaya valide et affecte** — icône bouclier (or). Tag « Wilaya ·
   EPIC ».
3. **Je participe et je donne mon avis** — icône appareil photo (vert).
   Tag « Suivi & évaluation ».

- Numéros en filigrane (01/02/03), icônes dans des carrés arrondis
  alternant vert / or.
- Survol : élévation douce. Révélation au défilement avec décalage
  progressif (0 → 280 ms).

---

## 5. Témoignages & réalisations

**Mini-carrousel de témoignages** (`section.testimonials`) :
- 3 citations (citoyenne, président d'association, chef d'équipe EPIC) avec
  étoiles or, avatar avec initiales.
- **Défilement automatique** (6 s), contrôles précédent/suivant + pastilles
  de sélection. Pause au survol (évite de couper la lecture).
- Transition : fondu + léger translate.
- Accessibilité : boutons avec `aria-label`, pastilles avec `aria-label`.

**Avant / après** (`section.ba`) :
- 2 cartes « réalisations » ; chaque visuel est une **comparaison
  interactive** : curseur or vertical déplaçable (deux SVG superposés,
  `clip-path` piloté par un `<input type="range">`).
- Libellés « Avant » (vert forêt) / « Après » (or).
- Contenu 100 % SVG inline : aucune image externe, aucune licence à gérer.

---

## 6. Footer

**Structure** : `footer.site-footer`, fond dégradé vert sombre
(`#14382D → #0E2C23`), liseré supérieur or.

- Colonne marque : logo blason + description + **réseaux sociaux**
  (Facebook, Instagram, X, YouTube — icônes Font Awesome brands,
  soulignées d'or au survol).
- Colonne Navigation ; colonne **Liens utiles** (Mentions légales, CGU,
  Politique de confidentialité, FAQ).
- Colonne Contact : adresse de la Wilaya, e-mail, téléphone.
- Barre inférieure : copyright, liens légaux, bouton « Haut de page ».
- `aria-label` sur chaque réseau social.

---

## 7. Widget d'assistance (chat)

**Structure** : `.chat-widget` `position: fixed; bottom/right: 1,6 rem`.

- **Bulle** : pilule arrondie vert forêt bordée d'or, icône message + libellé
  « Comment puis-je vous aider ? ». **Pulsation** : anneau or animé
  (`@keyframes pulse`, 2,4 s, en boucle) pour attirer l'attention.
- **Popup miniature au survol** : panneau 340 px (filtre, réponse
  automatique démo, 3 libellés rapides cliquables qui pré-remplissent le
  champ, champ de saisie + bouton d'envoi).
- **Comportement** : ouvert au survol **ou au clic** (`aria-expanded` géré),
  transition 0,28 s ; fermeture au clic sur la bulle.
- **Accessibilité** : `role="dialog"`, `aria-label`, champs labellisés
  (`sr-only`), saisie `autocomplete="off"`.

---

## Animations & transitions (récapitulatif)

| Élément | Animation | Déclencheur |
|---|---|---|
| Compteurs héro | `count 0 → valeur`, easeOutCubic, 2,2 s | Intersection Observer (arrivée sur la page) |
| Barres de progression | `width 0 → %`, 1,6 s | Intersection Observer |
| Révélations de sections | fondu + `translateY(28px)`, 0,7 s, décalages | Intersection Observer |
| Liens navbar | soulignement or `scaleX`, 0,3 s | `:hover` / `.active` |
| Boutons (navbar + CTA) | élévation, ombre, 0,3 s | `:hover` |
| Cartes statistiques | élévation 8 px + ombre or, 0,3 s | `:hover` |
| Carrousel témoignages | fondu + translate, 0,5 s ; auto 6 s | minuterie + contrôles |
| Curseurs avant/après | `clip-path` déplacé | glisser le curseur |
| Bulle chat | pulsation or, 2,4 s en boucle | permanent |
| Popup chat | fondu + scale 0,28 s | survol / clic |

## Mode rendu (`?render=1`)

Pour la capture des PNG, l'état final est figé : révélations appliquées
instantanément, compteurs terminés, barres pleines, pulsation désactivée.
Le comportement interactif complet reste actif en navigation normale.
