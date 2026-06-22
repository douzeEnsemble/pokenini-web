# Plan de migration — Refactoring des réponses API (côté pokenini-web)

> **Pour les workers agentiques :** SOUS-SKILL RECOMMANDÉE : `superpowers:subagent-driven-development` ou `superpowers:executing-plans` pour exécuter ce plan tâche par tâche. Les étapes utilisent la syntaxe checkbox (`- [ ]`).

**Goal :** Adapter `pokenini-web` aux changements de réponses introduits par la migration `pokenini-api/feature/refactoring_responses`, répercutés à l'identique par `pokenini-back` (déjà migré sur la branche `feature/ci`).

**Contexte architectural clé :** `pokenini-web` n'appelle **jamais** l'API directement — il passe par `pokenini-back` (BFF). Or le Back, bien qu'il « suive les mêmes modifications », a délibérément **absorbé en interne** la plupart des breaking changes pour garder son contrat de sortie stable vis-à-vis du Web. La vérité terrain de ce que le Web reçoit, ce sont les fixtures `tests/resources/functional/controller/*` du Back. Leur diff montre que **seuls 3 changements** atteignent réellement `pokenini-web`.

**Tech stack :** PHP 8.4, Symfony 8.0, Twig, Symfony Serializer (désérialisation des `ResponseObject`), Moco (mock HTTP), PHPUnit.

## Contraintes globales

- `declare(strict_types=1)` dans tous les fichiers PHP.
- Classes `final` pour DTO / ResponseObject / tests ; classes non-`final` pour les Services.
- PHPStan niveau 9 + Psalm strict : phpDoc à jour partout.
- 100 % de couverture et 100 % MSI (Infection) requis.
- `make quality` et `make measures` doivent être verts avant push.
- **Aucun commit ne doit être créé.**
- **Aucune exécution de test dans le cadre de ce plan** — les commandes de vérification sont listées à titre indicatif, à lancer manuellement par la suite.

---

## Périmètre réel côté Web : 3 changements

| # | Source (endpoint Back) | Consommé par | Nature du changement | Impact Web |
|---|------------------------|--------------|----------------------|------------|
| 1 | `/labels` → `game_bundles[]` | `ResponseObject\Label\GameBundle` | `generation_slug: "1"` → `generation: { "slug": "1" }` | ResponseObject + fixtures + tests |
| 2 | `/istration/reports` | `Service\Back\GetReportsService` (tableau brut) → templates Admin | `nb` → `count` ; `dex`/`catch_state` deviennent des objets imbriqués (avec `slug`) | 2 templates Twig + fixtures + tests |
| 3 | `election_top[]` (dans la réponse ElectionIndex) | `ResponseObject\Election\TopPokemon` | Structure plate → imbriquée : `pokemon{slug,labels,national_dex_number}`, `forms`, `types{primary,secondary}`, `score{elo,significance}` | ResponseObject (refonte) + macros/template + fixtures + tests |

### Hors périmètre (vérifié — NE PAS toucher)

Ces endpoints sont concernés par `migration.md` côté API mais **n'impactent pas le Web**, car le Back a gardé son contrat de sortie stable (diff `functional/controller/*` vide sur ces points) :

- **`/forms` (consolidation)** — le Back continue d'exposer `/labels` avec les formes en `string[][]` inchangées. `ResponseObject\Label\Labels` et les `AbstractForm` ne bougent pas.
- **`/action_logs` (objet → tableau)** — absorbé par le Back ; sa sortie `/istration/action-logs` reste un objet keyé par `action_type`. `ActionLog` / `ActionLogData` inchangés.
- **`/election/metrics` (`completion`)** — absorbé par le Back ; le bloc `metrics` de la réponse ElectionIndex reste plat (`view_count_sum`, `under_max_view_count`, `max_view_count`…). `DTO\ElectionMetrics` inchangé.
- **`/election/vote` (`trainer` imbriqué)** — c'est un body de requête côté Back→API ; le Web envoie son propre format au Back, inchangé.
- **Album, `/pokemons/to_choose`, `/debogage/*`** — formes de réponse Web inchangées (ajouts non-breaking côté API non répercutés dans le contrat Back→Web).

> ⚠️ **À re-vérifier au démarrage** : confirmer que les fixtures Back `functional/controller/Labels/all.json`, `.../ElectionIndex/demolite.json` (bloc `metrics`) et l'absence de `Admin/action-logs.json` dans le diff de migration tiennent toujours sur la version du Back déployée. Si le Back enrichit son contrat (ex. ajoute `game_bundles` à `election_top`), élargir le périmètre en conséquence.

---

## ✅ Décision tranchée : Option A (adapter le Web sans changer le Back)

Le nouveau format `election_top` renvoyé par le Back **ne contient plus** certains champs que le Web utilisait :

| Champ utilisé par le Web | Présent dans le nouveau payload ? | Conséquence |
|--------------------------|-----------------------------------|-------------|
| `pokemon_icon` (macros image `_image_macros.html.twig`) | ❌ Non | L'URL d'image/icône du top ne peut plus être construite directement |
| `pokemon_simplified_name` / `pokemon_simplified_french_name` (libellé du top, `_top.html.twig` l.23) | ❌ Non (seul `labels.name` / `labels.french_name` existe) | Le top afficherait le **nom complet** (ex. « Venusaur ♂️ ») au lieu du nom simplifié (« Venusaur ») |
| `pokemon_name` / `pokemon_french_name` (alt des images) | ✅ via `pokemon.labels.{name,french_name}` | OK |

**Options :**

- **(A) — RETENUE : adapter le Web sans changer le Back.** Dans le nouveau `TopPokemon`, dériver `getPokemonIcon()` à partir de `pokemon.slug` (dans les fixtures actuelles `icon == slug` pour tous les pokémons du top), et faire pointer `getPokemonSimplifiedName()`/`getPokemonSimplifiedFrenchName()` sur `labels.name`/`labels.french_name`. Régression assumée : libellés du top non simplifiés + icône dérivée du slug (risque marginal si un pokémon a un icône ≠ slug).
- **(B) — Écartée.** Enrichir le contrat du Back (`icon` + `simplified_name`/`simplified_french_name` dans `election_top`).

La Tâche 3 est rédigée pour l'**option A**.

---

## Fichiers modifiés par tâche

### Tâche 1 — game_bundles (`generation_slug` → `generation.slug`) ✅ IMPLÉMENTÉE
- Create : `src/ResponseObject/Label/Generation.php`
- Modify : `src/ResponseObject/Label/GameBundle.php`
- Modify : `tests/resources/moco/Back/responses/labels.json`
- Modify : `tests/resources/unit/service/back/labels.json`
- Modify : `tests/resources/integration/back/labels.json`
- Create : `tests/src/Unit/ResponseObject/Label/GenerationTest.php`
- Modify : `tests/src/Unit/ResponseObject/Label/GameBundleTest.php`
- Modify : `tests/src/Integration/ResponseObject/Label/GameBundleTest.php`
- Modify : `tests/src/Common/Traits/ResponseObjectTrait.php`

### Tâche 2 — reports (`nb` → `count`, objets imbriqués)
- Modify : `templates/Admin/_reports.html.twig`
- Modify : `templates/Admin/_reports_scripts.html.twig`
- Modify : `tests/resources/moco/Back/responses/reports.json`
- Modify : `tests/resources/unit/service/back/reports.json`
- Modify : `tests/resources/unit/service/api/reports.json`
- Modify : tests d'intégration `AdminController` / reports (snapshot W3C/HTML si présent)

### Tâche 3 — election_top (plat → imbriqué)
- Modify : `src/ResponseObject/Election/TopPokemon.php` (refonte)
- Create : value objects imbriqués nécessaires (voir Tâche 3, étape 1)
- Modify (si besoin) : `templates/Election/_top.html.twig` + `templates/common/Pokemon/_image_macros.html.twig` (uniquement si l'option A ne suffit pas à garder les getters publics stables)
- Modify : `tests/resources/moco/Back/responses/election/index_*.json` (9 fichiers contenant `election_top`)
- Modify : `tests/resources/unit/service/back/election_top_5_home_fav.json`
- Modify : `tests/resources/unit/service/back/election_top_10_demo_pref.json`
- Modify : `tests/resources/integration/back/election_mega_top_5.json`
- Modify : `tests/src/Integration/.../TopPokemonTest.php` + tests ElectionIndex concernés

> Les 9 fixtures moco `index_*.json` : `index_demolite.json`, `index_demoliteshiny.json`, `index_mega.json`, `index_mega_favorite.json`, `index_mega_lastone.json`, `index_mega_lastpage.json`, `index_mega_vote.json`, `index_swordshield.json`, `index_swordshield_favorite.json`.

---

## Task 1 — game_bundles : `generation_slug` → `generation.slug` ✅ IMPLÉMENTÉE

> **Statut : terminée (option 1a, mini-VO).** Code et fixtures modifiés ; tests non exécutés (validation de syntaxe JSON uniquement). À valider avec `make quality` / `make measures`.

**Interfaces :** `GameBundle::getGenerationSlug(): string` — signature publique **conservée**. Vérifié : les contrôleurs ne lisent que `getGameBundles()` et les templates de filtres `gameBundles[].slug`/`.frenchName` ; la génération n'est pas lue côté vue → aucun template impacté.

- [x] **Étape 1 — `src/ResponseObject/Label/GameBundle.php`** *(option 1a retenue)*

  4ᵉ paramètre `#[SerializedName('generation_slug')] string $generationSlug` → `#[SerializedName('generation')] Generation $generation`. `getGenerationSlug()` conservée, délègue à `$this->generation->getSlug()`.

  ```php
  public function __construct(
      #[SerializedName('name')] private readonly string $name,
      #[SerializedName('french_name')] private readonly string $frenchName,
      #[SerializedName('slug')] private readonly string $slug,
      #[SerializedName('generation')] private readonly Generation $generation,
  ) {}

  public function getGenerationSlug(): string
  {
      return $this->generation->getSlug();
  }
  ```

  Créé : `src/ResponseObject/Label/Generation.php` (`final`, `#[SerializedName('slug')] string $slug`, getter `getSlug()`).

- [x] **Étape 2 — Fixtures `labels.json` (×3)**

  Dans chacun des 3 fichiers, chaque entrée de `game_bundles` : `"generation_slug": "X"` → `"generation": { "slug": "X" }` (18 occurrences/fichier). JSON revalidé.
  Fichiers : `tests/resources/moco/Back/responses/labels.json`, `tests/resources/unit/service/back/labels.json`, `tests/resources/integration/back/labels.json`.

- [x] **Étape 3 — Tests**

  - `tests/src/Unit/ResponseObject/Label/GameBundleTest.php` : construction avec `new Generation('gen_y')`.
  - `tests/src/Integration/ResponseObject/Label/GameBundleTest.php` : JSON `generation_slug` → `generation: { slug }`.
  - `tests/src/Common/Traits/ResponseObjectTrait.php` : stub `GameBundle` mis à jour (`new Generation('gen_y')`).
  - **Créé** : `tests/src/Unit/ResponseObject/Label/GenerationTest.php` (couverture du VO).

- [ ] **Étape 4 — Vérification (à lancer manuellement, non exécutée)**
  ```bash
  docker compose exec php php vendor/bin/phpunit --filter 'GameBundle|Generation|Labels'
  ```

---

## Task 2 — reports : `nb` → `count` + objets `dex`/`catch_state` imbriqués ✅ IMPLÉMENTÉE

> **Statut : terminée.** Templates + 3 fixtures modifiés ; format aligné sur la source `pokenini-back/.../functional/controller/Admin/reports.json` (slugs exacts repris). Aucun test à modifier (assertions de comptage et valeurs inchangées). Tests non exécutés.

**Rappel du nouveau format** (depuis le diff Back `functional/controller/Admin/reports.json`) :
```json
{
  "catch_state_counts_defined_by_trainer": [
    { "count": 5735, "trainer": "f86c…" }
  ],
  "dex_usage": [
    { "count": 2, "dex": { "slug": "homeshiny", "name": "Home Shiny", "french_name": "Home Chromatique" } }
  ],
  "catch_state_usage": [
    { "count": 36, "catch_state": { "slug": "no", "name": "No", "french_name": "Non", "color": "#e57373" } }
  ]
}
```

`GetReportsService::get()` renvoie le tableau brut décodé → aucun changement de signature, mais les **templates** lisent les anciennes clés.

- [x] **Étape 1 — `templates/Admin/_reports.html.twig`**

  - `catch_state_counts_defined_by_trainer` : `d.nb` / `row.nb` → `d.count` / `row.count` (l.38, 51, 54). `row.trainer` inchangé.
  - `catch_state_usage` : `d.nb` / `row.nb` → `count` (l.108, 127, 130) ; `row.french_name` / `row.name` → `row.catch_state.french_name` / `row.catch_state.name` (l.110).
  - Les `.nb` restants sont des clés de traduction (`admin.reports.*.nb`), laissées telles quelles.

- [x] **Étape 2 — `templates/Admin/_reports_scripts.html.twig`**

  - `catch_state_counts_defined_by_trainer` (l.43) : `d.nb` → `d.count`. `d.trainer` inchangé (l.42).
  - `dex_usage` (l.124-125) : `d.french_name`/`d.name` → `d.dex.french_name`/`d.dex.name` ; `d.nb` → `d.count`.
  - `catch_state_usage` (l.185-187) : `d.french_name`/`d.name`/`d.color` → `d.catch_state.french_name`/`d.catch_state.name`/`d.catch_state.color` ; `d.nb` → `d.count`.

- [x] **Étape 3 — Fixtures reports (×3)**

  Nouveau format appliqué aux 3 fichiers (slugs exacts repris de la fixture source du Back). Styles d'échappement préservés : `\uXXXX` pour `moco/Back/responses/reports.json` et `unit/service/back/reports.json`, UTF-8 brut pour `unit/service/api/reports.json` (fixture orpheline, non référencée par un test). Valeurs numériques conservées.

- [x] **Étape 4 — Tests AdminController / reports**

  Aucune modification nécessaire : `GetReportsServiceTest` n'asserte que les comptages (3/12/6, inchangés) ; `AdminReportsTest` n'asserte que des comptages d'éléments et des valeurs rendues (`94`, `1.61`, `28`, `0.48`) issues de `count` et des libellés — toutes inchangées. Aucun snapshot HTML/W3C présent pour cette page.

- [ ] **Étape 5 — Vérification (à lancer manuellement)**
  ```bash
  docker compose exec php php vendor/bin/phpunit --filter Admin
  docker compose exec php php vendor/bin/phpunit --filter Report
  ```

---

## Task 3 — election_top : structure plate → imbriquée (option A) ✅ IMPLÉMENTÉE

> **Statut : terminée.** 3 VO créés + refonte `TopPokemon` + tests + 12 fixtures (65 entrées) reconstruites. Tests non exécutés (lint PHP `php -l` OK + JSON + structure validés statiquement).
>
> **Contrat réel vérifié dans le code source (`pokenini-api`/`pokenini-back` @ `feature/adapt_from_web`) :**
> - Le Web ne voit **jamais** l'API directement ; il consomme la réponse du Back. `pokenini-back/GetElectionTopService` fait `return $item` (passe-plat de l'item API) en **ajoutant seulement** `pokemon['pokemon_icon'] = pokemon['slug']`. Donc le Web reçoit **le bloc `pokemon` riche de l'API + `pokemon_icon` (= slug)**, ainsi que `forms`, `types`, `score`.
> - `pokemon_icon` vaut **toujours** le slug (imposé par le Back) → l'option A « icône = slug » est exactement le comportement réel. Conservée.
> - **`score.elo` est un `float`** (`pokenini-api/DTO/Response/ElectionEloScoreResponse::$elo`, non casté par le Back). **Bug corrigé** : `TopPokemonScore::$elo` / `getElo()` et `TopPokemon::getElo()` passés de `int` à `float` ; tests ajustés (`1000.0`, `1016.5`, `1250.5`).
>
> **Décision propriétaire (confirmée) :**
> - **Code** : option A littérale conservée (icône = slug, simplifié = nom complet) ; `forms`/`types`/champs riches **non mappés** (le Serializer ignore les clés en trop, comme `TopPokemon` ignorait déjà `primary_type_*`). VO `TopPokemonForms`/`TopPokemonTypes` non créés.
> - **Fixtures** : fidélité **maximale** au vrai contrat. Bloc `pokemon` complet (labels 6 champs, `regional_dex_number`, `icon`, `family_order`, `family_lead`, `original_game_bundle`, `order_number`, `game_bundles`, `pokemon_icon`), `forms` (4 clés `category/regional/special/variant`), `types{primary,secondary}` avec **vraies couleurs** et **vrais french_name de formes** repris de `pokenini-api` (tables extraites de ses fixtures). Seul `game_bundles` est dérivé de `original_game_bundle` (donnée par-pokémon absente des sources ; slug réel utilisé). Ordre des clés calqué sur les DTO API.

**Nouveau format d'une entrée** (depuis le diff Back `functional/controller/ElectionIndex/demolite.json`) :
```json
{
  "pokemon": {
    "slug": "venusaur-f",
    "labels": { "name": "Venusaur ♀", "french_name": "Florizarre ♀" },
    "national_dex_number": 3
  },
  "forms": { "variant": { "slug": "gender", "name": "Gender", "french_name": "Sexe" } },
  "types": {
    "primary": { "slug": "grass", "name": "Grass", "french_name": "Plante", "color": "#78C850" },
    "secondary": { "slug": "poison", "name": "Poison", "french_name": "Poison", "color": "#A040A0" }
  },
  "score": { "elo": 1040, "significance": false }
}
```
`forms` peut valoir `null` ou contenir n'importe lequel de `category`/`regional`/`special`/`variant` ; `types.secondary` peut être `null`.

**Interfaces (à conserver pour ne pas casser les vues) :** `TopPokemon` doit continuer d'exposer au minimum `getPokemonSlug()`, `getPokemonName()`, `getPokemonFrenchName()`, `getPokemonSimplifiedName()`, `getPokemonSimplifiedFrenchName()`, `getPokemonIcon()`, `getElo()`, `isSignificance()` (utilisés par `_top.html.twig` et `_image_macros.html.twig`). Objectif : **zéro changement de template** sous l'option A.

- [x] **Étape 1 — Value objects imbriqués**

  Créés sous `src/ResponseObject/Election/` :
  - `TopPokemonInfo` : `#[SerializedName('slug')] string $slug`, `#[SerializedName('labels')] TopPokemonLabels $labels`, `#[SerializedName('national_dex_number')] int $nationalDexNumber`.
  - `TopPokemonLabels` : `name`, `french_name`.
  - `TopPokemonScore` : `elo` (int), `significance` (bool).
  - ~~`forms` / `types`~~ : **non créés** (décision propriétaire — non consommés, ignorés par le Serializer). `TopPokemonForms`/`TopPokemonTypes` abandonnés.

  > Le Web désérialise `ElectionIndex` (donc `election_top` → `TopPokemon[]`) **via Symfony Serializer** (`GetElectionIndexService` l.31). Les sous-objets typés sont donc indispensables : un simple `SerializedName('pokemon.slug')` n'est pas supporté.

- [x] **Étape 2 — Refonte `TopPokemon.php`**

  Nouveau constructeur :
  ```php
  public function __construct(
      #[SerializedName('pokemon')] private readonly TopPokemonInfo $pokemon,
      #[SerializedName('forms')] private readonly ?TopPokemonForms $forms,
      #[SerializedName('types')] private readonly TopPokemonTypes $types,
      #[SerializedName('score')] private readonly TopPokemonScore $score,
  ) {}
  ```
  Getters de compatibilité (option A) :
  ```php
  public function getPokemonSlug(): string            { return $this->pokemon->getSlug(); }
  public function getPokemonName(): string            { return $this->pokemon->getLabels()->getName(); }
  public function getPokemonFrenchName(): string      { return $this->pokemon->getLabels()->getFrenchName(); }
  public function getPokemonSimplifiedName(): string  { return $this->pokemon->getLabels()->getName(); }        // pas de simplifié dans le payload
  public function getPokemonSimplifiedFrenchName(): string { return $this->pokemon->getLabels()->getFrenchName(); }
  public function getPokemonIcon(): string            { return $this->pokemon->getSlug(); }                     // dérivé du slug
  public function getPokemonNationalDexNumber(): int  { return $this->pokemon->getNationalDexNumber(); }
  public function getElo(): int                       { return $this->score->getElo(); }
  public function isSignificance(): bool              { return $this->score->isSignificance(); }
  ```
  Supprimer les anciens getters devenus sans source (`getPokemonFormsLabel`, `getCatchState*`, `getFamilyLeadSlug`, `getPokemonRegionalDexNumber`, `getPokemonFamilyOrder`, `getPokemon*FormsFrenchLabel`, etc.) **seulement après** avoir vérifié par `grep` qu'aucun template/test ne les utilise. Exposer `getForms()` / `getTypes()` si une vue en a besoin.

- [x] **Étape 3 — Vérifier les templates (no-op sous option A — confirmé)**

  `grep -rn "electionTop\|item\.\(pokemon\|elo\|significance\)" templates/` pour confirmer que seuls les getters conservés sont utilisés. `_top.html.twig` (l.13, 20, 23) et `_image_macros.html.twig` (l.18, 20, 48, 50) doivent fonctionner sans modification. Si un getter supprimé est référencé, soit le conserver, soit adapter le template.

- [x] **Étape 4 — Fixtures moco `index_*.json` (×9)**

  Chaque entrée `election_top` reconstruite à la forme **exacte du contrat Back→Web** : `{ pokemon{…11 champs…}, forms, types{primary,secondary}, score{elo:float, significance} }`, ordre des clés calqué sur les DTO `pokenini-api`. Données réelles : champs `pokemon` repris des fixtures plates d'origine (git HEAD) ; couleurs de types et french_name de formes repris des tables de référence `pokenini-api` ; `game_bundles` dérivé de `original_game_bundle` (slug réel). Tout le reste (`pokemons`, `pokedex`, `metrics`…) **inchangé** (vérifié par diff). Indentation (2) et UTF-8 brut préservés.

- [x] **Étape 5 — Fixtures unit/back + intégration**

  Même reconstruction fidèle. `election_mega_top_5.json` : les entrées n'ayant pas leurs propres `types`/`order_number`/`original_game_bundle`, ces champs sont repris par jointure sur le slug depuis la carte globale des entrées moco (les mêmes méga-pokémons figurent dans `index_mega.json`). Les deux `election_top_*.json` orphelins portent leurs propres `types`/`order_number` ; `game_bundles` y est vide (pas de `original_game_bundle` source). Échappement préservé (indent 4 ; UTF-8 brut pour l'intégration ; ASCII `\uXXXX` + apostrophe `'` pour les orphelins). 65 entrées au total sur les 12 fichiers.

- [x] **Étape 6 — Tests `TopPokemon` + ElectionIndex**

  `TopPokemonTest` réécrit (désérialisation imbriquée + getters de compat + cas `significance: true` + `elo` **float** `1016.5`/`1000.0`). 3 tests unitaires VO créés (`TopPokemonLabelsTest`, `TopPokemonInfoTest`, `TopPokemonScoreTest` avec `elo` `1250.5`). Stub `getStubTopPokemon()` du trait migré vers les VO. Tests d'intégration `ElectionIndexTest` : aucune modif nécessaire (n'assertent que des comptages + le titre `h4`, pas le texte des libellés du top → le passage au nom complet sous option A ne change pas les attendus). Aucun snapshot HTML/W3C pour cette page.

- [ ] **Étape 7 — Vérification (à lancer manuellement)**
  ```bash
  docker compose exec php php vendor/bin/phpunit --filter TopPokemon
  docker compose exec php php vendor/bin/phpunit --filter ElectionIndex
  ```

---

## Vérification finale (à lancer manuellement, hors périmètre de ce plan)

- [ ] `make quality` — 0 erreur PHPStan/Psalm/PHPMD/Deptrac/CS Fixer + W3C.
- [ ] `make tests` — unit + intégration + browser (Chrome + Firefox) verts.
- [ ] `make measures` — 100 % couverture + 100 % MSI.

> Procédure de régénération des snapshots : décommenter le `file_put_contents('tests/last.html', …)` (ou l'équivalent JSON) dans le test, relancer le test ciblé, copier le fichier généré dans le répertoire de référence, puis recommenter.

---

## Self-Review — Couverture du spec

| Changement API (`migration.md`) | Atteint le Web ? | Tâche | Couvert ? |
|---|---|---|---|
| `GET /forms` (consolidation) | Non (absorbé par le Back) | — | N/A |
| `GET /game_bundles` (generation imbriqué) | **Oui** (via `/labels`) | Task 1 | ✅ |
| `GET /reports` (slugs + count) | **Oui** | Task 2 | ✅ |
| `GET /election/top` (structure imbriquée) | **Oui** | Task 3 | ✅ |
| `GET /action_logs` (tableau `action_type`) | Non (absorbé par le Back) | — | N/A |
| `GET /election/metrics` (`completion`) | Non (absorbé par le Back) | — | N/A |
| `POST /election/vote` (`trainer` imbriqué) | Non (body Back→API) | — | N/A |
| `GET /album/*` (ajouts non-breaking) | Non (non répercuté) | — | N/A |
| `GET /pokemons/to_choose` (ajouts) | Non (non répercuté) | — | N/A |
| `GET /debogage/*` | Non exposé par le Back | — | N/A |
| Renommages camelCase→snake_case | Déjà en snake_case côté Back→Web | — | N/A |

**Risques résiduels à valider :**
1. Décision A vs B sur les champs perdus d'`election_top` (icône + nom simplifié) — voir section « Décision requise ».
2. Re-confirmer, sur la version du Back effectivement déployée, que `metrics`, `action_logs` et les formes restent stables côté contrat Web (sinon élargir le périmètre).
3. Vérifier qu'aucun autre template/JS ne lit les getters supprimés de `TopPokemon` avant suppression.
