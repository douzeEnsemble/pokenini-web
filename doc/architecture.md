# Architecture — Pokénini Web

## Vue d'ensemble

Pokénini Web est le **frontend Symfony 8.0 stateless** (aucune base de données propre) d'un tracker de Pokédex étendu (formes living dex, alternates, gender). Toute la donnée métier est stockée et calculée dans `pokenini-api` (service externe) ; ce projet ne fait que présenter et relayer les données via des templates Twig.

Périmètre fonctionnel :
- Consultation et mise à jour de l'album Pokédex d'un dresseur
- Système d'élection préférentielle de Pokémon (vote ELO)
- Gestion du profil dresseur (liste des dex, activation)
- Actions administratives (mise à jour des données, invalidation de cache)
- Authentification OAuth2 multi-fournisseur (Discord, Google)
- Interface multilingue (fr/en) via `/{_locale}` prefix

---

## Carte des répertoires

```
pokenini-web/
├── src/                        # Code applicatif (PSR-4: App\)
│   ├── AlbumFilters/           # Parsing filtres URL → AlbumFilterBag (value object)
│   │   ├── FromRequest         # Parse query params → AlbumFilterBag
│   │   └── AlbumFilterBag      # toApiParams() (clés longues API) + toRouteParams() (Twig/redirect)
│   ├── Controller/             # Un contrôleur par page/fonctionnalité ; délègue aux Services
│   │   └── Connect/            # Contrôleurs OAuth2 (Discord, Google, Fake)
│   ├── DTO/                    # Conteneurs de données Controller ↔ Service
│   ├── Exception/              # Exceptions métier (NoLoggedUser, ModifyFailed…)
│   ├── ResponseObject/         # Désérialisation JSON de l'API backend (readonly)
│   │   ├── Album/              # Album, Pokedex, Dex, Report, ReportDetail
│   │   ├── Common/             # Pokemon (partagé)
│   │   ├── Election/           # ElectionIndex, ElectionList, TopPokemon
│   │   └── Label/              # Labels, CatchState, Type, CategoryForm, GameBundle…
│   ├── Security/               # User, authenticateurs OAuth2, UserProvider, UserRefresher
│   ├── Service/                # Orchestration : compose Back services, gère erreurs HTTP
│   │   └── Back/               # Clients HTTP vers pokenini-api (AbstractBackService)
│   ├── Twig/                   # Extensions Twig (AppExtension, AppRequestExtension, AppTranslatorExtension)
│   ├── Utils/                  # JsonDecoder (json_decode avec JSON_THROW_ON_ERROR)
│   └── Validator/              # CatchStates (contrainte Symfony custom)
├── templates/                  # Vues Twig par feature (Album/, Home/, Election/…)
├── tests/
│   ├── src/
│   │   ├── Unit/               # PHPUnit pur, pas de container
│   │   ├── Integration/        # WebTestCase + Moco (groupe api-mocked-testing)
│   │   └── Browser/            # Panther/Chrome (groupe api-mocked-testing)
│   ├── Utils/                  # GetUserToken, WithConsecutive
│   └── resources/
│       ├── moco/Back/          # Fixtures JSON pour Moco (API backend)
│       └── moco/Matomo/        # Fixtures JSON pour Matomo
├── config/
│   ├── packages/               # Configs Symfony (security, cache, framework, twig…)
│   └── routes.yaml             # Routage global (préfixe /{_locale})
├── tools/                      # Outils qualité (chacun avec son vendor/)
│   ├── deptrac/
│   ├── infection/
│   ├── jsonlint/
│   ├── php-cs-fixer/
│   ├── phpmd/
│   ├── phpstan/
│   └── psalm/
├── .docker/                    # Dockerfiles (php, nginx, moco)
├── docker-compose.yaml         # Services : php, web, redis, moco.back, moco.matomo.gbl, vnu
└── Makefile                    # Toutes les commandes du projet
```

---

## Rôle de chaque couche

| Couche            | Rôle                                                                                                                                                                                                  | Règle de dépendance (Deptrac)                                                                 |
| ----------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------- |
| `Controller/`     | Point d'entrée HTTP. Lit Request, appelle Service, rend Twig. Aucune logique métier.                                                                                                                  | Peut dépendre de : Service, DTO, Exception, ResponseObject, Security, AlbumFilters, Validator |
| `Service/`        | Orchestration. Combine plusieurs Back services, gère `HttpExceptionInterface                                                                                                                          | TransportExceptionInterface` → null/exception métier.                                         | Peut dépendre de : Service\Back, DTO, Exception, ResponseObject, Security, Utils |
| `Service\Back/`   | Client HTTP pur vers pokenini-api. Hérite `AbstractBackService` (Bearer token, X-Provider, cafile, log). Désérialisent via Symfony Serializer.                                                        | Peut dépendre de : Utils, Exception, ResponseObject, Security, Serializer                     |
| `ResponseObject/` | POPOs désérialisés depuis le JSON de l'API. Constructeur promotionnel readonly + `#[SerializedName]`. Aucune logique.                                                                                 | Peut dépendre de : Serializer seulement                                                       |
| `DTO/`            | Conteneurs de transfert Controller ↔ Service. Deux variantes : immutables (factory `createFromArray`) ou avec `OptionsResolver` pour la validation.                                                   | Peut dépendre de : DTO, ResponseObject, HttpFoundation, OptionsResolver                       |
| `AlbumFilters/`   | `FromRequest` parse les query params en `AlbumFilterBag` (value object). `AlbumFilterBag::toApiParams()` mappe les clés courtes vers les clés API longues ; `toRouteParams()` retourne le tableau mixte pour Twig/redirectToRoute. | Dépend uniquement de HttpFoundation                                                           |
| `Security/`       | `User` (stocke `AccessToken` + rôles). Authenticateurs OAuth2 (Discord, Google, Fake). `UserTokenService` : `getLoggedUserId()` = `sha1(userIdentifier)`. `UserRefresher` rafraîchit le token expiré. | Peut dépendre de : DTO, Service, KnpU, LeagueOAuth2, SymfonySecurity                          |
| `Twig/`           | Extensions Twig : filtres `ksort`, `sha1`, `htmlNl2br`, `almost_exactly` ; fonctions `version()`, `getArrayFromRequest()`.                                                                            | Dépend de Twig, HttpFoundation, SymfonyContractsTranslation                                   |
| `Utils/`          | `JsonDecoder` : wrapper `json_decode` avec `JSON_THROW_ON_ERROR` et profondeur 5.                                                                                                                     | Aucune dépendance App                                                                         |
| `Validator/`      | `CatchStates` (contrainte) + `CatchStatesValidator` (interroge `GetLabelsService` pour valider les slugs).                                                                                            | Peut dépendre de : Service, Validator                                                         |

---

## Flux typique : consultation d'un album Pokédex

```
GET /{locale}/album/{dexSlug}?t={trainerId}&cs=caught&fc[]=mega
        │
        ▼
AlbumIndexController::index()
        │
        ├── AlbumFilters\FromRequest::get($request)       → $filterBag (AlbumFilterBag)
        ├── $filterBag->toApiParams()                    → $apiFilters (clés longues API)
        │
        ├── GetTrainerPokedexService::getPokedexData()  → ?Album
        │       └── Service\Back\GetPokedexService::get() ou getWithTrainerId()
        │               └── AbstractBackService::requestContent('GET', '/album/{slug}', …)
        │                       └── Symfony HttpClient → moco.back (test) ou pokenini-api (prod)
        │                       └── Symfony Serializer → Album { Pokedex { Dex, Pokemon[], Report } }
        │
        ├── GetLabelsService::getCatchStates/Types/Forms/…  (même pattern)
        │
        ├── UserTokenService::getLoggedUserId()         → ?string (sha1)
        │
        └── render('Album/index.html.twig', […])        → Response
```

## Flux typique : modification de catch state (AJAX)

```
POST /{locale}/album/{dexSlug}/update (JSON body: pokemon_slug + catch_state)
        │
        ▼
AlbumUpsertController::update()
  │
  ├── Symfony Validator (contrainte CatchStates)
  │       └── CatchStatesValidator → GetLabelsService → Service\Back\GetLabelsService
  │
  ├── ModifyTrainerAlbumService::update()
  │       └── Service\Back\ModifyAlbumService::modify('PUT', '/album/{dex}/pokemon/{slug}')
  │
  └── JsonResponse(200) ou JsonResponse(4xx)
```

## Flux d'authentification OAuth2

```
Browser → /{locale}/connect/discord → DiscordController::goto → OAuth2 redirect
  ← OAuth2 callback → /connect/discord/c → DiscordAuthenticator::authenticate
    └── fetchAccessToken(client)
    └── loadFromAccessToken (AuthenticatorTrait)
      └── GetUserInfoService::get($accessToken, 'discord') → /user → UserInfo
      └── new User($id, 'discord', $accessToken)
      └── addRoles selon UserInfo::getRoles()
    └── onAuthenticationSuccess → redirect home ou outerroom
```

---

## Points d'entrée

| URL                                                   | Contrôleur                  | Accès                             |
| ----------------------------------------------------- | --------------------------- | --------------------------------- |
| `/{locale}/`                                          | `HomeController`            | PUBLIC                            |
| `/{locale}/album/dex`                                 | `AlbumDexListController`    | ROLE_TRAINER                      |
| `/{locale}/album/{dexSlug}`                           | `AlbumIndexController`      | PUBLIC (avec conditions internes) |
| `/{locale}/album/{dexSlug}/{pokemonSlug}` (PATCH/PUT) | `AlbumUpsertController`     | ROLE_TRAINER                      |
| `/{locale}/trainer`                                   | `TrainerIndexController`    | ROLE_TRAINER                      |
| `/{locale}/trainer/dex/{dexSlug}` (PUT)               | `TrainerUpsertController`   | ROLE_TRAINER                      |
| `/{locale}/election/{dexSlug}/{electionSlug}` (GET)   | `ElectionIndexController`   | ROLE_TRAINER                      |
| `/{locale}/election/{dexSlug}/{electionSlug}` (POST)  | `ElectionVoteController`    | ROLE_TRAINER                      |
| `/{locale}/election/dex`                              | `ElectionDexListController` | ROLE_TRAINER                      |
| `/{locale}/istration`                                 | `AdminController`           | ROLE_ADMIN                        |
| `/{locale}/istration/action/*`                        | `AdminActionController`     | ROLE_ADMIN                        |
| `/{locale}/connect/discord`                           | `DiscordController`         | PUBLIC                            |
| `/{locale}/connect/google`                            | `GoogleController`          | PUBLIC                            |
| `/{locale}/connect/f/c?t={role}`                      | `FakeController`            | dev uniquement                    |
| `/{locale}/outerroom`                                 | `OuterRoomController`       | ROLE_USER                         |

---

## Dépendances entre modules (Deptrac)

| Couche            | Peut dépendre de                                                                                |
| ----------------- | ----------------------------------------------------------------------------------------------- |
| AppController     | AppAlbumFilters, AppDTO, AppException, AppResponseObject, AppSecurity, AppService, AppValidator |
| AppService        | AppDTO, AppException, AppResponseObject, AppSecurity, AppUtils, LeagueOAuth2, SymfonyContracts  |
| AppService\Back   | AppException, AppResponseObject, AppSecurity, AppUtils, LeagueOAuth2, SymfonySerializer         |
| AppResponseObject | SymfonySerializer uniquement                                                                    |
| AppDTO            | AppDTO, AppResponseObject, SymfonyOptionsResolver, SymfonyHttpFoundation                        |
| AppAlbumFilters   | SymfonyHttpFoundation uniquement                                                                |
| AppSecurity       | AppDTO, AppException, AppService, KnpUOAuth2, LeagueOAuth2, SymfonySecurity                     |
| AppTwig           | SymfonyTranslation, SymfonyHttpFoundation, Twig                                                 |
| AppValidator      | AppService, AppValidator, SymfonyValidator                                                      |
| AppUtils          | aucune dépendance App                                                                           |

---

## Infrastructure Docker

| Service Docker    | Image                                               | Version | Rôle                                        |
| ----------------- | --------------------------------------------------- | ------- | ------------------------------------------- |
| `php`             | `.docker/php/Dockerfile` (php:8.5.5-fpm-alpine3.23) | 8.5.5   | PHP-FPM + Xdebug + Chromedriver (dev)       |
| `web`             | `nginx:1.28-alpine`                                 | 1.28    | Reverse proxy HTTP/443 → PHP-FPM            |
| `redis`           | `redis:8.0-alpine`                                  | 8.0     | Cache session/app                           |
| `moco.back`       | `.docker/moco/Dockerfile` (Moco 1.5.0)              | 1.5.0   | Mock HTTP pour pokenini-api                 |
| `moco.matomo.gbl` | idem                                                | 1.5.0   | Mock HTTP pour Matomo analytics (port 8888) |
| `vnu`             | `ghcr.io/validator/validator:latest`                | latest  | Validateur W3C HTML                         |

## Environnements

| Env       | Auth                                       | API Backend                        | Cache       | Notes                                   |
| --------- | ------------------------------------------ | ---------------------------------- | ----------- | --------------------------------------- |
| `dev`     | Fake authenticator (`/connect/f/c?t=role`) | `http://moco.back` (Moco)          | Redis local | `APP_ENV=dev`, Xdebug activable         |
| `test`    | `GetUserToken::getFakeUserToken()`         | `http://moco.back` (Moco)          | Redis local | Groupe `api-mocked-testing` obligatoire |
| `panther` | idem test                                  | idem                               | idem        | Chrome headless via Panther             |
| `prod`    | Discord/Google OAuth2                      | pokenini-api réel (HTTPS + cafile) | Redis       | `APP_ENV=prod`, Xdebug off              |

## Gestion des outils qualité

Chaque outil de qualité a son propre `composer.json` et `vendor/` isolé sous `tools/<nom>/`. Ils ne polluent pas les dépendances du projet principal. Tous les outils s'exécutent via le container `php` via `make`.
