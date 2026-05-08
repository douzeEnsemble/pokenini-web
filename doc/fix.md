# Correctifs et suggestions structurelles — Pokénini Web

Aucun `TODO`, `FIXME`, `HACK` ou `@deprecated` trouvé dans le code source (recherche exhaustive sur `src/` et `tests/`). Les éléments ci-dessous sont des observations issues de l'analyse statique du code.

---

## Observations mineures

- [ ] [basse] Valeur de fallback magique dans `AppExtension::getVersion()`
  Fichier : `src/Twig/AppExtension.php:55`
  Observation : `$version = '0.0.toto';` est une chaîne hardcodée utilisée quand le fichier `resources/metadata/version` est absent.
  Suggestion : extraire la valeur par défaut en constante de classe ou en paramètre de service pour la rendre configurable sans modifier le code.

- [ ] [basse] Constructeur vide explicite inutile dans `AlbumDexListController`
  Fichier : `src/Controller/AlbumDexListController.php:17`
  Observation : `public function __construct() {}` sans corps ni dépendances — le constructeur implicite suffit.
  Suggestion : supprimer ce constructeur vide.

- [ ] [basse] Incohérence d'injection : `GetAlbumDexListService` injecté par méthode alors que d'autres controllers injectent leurs services par constructeur
  Fichier : `src/Controller/AlbumDexListController.php:25`
  Observation : `GetAlbumDexListService $service` est un paramètre de la méthode `index()` alors que le controller n'a aucune autre dépendance. Pas d'erreur, mais crée une légère incohérence avec les autres controllers.
  Suggestion : si la convention voulue est l'injection par constructeur pour les dépendances récurrentes, laisser tel quel. Si la convention est d'injecter par méthode quand c'est possible, appliquer à d'autres controllers.

- [ ] [basse] `catch (\Exception $e)` générique dans `AdminActionController`
  Fichier : `src/Controller/AdminActionController.php:89`
  Observation : le catch attrape toutes les exceptions sans discrimination, y compris des erreurs inattendues. Cela est partiellement justifié (on ne veut pas exposer une page d'erreur à l'admin) mais masque des problèmes de débogage.
  Suggestion : attraper explicitement `HttpExceptionInterface|TransportExceptionInterface` en premier lieu, et réserver `\Exception` en dernier recours, ou logger le stack trace complet avec `critical`.

---

## Observations structurelles

- [ ] [moyenne] Absence de répertoire `.claude/` dans le dépôt avant ce run
  Observation : le projet n'avait pas de documentation interne pour les outils d'IA ou les conventions de développement avancées (comblé par ce run).
  Suggestion : commiter les fichiers `.claude/` générés (codage.md, architecture.md, fix.md, improvement.md, update.md) pour que tous les développeurs en bénéficient.

- [ ] [basse] `psalm-src-only.xml` exécuté en doublon avec `psalm.xml`
  Fichier : `Makefile:244`
  Observation : Psalm est lancé deux fois (`psalm.xml` sur src+tests, `psalm-src-only.xml` sur src seul). Si les deux configurations ont les mêmes règles sur `src/`, l'une est redondante.
  Suggestion : vérifier si `psalm-src-only.xml` apporte des règles supplémentaires que `psalm.xml` ne peut appliquer (ex. taint analysis restreinte au src). Sinon, fusionner.

- [ ] [basse] Pas de fichier `.env.test` documenté — les variables d'environnement de test sont implicites
  Observation : `config/services.yaml` bind `$backUrl`, `$backCafilePath`, `$demoUserId` depuis `%env(...)%`. Les valeurs de test ne sont pas visibles sans inspecter les `.env.*` ou le container.
  Suggestion : s'assurer qu'un `.env.test` ou `.env.dev` expose des valeurs par défaut lisibles pour les nouveaux développeurs.
