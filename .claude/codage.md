# Conventions de codage — Pokénini Web

## Langage et framework

- **PHP 8.4+** strict, `declare(strict_types=1)` obligatoire sur chaque fichier
- **Symfony 8.0**, framework principal
- Autoload PSR-4 : `App\` → `src/`, `App\Tests\` → `tests/src/`, `App\Tests\Utils\` → `tests/Utils/`
- Pas de frontend JS bundler — HTML/CSS servis directement via Twig

Outils qualité actifs :

| Outil | Version | Commande |
|-------|---------|---------|
| PHP-CS-Fixer | ^3.95.1 | `make phpcsfixer` / `make phpcsfixer-fix` |
| PHPStan | ^2.1.54 (niveau 9) | `make phpstan` |
| Psalm | 6.16.1 | `make psalm` |
| PHPMD | ^2.15 | `make phpmd` |
| Deptrac | ^4.6.0 | `make deptrac` |
| Infection | ^0.32.7 (100 % MSI) | `make infection` |
| PHPUnit | ^13.1.8 | `make tests` |
| PHPInsights | ^2.14.2 | `make phpinsights` |

---

## Style de code

Formatter : **PHP-CS-Fixer** — config `.php-cs-fixer.dist.php`

Rulesets activés : `@PER-CS`, `@Symfony`, `@PSR12`, `@PhpCsFixer`, `@PHP83Migration`

Règles notables :
- `declare_strict_types` obligatoire sur chaque fichier PHP
- `psr_autoloading` : les classes suivent strictement PSR-4
- Analyse statique : **PHPStan niveau 9**, **Psalm** avec taint analysis
- Qualité structurelle : **PHPMD** (ruleset personnalisé `phpmd.ruleset.xml`)

Commande auto-fix : `make phpcsfixer-fix`

---

## Nommage

| Élément | Convention | Exemple |
|---------|-----------|---------|
| Classes | PascalCase | `GetAlbumDexListService` |
| Interfaces | PascalCase + suffixe `Interface` | `BackServiceInterface`, `ConnectControllerInterface` |
| Traits | PascalCase + suffixe `Trait` | `AuthenticatorTrait`, `TestNavTrait` |
| Méthodes | camelCase | `getPokedexData()`, `isAnAdmin()` |
| Variables PHP | camelCase | `$trainerId`, `$loggedUserId` |
| Constantes de classe | UPPER_SNAKE_CASE | `SESSION_ACTION_DATA`, `EXPIRES_IN` |
| Fichiers PHP | PascalCase (= nom de la classe) | `GetTrainerPokedexService.php` |
| Templates Twig | PascalCase par feature | `Album/index.html.twig`, `Home/index.html.twig` |
| Paramètres de query string | abréviations 2-3 lettres | `cs`, `f`, `fc`, `fr`, `fs`, `fv`, `at`, `t1`, `t2` |
| Slugs | kebab-case, validés par regex dans les routes | `[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*` |

Variables courtes (`$e`, `$i`, `$id`) autorisées via PHPMD (`phpmd.ruleset.xml:53-56`).

### Suffixes imposés par couche

| Couche | Suffixe |
|--------|---------|
| `Service/Back/` | `Service` (ex. `GetAlbumDexListService`) |
| `Service/` | `Service` (ex. `GetTrainerPokedexService`) |
| `Controller/` | `Controller` (ex. `AlbumIndexController`) |
| `ResponseObject/` | aucun — nom de la ressource (ex. `Dex`, `Pokemon`) |
| `DTO/` | aucun ou suffixe descriptif (ex. `DexFilters`, `ElectionVote`) |
| `Exception/` | `Exception` (ex. `ModifyFailedException`) |
| `Validator/` | contrainte sans suffixe + `Validator` (ex. `CatchStates`, `CatchStatesValidator`) |

---

## Structure des classes

- **Controllers** : toujours `final`, étendent `AbstractController` Symfony. Un fichier = une page/fonctionnalité. Aucune logique métier.
- **Services Back** : étendent `AbstractBackService`. Clients HTTP purs, pas de logique métier.
- **Services métier** : wrappent les services `Back`, gèrent les exceptions HTTP (`HttpExceptionInterface|TransportExceptionInterface` → `null` ou `ModifyFailedException`).
- **ResponseObjects** : toujours `final`, constructeur promotionnel readonly + `#[SerializedName]` sur chaque paramètre, aucune logique.
- **DTO** : `final`, construction via factory statique `createFromArray` ou constructeur avec `OptionsResolver` pour la validation.
- **Exceptions** : `final`, étendent `\RuntimeException`. Message par défaut dans `$message`.

---

## Patterns récurrents

### 1. Classe `final` systématique

Toutes les classes métier sont `final`. Seules les classes abstraites (`AbstractBackService`, `AbstractAuthenticator`, `AbstractConnectController`) ne le sont pas.

### 2. Propriétés `readonly` via constructor promotion

Toutes les dépendances injectées sont `private readonly`.

```php
public function __construct(
    private readonly GetTrainerPokedexService $getTrainerPokedexService,
    private readonly UserTokenService $userTokenService,
) {}
```

### 3. `#[SerializedName]` sur les ResponseObjects

Tous les champs désérialisés depuis l'API portent l'attribut `#[SerializedName('snake_case')]`.

### 4. Services Back sans état + injection via `AbstractBackService`

Chaque `Service\Back\*` extends `AbstractBackService` qui fournit `request()` et `requestContent()` avec auth Bearer/X-Provider automatique.

### 5. Filtre HTTP capturé au niveau Service (couche orchestration)

Les `HttpExceptionInterface` et `TransportExceptionInterface` sont attrapés dans `Service\*` (pas dans `Service\Back\*` ni dans les contrôleurs), et se transforment en `null` ou exception métier.

### 6. AlbumFilters statiques

`FromRequest::get()` et `Mapping::get()` sont des méthodes statiques pures sans état. Pas de service, pas de DI.

### 7. Traits partagés

`AuthenticatorTrait` pour `onAuthenticationSuccess/Failure` et `loadFromAccessToken` (partagé entre Discord, Google, Fake authenticators).

### 8. `OptionsResolver` pour les DTO

Utilisé pour valider les tableaux de données brutes entrant dans les DTO : `ElectionVote`, `ElectionMetrics`, `DexFilters`, `DexFiltersRequest`.

### 9. SHA1 comme trainer ID

`sha1(userIdentifier)` exposé dans les URLs (ex : `?t=7b52009b64…`) via `UserTokenService::getLoggedUserId()`.

---

## Annotations et style

| Annotation | Obligatoire | Contexte |
|-----------|-------------|---------|
| `declare(strict_types=1)` | Oui | Tout fichier PHP |
| `#[\Override]` | Oui | Méthodes qui surchargent une interface ou classe parente |
| `@SuppressWarnings("PHPMD.ExcessiveParameterList")` | Optionnel | ResponseObjects avec >5 params |
| `@SuppressWarnings("PHPMD.UnusedFormalParameter")` | Optionnel | Méthodes d'interface avec param ignoré |
| `@psalm-suppress RiskyTruthyFalsyComparison` | Optionnel | Comparaisons truthy inévitables |
| `@phpstan-ignore return.unusedType` | Optionnel | Types retour d'interface non utilisés |
| `// @codeCoverageIgnoreStart/End` | Optionnel | Blocs non testables (ex: `eraseCredentials`) |
| `/** @var Type */` | Obligatoire | Quand PHPStan/Psalm ne peut pas inférer |

---

## Tests

### Nommage

- Méthodes de test : `test` + description camelCase (`testListRead`, `testAuthenticateTrainer`)
- Classes : nom de la feature + `Test` (`CommonTest`, `FakeAuthenticatorAuthenticateTest`)

### Attributs obligatoires sur chaque classe de test

```php
/**
 * @internal
 */
#[CoversClass(ClassUnderTest::class)]
#[Group('api-mocked-testing')]
final class FooTest extends WebTestCase {}
```

- `@internal` : toujours présent (docblock)
- `#[CoversClass]` : obligatoire, peut être multiple si plusieurs classes testées
- `#[Group('api-mocked-testing')]` : obligatoire pour les tests Integration et Browser (dépendent de Moco)
- `final` : toujours présent
- Héritage : `TestCase` (Unit), `WebTestCase` (Integration), `AbstractBrowserTestCase` (Browser)

### Fixtures et données de test

- Fixtures HTTP : JSON dans `tests/resources/moco/Back/` (format Moco), organisées par domaine (`/album/`, `/dex/`, `/election/`)
- Jamais de mock du client HTTP dans les tests d'intégration — utiliser Moco
- Authentification dans les tests : `GetUserToken::getFakeUserToken('id', 'Provider')` + `$client->loginUser($user, 'web')`
- Couverture 100% exigée, MSI Infection 100% exigé
- Helpers partagés via traits : `TestNavTrait` (assertions nav bar), `ResponseObjectTrait` (stubs d'objets)
- Debug HTML : `file_put_contents('tests/last.html', $client->getCrawler()->html())`

---

## Règles framework spécifiques

- **Routes** : définies via `#[Route]` PHP Attribute ; toujours préfixées `/{_locale}` (géré par `config/routes.yaml`)
- **Sécurité** : `access_control` dans `security.yaml`, pas de vérification manuelle de rôle dans les controllers sauf logique fine (`AlbumIndexController`, `AlbumUpsertController`, `TrainerUpsertController`)
- **Désérialisation** : le Serializer Symfony est utilisé (pas JMS) ; `property_info` désactivé, extraction de types par constructor promotion uniquement
- **DI** : autowiring complet via `config/services.yaml` ; les scalaires (`$backUrl`, `$backCafilePath`, `$demoUserId`) sont bindés globalement
- **Cache** : Redis via APCu-compatible adapter ; pas de cache local dans le code applicatif
- **Validation** : contraintes Symfony (`#[Assert\*]`) + contrainte custom `CatchStates` qui appelle un service Back
- **Deptrac** : les couches sont strictement isolées — `AppController` ne peut pas dépendre directement de `AppService\Back`
- **Twig** : `AppRequestExtension` utilise `#[AsTwigFunction]` natif PHP au lieu d'étendre `AbstractExtension`

---

## À faire / À éviter

**À faire**
- `declare(strict_types=1)` en première ligne
- `final` sur toute classe non conçue pour l'extension
- `private readonly` pour toutes les injections constructeur
- `#[\Override]` sur toutes les surcharges / implémentations
- Attraper les exceptions HTTP au niveau `Service\*` (orchestration), pas dans le contrôleur
- Utiliser Moco pour les fixtures HTTP dans les tests d'intégration
- Annoter les classes de test avec `@internal`, `#[CoversClass]`, `final`
- `OptionsResolver` pour valider les tableaux d'entrée des DTOs
- `JsonDecoder::decode()` plutôt que `json_decode()` direct

**À éviter**
- Dépendance directe `Controller → Service\Back` (violation Deptrac)
- Mocker le `HttpClientInterface` dans les tests d'intégration (utiliser Moco)
- `catch (\Exception $e)` trop large sans ré-lancer ou logger précisément
- Logique métier dans les `ResponseObject` — ils sont des conteneurs passifs
- Injecter `Request` dans le constructeur d'un contrôleur — passer en paramètre de méthode
- `json_decode()` direct — utiliser `JsonDecoder::decode()`
