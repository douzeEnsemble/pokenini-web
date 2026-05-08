# Architecture — Pokénini Web

## Vue d'ensemble

Application web Symfony 8.0 (PHP 8.4) qui sert de **frontend** pour un tracker de Pokédex (living dex, shiny, formes alternatives, élections). Elle n'a **pas de base de données** propre : toutes les données proviennent d'un backend API séparé (`pokenini-api`) consommé via HTTP. Le rendu est côté serveur (Twig). L'authentification est OAuth2 (Discord ou Google) avec des rôles gérés par l'API backend.

## Infrastructure Docker

| Conteneur | Image | Rôle |
|---|---|---|
| `php` | Build local (php_dev) | PHP 8.4-FPM, exécute l'application |
| `web` | nginx:1.28-alpine | Reverse proxy, port 80/443 |
| `redis` | redis:8.0-alpine | Cache APCu-compatible (password: `douze`) |
| `moco.back` | Build local (Moco 1.5.0) | Mock HTTP du backend API (tests) |
| `moco.matomo.gbl` | Build local (Moco 1.5.0) | Mock Matomo analytics, port 8888 |
| `vnu` | ghcr.io/validator/validator | Validateur HTML W3C |

## Carte des répertoires

```
pokenini-web/
├── src/                    Code source PHP (PSR-4: App\)
│   ├── AlbumFilters/       Parsing et mapping des filtres de query string
│   ├── Controller/         Controllers Symfony (un par page)
│   │   └── Connect/        Controllers OAuth2 (Discord, Google, Fake)
│   ├── DTO/                Objets de transfert Controller → Service
│   ├── Exception/          Exceptions métier (EmptyContent, InvalidJson, ModifyFailed, NoLoggedUser)
│   ├── ResponseObject/     Objets deserialisés depuis les réponses JSON de l'API
│   │   ├── Album/          Dex, Album, Pokedex, Report, ReportDetail
│   │   ├── Common/         Pokemon (partagé album/election)
│   │   ├── Election/       ElectionIndex, ElectionList, TopPokemon
│   │   └── Label/          CatchState, Type, CategoryForm, RegionalForm, SpecialForm, VariantForm, GameBundle, Collection
│   ├── Security/           User, authenticators OAuth2, UserProvider, UserTokenService
│   ├── Service/            Services d'orchestration (ne font pas de HTTP directement)
│   │   └── Back/           Services HTTP vers le backend (tous héritent AbstractBackService)
│   ├── Twig/               Extensions Twig (ksort, sha1, htmlNl2br, version())
│   ├── Utils/              Utilitaires purs (JsonDecoder)
│   └── Validator/          Contrainte CatchStates (valide via l'API)
│
├── config/
│   ├── packages/           Config Symfony (security.yaml, cache.yaml, routing.yaml, etc.)
│   ├── routes.yaml         Routes globales + préfixe /{_locale}
│   └── services.yaml       Bind: $backUrl, $backCafilePath, $demoUserId
│
├── templates/              Twig, miroir de la structure Controller
│   ├── base.html.twig      Layout racine
│   ├── _nav.html.twig      Barre de navigation
│   ├── _footer.html.twig   Pied de page
│   ├── Album/              Album (index, report, offcanvas, toasts, macros)
│   ├── AlbumDexList/       Liste des dex disponibles
│   ├── Election/           Élection (index, dex, candidats, top, filtres)
│   ├── Admin/              Administration (actions, rapports)
│   ├── Trainer/            Profil trainer
│   └── Connect/            Pages OAuth
│
├── tests/
│   ├── src/
│   │   ├── Unit/           Tests unitaires (mocks PHPUnit, pas de conteneur)
│   │   ├── Integration/    Tests WebTestCase (client HTTP + Moco)
│   │   │   └── Controller/ Organisé par feature puis par aspect (Access, Display, Template…)
│   │   ├── Browser/        Tests Panther (vrai navigateur)
│   │   └── Common/Traits/  Traits d'assertions réutilisables
│   ├── Utils/              GetUserToken (helper pour créer des User de test)
│   └── resources/moco/     Fixtures JSON pour Moco
│       ├── Back/           Réponses mockées du backend API
│       └── Matomo/         Réponses mockées de Matomo
│
├── tools/                  Outils qualité (chacun a son propre vendor/)
│   ├── php-cs-fixer/       Formatteur de code
│   ├── phpstan/            Analyse statique (niveau 9)
│   ├── psalm/              Analyse statique + taint (errorLevel 1)
│   ├── phpmd/              Métriques de qualité
│   ├── deptrac/            Contrôle des dépendances entre couches
│   ├── infection/          Mutation testing (100% MSI requis)
│   └── ...                 jsonlint, phpinsights, cachetool, w3c-validate…
│
├── public/                 Racine web (index.php, css/, js/, img/, lib/)
├── resources/metadata/     Fichier `version` lu par Twig version()
├── translations/           messages+intl-icu.fr.yaml / .en.yaml
└── .docker/                Dockerfiles + configs nginx, moco
```

## Rôle de chaque couche

### Controller
Point d'entrée HTTP. Route PHP attribute, méthodes thin. Orchestre : récupère les données via `Service`, vérifie les droits, rend le template Twig. Ne contacte jamais `Service\Back` directement.

### Service (src/Service/)
Couche d'orchestration. Capture les exceptions HTTP (`HttpExceptionInterface`, `TransportExceptionInterface`) et retourne `null` plutôt que de propager. Compose un ou plusieurs `Service\Back`. Peut construire des DTOs à partir des ResponseObjects. Exemple : `GetTrainerPokedexService` délègue à `GetPokedexService` selon la présence d'un `trainerId`.

### Service\Back (src/Service/Back/)
Toute communication HTTP avec `pokenini-api`. Héritent de `AbstractBackService` qui :
- Injecte l'header `Authorization: Bearer <token>` depuis la session courante
- Injecte `X-Provider: <oauth_provider>`
- Logue chaque requête et réponse
- Utilise le certificat CA configuré (`$backCafilePath`)

### ResponseObject (src/ResponseObject/)
Plain PHP objects, `final`, constructeur uniquement, que des getters. Peuplés par `Symfony\Component\Serializer` (désérialisation JSON → objet). Mapping field↔JSON via `#[SerializedName]`. Aucune logique métier.

### DTO (src/DTO/)
Conteneurs de données passées entre Controller et Service. Parfois construits avec factory statique + OptionsResolver (`DexFilters`). Différents des ResponseObjects : ils représentent les besoins de l'application, pas la forme de l'API.

### AlbumFilters (src/AlbumFilters/)
Deux classes statiques :
- `FromRequest::get(Request)` → extrait les query params filtrants (`cs`, `f`, `fc`, `fr`, `fs`, `fv`, `at`, `t1`, `t2`, `ogb`, `gba`, `gbsa`, `ca`)
- `Mapping::get(array)` → transforme en format attendu par l'API backend

### Security (src/Security/)
- `User` : implémente `UserInterface`, porte les rôles et l'`AccessToken` OAuth2
- `UserTokenService` : expose `getLoggedUserId()` = `sha1($user->getUserIdentifier())` — c'est l'identifiant trainer utilisé dans les URLs comme `?t=<sha1>`
- `AuthenticatorTrait` : comportement commun après succès/échec auth (redirect vers home si trainer, OuterRoom sinon)
- `FakeAuthenticator` : disponible uniquement en `dev`, activé par `make bash` + URL `/fr/connect/f/c?t=admin|collector|trainer`

### Twig (src/Twig/)
- `AppExtension` : filtres `ksort`, `sha1`, `htmlNl2br` + fonction `version()`
- `AppRequestExtension` : accès à la request courante depuis Twig
- `AppTranslatorExtension` : helpers de traduction custom

## Flux typique d'une requête album

```
GET /fr/album/demolite?t=7b52009b...&cs=no
         ↓
Nginx → PHP-FPM
         ↓
AlbumIndexController::index()
  ├── FromRequest::get($request)          → ['cs' => 'no']
  ├── Mapping::get($filters)              → ['catch_state' => 'no']
  ├── GetTrainerPokedexService::getPokedexData('demolite', apiFilters, trainerId)
  │     └── GetPokedexService::getWithTrainerId(trainerId, 'demolite', filters)
  │           └── AbstractBackService::requestContent('GET', '/album/{trainerId}/demolite?...')
  │                 → HTTP vers pokenini-api (moco.back en test)
  │                 → JSON → Serializer → Album (ResponseObject)
  ├── GetLabelsService::getCatchStates/getTypes/...  (lazy, 1 appel HTTP max par request)
  │     └── GetLabelsService (Back)
  └── $this->render('Album/index.html.twig', [...])
```

## Points d'entrée

| Point | Fichier |
|---|---|
| Bootstrap Symfony | `src/Kernel.php` |
| Index web | `public/index.php` |
| Routes | `config/routes.yaml` (préfixe `/{_locale}`, en/fr) |
| Services bind | `config/services.yaml` |

## Dépendances entre modules (Deptrac)

```
Controller → Service → Service\Back → (HTTP)
Controller → DTO
Controller → ResponseObject (lecture)
Controller → Security (UserTokenService)
Controller → AlbumFilters
Controller → Validator (pour les PUT/POST)

Service\Back → ResponseObject (désérialisation)
Service\Back → Security (UserTokenService pour le token Bearer)
Service\Back → Utils (JsonDecoder)

Validator → Service (pour valider via l'API)
Twig → (aucune dépendance App)
Exception → (aucune dépendance App)
```

## Routing et localisation

- Toutes les routes ont le préfixe `/{_locale}` avec `requirements: _locale: "en|fr"`
- `/` redirige vers `app_home_index`
- Routes déclarées par attributs PHP sur les controllers
- Sous-routes Connect : `#[Route('/connect/google')]` → `#[Route('/connect/discord')]` etc.

## Contrôle d'accès (security.yaml)

| Chemin | Rôle requis |
|---|---|
| `/album/dex` | `ROLE_TRAINER` |
| `/album/*` | `PUBLIC_ACCESS` |
| `/istration/*` | `ROLE_ADMIN` |
| `/trainer/*` | `ROLE_TRAINER` |
| `/election/*` | `ROLE_TRAINER` |
| `/outerroom` | `ROLE_USER` |
