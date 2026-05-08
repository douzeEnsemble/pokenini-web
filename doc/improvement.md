# Améliorations et optimisations — Pokénini Web

## Performance

### Cache des labels : lazy in-request seulement

**Problème** : `GetLabelsService` (src/Service/GetLabelsService.php) effectue au maximum un appel HTTP par requête grâce à un cache in-memory (`private ?Labels $labels`). Mais si deux requêtes simultanées arrivent (ex. album + ajaxCall), l'API backend est sollicitée deux fois. Il n'y a pas de cache HTTP ou Redis côté frontend pour les labels.

**Comment corriger** : Intégrer le cache Symfony (`CacheInterface`) dans `GetLabelsService` avec une TTL raisonnable (ex. 5 min). Le composant `symfony/cache` est déjà disponible. Exemple :
```php
$labels = $this->cache->get('labels', fn() => $this->getService->get());
```
L'invalidation existe côté admin (`/istration/action/invalidate/labels`), il faudrait appeler `$cache->delete('labels')` dans `AdminActionService`.

---

## Qualité du code

### `Pokemon` : constructeur à 31 paramètres

**Problème** : `src/ResponseObject/Common/Pokemon.php` a 31 paramètres dans son constructeur. La suppression PHPMD `ExcessiveParameterList` masque le problème. Ce n'est pas un bug, mais la classe est lourde à lire et à tester.

**Comment corriger** : Regrouper les champs thématiques en Value Objects imbriqués (ex. `PokemonName { english, french, simplified }`, `PokemonForms { category, regional, special, variant }`, `PokemonTypes { primary, secondary }`). Cela réduirait le constructeur de `Pokemon` à ~8 paramètres et améliorerait la lisibilité des templates Twig.

### Services non `final` par exception Psalm

**Problème** : Les classes `src/Service/` ne sont pas `final` (exception Psalm `ClassMustBeFinal` supprimée pour ce répertoire). Aucune d'entre elles n'est étendue dans le code actuel.

**Comment corriger** : Passer les services en `final` un par un et retirer la suppression Psalm. Les services mockés dans les tests unitaires (`createMock()`) fonctionnent même sur des classes `final` en PHPUnit 10+.

---

## Tests

### Tests Browser (Panther) : couverture partielle

**Problème** : Les tests Browser dans `tests/src/Browser/` couvrent quelques scénarios (modal, offcanvas, select+label, screenshot mode, admin actions, election). Les scénarios d'erreur réseau et les cas limites UI (filtres combinés, pagination) ne semblent pas couverts.

**Comment améliorer** : Identifier les interactions JavaScript critiques (mise à jour du catch state, application de filtres multiples) et ajouter des tests Browser ciblés. Le groupe `api-mocked-testing` facilite l'exécution déterministe.

### Mutation testing : exclusions identifiées

**Problème** : `infection.json5` exclut les mutations sur `TotalRoundCountHelper::calculate` (RoundingFamily, CastFloat) et les lignes de log. Ces exclusions indiquent des zones peu testées en profondeur.

**Comment améliorer** : Ajouter des tests unitaires vérifiant les cas limites de `TotalRoundCountHelper::calculate` (valeur 0, valeur maximale, arrondi) pour lever l'exclusion.

---

## Sécurité

### Trainer ID exposé en URL : SHA1

**Situation** : Le trainer ID passé en `?t=` est `sha1($userIdentifier)`. SHA1 est déprécié pour la cryptographie mais suffisant pour un identifiant non-secret de type "token de partage". Cependant, si l'identifiant OAuth (email ou ID Discord) est court ou prévisible, la collision SHA1 devient une considération.

**Recommandation** : Pour une sécurité renforcée, utiliser `hash('sha256', $userIdentifier)` dans `UserTokenService::getLoggedUserId()`. Nécessite une migration des IDs existants côté backend.

### Certificat CA configuré par variable d'environnement

**Situation** : `$backCafilePath` est injecté depuis `BACK_CAFILE_PATH`. Si cette variable est vide ou pointe vers un fichier inexistant, les appels HTTPS vers le backend pourraient échouer silencieusement ou être désactivés.

**Recommandation** : Ajouter une validation au démarrage (ex. dans `AbstractBackService::__construct()`) qui vérifie que le fichier CA existe si la var est non-vide.

---

## Maintenabilité

### Moco fixtures : nettoyage manuel requis

**Situation** : Les fixtures Moco dans `tests/resources/moco/Back/responses/` doivent être nettoyées manuellement (`make clean-unused-files`, `make clean-moco-routes`). Des fichiers orphelins peuvent s'accumuler silencieusement.

**Recommandation** : Intégrer `make clean-unused-files` et `make clean-moco-routes` dans la CI (GitHub Actions) pour détecter les fixtures orphelines automatiquement.

### Traductions : deux locales, pas de fallback testé

**Situation** : `translations/messages+intl-icu.en.yaml` et `messages+intl-icu.fr.yaml` existent. Aucun test visible ne valide l'exhaustivité des clés entre les deux locales.

**Recommandation** : Ajouter un test qui compare les clés de traduction entre `en` et `fr` pour détecter les clés manquantes.

---

## DevX

### Makefile : pas de cible `test-file`

**Situation** : Pour lancer un test individuel, il faut taper la commande `docker compose exec php` complète.

**Recommandation** : Ajouter une cible :
```makefile
tf: ## Run a single test file, example: make tf f=tests/src/Unit/...
    $(PHPUNIT) $(f)
```

### CI GitHub Actions : vérifier la cohérence avec `make quality`

**Situation** : Les workflows dans `.github/workflows/` exécutent probablement un sous-ensemble de `make quality`. Tout nouvel outil ajouté au Makefile doit être ajouté explicitement à la CI.

**Recommandation** : Documenter dans le README ou le CLAUDE.md les étapes CI et leur correspondance avec les cibles Makefile.
