# Améliorations — pokenini-web

## Performance

### 1. Cache des labels au niveau APCu/Redis
**Problème** : `GetLabelsService` maintient un cache en propriété d'objet (`?Labels $labels = null`). Ce cache est perdu à chaque requête HTTP. Les labels (catch states, types, formes…) sont des données quasi-statiques qui ne changent que sur action admin explicite.

**Correction** : Utiliser le cache Symfony (APCu ou Redis déjà en place) avec un tag invalidable. L'admin dispose déjà d'une action `/istration/action/invalidate/labels` — le cache tagué pourrait être invalidé automatiquement à ce moment.

**Fichiers** : `src/Service/GetLabelsService.php`, `src/Service/Back/GetLabelsService.php`, `config/packages/cache.yaml`

---

### 2. Logging verbeux en production
**Problème** : `AbstractBackService::request` log systématiquement le contenu complet de chaque réponse HTTP (`$response->getContent()`) à chaque appel. Pour des payloads de centaines de Pokémons, cela représente des mégaoctets de logs par requête.

**Correction** : Limiter le log de la réponse à un extrait (256 premiers caractères), ou le conditionner au niveau `DEBUG` uniquement via `$this->logger->debug(...)`.

**Fichier** : `src/Service/Back/AbstractBackService.php:76-80`

---

## Qualité du code

### 3. Incohérence de paradigme dans les DTO
**Problème** : Les DTO utilisent deux patterns incompatibles :
- `ElectionIndexData` : promotionnel readonly, propriétés publiques — accès direct.
- `ElectionMetrics` / `ElectionVote` : propriétés publiques mutables, pas de `readonly`.
- `UserInfo` / `DexFilters` : readonly via factory `createFromArray`.

Ce manque d'uniformité rend le codebase moins prévisible.

**Correction** : Adopter un pattern unique — le constructor readonly promotionnel (comme `ElectionIndexData` et `UserInfo`). Éliminer les propriétés mutables publiques non nécessaires.

**Fichiers** : `src/DTO/ElectionMetrics.php`, `src/DTO/ElectionVote.php`

---

### 4. `BackServiceInterface` est vide
**Problème** : `BackServiceInterface` est une interface vide utilisée uniquement comme marqueur de type. Elle n'apporte aucun contrat.

**Correction** : Soit la supprimer (Deptrac peut se baser sur le namespace), soit y déclarer un contrat minimal (par exemple la méthode `request` de `AbstractBackService` pour permettre le mock par interface plutôt que par classe concrète).

**Fichiers** : `src/Service/Back/BackServiceInterface.php`, `src/Service/Back/AbstractBackService.php`

---

### 5. `ElectionVoteService` sans valeur ajoutée
**Problème** : `ElectionVoteService::vote` délègue directement à `PostElectionVoteService::vote` sans aucun traitement supplémentaire. Contrairement aux autres services métier (`ElectionIndexService`, `GetTrainerPokedexService`), il ne gère pas les exceptions HTTP — une erreur réseau en production remonte en 500 non géré.

**Correction** : Ajouter la gestion `HttpExceptionInterface|TransportExceptionInterface` ou supprimer la couche et accéder directement depuis le controller.

**Fichiers** : `src/Service/ElectionVoteService.php`, `src/Controller/ElectionVoteController.php`

---

## Sécurité

### 6. Tokens OAuth exposés dans les logs
**Problème** : `AbstractBackService::request` log `$finalOptions` qui contient le header `Authorization: Bearer <access_token_complet>`. Si les logs sont exportés vers un agrégateur externe, les tokens OAuth des utilisateurs sont exposés.

**Correction** :
```php
$loggableOptions = $finalOptions;
$loggableOptions['headers']['Authorization'] = 'Bearer [REDACTED]';
$this->logger->info("Requesting {$method} {$endpointUrl}", $loggableOptions);
```

**Fichier** : `src/Service/Back/AbstractBackService.php:65-68`

---

### 7. Absence de protection CSRF sur les actions admin (GET)
**Problème** : Les actions admin (update, calculate, invalidate) sont déclenchées par des requêtes GET. Un lien malveillant dans un e-mail suffit à déclencher une action si l'admin est connecté.

**Correction** : Passer ces actions en POST avec un token CSRF Symfony (ou a minima en confirmation JavaScript). Utiliser `CsrfTokenManagerInterface`.

**Fichier** : `src/Controller/AdminActionController.php:26-79`

---

## Tests

### 8. `dependabot.yml` ne surveille pas les `tools/`
**Problème** : Les sous-projets `tools/phpstan`, `tools/psalm`, `tools/infection`, etc. ont leurs propres `composer.json` avec des versions fixées manuellement. Dependabot ne les surveille pas.

**Correction** :
```yaml
  - package-ecosystem: "composer"
    directory: "/tools/phpstan"
    schedule:
      interval: "weekly"
```
Répéter pour chaque outil.

**Fichier** : `.github/dependabot.yml`

---

### 9. `testListCachesCleared` utilise `exec` pour vider le cache
**Problème** : `exec('rm -Rf /app/var/cache/test/*')` dans un test est une dépendance sur le chemin absolu du container Docker, pas portable en dehors de l'environnement Docker.

**Correction** : Utiliser `$this->getContainer()->get('cache.app')->clear()` ou une commande Symfony `cache:pool:clear` via le KernelBrowser en test.

**Fichier** : `tests/src/Integration/Controller/Album/Display/CommonTest.php:83`

---

## Maintenabilité / DevX

### 10. Deux configurations Psalm sans commentaire explicatif
**Problème** : Le projet maintient `psalm.xml` et `psalm-src-only.xml` sans documentation sur leur différence. La commande `make psalm` les exécute tous deux.

**Correction** : Ajouter un commentaire en tête de chaque fichier XML expliquant la différence (ex : taint analysis complète vs analyse de src sans tests).

**Fichiers** : `psalm.xml`, `psalm-src-only.xml`, `Makefile:243-245`

---

### 11. Version Moco hardcodée dans `Makefile` et `docker-compose.yaml`
**Problème** : La version Moco `1.5.0` est dupliquée dans `docker-compose.yaml:8` (deux services) et dans `Makefile:13`. Si elle est mise à jour, il faut modifier les 3 occurrences.

**Correction** : Extraire dans une variable `.env` ou en argument de build Docker :
```yaml
args:
  MOCO_VERSION: ${MOCO_VERSION:-1.5.0}
```

**Fichiers** : `docker-compose.yaml:8,14`, `Makefile:13`
