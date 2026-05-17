# Correctifs — Pokénini Web

## Recherche préliminaire

```bash
grep -rn "TODO\|FIXME\|HACK\|XXX\|@deprecated\|@todo" src/ tests/ --include="*.php"
```

**Résultat : aucun marqueur TODO/FIXME/HACK/XXX/@deprecated trouvé dans src/ et tests/.**

---

## Haute priorité

- [x] [haute] `catch (\Exception $e)` trop large dans `AdminActionController`
    Fichier : `src/Controller/AdminActionController.php:89`
    Suggestion : remplacer par `catch (HttpExceptionInterface|TransportExceptionInterface|\InvalidArgumentException $e)` pour éviter de masquer des erreurs de programmation (TypeError, LogicException…). Actuellement, toute exception produit silencieusement un AdminAction d'échec sans remonter l'erreur.

- [x] [haute] `phpunit.xml.dist` référence PHPUnit 11.3 mais le schéma XSD pointe vers 11.4
    Fichier : `phpunit.xml.dist:4`
    Suggestion : aligner `xsi:noNamespaceSchemaLocation` sur `https://schema.phpunit.de/13.1/phpunit.xsd` correspondant à `phpunit/phpunit ^13.1.8` déclaré dans `composer.json:49`.
    **Traité** : XSD aligné sur `13.1` et `SYMFONY_PHPUNIT_VERSION` mis à jour de `11.3` → `13.1`.

- [x] [haute] `AlbumDexListController` a un constructeur vide explicite inutile
    Fichier : `src/Controller/AlbumDexListController.php:16`
    Suggestion : supprimer `public function __construct() {}` — PHP l'hérite de `AbstractController` sans déclaration.

- [x] [haute] `GetAlbumDexListService` construit l'URL avec query string à la main
    Fichier : `src/Service/Back/GetAlbumDexListService.php:17`
    Suggestion : passer `['query' => ['trainer_id' => $trainerId]]` comme option HTTP au lieu de concaténer la query string manuellement — cohérent avec toutes les autres Back services.

- [x] [haute] `ConnectController::logout` lance une `\Exception` générique
    Fichier : `src/Controller/ConnectController.php:16`
    Suggestion : utiliser `\LogicException` à la place, plus idiomatique et documenté dans Symfony pour forcer la gestion par le firewall.

- [ ] [haute] `DexFiltersRequest::dexFiltersFromRequest` ignore les paramètres inconnus sans les rejeter
    Fichier : `src/DTO/DexFiltersRequest.php:14`
    Suggestion : ajouter `$resolver->setDefined([...])` et `$resolver->setIgnoreUndefined()` ou valider que la query string ne contient pas de clés inattendues.

- [ ] [haute] Image Docker `vnu` utilise `latest` sans tag fixé
    Fichier : `docker-compose.yaml:30`
    Suggestion : fixer une version explicite (ex. `ghcr.io/validator/validator:24.x`) pour garantir la reproductibilité des builds.

---

## Priorité moyenne

- [x] [moyenne] `AbstractBackService::request()` logue le contenu complet de la réponse en `info`
    Fichier : `src/Service/Back/AbstractBackService.php:69`
    Suggestion : le log `'response' => $response->getContent()` peut produire des payloads très volumineux. Passer au niveau `debug` ou tronquer à 256 caractères pour éviter de saturer les logs en production.
    **Traité** : réponse tronquée à 256 chars en `info`, contenu complet en `debug` (commit `509febe`)

- [ ] [moyenne] `UserTokenService` n'est pas `final`
    Fichier : `src/Security/UserTokenService.php:9`
    Suggestion : ajouter `final` pour cohérence avec les conventions du projet (toutes les classes concrètes sont `final`). Pas d'héritage prévu.

- [ ] [moyenne] `ElectionVoteService::vote` est une couche de passe-plat triviale sans valeur ajoutée
    Fichier : `src/Service/ElectionVoteService.php:16-18`
    Suggestion : ajouter la gestion `HttpExceptionInterface|TransportExceptionInterface` pour être cohérent avec les autres services, ou supprimer la couche et appeler directement `PostElectionVoteService` depuis `ElectionVoteController`.

- [x] [moyenne] `GetLabelsService` (métier) utilise un cache manuel `?Labels $labels = null`
    Fichier : `src/Service/GetLabelsService.php:20`
    Suggestion : ce cache per-request est correct mais non documenté. Ajouter un commentaire. Envisager l'APCu/Redis pour partager entre requêtes (les labels ne changent que sur action admin).
    **Traité** : cache APCu/Redis avec `TagAwareCacheInterface`, invalidation automatique sur action admin (commit `720e2f1`)

- [ ] [moyenne] Absence de gestion de l'expiration de token dans `FakeAuthenticator`
    Fichier : `src/Security/FakeAuthenticator.php:43-47`
    Suggestion : le `EXPIRES_IN = 3600` est codé en dur. Si le token fake expire, `UserRefresher` va tenter un refresh OAuth2 qui n'existe pas pour le Fake provider. Ajouter un `expires_in` très large ou traiter le cas dans `UserRefresher`.

- [ ] [moyenne] `ActionLog::createFromArray` utilise des accès de tableau non typés avec casts manuels
    Fichier : `src/DTO/ActionLog.php:27-40`
    Suggestion : utiliser la désérialisation Symfony (comme `UserInfo`, `Album`…) plutôt que des `createFromArray` manuels avec casts — élimine les `@psalm-suppress RiskyCast` et les `/** @var */` forcés.

- [ ] [moyenne] `TrainerIndexController::filtersTrainerDex` compare avec `==` (pas `===`)
    Fichier : `src/Controller/TrainerIndexController.php:53,60,66,72,78`
    Suggestion : les comparaisons `$filters->xxx->value == $item['is_private']` sont intentionnellement lâches (bool vs string JSON). Documenter l'intention ou normaliser les valeurs du tableau en booléens à la désérialisation.

- [ ] [moyenne] `dependabot.yml` ne couvre que le `composer` racine, pas les `tools/*/composer.json`
    Fichier : `.github/dependabot.yml`
    Suggestion : ajouter des entrées pour chaque sous-répertoire `tools/` afin que Dependabot surveille aussi `phpstan`, `psalm`, `infection`, etc.

- [ ] [moyenne] Règle de routing `access_control` utilise `^/(en|fr)/istration`
    Fichier : `config/packages/security.yaml:20`
    Suggestion : le pattern `/istration` semble être une troncature volontaire de `/administration` pour obscurcir l'URL admin (sécurité par l'obscurité). Si intentionnel, ajouter un commentaire explicite.

- [ ] [moyenne] `AppExtension::getVersion()` utilise un chemin hardcodé `__DIR__.'/../../resources/metadata/'`
    Fichier : `src/Twig/AppExtension.php:54`
    Suggestion : passer le chemin de base du projet via injection de dépendance (paramètre `%kernel.project_dir%`) plutôt qu'un chemin relatif basé sur `__DIR__`.

- [ ] [moyenne] `GetUserToken` dans `tests/Utils/` utilise un token OAuth factice hardcodé
    Fichier : `tests/Utils/GetUserToken.php`
    Suggestion : vérifier que le token n'est pas un vrai token d'API accidentellement commité. Si c'est un faux token de test, ajouter un commentaire explicite.

---

## Basse priorité

- [ ] [basse] `AbstractBackService::request` log les options de requête entières (contenu potentiellement sensible)
    Fichier : `src/Service/Back/AbstractBackService.php:64-68`
    Suggestion : le `$finalOptions` logué contient le header `Authorization: Bearer <token>`. Exclure ou masquer les en-têtes sensibles avant logging.

- [ ] [basse] `ModifyAlbumService::modify` valide la méthode HTTP en interne mais l'erreur est silencieuse pour le client
    Fichier : `src/Service/Back/ModifyAlbumService.php:17-20`
    Suggestion : l'`\InvalidArgumentException` levée remonte jusqu'au handler d'erreur Symfony par défaut. Documenter ce comportement ou la capturer explicitement dans `ModifyTrainerAlbumService`.

- [ ] [basse] `infection_summary.log` et `infection_text.log` présents à la racine du dépôt
    Fichier : `infection_summary.log`, `infection_text.log`
    Suggestion : ajouter ces fichiers au `.gitignore` (artefacts de build).

- [ ] [basse] `build/coverage/` présent à la racine
    Fichier : `build/`
    Suggestion : vérifier que `build/` est bien dans `.gitignore`.

- [ ] [basse] `AlbumFilters\Mapping::get()` traite les valeurs `string` comme des tableaux à un élément
    Fichier : `src/AlbumFilters/Mapping.php:30`
    Suggestion : la logique `is_array($value) ? $value : [$value]` force toutes les valeurs à être des tableaux en sortie, même les scalaires. Documenter ce comportement intentionnel avec un commentaire ou normaliser l'entrée dès `FromRequest`.

- [ ] [basse] `JsonDecoder::decode()` fixe la profondeur à 5
    Fichier : `src/Utils/JsonDecoder.php:9`
    Suggestion : vérifier que la profondeur 5 couvre bien tous les payloads API actuels et futurs.
