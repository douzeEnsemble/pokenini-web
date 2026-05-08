# Architecture — pokenini-web

## Vue d'ensemble

Pokénini Web est un **frontend Symfony 8.0 stateless** (aucune base de données) qui sert d'interface utilisateur pour un tracker de Pokédex étendu. Toute la donnée métier est stockée et calculée dans `pokenini-api` (service externe). Ce projet ne fait que présenter et relayer les données via des templates Twig.

Périmètre fonctionnel :
- Consultation et mise à jour de l'album Pokédex d'un dresseur
- Système d'élection préférentielle de Pokémon (vote ELO)
- Gestion du profil dresseur (liste des dex, activation)
- Actions administratives (mise à jour des données, invalidation de cache)
- Authentification OAuth2 multi-fournisseur (Google, Discord)
- Interface multilingue (fr/en) via `/{_locale}` prefix

---

## Carte des répertoires

```
pokenini-web/
├── src/
│   ├── AlbumFilters/          # Parsing et mapping des query params de filtres album
│   │   ├── FromRequest        # Parse query params → tableau court (clés abrégées)
│   │   └── Mapping            # Tableau court → clés longues API
│   ├── Controller/            # Contrôleurs Symfony (un par page/fonctionnalité)
│   │   └── Connect/           # Contrôleurs OAuth2 (Discord, Google, Fake)
│   ├── DTO/                   # Objets de transfert Controller ↔ Service
│   ├── Exception/             # Exceptions métier (NoLoggedUser, ModifyFailed…)
│   ├── ResponseObject/        # Désérialisation JSON de l'API backend
│   │   ├── Album/             # Album, Pokedex, Dex, Report, ReportDetail
│   │   ├── Common/            # Pokemon
│   │   ├── Election/          # ElectionIndex, ElectionList, TopPokemon
│   │   └── Label/             # Labels, CatchState, Type, CategoryForm, GameBundle…
│   ├── Security/              # Authentificateurs OAuth2, User, UserProvider, UserRefresher
│   ├── Service/               # Couche métier orchestrant les appels Back
│   │   └── Back/              # Clients HTTP vers pokenini-api (AbstractBackService)
│   ├── Twig/                  # Extensions Twig (AppExtension, AppRequestExtension, AppTranslatorExtension)
│   ├── Utils/                 # Utilitaires (JsonDecoder)
│   └── Validator/             # Contraintes Symfony (CatchStates)
├── config/
│   ├── packages/              # Configuration Symfony (security, cache, framework, twig…)
│   └── routes.yaml            # Routage global (préfixe /{_locale})
├── templates/                 # Templates Twig
├── tests/
│   ├── src/
│   │   ├── Unit/              # Tests unitaires (PHPUnit + mocks)
│   │   ├── Integration/       # Tests d'intégration (WebTestCase + Moco)
│   │   └── Browser/           # Tests navigateur (Panther/Chrome)
│   ├── Utils/                 # GetUserToken, WithConsecutive
│   └── resources/
│       ├── moco/Back/         # Fixtures JSON pour le mock HTTP Moco (backend API)
│       └── moco/Matomo/       # Fixtures pour Matomo
├── tools/                     # Outils qualité isolés (chacun avec son vendor/)
│   ├── deptrac/
│   ├── infection/
│   ├── jsonlint/
│   ├── php-cs-fixer/
│   ├── phpinsights/
│   ├── phpmd/
│   ├── phpstan/
│   └── psalm/
├── docker-compose.yaml        # Orchestration Docker (php, web, redis, moco.back, moco.matomo, vnu)
└── Makefile                   # Interface unique pour toutes les commandes
```

---

## Rôle de chaque couche

| Couche | Rôle | Règle Deptrac |
|--------|------|---------------|
| `Controller/` | Reçoit la requête HTTP, orchestre Service et AlbumFilters, rend le template Twig. Aucune logique métier. | Peut dépendre de : Service, AlbumFilters, DTO, Security, Validator, Exception |
| `AlbumFilters/` | Deux classes statiques : `FromRequest` (parse query params → tableau court) et `Mapping` (tableau court → clés longues API). | Dépend uniquement de HttpFoundation |
| `Service/` | Orchestration : appelle les services `Back`, gère les exceptions HTTP (retourne `null` ou relance `ModifyFailedException`), compose les DTO de sortie. | Peut dépendre de : Back, DTO, ResponseObject, Security, Utils |
| `Service\Back/` | Clients HTTP vers `pokenini-api`. Héritent de `AbstractBackService` qui gère : en-têtes `Authorization: Bearer`, `X-Provider`, `cafile`, logging. Désérialisent via Symfony Serializer. | Pas de dépendance vers Controller |
| `ResponseObject/` | POPOs désérialisés depuis le JSON de l'API. Constructeur promotionnel readonly + `#[SerializedName]`. Aucune logique. | Dépend de SymfonySerializer uniquement |
| `DTO/` | Objets de transfert internes. Deux variantes : immutables (factory `createFromArray`) ou mutables via `OptionsResolver`. | Peut dépendre de : DTO, ResponseObject, HttpFoundation, OptionsResolver |
| `Security/` | `User` (implémente `UserInterface`, stocke `AccessToken` + rôles). `AbstractAuthenticator` + `AuthenticatorTrait` (OAuth2). `UserTokenService` expose `getLoggedUserId()` = `sha1(userIdentifier)`. `UserRefresher` rafraîchit le token expiré. | Peut dépendre de : DTO, Service, LeagueOAuth2, KnpU |
| `Twig/` | Extensions Twig : filtres `ksort`, `sha1`, `htmlNl2br`, `almost_exactly` ; fonctions `version()`, `getArrayFromRequest()`. | Dépend de Twig, HttpFoundation, SymfonyContractsTranslation |
| `Validator/` | `CatchStates` (contrainte) + `CatchStatesValidator` (validateur qui interroge `GetLabelsService` pour la liste des slugs valides). | Peut dépendre de : Service, Validator |
| `Utils/` | `JsonDecoder` : wrapper `json_decode` avec `JSON_THROW_ON_ERROR` et profondeur 5. | Aucune dépendance App |

---

## Flux typique : lecture d'un album

```
Browser → Nginx (port 80) → PHP-FPM (php container)
  → Symfony router : /{_locale}/album/{dexSlug} → AlbumIndexController::index
  → AlbumIndexController::index :
      a. FromRequest::get($request)          → $filters (clés courtes)
      b. Mapping::get($filters)              → $apiFilters (clés longues)
      c. GetTrainerPokedexService::getPokedexData($dexSlug, $apiFilters, $trainerId)
         └── GetPokedexService::get() ou getWithTrainerId()
             └── AbstractBackService::requestContent('GET', '/album/{dexSlug}', ['query' => $filters])
                 └── Symfony HttpClient → moco.back (test) ou pokenini-api (prod)
                 └── Symfony Serializer::deserialize(json, Album::class, 'json')
                 └── → Album { Pokedex { Dex, Pokemon[], Report } }
      d. GetLabelsService::getCatchStates() / getTypes() / …
         └── [cache interne $labels] → GetLabelsBackService::get() → /labels
      e. UserTokenService::getLoggedUserId()  → sha1(userIdentifier) ou exception
      f. Vérifications accessDexIsGranted / editDexIsGranted
  → render('Album/index.html.twig', [...])
  → HTTP Response 200
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

| URL | Contrôleur | Accès |
|-----|-----------|-------|
| `/{locale}/` | `HomeController` | PUBLIC |
| `/{locale}/album/dex` | `AlbumDexListController` | ROLE_TRAINER |
| `/{locale}/album/{dexSlug}` | `AlbumIndexController` | PUBLIC (avec conditions internes) |
| `/{locale}/album/{dexSlug}/{pokemonSlug}` (PATCH/PUT) | `AlbumUpsertController` | ROLE_TRAINER |
| `/{locale}/trainer` | `TrainerIndexController` | ROLE_TRAINER |
| `/{locale}/trainer/dex/{dexSlug}` (PUT) | `TrainerUpsertController` | ROLE_TRAINER |
| `/{locale}/election/{dexSlug}/{electionSlug}` (GET) | `ElectionIndexController` | ROLE_TRAINER |
| `/{locale}/election/{dexSlug}/{electionSlug}` (POST) | `ElectionVoteController` | ROLE_TRAINER |
| `/{locale}/election/dex` | `ElectionDexListController` | ROLE_TRAINER |
| `/{locale}/istration` | `AdminController` | ROLE_ADMIN |
| `/{locale}/istration/action/*` | `AdminActionController` | ROLE_ADMIN |
| `/{locale}/connect/discord` | `DiscordController` | PUBLIC |
| `/{locale}/connect/google` | `GoogleController` | PUBLIC |
| `/{locale}/connect/f/c` (dev only) | `FakeController` | dev |
| `/{locale}/outerroom` | `OuterRoomController` | ROLE_USER |

---

## Infrastructure Docker

| Service Docker | Rôle |
|---------------|------|
| `php` | PHP 8.4 FPM, image custom `.docker/php/Dockerfile` |
| `web` | Nginx 1.28-alpine, port 80/443 → 8080 interne |
| `redis` | Redis 8.0-alpine, cache avec mot de passe |
| `moco.back` | Moco 1.5.0, mock HTTP de `pokenini-api` (port interne) |
| `moco.matomo.gbl` | Moco 1.5.0, mock Matomo analytics (port 8888 exposé) |
| `vnu` | W3C HTML validator |

## Gestion des outils qualité

Chaque outil de qualité a son propre `composer.json` et `vendor/` isolé sous `tools/<nom>/`. Ils ne polluent pas les dépendances du projet principal. Tous les outils s'exécutent via le container `php` via `make`.
