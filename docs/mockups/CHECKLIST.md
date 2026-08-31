# Checklist de relecture — Landing « Wilaya Harmonia »

> Grille de vérification à l'usage de l'équipe projet (chef de projet, UX,
> développeurs). Cocher chaque critère avant validation.

## 1. Navbar
- [ ] Fond vert forêt `#1A4D3E` avec dégradé vers un vert plus clair en bas
- [ ] Liens présents : Accueil · Carte des interventions · Événements · Anomalies · Contact
- [ ] Survol des liens : soulignement / surbrillance dorée `#D4AF37`
- [ ] Bouton « Se connecter » : fond plein clair, texte vert foncé
- [ ] Bouton « S'inscrire » : contour doré, fond transparent, texte doré
- [ ] Effets de survol sur les deux boutons (légère accentuation)
- [ ] Logo à gauche : blason stylisé avec un arbre + mention « Wilaya Harmonia »
- [ ] Navbar fixe au défilement (sticky)

## 2. Section héroïque
- [ ] Arrière-plan : dégradé vert foncé → vert émeraude
- [ ] Illustration : volontaires diversifiés (femmes, hommes, jeunes, personnes âgées) plantant / nettoyant / réparant
- [ ] Titre centré « ENSEMBLE pour notre Wilaya : Agissons pour l'environnement. » — blanc, gras, 48 px, ombre légère
- [ ] Sous-titre conforme au brief
- [ ] Compteurs dynamiques : « 1 200+ arbres plantés » · « 55 événements organisés » · « 150 volontaires mobilisés »
- [ ] Compteurs animés à l'arrivée sur la page (Intersection Observer)
- [ ] Bouton « Je participe » (vert, grande taille)
- [ ] Bouton « Signaler une anomalie » (contour doré)
- [ ] Disposition : texte centré, illustration à droite

## 3. Cartes statistiques
- [ ] 3 cartes en verre dépoli (glassmorphism) : fond semi-transparent, flou, bordure fine dorée
- [ ] Chaque carte : icône (FontAwesome) + chiffre clé + description + barre de progression
- [ ] « Anomalies signalées » + barre de résolution
- [ ] « Événements à venir » + places restantes
- [ ] « Associations partenaires » + taux de participation
- [ ] Cliquer sur une carte redirige vers une section dédiée (lien fictif)
- [ ] Effet au survol : élévation + ombre portée

## 4. « Comment ça fonctionne ? »
- [ ] Parcours en 3 étapes (icônes + texte) :
      1. Je signale une anomalie ou je crée un événement
      2. La Wilaya valide et affecte les EPIC
      3. Je participe, je consulte l'album, je donne mon avis
- [ ] Icônes colorées (vert/or)
- [ ] Connecteur visuel entre les étapes

## 5. Témoignages / réalisations
- [ ] Mini-carrousel de citations (citoyens / associations) avec navigation
- [ ] Photos de réalisations (avant/après) — comparateur interactif

## 6. Footer
- [ ] Liens utiles : Mentions légales · CGU · Contact · FAQ
- [ ] Logo + adresse de la Wilaya
- [ ] Réseaux sociaux (icônes)
- [ ] Fond vert sombre, texte clair

## 7. Widget d'assistance (chat)
- [ ] En bas à droite : bulle arrondie avec icône message + texte « Comment puis-je vous aider ? »
- [ ] Au survol : popup miniature avec champ de saisie simplifié
- [ ] Légère pulsation pour attirer l'attention

## Interactions & animations
- [ ] Compteurs animés à l'arrivée (JS / Intersection Observer)
- [ ] Cartes statistiques : élévation + ombre au survol
- [ ] Boutons CTA : transition douce 0,3 s
- [ ] Widget chat flottant en permanence, pulsation

## Contraintes techniques
- [ ] Intégration possible avec Bootstrap 5 / Tailwind (cohérence avec les composants du projet)
- [ ] Icônes issues de Font Awesome 6 (vendored : `public/assets/vendor/fontawesome/`)
- [ ] Images libres de droits et optimisées (illustrations SVG inline, aucune requête externe)
- [ ] Code responsive (mobile-first) — maquette livrée en desktop 1440 × 900
- [ ] Aucune erreur JavaScript en navigation normale (compteurs, carrousel, chat)
- [ ] HTML sémantique (`header`, `nav`, `section`, `article`, `footer`, `main`)
- [ ] Accessibilité : `aria-label`, focus visible, contrastes, `role="progressbar"`

## Rappel du contexte fonctionnel (cohérence métier)
- [ ] La landing reflète l'engagement collectif (citoyens, associations, EPIC, wilaya)
- [ ] La notion de transparence est visible (barres de progression, chiffres)
- [ ] Les 4 rôles cibles sont identifiables dans les parcours et CTA
