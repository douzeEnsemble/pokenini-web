# Plan de migration — Refactoring des réponses API (côté pokenini-web)

> **Pour les workers agentiques :** SOUS-SKILL RECOMMANDÉE : `superpowers:subagent-driven-development` ou `superpowers:executing-plans` pour exécuter ce plan tâche par tâche. Les étapes utilisent la syntaxe checkbox (`- [ ]`).

**Goal :** Adapter `pokenini-web` aux changements de réponses introduits par la migration `pokenini-api/feature/refactoring_responses`, **répercutés à l'identique par `pokenini-back`** (le Back suit exactement les mêmes modifications et ne masque plus rien).

**Contexte architectural clé :** `pokenini-web` n'appelle **jamais** l'API directement — il passe par `pokenini-back` (BFF). Les endpoints réellement consommés par le Web sont des endpoints **composites** du Back (`/labels`, `/election/{dex}/{election}`, `/istration/reports`, `/istration/action-logs`, `/album`, `/pokemons/to_choose`), et non les routes brutes de l'API. Les formes cibles de ce plan sont donc **déduites** de `pokenini-api/doc/migration.md` + `doc/endpoints.md`, en supposant que le Back applique la même nature de changement à son contrat de sortie.

> ⚠️ **Ordre inter-repos (règle du workspace) : `pokenini-api` → `pokenini-back` → `pokenini-web`.** Ce plan est **en avance** sur le Back : à ce jour le Back absorbe encore la plupart des changements (branche `feature/adapt_from_web`). Les fixtures Moco écrites ici reflètent la sortie **attendue** du Back une fois propagée ; elles **doivent être réconciliées** avec la sortie réelle du Back (`tests/resources/functional/controller/*`) au moment de l'exécution. Toute divergence sur une forme composite (voir « Hypothèses à confirmer ») prime sur ce document.

**Tech stack :** PHP 8.4, Symfony 8.0, Twig, Symfony Serializer (désérialisation des `ResponseObject`), Moco (mock HTTP), PHPUnit.

## Contraintes globales

- `declare(strict_types=1)` dans tous les fichiers PHP.
- Classes `final` pour DTO / ResponseObject / tests ; classes non-`final` pour les Services.
- PHPStan niveau 9 + Psalm strict : phpDoc à jour partout.
- 100 % de couverture et 100 % MSI (Infection) requis.
- `make quality` et `make measures` doivent être verts avant push.
- **Aucun commit ne doit être créé.**
- **Aucune exécution de test dans le cadre de ce plan** — les commandes de vérification sont listées à titre indicatif, à lancer manuellement par la suite.
- **Stratégie transverse : préservation des interfaces.** Chaque migration introduit/réagence des Value Objects imbriqués **en conservant les getters publics existants** (délégation interne). Objectif : **zéro changement de template** sauf nécessité absolue.

---

## Périmètre — changements atteignant le Web

| # | Source (concept API) | Consommé par | Nature du changement | Statut |
|---|----------------------|--------------|----------------------|--------|
| 1 | `game_bundles` (via `/labels`) | `ResponseObject\Label\GameBundle` | `generation_slug` → `generation.slug` | ✅ FAIT (`1a521c9`) |
| 2 | `reports` (`/istration/reports`) | `Service\Back\GetReportsService` → templates Admin | `nb` → `count` ; `dex`/`catch_state` imbriqués (avec `slug`) | ✅ FAIT (`bfdba3e`) |
| 3 | `election_top` (réponse ElectionIndex) | `ResponseObject\Election\TopPokemon` | Plat → imbriqué (`pokemon`/`forms`/`types`/`score`) | ✅ FAIT (`1c59b41`, `a72ce4d`) |
| 4 | `forms` (via `/labels`) | `ResponseObject\Label\Labels` | 4 tableaux plats → objet imbriqué `forms{category,regional,special,variant}` | ✅ FAIT (`e98c5fc`) |
| 5 | `election/metrics` (réponse ElectionIndex) | `DTO\ElectionMetrics` | `under_max_view_count`/`max_view_count` → `completion{under_max_count,at_max_count}` (+ `view_count`/`win_count` imbriqués `{sum,max}`) | ✅ FAIT |
| 6 | `action_logs` (`/istration/action-logs`) | `Service\Back\GetActionLogsService` | Objet à clés dynamiques → tableau avec `action_type` | ✅ FAIT |
| 7 | `election/vote` (`POST /election/...`) | `Service\Back\PostElectionVoteService` | Réponse imbriquée `trainer` + `score` | ✅ VÉRIFIÉ (no-op) |

### Hors périmètre (vérifié — NE PAS toucher)

- **Album (`/album/...`)** — les changements `migration.md` y sont **purement additifs** (`pokemon.game_bundles`, `forms.*.french_name`). Le `Pokemon` plat du Web porte déjà `game_bundles` ; les clés en trop sont ignorées par le Serializer. **Aucune migration.**
- **`/pokemons/to_choose`** — changements **additifs** (`pokemon.game_bundles`, `types.*.color`). `Pokemon` plat inchangé. **Aucune migration.**
- **`/debogage/*`** — **non consommé** par le Web.
- **Renommages camelCase → snake_case** — déjà en `snake_case` sur le contrat Back→Web.

---

## Hypothèses à confirmer contre la sortie réelle du Back

Avant d'exécuter chaque tâche À FAIRE, **confirmer la forme exacte** dans les fixtures `functional/controller/*` du Back une fois celui-ci propagé. Si le Back diffère, c'est lui qui fait foi — adapter fixtures **et** code en conséquence.

- **Tâche 4** : le Back regroupe-t-il les formes sous une clé `forms` imbriquée (`forms.category[]`…) ou conserve-t-il `category_forms`/… en tête de `/labels` ? Ce plan suppose le **regroupement imbriqué**.
- **Tâche 5** : le Back imbrique-t-il aussi `view_count`/`win_count` en `{sum,max}` (comme l'API), ou garde-t-il `view_count_sum`/… plats ? Le **seul** changement garanti par `migration.md` est `completion`. Ce plan traite `completion` **et** prépare l'imbrication `view_count`/`win_count`, à activer selon le contrat réel.
- **Tâche 6** : la réponse `/istration/action-logs` devient-elle un **tableau** d'objets `{action_type, current, last}` ? Ce plan le suppose.
- **Tâche 7** : le corps **Web→Back** d'un vote reste-t-il `{winners_slugs, losers_slugs}` ? Ce plan le suppose (le `trainer` imbriqué est un détail Back→API, et la réponse n'est pas consommée par le Web).

---

## Task 1 — game_bundles : `generation_slug` → `generation.slug` ✅ FAIT

> Implémentée (`1a521c9`). VO `Generation` créé, `GameBundle::getGenerationSlug()` conservée (délègue à `generation.slug`). Fixtures `labels.json` (×3) + tests à jour. Validation finale `make quality`/`make measures` à lancer manuellement.

- [x] `src/ResponseObject/Label/Generation.php` créé ; `GameBundle.php` migré (getter conservé).
- [x] Fixtures `labels.json` (×3) : `generation_slug` → `generation:{slug}`.
- [x] Tests unitaires + intégration `GameBundle`/`Generation` + stub `ResponseObjectTrait`.
- [ ] Vérif manuelle : `docker compose exec php php vendor/bin/phpunit --filter 'GameBundle|Generation|Labels'`

---

## Task 2 — reports : `nb` → `count` + objets imbriqués ✅ FAIT

> Implémentée (`bfdba3e`). Templates `Admin/_reports.html.twig` + `_reports_scripts.html.twig` lisent `count` et `d.dex.*` / `d.catch_state.*`. 3 fixtures `reports.json` au nouveau format. Aucune assertion de test à modifier (comptages inchangés).

- [x] `templates/Admin/_reports.html.twig` : `.nb` → `.count`, libellés via `row.catch_state.*`.
- [x] `templates/Admin/_reports_scripts.html.twig` : `d.nb` → `d.count`, `d.dex.*` / `d.catch_state.*`.
- [x] Fixtures reports (×3) au nouveau format (slugs repris de la source Back).
- [ ] Vérif manuelle : `docker compose exec php php vendor/bin/phpunit --filter 'Admin|Report'`

---

## Task 3 — election_top : plat → imbriqué ✅ FAIT

> Implémentée (`1c59b41`, `a72ce4d`). VO `TopPokemonInfo`/`TopPokemonLabels`/`TopPokemonScore` créés ; `TopPokemon` refondu en conservant les getters de compat (`getPokemonSlug/Name/FrenchName/SimplifiedName/SimplifiedFrenchName/Icon/Elo`, `isSignificance`). `score.elo` est un **float**. 12 fixtures (`election_top`) reconstruites. Tests réécrits.

- [x] VO imbriqués + refonte `TopPokemon` (getters de compat, option « icône = slug »).
- [x] 9 fixtures moco `index_*.json` + 2 unit + 1 intégration reconstruites.
- [x] Tests `TopPokemon*` + VO ; ElectionIndex sans modif.
- [ ] Vérif manuelle : `docker compose exec php php vendor/bin/phpunit --filter 'TopPokemon|ElectionIndex'`

---

## Task 4 — forms : 4 tableaux plats → objet `forms` imbriqué ✅ FAIT

**État actuel :** `src/ResponseObject/Label/Labels.php:26-33` expose 4 tableaux en tête : `category_forms`, `regional_forms`, `special_forms`, `variant_forms` (chacun `array<int, *Form>`, classes héritant de `AbstractForm` : `name`/`french_name`/`slug`).

**Forme cible** (calquée sur `GET /forms` de `migration.md`) :
```json
{
  "forms": {
    "category": [{ "slug": "starter", "name": "Starter", "french_name": "de Départ" }],
    "regional": [{ "slug": "alolan",  "name": "Alolan",  "french_name": "d'Alola" }],
    "special":  [{ "slug": "mega",    "name": "Mega",    "french_name": "Mega" }],
    "variant":  [{ "slug": "gender",  "name": "Gender",  "french_name": "Sexe" }]
  }
}
```

**Interface à conserver :** `Labels::getCategoryForms()`, `getRegionalForms()`, `getSpecialForms()`, `getVariantForms()` (lues par `ElectionIndexController` et `templates/common/Filter/_dex_filters_blocks.html.twig`). Objectif : **zéro changement de template/contrôleur**.

- [x] **Étape 1 — VO `Forms`**

  Créer `src/ResponseObject/Label/Forms.php` (`final`, `declare(strict_types=1)`), constructeur :
  ```php
  /**
   * @param array<int, CategoryForm> $category
   * @param array<int, RegionalForm> $regional
   * @param array<int, SpecialForm>  $special
   * @param array<int, VariantForm>  $variant
   */
  public function __construct(
      #[SerializedName('category')] private readonly array $category,
      #[SerializedName('regional')] private readonly array $regional,
      #[SerializedName('special')]  private readonly array $special,
      #[SerializedName('variant')]  private readonly array $variant,
  ) {}
  ```
  + getters `getCategory()/getRegional()/getSpecial()/getVariant()` typés `array<int, *Form>`.

  > Le Serializer doit pouvoir typer chaque sous-tableau : conserver le typage générique via phpDoc `@param array<int, CategoryForm>` comme pour les autres tableaux de `Labels` (le Serializer s'appuie sur le phpDoc du constructeur pour la dénormalisation des collections).

- [x] **Étape 2 — Refonte `Labels.php`**

  Remplacer les 4 paramètres `category_forms`/`regional_forms`/`special_forms`/`variant_forms` par un seul :
  ```php
  #[SerializedName('forms')] private readonly Forms $forms,
  ```
  Conserver les 4 getters publics en délégation :
  ```php
  public function getCategoryForms(): array { return $this->forms->getCategory(); }
  public function getRegionalForms(): array { return $this->forms->getRegional(); }
  public function getSpecialForms(): array  { return $this->forms->getSpecial(); }
  public function getVariantForms(): array  { return $this->forms->getVariant(); }
  ```
  Mettre à jour le phpDoc du constructeur. `catch_states`, `types`, `game_bundles`, `collections` inchangés.

- [x] **Étape 3 — Fixtures `labels.json` (×3)**

  Dans chaque fichier, remplacer les 4 tableaux de tête `"category_forms": [...]`, `"regional_forms": [...]`, `"special_forms": [...]`, `"variant_forms": [...]` par un unique bloc imbriqué `"forms": { "category": [...], "regional": [...], "special": [...], "variant": [...] }` (mêmes objets `{slug,name,french_name}`, valeurs et échappement préservés).
  Fichiers : `tests/resources/moco/Back/responses/labels.json`, `tests/resources/unit/service/back/labels.json`, `tests/resources/integration/back/labels.json`.

- [x] **Étape 4 — Vérifier templates/contrôleur (no-op attendu)**

  `grep -rn "CategoryForms\|RegionalForms\|SpecialForms\|VariantForms\|categoryForms\|regionalForms" templates/ src/Controller/` : confirmer que seuls les getters conservés sont utilisés (`_dex_filters_blocks.html.twig`, `ElectionIndexController`). Aucune modif attendue.

- [x] **Étape 5 — Tests**

  - Créer `tests/src/Unit/ResponseObject/Label/FormsTest.php` (couverture du VO).
  - Mettre à jour `tests/src/Unit/ResponseObject/Label/LabelsTest.php` (construction via `new Forms([...], [...], [...], [...])`).
  - Mettre à jour `tests/src/Integration/ResponseObject/Label/LabelsTest.php` (JSON imbriqué).
  - Mettre à jour le stub `Labels` dans `tests/src/Common/Traits/ResponseObjectTrait.php` si présent (`new Forms(...)`).

- [x] **Étape 6 — Vérification (manuelle)**
  ```bash
  docker compose exec php php vendor/bin/phpunit --filter 'Labels|Forms'
  ```

---

## Task 5 — election/metrics : `completion` imbriqué ✅ FAIT

**État actuel :** `src/DTO/ElectionMetrics.php` est plat — `createFromArray()` (via `OptionsResolver`) lit `view_count_sum`, `win_count_sum`, `view_count_max`, `win_count_max`, `under_max_view_count`, `max_view_count`, `dex_total_count`, `round_count`, `winner_average`, `total_round_count`. Construit dans `Service\ElectionIndexService::get()` à partir de `ElectionIndex::getMetrics()` (tableau brut).

**Forme cible** (calquée sur `GET /election/metrics` de `migration.md`) :
```json
{
  "view_count": { "sum": 0, "max": 0 },
  "win_count":  { "sum": 0, "max": 0 },
  "completion": { "at_max_count": 15, "under_max_count": 15 },
  "dex_total_count": 21,
  "round_count": 0,
  "winner_average": 0.0,
  "total_round_count": 0
}
```
> `round_count`, `winner_average`, `total_round_count` sont **spécifiques au Back** (absents de l'API) → restent plats. Seuls `view_count`/`win_count`/`completion` sont concernés par l'imbrication.

**Interface à conserver :** propriétés publiques `viewCountSum/winCountSum/viewCountMax/winCountMax/underMaxViewCount/maxViewCount/dexTotalCount/roundCount/winnerAverage/totalRoundCount` (lues par `templates/Election/_bar_top.html.twig` : `underMaxViewCount`, `maxViewCount`, `roundCount`, `totalRoundCount` ; et `_info.html.twig` : `roundCount`, `totalRoundCount`, `winnerAverage`). Objectif : **zéro changement de template** — l'imbrication n'affecte que le **parsing** dans `createFromArray()`.

- [x] **Étape 1 — Adapter `ElectionMetrics::createFromArray()`**

  Reconfigurer l'`OptionsResolver` pour la nouvelle forme imbriquée, **sans changer le constructeur ni les propriétés** :
  - `completion` : tableau requis `{at_max_count:int, under_max_count:int}` → alimente `maxViewCount = completion['at_max_count']` et `underMaxViewCount = completion['under_max_count']`.
  - `view_count` : tableau requis `{sum:int, max:int}` → `viewCountSum = view_count['sum']`, `viewCountMax = view_count['max']`.
  - `win_count` : tableau requis `{sum:int, max:int}` → `winCountSum`, `winCountMax`.
  - `dex_total_count`, `round_count`, `winner_average`, `total_round_count` : inchangés.

  Mettre à jour le phpDoc `@param array{...}` de `createFromArray()` et `configureOptions()` (`setRequired`/`setAllowedTypes('completion', 'array')`, etc.). Ajouter une validation de structure des sous-tableaux si l'OptionsResolver ne la garantit pas (sinon couverture/MSI échouera sur les branches manquantes).

  > **Mapping `migration.md`** : `max_view_count` → `completion.at_max_count` ; `under_max_view_count` → `completion.under_max_count`.

  > **Garde-fou Back** : si la sortie réelle du Back garde `view_count_sum`/… plats et n'imbrique **que** `completion`, ne migrer que `completion` et laisser `view_count`/`win_count` plats. Voir « Hypothèses à confirmer ».

- [x] **Étape 2 — Fixtures metrics**

  Reconstruire le bloc `metrics` à la forme imbriquée dans :
  - les fixtures moco `tests/resources/moco/Back/responses/election/index_*.json` qui portent un bloc `metrics` (vérifier chacune par `grep -l '"metrics"'`) ;
  - `tests/resources/unit/service/back/election_metrics_home_fav.json` ;
  - `tests/resources/unit/service/back/election_metrics_demo_pref.json` ;
  - `tests/resources/integration/back/election_index.json` (bloc `metrics`).

  Valeurs numériques conservées : `under_max_view_count` → `completion.under_max_count`, `max_view_count` → `completion.at_max_count`, `view_count_sum`/`view_count_max` → `view_count.{sum,max}`, idem `win_count`.

- [x] **Étape 3 — Tests**

  - `tests/src/Unit/DTO/ElectionMetricsTest.php` : alimenter `createFromArray()` avec la forme imbriquée ; couvrir les cas d'erreur (sous-tableau manquant / mal typé) pour 100 % MSI.
  - Vérifier les tests intégration `ElectionIndex` (n'assertent que des comptages/titres → a priori inchangés ; sinon ajuster).

- [x] **Étape 4 — Vérification (manuelle)**
  ```bash
  docker compose exec php php vendor/bin/phpunit --filter 'ElectionMetrics|ElectionIndex'
  ```

---

## Task 6 — action_logs : objet à clés → tableau `action_type` ✅ FAIT

**État actuel :** `src/Service/Back/GetActionLogsService.php:23-38` décode un **objet à clés dynamiques** (`array<string, {current, last}>`), itère `foreach ($actionLogsData as $item => $data)` et construit `array<string, ActionLogData>` keyé par `action_type`. Le template `templates/Admin/_macros.html.twig` fait un **accès par clé** `actionLogsData[actionItem]`.

**Forme cible d'entrée** (calquée sur `GET /action_logs` de `migration.md`) :
```json
[
  { "action_type": "update_pokemons", "current": { "created_at": "...", "done_at": "...", "execution_time": 0, "details": {...}, "error_trace": null }, "last": { ... } },
  { "action_type": "calculate_dex_availabilities", "current": null, "last": { ... } }
]
```

**Décision : ré-indexer dans le service** pour préserver le contrat de sortie `array<string, ActionLogData>` keyé par `action_type` → **zéro changement de template/contrôleur**. `ActionLog` (`created_at`/`done_at`/`execution_time`/`details`/`error_trace`) inchangé.

- [x] **Étape 1 — Adapter `GetActionLogsService::get()`**

  - Changer le phpDoc de décodage : `array<int, array{action_type: string, current: ...|null, last: ...|null}>`.
  - Itérer la **liste** ; pour chaque entrée, lire `action_type` (clé de sortie) et `current`/`last`. Construire `$list[$entry['action_type']] = new ActionLogData($entry['action_type'], ...)`.
  - Gérer `current` à `null` si la nouvelle forme l'autorise (le `migration.md` montre `current: null`) — vérifier la signature de `ActionLogData` (champ `current` nullable ou non) et l'aligner si besoin ; couvrir la branche pour le MSI.

  > Si `current` peut être `null`, `ActionLogData`/template doivent le tolérer. Vérifier `src/DTO/ActionLogData.php` et `_macros.html.twig` (l.23-32, 39-53). Si le contrat actuel garantit toujours `current`, conserver l'hypothèse non-nullable.

- [x] **Étape 2 — Fixtures `action-logs.json` (×2)**

  Convertir l'objet racine en **tableau** dans :
  - `tests/resources/moco/Back/responses/action-logs.json`
  - `tests/resources/unit/service/back/action-logs.json`

  Chaque ancienne entrée `"<action_type>": { "item": ..., "current": ..., "last": ... }` → `{ "action_type": "<action_type>", "current": ..., "last": ... }`. Conserver l'ordre et les valeurs. (Le champ `item` redondant est remplacé par `action_type`.)

- [x] **Étape 3 — Tests**

  - `tests/src/Unit/Service/Back/GetActionLogsServiceTest.php` : entrée tableau → sortie keyée inchangée ; couvrir `last: null` et (si applicable) `current: null`.
  - Tests intégration `AdminController`/action-logs : assertions de rendu inchangées si la sortie keyée est préservée ; sinon ajuster.

- [x] **Étape 4 — Vérification (manuelle)**
  ```bash
  docker compose exec php php vendor/bin/phpunit --filter 'ActionLog|Admin'
  ```

---

## Task 7 — election/vote : vérification (no-op confirmé) ✅ VÉRIFIÉ

**État actuel :** le vote est **fire-and-forget**. `Controller\ElectionVoteController` construit un `DTO\ElectionVote` puis `Service\Back\PostElectionVoteService::vote()` envoie au Back un corps `{ "winners_slugs": [...], "losers_slugs": [...] }` (les `dex_slug`/`election_slug` sont dans l'URL). **La réponse n'est pas consommée.**

**Analyse `migration.md`** : le changement `/election/vote` porte sur (a) la **réponse** (`trainer_external_id` → `trainer.external_id`, ajout `score`/`pokemons_elo`) et (b) le **corps Back→API**. Aucun des deux n'impacte le corps **Web→Back** ni un parsing côté Web. → **Aucun changement de code attendu.**

- [x] **Étape 1 — Confirmer le no-op**

  - Vérifier que `PostElectionVoteService::vote()` retourne `void` et n'invoque aucun désérialiseur sur la réponse.
  - `grep -rn "election_vote\|pokemons_elo\|trainer_external_id" src/ templates/` : confirmer qu'aucun code/template ne lit la réponse de vote.
  - Confirmer que le corps Web→Back reste `{winners_slugs, losers_slugs}` (pas de `trainer` imbriqué attendu par le Back sur ce contrat).

- [ ] **Étape 2 — (Optionnel) Rafraîchir les fixtures inutilisées** — _ignoré volontairement : réponse de vote non consommée, et le Back n'a pas migré ce contrat (sa fixture API porte encore `trainer_external_id` plat). Pas de source de vérité pour la forme `trainer.external_id`+`score`._

  Par fidélité au nouveau contrat, mettre à jour `tests/resources/moco/Back/responses/election/election_vote.json` et `tests/resources/unit/service/back/election_vote_demo_whatever.json` vers la forme `election_vote.trainer.external_id` + `pokemons_elo` + `score`. **Sans impact fonctionnel** (non parsées). À ne faire que si l'on tient à l'exactitude documentaire des fixtures.

- [x] **Étape 3 — Vérification (manuelle)**
  ```bash
  docker compose exec php php vendor/bin/phpunit --filter 'ElectionVote'
  ```

---

## Vérification finale (à lancer manuellement, hors périmètre de ce plan)

- [ ] `make quality` — 0 erreur PHPStan/Psalm/PHPMD/Deptrac/CS Fixer + W3C.
- [ ] `make tests` — unit + intégration + browser (Chrome + Firefox) verts.
- [ ] `make measures` — 100 % couverture + 100 % MSI.

> Procédure de régénération des snapshots : décommenter le `file_put_contents('tests/last.html', …)` (ou l'équivalent JSON) dans le test, relancer le test ciblé, copier le fichier généré dans le répertoire de référence, puis recommenter.

---

## Self-Review — Couverture du spec

| Changement API (`migration.md`) | Atteint le Web ? | Tâche | Statut |
|---|---|---|---|
| `GET /forms` (consolidation) | **Oui** (via `/labels`) | Task 4 | ✅ FAIT |
| `GET /game_bundles` (generation imbriqué) | **Oui** (via `/labels`) | Task 1 | ✅ FAIT |
| `GET /reports` (slugs + count) | **Oui** | Task 2 | ✅ FAIT |
| `GET /election/top` (structure imbriquée) | **Oui** | Task 3 | ✅ FAIT |
| `GET /election/metrics` (`completion`) | **Oui** | Task 5 | ✅ FAIT |
| `GET /action_logs` (tableau `action_type`) | **Oui** | Task 6 | ✅ FAIT |
| `POST /election/vote` (`trainer` imbriqué + `score`) | Réponse non consommée | Task 7 | ✅ VÉRIFIÉ (no-op) |
| `GET /album/*` (ajouts non-breaking) | Non (additif, ignoré) | — | N/A |
| `GET /pokemons/to_choose` (ajouts) | Non (additif, ignoré) | — | N/A |
| `GET /debogage/*` | Non consommé | — | N/A |
| Renommages camelCase→snake_case | Déjà en snake_case Back→Web | — | N/A |

**Risques résiduels à valider :**
1. **Réconciliation Back** : confirmer chaque forme composite cible (Tâches 4-7) contre les fixtures `functional/controller/*` du Back **une fois propagé** ; le Back fait foi (voir « Hypothèses à confirmer »).
2. **Tâche 5** : décider de l'imbrication `view_count`/`win_count` selon le contrat réel du Back (seul `completion` est garanti par `migration.md`).
3. **Tâche 6** : tolérance `current: null` à valider sur `ActionLogData` + `_macros.html.twig` avant d'introduire la branche nullable.
4. **MSI 100 %** : chaque nouveau VO / parsing imbriqué doit couvrir ses branches d'erreur (sous-tableau manquant/mal typé) pour ne pas régresser l'Infection Score.
