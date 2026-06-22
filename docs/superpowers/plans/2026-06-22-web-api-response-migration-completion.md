# Web API Response Migration — Completion Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Terminer la migration de `pokenini-web` vers le nouveau format de réponses de l'API — la seule partie restante est la consommation correcte des champs *enrichis* de `election_top` (`labels.simplified_name`, `labels.simplified_french_name`, `pokemon.pokemon_icon`) qui sont actuellement perdus.

**Architecture :** `pokenini-web` ne parle jamais directement à l'API : il consomme les réponses de `pokenini-back` (BFF). `pokenini-back` est un pass-through qui, pour la plupart des endpoints, **préserve sa forme de sortie publique** vers le web (voir « Hors périmètre » plus bas). Le web désérialise le JSON du Back dans des objets `src/ResponseObject/`, exposés via des getters consommés par les templates Twig.

**Tech Stack :** PHP 8.5, Symfony 8.0, Symfony Serializer (`#[SerializedName]`), Twig, PHPUnit (unit + integration WebTestCase + browser Panther), Moco (mock HTTP).

## Global Constraints

- `declare(strict_types=1)` dans tous les fichiers PHP.
- Classes `final` pour DTO / ResponseObject / classes de test ; chaque test porte `/** @internal */` et `#[CoversClass(...)]`.
- PHPStan niveau 9 + Psalm strict : phpDoc à jour, aucune régression de baseline.
- **Couverture 100 % et MSI 100 %** : tout nouveau getter doit être couvert.
- Les tests s'exécutent dans le container : `docker compose exec php php vendor/bin/phpunit ...` (préférence : exécution directe dans le container, pas via Makefile).
- **Aucun commit ne doit être créé** (instruction permanente de l'utilisateur). Les étapes « Commit » du gabarit standard sont volontairement remplacées par une étape de vérification.

---

## Contexte : état vérifié de la migration

Trois migrations sont **déjà committées** sur `feature/api_responses` et correspondent exactement aux trois seuls changements qui atteignent réellement le web (vérifié contre les snapshots web-facing de `pokenini-back`, branche `feature/adapt_from_web`) :

| Changement | Atteint le web ? | Statut web |
|---|---|---|
| `game_bundles` → `generation.slug` (dans `/labels`) | ✅ oui | ✅ fait (`1a521c9`) |
| `reports` → `count` + `dex`/`catch_state` imbriqués | ✅ oui | ✅ fait (`bfdba3e`) |
| `election/top` → structure imbriquée `pokemon`/`forms`/`types`/`score` | ✅ oui | ⚠️ **fait mais incomplet** (`1c59b41`) — voir Task 1 |

**Régression introduite par `1c59b41` (à corriger — objet de ce plan) :**
Avant la migration, `TopPokemon` lisait des champs plats distincts `pokemon_simplified_name`, `pokemon_simplified_french_name`, `pokemon_icon`. Après la migration, ces getters ont été câblés sur des alias erronés :

```php
// src/ResponseObject/Election/TopPokemon.php (actuel — INCORRECT)
public function getPokemonSimplifiedName(): string       { return $this->pokemon->getLabels()->getName(); }       // → "Mega Venusaur" au lieu de "Venusaur"
public function getPokemonSimplifiedFrenchName(): string { return $this->pokemon->getLabels()->getFrenchName(); } // → "Mega Florizarre" au lieu de "Florizarre"
public function getPokemonIcon(): string                 { return $this->pokemon->getSlug(); }                      // → slug au lieu du champ icon
```

Or `pokenini-back` fournit **déjà** ces champs dans sa sortie `election_top` (enrichissement BFF explicite, « découvert par pokenini-web ») et **les fixtures du web les contiennent déjà** :

```jsonc
// extrait d'un item de election_top (moco + integration fixtures du web)
"pokemon": {
  "slug": "venusaur-mega",
  "labels": {
    "name": "Mega Venusaur",
    "french_name": "Mega Florizarre",
    "simplified_name": "Venusaur",
    "simplified_french_name": "Florizarre",
    "forms_label": "Mega",
    "forms_french_label": "Mega"
  },
  "national_dex_number": 3,
  "icon": "venusaur-mega",
  "pokemon_icon": "venusaur-mega",
  ...
}
```

**Impact utilisateur :** les templates `Election/_top.html.twig`, `Election/_candidates.html.twig` et `Album/_album_macros.html.twig` affichent `pokemonSimplifiedName` / `pokemonSimplifiedFrenchName`. Les dresseurs voient donc « Mega Venusaur » au lieu de « Venusaur » dans le top d'élection. L'icône fonctionne par coïncidence (`slug == icon` pour ces cas) mais est sémantiquement fausse.

---

## Hors périmètre (vérifié : ne nécessite AUCUN travail web)

Ces breaking changes de l'API **ne se propagent pas** jusqu'au web car `pokenini-back` préserve sa forme de sortie publique vers le web (vérifié dans `pokenini-back/tests/resources/functional/controller/`) :

- **`GET /forms` (consolidation)** — le Back garde `/labels` avec `category_forms`/`regional_forms`/`special_forms`/`variant_forms` séparés. `src/ResponseObject/Label/Labels.php` inchangé.
- **`GET /action_logs` (objet → tableau)** — la sortie web-facing du Back reste un **objet à clés dynamiques** avec `item` (`tests/resources/functional/controller/Admin/action-logs.json`). `src/ResponseObject/ActionLog.php` + `GetActionLogsService` (keyed object) inchangés.
- **`GET /election/metrics` (`completion` imbriqué)** — la sortie web-facing du Back reste **plate** (`under_max_view_count`, `max_view_count`, …). `src/DTO/ElectionMetrics.php` inchangé.
- **`POST /election/vote` (`trainer.external_id`)** — le web n'envoie pas `trainer_external_id` dans son body (`src/Service/Back/PostElectionVoteService.php`) et n'utilise pas la réponse. Inchangé.
- **`/debogage/*`** — non exposés par le Back, non consommés par le web.

**Additifs optionnels (non requis, non couverts par ce plan)** : `pokemon.game_bundles` et couleurs de types sur `/album` et `/pokemons/to_choose`, `catch_state_counts_defined_by_trainer` sur `/reports`. Non-breaking — à consommer ultérieurement seulement si une feature les exige.

---

## File Structure

| Fichier | Rôle | Action |
|---|---|---|
| `src/ResponseObject/Election/TopPokemonLabels.php` | Labels imbriqués du pokémon du top | Modifier : +2 props (`simplified_name`, `simplified_french_name`) + getters |
| `src/ResponseObject/Election/TopPokemonInfo.php` | Infos imbriquées du pokémon du top | Modifier : +1 prop (`pokemon_icon`) + getter |
| `src/ResponseObject/Election/TopPokemon.php` | Façade getters consommée par Twig | Modifier : 3 getters délèguent désormais aux vrais champs |
| `tests/src/Unit/ResponseObject/Election/TopPokemonLabelsTest.php` | Test unitaire | Modifier : assertions sur les 2 nouveaux getters |
| `tests/src/Unit/ResponseObject/Election/TopPokemonInfoTest.php` | Test unitaire | Modifier : assertion sur le nouveau getter + signature constructeur |
| `tests/src/Integration/ResponseObject/Election/TopPokemonTest.php` | Test de désérialisation | Modifier : JSON enrichi + corriger les assertions qui figeaient la régression |

Aucune fixture Moco/intégration à modifier : elles **contiennent déjà** `simplified_name`, `simplified_french_name`, `pokemon_icon`.

---

## Task 1 : Consommer les champs enrichis de `election_top`

**Files:**
- Modify: `src/ResponseObject/Election/TopPokemonLabels.php`
- Modify: `src/ResponseObject/Election/TopPokemonInfo.php`
- Modify: `src/ResponseObject/Election/TopPokemon.php`
- Test: `tests/src/Unit/ResponseObject/Election/TopPokemonLabelsTest.php`
- Test: `tests/src/Unit/ResponseObject/Election/TopPokemonInfoTest.php`
- Test: `tests/src/Integration/ResponseObject/Election/TopPokemonTest.php`

**Interfaces:**
- Consomme : le JSON `election_top[]` du Back, dont chaque item a `pokemon.labels.{name,french_name,simplified_name,simplified_french_name}`, `pokemon.pokemon_icon`, `pokemon.{slug,national_dex_number}`, `score.{elo,significance}`.
- Produit (signatures finales, consommées par Twig) :
  - `TopPokemonLabels::__construct(string $name, string $frenchName, string $simplifiedName, string $simplifiedFrenchName)`
  - `TopPokemonLabels::getSimplifiedName(): string`
  - `TopPokemonLabels::getSimplifiedFrenchName(): string`
  - `TopPokemonInfo::__construct(string $slug, TopPokemonLabels $labels, int $nationalDexNumber, string $icon)`
  - `TopPokemonInfo::getIcon(): string`
  - `TopPokemon::getPokemonSimplifiedName()` / `getPokemonSimplifiedFrenchName()` / `getPokemonIcon()` délèguent désormais aux vrais champs.

---

- [ ] **Step 1 : Écrire le test unitaire `TopPokemonLabels` (échec attendu)**

Remplacer le corps de `tests/src/Unit/ResponseObject/Election/TopPokemonLabelsTest.php::testConstructor` par :

```php
public function testConstructor(): void
{
    $object = new TopPokemonLabels('Mega Venusaur', 'Mega Florizarre', 'Venusaur', 'Florizarre');

    $this->assertSame('Mega Venusaur', $object->getName());
    $this->assertSame('Mega Florizarre', $object->getFrenchName());
    $this->assertSame('Venusaur', $object->getSimplifiedName());
    $this->assertSame('Florizarre', $object->getSimplifiedFrenchName());
}
```

- [ ] **Step 2 : Lancer le test, vérifier l'échec**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/ResponseObject/Election/TopPokemonLabelsTest.php`
Expected: FAIL — `Too few arguments to function ... __construct()` / `Call to undefined method ...getSimplifiedName()`.

- [ ] **Step 3 : Implémenter `TopPokemonLabels`**

Remplacer le contenu de `src/ResponseObject/Election/TopPokemonLabels.php` par :

```php
<?php

declare(strict_types=1);

namespace App\ResponseObject\Election;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class TopPokemonLabels
{
    public function __construct(
        #[SerializedName('name')]
        private readonly string $name,
        #[SerializedName('french_name')]
        private readonly string $frenchName,
        #[SerializedName('simplified_name')]
        private readonly string $simplifiedName,
        #[SerializedName('simplified_french_name')]
        private readonly string $simplifiedFrenchName,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getFrenchName(): string
    {
        return $this->frenchName;
    }

    public function getSimplifiedName(): string
    {
        return $this->simplifiedName;
    }

    public function getSimplifiedFrenchName(): string
    {
        return $this->simplifiedFrenchName;
    }
}
```

- [ ] **Step 4 : Lancer le test, vérifier le succès**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/ResponseObject/Election/TopPokemonLabelsTest.php`
Expected: PASS.

- [ ] **Step 5 : Écrire le test unitaire `TopPokemonInfo` (échec attendu)**

Remplacer le corps de `tests/src/Unit/ResponseObject/Election/TopPokemonInfoTest.php::testConstructor` par :

```php
public function testConstructor(): void
{
    $labels = new TopPokemonLabels('Mega Venusaur', 'Mega Florizarre', 'Venusaur', 'Florizarre');
    $object = new TopPokemonInfo('venusaur-mega', $labels, 3, 'venusaur-mega');

    $this->assertSame('venusaur-mega', $object->getSlug());
    $this->assertSame($labels, $object->getLabels());
    $this->assertSame(3, $object->getNationalDexNumber());
    $this->assertSame('venusaur-mega', $object->getIcon());
}
```

- [ ] **Step 6 : Lancer le test, vérifier l'échec**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/ResponseObject/Election/TopPokemonInfoTest.php`
Expected: FAIL — `Too few arguments` / `Call to undefined method ...getIcon()`.

- [ ] **Step 7 : Implémenter `TopPokemonInfo`**

Remplacer le contenu de `src/ResponseObject/Election/TopPokemonInfo.php` par :

```php
<?php

declare(strict_types=1);

namespace App\ResponseObject\Election;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class TopPokemonInfo
{
    public function __construct(
        #[SerializedName('slug')]
        private readonly string $slug,
        #[SerializedName('labels')]
        private readonly TopPokemonLabels $labels,
        #[SerializedName('national_dex_number')]
        private readonly int $nationalDexNumber,
        #[SerializedName('pokemon_icon')]
        private readonly string $icon,
    ) {}

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getLabels(): TopPokemonLabels
    {
        return $this->labels;
    }

    public function getNationalDexNumber(): int
    {
        return $this->nationalDexNumber;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }
}
```

> Note : on mappe `pokemon_icon` (alias injecté délibérément par le BFF) plutôt que `icon`, pour refléter le contrat explicite du Back et l'ancien comportement web (`#[SerializedName('pokemon_icon')]`).

- [ ] **Step 8 : Lancer le test, vérifier le succès**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/ResponseObject/Election/TopPokemonInfoTest.php`
Expected: PASS.

- [ ] **Step 9 : Corriger les délégations dans `TopPokemon`**

Dans `src/ResponseObject/Election/TopPokemon.php`, remplacer les 3 getters incorrects :

```php
    public function getPokemonSimplifiedName(): string
    {
        return $this->pokemon->getLabels()->getSimplifiedName();
    }

    public function getPokemonSimplifiedFrenchName(): string
    {
        return $this->pokemon->getLabels()->getSimplifiedFrenchName();
    }

    public function getPokemonIcon(): string
    {
        return $this->pokemon->getIcon();
    }
```

(Les autres getters — `getPokemonSlug`, `getPokemonName`, `getPokemonNationalDexNumber`, `getPokemonFrenchName`, `getElo`, `isSignificance` — restent inchangés.)

- [ ] **Step 10 : Mettre à jour le test d'intégration de désérialisation (échec attendu d'abord)**

Dans `tests/src/Integration/ResponseObject/Election/TopPokemonTest.php`, dans `testDeserialize`, enrichir le bloc `labels` du JSON et corriger les assertions qui figeaient la régression :

```php
        $json = <<<'JSON'
            {
                "pokemon": {
                    "slug": "venusaur-mega",
                    "labels": {
                        "name": "Mega Venusaur",
                        "french_name": "Mega Florizarre",
                        "simplified_name": "Venusaur",
                        "simplified_french_name": "Florizarre"
                    },
                    "national_dex_number": 3,
                    "pokemon_icon": "venusaur-mega"
                },
                "score": {
                    "elo": 1000,
                    "significance": false
                }
            }
            JSON;

        $object = $serializer->deserialize($json, TopPokemon::class, 'json');

        $this->assertSame('venusaur-mega', $object->getPokemonSlug());
        $this->assertSame('Mega Venusaur', $object->getPokemonName());
        $this->assertSame(3, $object->getPokemonNationalDexNumber());
        $this->assertSame('Venusaur', $object->getPokemonSimplifiedName());
        $this->assertSame('Mega Florizarre', $object->getPokemonFrenchName());
        $this->assertSame('Florizarre', $object->getPokemonSimplifiedFrenchName());
        $this->assertSame('venusaur-mega', $object->getPokemonIcon());
        $this->assertSame(1000.0, $object->getElo());
        $this->assertFalse($object->isSignificance());
```

Faire de même pour le bloc `labels` + `pokemon_icon` de `testDeserializeSignificant` (ajouter les 2 `simplified_*` et `pokemon_icon` pour que la désérialisation reste valide ; ses assertions portent seulement sur `elo`/`significance`).

> `testDeserializeArray` et `testDeserializeEmptyArray` n'ont pas besoin de modification : la fixture `election_mega_top_5.json` contient déjà les champs enrichis.

- [ ] **Step 11 : Lancer le test d'intégration, vérifier le succès**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/ResponseObject/Election/TopPokemonTest.php`
Expected: PASS (les 4 tests).

- [ ] **Step 12 : Vérification finale (pas de commit)**

Lancer les tests touchés puis la qualité statique :

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/ResponseObject/Election tests/src/Integration/ResponseObject/Election/TopPokemonTest.php
docker compose exec php php tools/phpstan/vendor/bin/phpstan --memory-limit=-1
```

Expected: tous verts ; PHPStan 0 erreur. **Ne pas committer** — laisser les changements dans l'arbre de travail.

---

## Self-Review — Couverture

| Élément du périmètre | Tâche | Couvert ? |
|---|---|---|
| `election_top` — `simplified_name` consommé | Task 1 (steps 1–4, 9–11) | ✅ |
| `election_top` — `simplified_french_name` consommé | Task 1 (steps 1–4, 9–11) | ✅ |
| `election_top` — `pokemon_icon` consommé | Task 1 (steps 5–9, 10–11) | ✅ |
| Test figeant la régression corrigé | Task 1 (step 10) | ✅ |
| `game_bundles`, `reports`, structure `election_top` | déjà committés | ✅ (hors plan) |
| `forms`, `action_logs`, `metrics`, `vote`, `debogage` | n'atteignent pas le web | N/A (Hors périmètre, vérifié) |
| Additifs `game_bundles`/couleurs sur album & to_choose | non-breaking, optionnel | — (non couvert volontairement) |

**Vérification couverture/MSI** : les 3 nouveaux getters et le constructeur élargi sont tous exercés par les tests unitaires + intégration ci-dessus, préservant 100 % de couverture et MSI.
