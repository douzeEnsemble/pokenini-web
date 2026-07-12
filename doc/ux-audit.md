# Audit UX/UI — Pokénini Web

Généré à partir de `scripts/screenshot-audit.php` (172 captures, 5 rôles × 3 viewports) puis vérifié dans le code (templates, JS, traductions) pour chaque point à cocher ci-dessous. Rapport visuel complet (captures + contexte) : voir l'artifact HTML partagé séparément.

Les valeurs numériques affichées dans les captures viennent d'un jeu de données de démonstration incohérent — ce n'est pas la matière de cet audit, qui porte uniquement sur le rendu, la mise en page et l'UX.

## Note de méthodologie

`HomeController::index()` (`src/Controller/HomeController.php:25-29`) redirige tout utilisateur connecté vers `app_albumdexlist_index`. Les captures `admin__home__*` / `collector__home__*` de cet audit montrent donc en réalité la page **Album Dex List**, pas une page d'accueil dédiée — c'est voulu, pas un bug. Les points 1 et 2 ci-dessous s'appliquent à cette page unique, vue sous les deux noms de capture.

---

## Haute priorité

- [x] [haute] La bannière "Prêt à choisir ton Pokémon préféré ?" ne se marque comme vue que si l'utilisateur la refuse, jamais s'il l'accepte
    Fichiers : `public/js/album-dex-list.js:1-19`, `templates/AlbumDexList/index.html.twig:35-44`
    Constat : `onCloseJumbotron()` (qui écrit `localStorage['app/album-dex-list/jumbotron/hidden'] = 'true'`) n'est attaché qu'aux éléments `.jumbotron-close` — le bouton "Nan merci" et le X de fermeture. Le lien "C'est parti !" qui mène au vote (`<a href="{{ path('app_electiondexlist_index') }}" ...>`, ligne 36) n'a pas cette classe et ne déclenche donc jamais l'écriture du flag. Un dresseur qui clique sur le CTA principal — l'action désirée — reverra donc la bannière à chaque retour sur cette page, alors qu'un dresseur qui la refuse ne la revoit plus jamais. C'est l'inverse de l'effet recherché.
    Suggestion : écrire aussi le flag `localStorage` au clic sur le lien de vote (ou déclencher `onCloseJumbotron` sur les deux types d'action).
    Capture : `admin__album-dex-list__desktop.png`, `admin__home__desktop.png`
    **Traité** : le lien "C'est parti !" porte désormais la classe `jumbotron-accept` (`templates/AlbumDexList/index.html.twig:36`). `album-dex-list.js` lui attache un handler `rememberJumbotronSeen()` qui écrit le flag `localStorage` sans `preventDefault()` (la navigation vers l'élection continue normalement) ; `onCloseJumbotron` réutilise la même fonction. Vérifié en navigateur réel (Panther/Selenium) : clic sur "C'est parti !" → navigation vers `/fr/election/dex` + flag posé → retour sur `/fr/album/dex` → bannière non réaffichée. Chemin "Nan merci" revérifié, toujours fonctionnel.

- [x] [haute] Grille cassée à 768px pour les albums utilisant le gabarit `list-N` avec N non multiple de 4 (ex. « Méga », N=5)
    Fichier : `templates/Album/view/_list.html.twig:9-29`
    Constat : vérifié en HTML brut — l'album Méga rend `row row-cols-3 row-cols-sm-4 row-cols-md-5`, avec un nouveau `<div class="row">` inséré tous les `nbCaseByLine` (=5) éléments (boucle `loop.index0 % nbCaseByLine`, ligne 23). À 768px, `row-cols-sm-4` est la classe active (4 colonnes visibles) : un bloc de 5 éléments s'y découpe en une ligne pleine de 4 puis une ligne orpheline d'1 seul élément, avant que le bloc suivant reparte à zéro sur une nouvelle ligne — la grille est dentelée sur toute sa hauteur, avec ~75% de la largeur inexploitée sur les lignes orphelines. Les gabarits `box` (Home, Or/Argent/Cristal, Rouge/Vert/Bleu/Jaune, tous confirmés propres au même viewport) n'ont pas ce problème car `_box.html.twig` ne pré-découpe pas la liste en blocs fixes — un seul conteneur flex laisse Bootstrap gérer le retour à la ligne nativement.
    Suggestion : abandonner le découpage manuel en blocs de `nbCaseByLine` et laisser un seul conteneur `row row-cols-*` par boîte (comme `_box.html.twig`), ou recalculer dynamiquement la taille de bloc selon le breakpoint actif.
    Capture : `admin__album-mega__tablet.png` vs `admin__album-home__tablet.png` (même viewport, comparaison directe)
    **Traité** : suppression du découpage manuel en blocs de `nbCaseByLine` — tous les éléments partagent désormais un seul `<div class="row {{ rowColsClasses }} album-line">`, comme la branche `filters is not empty` de `_box.html.twig`. Bootstrap gère le retour à la ligne nativement à chaque breakpoint, sans notion de "bloc". Vérifié en navigateur réel à 768px : un seul `.album-line` pour les 50 items de l'album Méga, grille continue à 4 colonnes sur toute la hauteur, plus aucune ligne orpheline.

- [x] [haute] Libellés des 5 filtres de l'espace dresseur tous tronqués sur mobile, + coquille "Filter" → "Filtrer"
    Fichiers : `templates/Trainer/Section/_dex_filters.html.twig:32-49` (markup `form-floating`), `translations/messages+intl-icu.fr.yaml:255-279` (texte)
    Constat : à 375px, les 5 libellés `trainer.filters.attributes.*.label` débordent du `form-floating` et sont coupés par une ellipse : "Filtrer les albums privées/p…", "…sur l'accu…", "…disponibl…", "…chromatiq…", "…premiums …". Le libellé tronqué ne dit plus ce que le filtre fait. Par ailleurs les clés `shiny` (ligne 271) et `premium` (ligne 276) utilisent "Filter" au lieu de "Filtrer".
    Suggestion : raccourcir les 5 libellés (ex. "Privé/Public", "Sur l'accueil", "Disponible", "Chromatique", "Premium") pour qu'ils tiennent sur mobile, et corriger la coquille "Filter" → "Filtrer".
    Capture : `admin__trainer__mobile.png`
    **Traité** : les 5 `label` de `translations/messages+intl-icu.fr.yaml:255-279` raccourcis en "Privé/Public", "Sur l'accueil", "Disponible", "Chromatique", "Premium" (coquille "Filter" éliminée avec le reste du texte). Mêmes libellés raccourcis en anglais (`messages+intl-icu.en.yaml:250-273` : "Private/Public", "On home", "Released", "Shiny", "Premium") pour ne pas laisser le même bug côté EN. Vérifié en navigateur réel à 375px : les 5 libellés s'affichent en entier, plus aucune ellipse.

---

## Priorité moyenne

- [ ] [moyenne] Badge de statut « Non » stylé comme un lien hypertexte classique
    Fichiers : `templates/Album/_album_macros.html.twig:61-63`, `public/css/album.css:73`
    Constat : le badge est un vrai `<a href="#{{ item.pokemonSlug }}" class="link-dark album-case-catch-state-label">` avec `text-decoration: underline` toujours actif (pas seulement au survol) — visuellement indiscernable des vrais liens de navigation de la page, alors qu'il ouvre un contrôle d'édition inline plutôt que de naviguer.
    Suggestion : retirer le `text-decoration: underline` de ce composant et garder uniquement le style "badge" (fond coloré) déjà en place ; réserver le soulignement aux vrais liens.
    Capture : `admin__album-home__desktop.png`

- [ ] [moyenne] Icônes filtre positif/négatif (`bi-filter-circle` / `bi-filter-circle-fill`) peu distinctes à ce format, dépendantes d'un tooltip absent au tactile
    Fichier : `templates/Album/_report.html.twig:33,50` (+ ligne "Total" : 80, `bi-funnel`/`bi-funnel-fill`)
    Constat : chaque ligne du tableau Stats porte une icône vide et une pleine, avec un intitulé explicite déjà présent via `data-bs-title` (tooltip Bootstrap) — mais un tooltip ne se déclenche qu'au survol souris, absent sur mobile/tablette (la majorité des viewports de cet audit). Au premier coup d'œil les deux variantes (contour vs plein) sont presque identiques à cette taille.
    Suggestion : renforcer le contraste visuel entre les deux états (couleur, taille, ou familles d'icônes différentes) plutôt que de compter uniquement sur le tooltip pour les distinguer sur tactile.
    Capture : `admin__album-home__desktop.png`

- [ ] [moyenne] Barre "100%" en rouge sur les pages d'album, sans indication de ce qu'elle mesure
    Fichier : `templates/Album/_report.html.twig:9` (macro `progressBar.catchStateBar`)
    Constat : la barre affiche uniquement "100%" en toutes lettres ; il s'agit en réalité de "100% non capturés" (le rouge correspond à la ligne "Non" de la légende juste en dessous), lisible seulement en croisant avec le tableau.
    Suggestion : ajouter le libellé de l'état dans la barre elle-même (ex. "100% non capturés"), ou reprendre le style segmenté multicolore déjà utilisé sur les cartes de la page Album Dex List / accueil anonyme.
    Capture : `admin__album-home__desktop.png`

- [ ] [moyenne] Paragraphe entier en couleur "lien" sur la page Salle d'attente
    Fichier : `templates/OuterRoom/index.html.twig` (`<p class="text-primary">{{ 'outer_room.message'|trans(...) }}</p>`)
    Constat : seul le bouton mailto juste en dessous est cliquable, mais tout le paragraphe explicatif hérite de la couleur lien Bootstrap (`text-primary`), au risque de faire croire que des phrases entières sont cliquables.
    Suggestion : retirer `text-primary` du paragraphe, ne garder la couleur d'accent que sur les éléments réellement interactifs (le bouton mailto, "Se déconnecter").
    Capture : `uninvited__outerroom__desktop.png`

---

## Basse priorité

- [ ] [basse] Page 404 : grand vide visuel, aucun élément de marque
    Fichier : `templates/bundles/TwigBundle/Exception/error404.html.twig` → `error.html.twig`
    Constat : le texte d'erreur est bien tourné mais laisse un très grand vide vertical en dessous sur desktop, sans mascotte ni illustration — se lit au premier regard comme une page cassée plutôt qu'un état d'erreur soigné.
    Suggestion : recentrer le bloc verticalement ou ajouter un petit élément graphique (la mascotte déjà présente dans le bandeau du bas s'y prêterait).
    Capture : `admin__error-404__desktop.png`

- [ ] [basse] Coquille : "Je choisi celui-là" → "Je choisis celui-là"
    Fichier : `translations/messages+intl-icu.fr.yaml:472` (`election.choose.action`)
    Capture : `admin__election-mega__desktop.png`

- [ ] [basse] Camembert admin à hachures plutôt qu'aplats de couleur, peu lisible pour seulement 3 parts
    Constat : les remplissages texturés (rayures/pois) ajoutent du bruit visuel sans aider à distinguer 3 catégories déjà différenciées par teinte et par la légende en tableau à côté.
    Suggestion : passer à des aplats de couleur simples.
    Capture : `admin__admin-reports__desktop.png`

- [ ] [basse] Deux styles de barre de progression sans légende sur les cartes de dex (pleine segmentée vs hachurée)
    Constat : les cartes de dex de la page Album Dex List / accueil anonyme utilisent tantôt une barre pleine segmentée multicolore, tantôt une barre à hachures diagonales — la différence de sens (probablement "non démarré"/"verrouillé" vs "en cours") n'est expliquée nulle part sur la page.
    Suggestion : ajouter une légende courte, ou un `title`/tooltip sur la barre elle-même.
    Capture : `visitor__home__desktop.png`, `admin__album-dex-list__desktop.png`
