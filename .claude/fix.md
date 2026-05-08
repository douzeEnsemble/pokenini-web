# Correctifs — pokenini-web

## Haute priorité

- [ ] [haute] `phpunit.xml.dist` référence PHPUnit 11.3 mais le schéma XSD pointe vers 11.4
  Fichier : `phpunit.xml.dist:4`
  Suggestion : Aligner `xsi:noNamespaceSchemaLocation` sur `https://schema.phpunit.de/13.1/phpunit.xsd` correspondant à `phpunit/phpunit ^13.1.8` déclaré dans `composer.json:49`.

- [ ] [haute] `AlbumDexListController` a un constructeur vide explicite inutile
  Fichier : `src/Controller/AlbumDexListController.php:16`
  Suggestion : Supprimer `public function __construct() {}` — PHP l'hérite de `AbstractController` sans déclaration.

- [ ] [haute] `GetAlbumDexListService` construit l'URL avec query string à la main
  Fichier : `src/Service/Back/GetAlbumDexListService.php:17`
  Suggestion : Passer `['query' => ['trainer_id' => $trainerId]]` comme option HTTP au lieu de concaténer la query string manuellement — cohérent avec toutes les autres Back services (`GetPokedexService`, `GetElectionIndexService`).

- [ ] [haute] `AdminActionController::execute` attrape `\Exception` trop large
  Fichier : `src/Controller/AdminActionController.php:89`
  Suggestion : Restreindre le catch à `HttpExceptionInterface|TransportExceptionInterface|\InvalidArgumentException` pour ne pas silencier des erreurs de programmation.

- [ ] [haute] `ConnectController::logout` lance une `\Exception` générique
  Fichier : `src/Controller/ConnectController.php:16`
  Suggestion : Ce pattern "throw pour forcer la gestion Symfony" est valide mais utiliser `\LogicException` est plus idiomatique et documenté dans Symfony.

- [ ] [haute] `DexFiltersRequest::dexFiltersFromRequest` ignore les paramètres inconnus sans les rejeter
  Fichier : `src/DTO/DexFiltersRequest.php:14`
  Suggestion : Ajouter `$resolver->setDefined([...])` et `$resolver->setIgnoreUndefined()` ou valider que la query string ne contient pas de clés inattendues.

## Priorité moyenne

- [ ] [moyenne] `ElectionVoteService::vote` est une couche de passe-plat triviale sans valeur ajoutée
  Fichier : `src/Service/ElectionVoteService.php:16-18`
  Suggestion : Soit supprimer cette classe et appeler directement `PostElectionVoteService` depuis `ElectionVoteController`, soit lui ajouter une gestion d'exception `HttpExceptionInterface` pour être cohérent avec les autres services.

- [ ] [moyenne] `GetLabelsService` (métier) utilise un cache manuel `?Labels $labels = null`
  Fichier : `src/Service/GetLabelsService.php:20`
  Suggestion : Ce cache per-request est correct mais non documenté. Ajouter un commentaire. Envisager l'APCu/Redis pour partager entre requêtes (les labels ne changent que sur action admin).

- [ ] [moyenne] Absence de gestion de l'expiration de token dans `FakeAuthenticator`
  Fichier : `src/Security/FakeAuthenticator.php:43-47`
  Suggestion : Le `EXPIRES_IN = 3600` est codé en dur. Si le token fake expire, `UserRefresher` va tenter un refresh OAuth2 qui n'existe pas pour le Fake provider. Ajouter un `expires_in` très large ou traiter le cas dans `UserRefresher`.

- [ ] [moyenne] `ActionLog::createFromArray` utilise des accès de tableau non typés avec casts manuels
  Fichier : `src/DTO/ActionLog.php:27-40`
  Suggestion : Utiliser la désérialisation Symfony (comme `UserInfo`, `Album`…) plutôt que des `createFromArray` manuels avec casts — élimine les `@psalm-suppress RiskyCast` et les `/** @var */` forcés.

- [ ] [moyenne] `TrainerIndexController::filtersTrainerDex` compare avec `==` (pas `===`)
  Fichier : `src/Controller/TrainerIndexController.php:53,60,66,72,78`
  Suggestion : Les comparaisons `$filters->xxx->value == $item['is_private']` sont intentionnellement lâches (bool vs string JSON). Documenter l'intention ou normaliser les valeurs du tableau en booléens à la désérialisation.

- [ ] [moyenne] `dependabot.yml` ne couvre que le `composer` racine, pas les `tools/*/composer.json`
  Fichier : `.github/dependabot.yml`
  Suggestion : Ajouter des entrées pour chaque sous-répertoire `tools/` afin que Dependabot surveille aussi `phpstan`, `psalm`, `infection`, etc.

## Basse priorité

- [ ] [basse] `AbstractBackService::request` log les options de requête entières (contenu potentiellement sensible)
  Fichier : `src/Service/Back/AbstractBackService.php:64-68`
  Suggestion : Le `$finalOptions` logué contient le header `Authorization: Bearer <token>`. Exclure ou masquer les en-têtes sensibles avant logging.

- [ ] [basse] `AppExtension::getVersion` appelle `file_get_contents` directement sans injection
  Fichier : `src/Twig/AppExtension.php:53-63`
  Suggestion : Extraire le chemin du fichier dans un paramètre de service injecté (`string $resourcesPath`) pour faciliter les tests et éviter le chemin relatif à `__DIR__`.

- [ ] [basse] `ModifyAlbumService::modify` valide la méthode HTTP en interne mais l'erreur est silencieuse pour le client
  Fichier : `src/Service/Back/ModifyAlbumService.php:17-20`
  Suggestion : L'`\InvalidArgumentException` levée remonte jusqu'au handler d'erreur Symfony par défaut. Documenter ce comportement ou la capturer explicitement dans `ModifyTrainerAlbumService`.
