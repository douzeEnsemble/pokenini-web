# Conventions de codage — pokenini-web

## Langage et runtime

- **PHP 8.4** strict, `declare(strict_types=1)` obligatoire sur chaque fichier
- **Symfony 8.0**, framework principal
- Autoload PSR-4 : `App\` → `src/`, `App\Tests\` → `tests/src/`, `App\Tests\Utils\` → `tests/Utils/`

## Style de code

- Outil : **PHP CS Fixer** avec ruleset `@PER-CS`, `@Symfony`, `@PSR12`, `@PhpCsFixer`, `@PHP83Migration` (`.php-cs-fixer.dist.php:13-20`)
- Préfixe `declare_strict_types` systématique
- Analyse statique : **PHPStan niveau 9** (`phpstan.neon.dist:6`), **Psalm** avec taint analysis (`Makefile:244`)
- Qualité structurelle : **PHPMD** (ruleset personnalisé `phpmd.ruleset.xml`)

## Nommage

- **Classes** : PascalCase. Suffixe explicite selon le rôle : `Controller`, `Service`, `Exception`, `Validator`, `Test`.
- **Méthodes** : camelCase. Préfixes conventionnels `get`, `is`, `has`, `add` pour les accesseurs.
- **Constantes de classe** : `UPPER_SNAKE_CASE` (ex : `SESSION_ACTION_DATA` dans `src/Controller/AdminActionController.php:18`, `EXPIRES_IN` dans `src/Security/FakeAuthenticator.php:20`).
- **Variables** : camelCase. Variables courtes (`$e`, `$i`, `$id`) autorisées explicitement via PHPMD (`phpmd.ruleset.xml:53-56`).
- **Paramètres de query string** : abréviations à 2-3 lettres pour les filtres album (`cs`, `f`, `fc`, `fr`, `fs`, `fv`, `at`, `t1`, `t2`) — mapping explicite dans `src/AlbumFilters/Mapping.php`.
- **Slugs** : kebab-case, validés par regex dans les routes (`[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*`).

## Structure des classes

- **Controllers** : toujours `final`, étendent `AbstractController` Symfony. Un fichier = une page/fonctionnalité. Les dépendances longues sont injectées via les paramètres de méthode plutôt que le constructeur quand ce sont des services secondaires.
- **Services** : non-`final` (pour le mock). Le service de couche `Back` étend `AbstractBackService`. Les services de couche métier wrappent les services `Back` et gèrent les exceptions HTTP (`HttpExceptionInterface|TransportExceptionInterface` → retour `null` ou lancement de `ModifyFailedException`).
- **ResponseObjects** : toujours `final`, constructeur promotionnel avec `#[SerializedName]` sur chaque paramètre, aucune logique métier.
- **DTO** : `final`, construction via factory statique `createFromArray` ou constructeur avec `OptionsResolver` pour la validation des données entrantes.
- **Exceptions** : `final`, étendent `\RuntimeException`. Message par défaut dans `$message`.

## Patterns structurels récurrents

- **Readonly constructor promotion** systématique sur tous les objets (ResponseObjects, DTO immutables) : `src/ResponseObject/Album/Album.php:14-18`.
- **Attributs PHP 8 sur les méthodes** : routes via `#[Route]`, droits via `#[IsGranted]`, `#[\Override]` systématique sur les redéfinitions.
- **Traits partagés** : `AuthenticatorTrait` pour `onAuthenticationSuccess/Failure` et `loadFromAccessToken` (partagé entre Discord, Google, Fake authenticators).
- **OptionsResolver** pour valider les tableaux de données brutes entrant dans les DTO : `ElectionVote`, `ElectionMetrics`, `DexFilters`, `DexFiltersRequest`.
- **Deptrac** comme garde-fou architectural : `Controllers → Services → Back Services`, jamais de court-circuit direct (`deptrac.yaml`).
- **SHA1** du `userIdentifier` comme trainer ID exposé dans les URLs (ex : `?t=7b52009b64...`) — `src/Security/UserTokenService.php:30`.
- **`@SuppressWarnings("PHPMD.UnusedFormalParameter")`** annotant les méthodes d'interface avec paramètres non utilisés (onAuthenticationSuccess, upgradePassword…).
- **`@codeCoverageIgnoreStart/End`** pour le code non testable (`eraseCredentials`, `check()` des controllers OAuth).

## Tests

- **3 niveaux** : `Unit/` (mocks PHPUnit purs), `Integration/` (WebTestCase + Moco HTTP), `Browser/` (Panther, headless Chrome).
- Toutes les classes de test sont `final`, annotées `@internal`, décorées `#[CoversClass(...)]` et `#[Group('api-mocked-testing')]` pour les tests d'intégration/browser.
- Pas de mock du client HTTP dans les tests d'intégration — seuls les fixtures Moco JSON sont autorisés.
- Couverture 100% exigée, MSI Infection 100% exigé (`Makefile:316`).
- Fixtures Moco organisées par domaine : `tests/resources/moco/Back/responses/album/`, `/dex/`, `/election/`.
- Tests unitaires : assertions directes sur les objets, pas d'accès conteneur.
- Helpers partagés via traits : `TestNavTrait` (assertions nav bar), `ResponseObjectTrait` (stubs d'objets).
- `GetUserToken::getFakeUserToken()` pour créer un `User` authentifié dans les tests (`tests/Utils/GetUserToken.php`).

## Règles framework spécifiques

- Routes toutes préfixées `/{_locale}` (en|fr) via `config/routes.yaml:6`.
- Les paramètres liés (`$backUrl`, `$backCafilePath`, `$demoUserId`) sont injectés par nom via `services.yaml:6-8`.
- `property_info` désactivé (`framework.yaml:22-24`), extraction de types par constructor promotion uniquement.
- Sécurité : `access_control` dans `security.yaml`, pas de vérification manuelle de rôle dans les controllers sauf logique fine (premium/collector dans `AlbumIndexController`, `AlbumUpsertController`, `TrainerUpsertController`).
- L'extension Twig `AppRequestExtension` utilise `#[AsTwigFunction]` natif PHP au lieu d'étendre `AbstractExtension`.

## À faire / À éviter

**À faire**
- `declare(strict_types=1)` en première ligne
- `final` sur toute classe non conçue pour l'extension
- `private readonly` pour toutes les injections constructeur
- `#[\Override]` sur toutes les surcharges / implémentations
- PHPDoc sur tous les tableaux complexes
- `OptionsResolver` pour valider les tableaux d'entrée des DTOs
- 100% couverture et 100% MSI Infection

**À éviter**
- `json_decode()` direct — utiliser `JsonDecoder::decode()`
- Accès à `Service\Back` depuis les Controllers (Deptrac interdit)
- Logique métier dans les Controllers
- Mocks du client HTTP dans les tests d'intégration
