# Design : AlbumFilterBag — suppression du couplage implicite entre FromRequest et Mapping

## Contexte

`AlbumFilters\FromRequest::get()` retourne `string[]|string[][]` : certaines clés (`cs`, `f`) portent un scalaire `string`, les autres portent un `string[]`. `AlbumFilters\Mapping::get()` reçoit ce tableau mixte et utilise `is_array($value)` pour distinguer les deux cas à l'exécution. Si `FromRequest` change son format de sortie, `Mapping` peut silencieusement produire des résultats incorrects sans erreur de type.

## Objectif

Introduire un type dédié `AlbumFilterBag` que `FromRequest` retourne et qui absorbe la logique de mapping (aujourd'hui dans `Mapping`). Éliminer `Mapping` comme classe séparée. Aucun `is_array` runtime.

## Architecture

### Nouveau composant : `src/AlbumFilters/AlbumFilterBag.php`

```php
final readonly class AlbumFilterBag
{
    private const array MAPPING = [
        'cs'   => 'catch_states',
        'f'    => 'families',
        'fc'   => 'category_forms',
        'fr'   => 'regional_forms',
        'fs'   => 'special_forms',
        'fv'   => 'variant_forms',
        'at'   => 'any_types',
        't1'   => 'primary_types',
        't2'   => 'secondary_types',
        'ogb'  => 'original_game_bundles',
        'gba'  => 'game_bundle_availabilities',
        'gbsa' => 'game_bundle_shiny_availabilities',
        'ca'   => 'collection_availabilities',
    ];

    /**
     * @param array<string, string>   $stringFilters   Filtres scalaires (cs, f)
     * @param array<string, string[]> $multipleFilters  Filtres multi-valeurs
     */
    public function __construct(
        public array $stringFilters = [],
        public array $multipleFilters = [],
    ) {}

    /**
     * Format pour redirectToRoute — conserve le format actuel (mixed).
     *
     * @return array<string, string|string[]>
     */
    public function toRouteParams(): array
    {
        return array_merge($this->stringFilters, $this->multipleFilters);
    }

    /**
     * Format pour l'API backend — clés longues, tout en string[].
     *
     * @return array<string, string[]>
     */
    public function toApiParams(): array
    {
        $result = [];
        foreach ($this->stringFilters as $key => $value) {
            $result[self::MAPPING[$key]] = [$value];
        }
        foreach ($this->multipleFilters as $key => $values) {
            $result[self::MAPPING[$key]] = $values;
        }
        return $result;
    }
}
```

## Fichiers modifiés

| Fichier | Changement |
|---|---|
| `src/AlbumFilters/AlbumFilterBag.php` | **créé** |
| `src/AlbumFilters/FromRequest.php` | retourne `AlbumFilterBag` au lieu de `array` |
| `src/AlbumFilters/Mapping.php` | **supprimé** |
| `src/Controller/AlbumIndexController.php` | `Mapping::get($filters)` → `$filterBag->toApiParams()` |
| `src/Controller/ElectionIndexController.php` | idem |
| `src/Controller/ElectionVoteController.php` | `$filters` → `$filterBag->toRouteParams()` dans `array_merge` |

## Data flow

```
Avant :
Request → FromRequest::get()  → array<string, string|string[]>
                              → Mapping::get()     → array<string, string[]>  (API)
                              → redirectToRoute    → query params

Après :
Request → FromRequest::get()  → AlbumFilterBag
                              → toApiParams()      → array<string, string[]>  (API)
                              → toRouteParams()    → array<string, string|string[]> (redirect)
```

## Tests

| Fichier test | Changement |
|---|---|
| `tests/src/Unit/AlbumFilters/AlbumFilterBagTest.php` | **créé** — couvre `toApiParams()` et `toRouteParams()` avec les fixtures de `MappingTest` |
| `tests/src/Unit/AlbumFilters/MappingTest.php` | **supprimé** |
| `tests/src/Unit/AlbumFilters/FromRequestTest.php` | adapté — assertions sur `$bag->stringFilters` et `$bag->multipleFilters` |
| Tests controllers | inchangés en substance (tests d'intégration HTTP) |

## Contraintes

- PHPStan niveau 9 doit rester vert
- Psalm + Deptrac 0 violations
- `make tests-unit` vert
