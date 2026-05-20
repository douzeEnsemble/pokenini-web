# Design — Tests de désérialisation des ResponseObjects (point 12)

## Contexte

Le point 12 de `doc/improvement.md` demande des tests de désérialisation pour les ResponseObjects critiques afin de détecter des régressions de mapping JSON→PHP sans dépendre de Moco.

17 tests d'intégration existent déjà dans `tests/src/Integration/ResponseObject/`. Il manque 2 classes.

## Périmètre

### Fichiers à créer

#### `tests/src/Integration/ResponseObject/ActionLogTest.php`

- Classe : `KernelTestCase`, `final`, `@internal`, `#[CoversClass(ActionLog::class)]`
- Méthode `testDeserialize()` : JSON avec `created_at` ISO 8601, `done_at: null`, `execution_time: null`, `details: []`, `error_trace: null`
  - Assert `\DateTime` correctement désérialisé (vérification `format('c')` pour la timezone)
  - Assert `done_at === null`, `execution_time === null`, `error_trace === null`
  - Assert `details === []`
- Méthode `testDeserializeWithDoneAt()` : JSON avec `done_at` non-null, `execution_time: 3032`, `details: {"dex_availabilities": 22472}`
  - Assert `\DateTime` pour `done_at`
  - Assert `execution_time === 3032`
  - Assert `details === ['dex_availabilities' => 22472]`

Le JSON doit correspondre à la structure que `GetActionLogsService` sérialise via `json_encode($currentData)` — soit l'objet `ActionLog` direct, pas la structure wrapper.

#### `tests/src/Integration/ResponseObject/Label/CatchStateTest.php`

- Classe : `KernelTestCase`, `final`, `@internal`, `#[CoversClass(CatchState::class)]`
- Méthode `testDeserialize()` : JSON avec `name`, `french_name`, `slug`, `color`
  - Assert les 4 getters

### Fichier à modifier

**`doc/improvement.md`** : ajouter section `**Traité**` au point 12 décrivant les 19 tests de désérialisation dans `tests/src/Integration/ResponseObject/`.

## Contraintes

- Pattern identique aux 17 tests existants (JSON inline, pas de fixture fichier)
- Pas de `#[Group('api-mocked-testing')]` — ces tests ne nécessitent pas Moco
- Les assertions `ActionLog` doivent vérifier le format `\DateTime` car c'est le cas à risque réel si le format de date API change
