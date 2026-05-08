# Conventions de codage — Pokénini Web

## Nommage

| Élément | Convention | Exemple concret |
|---|---|---|
| Classes | PascalCase, nom explicite du rôle | `GetTrainerPokedexService`, `AlbumIndexController` |
| Méthodes | camelCase, verbe d'action | `getPokedexData()`, `accessDexIsGranted()` |
| Variables | camelCase | `$requestedTrainerId`, `$catchStates` |
| Constantes | SCREAMING_SNAKE_CASE | `SESSION_ACTION_DATA` |
| Fichiers | PascalCase, correspond exactement au nom de la classe | `GetTrainerPokedexService.php` |
| Tests | même nom que la classe couverte + `Test` | `AlbumIndexControllerTest` |

## Structure des fichiers

```
src/
  Controller/       Un fichier par page/action principale
  Controller/Connect/  Sous-répertoire pour les OAuth controllers
  Service/          Orchestration (ne touche pas HTTP directement)
  Service/Back/     Tous les appels HTTP vers l'API backend
  ResponseObject/   Objets peuplés par le Serializer depuis le JSON de l'API
  ResponseObject/Album/, /Election/, /Common/, /Label/   (sous-groupes fonctionnels)
  DTO/              Conteneurs entre Controller et Service
  AlbumFilters/     Parsers de query params (statiques)
  Security/         OAuth2 + User + UserTokenService
  Twig/             Extensions Twig
  Validator/        Contraintes Symfony custom
  Utils/            Utilitaires purs sans dépendance framework
```

## Patterns récurrents

### Tous les fichiers PHP

```php
<?php
declare(strict_types=1);
namespace App\...;
```
Toujours en tête. Référence : tous les fichiers `src/`.

### Classes : `final` par défaut

- Toutes les classes `src/` sont `final` sauf les classes abstraites et les classes de `Service/` (exception tolérée par Psalm : `ClassMustBeFinal` supprimé pour `src/Service`).
- Les classes `tests/` sont toutes `final`.

### Controllers : thin, injection par constructeur ou méthode

- Les dépendances récurrentes (utilisées dans plusieurs méthodes) sont injectées par constructeur.
- Les dépendances one-shot sont injectées directement dans la méthode action.
- Référence : `src/Controller/AlbumIndexController.php` (constructeur) vs `src/Controller/ElectionIndexController.php` (paramètre de méthode).

### Service\Back : AbstractBackService

Tout service qui appelle l'API backend hérite de `AbstractBackService` (`src/Service/Back/AbstractBackService.php`). Il expose `requestContent(method, url, options)` et `request(...)`. L'auth Bearer + X-Provider est injectée automatiquement.

```php
class GetTrainerDexListService extends AbstractBackService
{
    public function get(): array
    {
        $json = $this->requestContent('GET', '/trainer/dex');
        return JsonDecoder::decode($json);
    }
}
```

### ResponseObjects : Serializer + #[SerializedName]

Tous les champs mappent le JSON de l'API via `#[SerializedName('snake_case')]`. Le constructeur est le seul point d'entrée. Uniquement des getters, zéro logique.

```php
final class Dex {
    public function __construct(
        #[SerializedName('is_released')]
        private readonly bool $isReleased,
        ...
    ) {}
    public function isReleased(): bool { return $this->isReleased; }
}
```

Référence : `src/ResponseObject/Album/Dex.php`, `src/ResponseObject/Common/Pokemon.php`.

### DTOs : factory statique + OptionsResolver

Les DTOs complexes utilisent un constructeur privé et une méthode `createFromArray()` avec `OptionsResolver`. Référence : `src/DTO/DexFilters.php:16`.

### AlbumFilters : méthodes statiques pures

`FromRequest::get(Request)` parse les query params. `Mapping::get(array)` les transforme en paramètres API. Toujours appelés en binôme dans les controllers. Référence : `src/Controller/AlbumIndexController.php:43-44`.

## Style

- **Pas de commentaires sauf si** le WHY est non-évident. On voit `/** @var Type $var */` pour aider le type-checker, mais pas de docblocks narratifs.
- `#[\Override]` systématique sur les méthodes héritées/implémentées.
- `@SuppressWarnings("PHPMD.ExcessiveParameterList")` sur les constructeurs/méthodes volumineux légitimes.
- `// @codeCoverageIgnoreStart / End` uniquement sur le code inatteignable (ex. `eraseCredentials()`, `check()` du OAuth callback).
- Pas d'injection de `ContainerInterface`. L'autowiring Symfony fait tout.

## Tests

### Structure obligatoire pour chaque classe de test

```php
/**
 * @internal
 */
#[CoversClass(ClassUnderTest::class)]
#[Group('api-mocked-testing')]  // si nécessaire
final class ClassUnderTestTest extends TestCase  // ou WebTestCase
{
    public function testSomething(): void { ... }
}
```

### Règles de test

| Type | Étend | Réseau | Groupe |
|---|---|---|---|
| Unit | `TestCase` | non | aucun |
| Integration | `WebTestCase` | Moco (mock HTTP) | `api-mocked-testing` |
| Browser | `AbstractBrowserTestCase` (Panther) | Moco | `api-mocked-testing` |

- Les tests unitaires mockent les dépendances avec `$this->createMock()`.
- Les tests d'intégration utilisent `self::createClient()`, `$client->loginUser($user, 'web')` pour authentifier.
- Helpers réutilisables dans `tests/src/Common/Traits/` (ex. `TestNavTrait`, `ResponseObjectTrait`).
- Créer un faux utilisateur : `GetUserToken::getFakeUserToken('id', 'Provider')` (`tests/Utils/`).

### Couverture et mutation

100% coverage et 100% MSI (Infection) sont requis. Tout code non atteignable doit être marqué `@codeCoverageIgnoreStart`.

## Règles framework / Deptrac

Les dépendances entre couches sont contrôlées par Deptrac (`deptrac.yaml`). Règles clés :

- `AppController` peut dépendre de `AppService`, `AppDTO`, `AppResponseObject`, `AppSecurity`, `AppValidator` — mais **pas** de `AppService\Back` directement.
- `AppService\Back` peut dépendre de `AppResponseObject`, `AppUtils`, `AppSecurity`.
- `AppResponseObject` ne dépend que de `SymfonySerializer`.
- `AppException` n'a aucune dépendance.

Violer Deptrac fait échouer `make deptrac`.

## À faire / À éviter

| À faire | À éviter |
|---|---|
| `final class` partout sauf exceptions documentées | Classes non-final sans raison |
| Injection par constructeur avec `readonly` | `$this->get('service.id')` (locator pattern) |
| `#[SerializedName]` sur chaque propriété de ResponseObject | Nommage camelCase dans les ResponseObjects sans mapping |
| Appeler `requestContent()` dans `Service\Back` | Appels HTTP depuis `Service/` ou `Controller/` |
| Tests `final` avec `#[CoversClass]` | Tests sans `#[CoversClass]` (rompt l'infection/coverage) |
| Fixtures Moco dans `tests/resources/moco/Back/` pour les tests d'intégration | Mocker `HttpClientInterface` en test d'intégration |
