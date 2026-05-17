# Améliorations — Pokénini Web

## Performance

### 1. ✅ Cache des labels au niveau APCu/Redis

**Problème** : `GetLabelsService` maintient un cache en propriété d'objet (`?Labels $labels = null`). Ce cache est perdu à chaque requête HTTP. Les labels (catch states, types, formes…) sont des données quasi-statiques qui ne changent que sur action admin explicite. Plusieurs appels HTTP backend sont donc effectués à chaque requête.

**Correction** : utiliser le cache Symfony (APCu ou Redis déjà en place) avec un tag invalidable. L'admin dispose déjà d'une action `/istration/action/invalidate/labels` — le cache tagué pourrait être invalidé automatiquement à ce moment.

**Fichiers** : `src/Service/GetLabelsService.php`, `src/Service/Back/GetLabelsService.php`, `config/packages/cache.yaml`

**Traité** : `TagAwareCacheInterface` avec pool `cache.labels`, invalidation via `LabelsCacheInvalidationListener` sur l'événement `AdminActionSucceededEvent` (commit `720e2f1`)

---

### 2. ✅ Logs verbeux en production (réponses HTTP complètes)

**Problème** : `AbstractBackService::request` log systématiquement le contenu complet de chaque réponse HTTP (`$response->getContent()`) à chaque appel. Pour des payloads de centaines de Pokémons, cela représente des mégaoctets de logs par requête.

**Correction** : limiter le log de la réponse à un extrait (256 premiers caractères), ou le conditionner au niveau `DEBUG` uniquement via `$this->logger->debug(...)`.

**Fichier** : `src/Service/Back/AbstractBackService.php:65-80`

**Traité** : `info` log tronqué à 256 chars, contenu complet déplacé en `debug` (commit `509febe`)

---

## Qualité du code

### 3. ✅ Incohérence de paradigme dans les DTO

**Problème** : les DTO utilisent deux patterns incompatibles :
- `ElectionIndexData` : promotionnel readonly, propriétés publiques — accès direct.
- `ElectionMetrics` / `ElectionVote` : propriétés publiques mutables, pas de `readonly`.
- `UserInfo` / `DexFilters` : readonly via factory `createFromArray`.

Ce manque d'uniformité rend le codebase moins prévisible.

**Correction** : adopter un pattern unique — le constructor readonly promotionnel (comme `ElectionIndexData` et `UserInfo`). Éliminer les propriétés mutables publiques non nécessaires.

**Fichiers** : `src/DTO/ElectionMetrics.php`, `src/DTO/ElectionVote.php`

**Traité** : constructeur privé + `public readonly` promotionnel + factory `createFromArray` (pattern `DexFilters`). Tous les sites d'appel mis à jour. PHPStan niveau 9 + 277 tests unitaires verts.

---

### 4. ✅ `BackServiceInterface` est vide

**Problème** : `BackServiceInterface` est une interface vide utilisée uniquement comme marqueur de type. Elle n'apporte aucun contrat.

**Correction** : soit la supprimer (Deptrac peut se baser sur le namespace), soit y déclarer un contrat minimal (par exemple la méthode `request` de `AbstractBackService` pour permettre le mock par interface plutôt que par classe concrète).

**Fichiers** : `src/Service/Back/BackServiceInterface.php`, `src/Service/Back/AbstractBackService.php`

**Traité** : interface supprimée. `AbstractBackService` et les services concrets (`AdminActionService`, `GetUserInfoService`) n'implémentent plus l'interface. Les types de retour dans les tests Back sont mis à jour en `AbstractBackService`. 283 tests unitaires verts, PHPStan niveau 9, Deptrac 0 violations.

---

### 5. ✅ `ElectionVoteService` sans valeur ajoutée

**Problème** : `ElectionVoteService::vote` délègue directement à `PostElectionVoteService::vote` sans aucun traitement supplémentaire. Contrairement aux autres services métier, il ne gère pas les exceptions HTTP — une erreur réseau en production remonte en 500 non géré.

**Correction** : ajouter la gestion `HttpExceptionInterface|TransportExceptionInterface` ou supprimer la couche et accéder directement depuis le controller.

**Fichiers** : `src/Service/ElectionVoteService.php`, `src/Controller/ElectionVoteController.php`

**Traité** : `ElectionVoteService::vote` attrape `HttpExceptionInterface|TransportExceptionInterface` et lance `ModifyFailedException`. Le contrôleur catch `ModifyFailedException` et redirige normalement (échec silencieux). 280 tests unitaires verts, PHPStan niveau 9 sans erreur.

---

### 6. ✅ Gestion d'erreur trop large dans `AdminActionController`

**Problème** : `catch (\Exception $e)` attrape n'importe quelle exception, y compris les `TypeError` et `LogicException`, masquant des bugs de programmation derrière un message d'échec silencieux.

**Correction** : remplacer par `catch (HttpExceptionInterface|TransportExceptionInterface|\RuntimeException $e)` et laisser les exceptions de programmation remonter.

**Fichier** : `src/Controller/AdminActionController.php:89`

**Traité** : `catch (\Exception $e)` remplacé par `catch (HttpExceptionInterface|TransportExceptionInterface $e)`. Les bugs de programmation (`\LogicException`, etc.) propagent désormais correctement. Test ajouté pour vérifier que `\LogicException` n'est plus avalée. 282 tests unitaires verts, PHPStan niveau 9 sans erreur.

---

### 7. ✅ `AlbumIndexController::index()` trop long (forte densité logique)

**Problème** : la méthode `index()` fait 9 appels de service consécutifs pour les labels (catch states, types, 4 types de forms, game bundles, collections). C'est de la duplication d'orchestration que le contrôleur ne devrait pas contenir.

**Correction** : extraire ces 9 appels dans un objet `AlbumLabelBag` ou une méthode dédiée dans `GetLabelsService` (`getAllLabels(): LabelBag`) pour simplifier le contrôleur et faciliter la réutilisation.

**Fichier** : `src/Controller/AlbumIndexController.php:55-71`

**Traité** : ajout de `getAllLabels(): Labels` dans `GetLabelsService`. `AlbumIndexController::index()` et `ElectionIndexController::index()` font désormais 1 seul appel à `getAllLabels()`. 283 tests unitaires verts, PHPStan niveau 9 sans erreur.

---

## Sécurité

### 8. Tokens OAuth exposés dans les logs

**Problème** : `AbstractBackService::request` log `$finalOptions` qui contient le header `Authorization: Bearer <access_token_complet>`. Si les logs sont exportés vers un agrégateur externe, les tokens OAuth des utilisateurs sont exposés.

**Correction** :
```php
$loggableOptions = $finalOptions;
$loggableOptions['headers']['Authorization'] = 'Bearer [REDACTED]';
$this->logger->info("Requesting {$method} {$endpointUrl}", $loggableOptions);
```

**Fichier** : `src/Service/Back/AbstractBackService.php:65-68`

---

### 9. Absence de protection CSRF sur les actions admin (GET)

**Problème** : les actions admin (update, calculate, invalidate) sont déclenchées par des requêtes GET. Un lien malveillant dans un e-mail suffit à déclencher une action si l'admin est connecté.

**Correction** : passer ces actions en POST avec un token CSRF Symfony (`CsrfTokenManagerInterface`), ou a minima ajouter une confirmation JavaScript.

**Fichier** : `src/Controller/AdminActionController.php:26-79`

---

### 10. Credentials OAuth de développement dans `.env` et `.env.dev` versionnés

**Problème** : les fichiers `.env` et `.env.dev` contiennent des `OAUTH_DISCORD_CLIENT_ID`, `OAUTH_DISCORD_CLIENT_SECRET`, `OAUTH_GOOGLE_CLIENT_ID`, `OAUTH_GOOGLE_CLIENT_SECRET`. Même des valeurs de dev committées dans le dépôt créent un risque si le dépôt devient public.

**Correction** : déplacer ces valeurs dans `.env.dev.local` (non versionné) et ne conserver dans `.env.dev` que des placeholders vides ou des valeurs fictives marquées `CHANGEME`.

**Fichiers** : `.env`, `.env.dev`

---

### 11. Mot de passe Redis hardcodé dans docker-compose

**Problème** : `redis-server --requirepass douze` dans `docker-compose.yaml` expose un mot de passe en clair dans un fichier versionné. Ce pattern peut se retrouver accidentellement en staging/prod.

**Correction** : utiliser une variable d'environnement `${REDIS_PASSWORD:-douze}` et documenter que la valeur par défaut est uniquement pour le dev.

**Fichier** : `docker-compose.yaml:22`

---

## Tests

### 12. Absence de tests unitaires pour les Back Services

**Problème** : les `Service\Back\*` n'ont que des tests d'intégration (via Moco). Si Moco n'est pas disponible ou que le schéma JSON change, il n'y a pas de filet de sécurité rapide pour détecter les régressions de désérialisation.

**Correction** : ajouter des tests unitaires de désérialisation pour les ResponseObjects critiques (`Dex`, `Pokemon`, `Album`) en passant du JSON fixture directement au Serializer Symfony.

**Fichiers** : `tests/src/Unit/` (dossier absent pour `Service/Back/`)

---

### 13. `JsonDecoder::decode()` non testé pour les cas d'erreur

**Problème** : `JsonDecoder::decode()` utilise `JSON_THROW_ON_ERROR` mais aucun test ne vérifie que `InvalidJsonException` est bien lancée et remontée correctement depuis les Back Services.

**Correction** : ajouter un test unitaire qui passe du JSON invalide et vérifie que `JsonException` est bien propagée.

**Fichiers** : `src/Utils/JsonDecoder.php`, `src/Exception/InvalidJsonException.php`

---

### 14. `testListCachesCleared` utilise `exec` pour vider le cache

**Problème** : `exec('rm -Rf /app/var/cache/test/*')` dans un test est une dépendance sur le chemin absolu du container Docker, pas portable en dehors de l'environnement Docker.

**Correction** : utiliser `$this->getContainer()->get('cache.app')->clear()` ou une commande Symfony `cache:pool:clear` via le KernelBrowser en test.

**Fichier** : `tests/src/Integration/Controller/Album/Display/CommonTest.php:83`

---

### 15. `dependabot.yml` ne surveille pas les `tools/`

**Problème** : les sous-projets `tools/phpstan`, `tools/psalm`, `tools/infection`, etc. ont leurs propres `composer.json` avec des versions fixées manuellement. Dependabot ne les surveille pas.

**Correction** :
```yaml
-   package-ecosystem: "composer"
    directory: "/tools/phpstan"
    schedule:
        interval: "weekly"
```
Répéter pour chaque outil.

**Fichier** : `.github/dependabot.yml`

---

## Maintenabilité / DevX

### 16. Filtre `AlbumFilters\Mapping` : couplage implicite avec `FromRequest`

**Problème** : `Mapping::get()` accepte `string[]|string[][]` et fait une détection de type `is_array($value)` en interne. Si `FromRequest::get()` change son format de sortie, `Mapping` peut silencieusement produire des résultats incorrects sans erreur de type.

**Correction** : introduire un type dédié (ex. `AlbumFilterBag`) que `FromRequest` retourne et que `Mapping` attend, éliminant la détection de type runtime.

**Fichiers** : `src/AlbumFilters/Mapping.php:28-30`, `src/AlbumFilters/FromRequest.php`

---

### 17. `Twig\AppExtension::getVersion()` lit un fichier à chaque rendu

**Problème** : `getVersion()` appelle `file_get_contents()` à chaque invocation depuis Twig, sans cache. Sur des pages qui affichent la version plusieurs fois, cela génère plusieurs lectures disque.

**Correction** : mémoriser la valeur dans une propriété de classe (`private ?string $version = null`) et ne lire le fichier qu'une fois par cycle de vie du service.

**Fichier** : `src/Twig/AppExtension.php:48-59`

---

### 18. Deux configurations Psalm sans commentaire explicatif

**Problème** : le projet maintient `psalm.xml` et `psalm-src-only.xml` sans documentation sur leur différence. La commande `make psalm` les exécute tous deux.

**Correction** : ajouter un commentaire en tête de chaque fichier XML expliquant la différence (ex : taint analysis complète vs analyse de src sans tests).

**Fichiers** : `psalm.xml`, `psalm-src-only.xml`, `Makefile:243-245`

---

### 19. Version Moco hardcodée dans `Makefile` et `docker-compose.yaml`

**Problème** : la version Moco `1.5.0` est dupliquée dans `docker-compose.yaml:8` (deux services) et dans `Makefile:13`. Si elle est mise à jour, il faut modifier les 3 occurrences.

**Correction** : extraire dans une variable `.env` ou en argument de build Docker :
```yaml
args:
    MOCO_VERSION: ${MOCO_VERSION:-1.5.0}
```

**Fichiers** : `docker-compose.yaml:8,14`, `Makefile:13`

---

### 20. Pas de Makefile target pour lancer un test de mutation sur un seul fichier

**Problème** : `make measures` lance Infection sur tout `src/`. Il n'existe pas de commande rapide pour cibler un seul fichier ou classe lors du développement, ce qui ralentit le cycle TDD/mutation.

**Correction** : ajouter une target `make infection-file f=src/...` qui passe `--filter=$f` à Infection.

**Fichier** : `Makefile`
