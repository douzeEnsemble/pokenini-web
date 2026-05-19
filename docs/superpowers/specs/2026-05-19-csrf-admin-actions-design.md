# Design : Protection CSRF sur les actions admin

## Contexte

`AdminActionController` expose trois routes GET (`/update/{name}`, `/calculate/{name}`, `/invalidate/{name}`) déclenchées par de simples liens `<a href>`. Un lien malveillant dans un e-mail suffit à exécuter une action admin si l'admin est authentifié.

## Objectif

Passer les actions admin en POST avec validation de token CSRF Symfony. Aucune confirmation JS.

## Architecture

### Controller (`src/Controller/AdminActionController.php`)

- Routes : `methods: ['GET']` → `methods: ['POST']`
- Chaque méthode publique reçoit un `Request $request` en paramètre
- Validation du token avant appel à `execute()` via `isCsrfTokenValid()`
- Token invalide → `$this->createAccessDeniedException()` (403 standard Symfony)
- Un token par type d'action :
  - `update` → id `admin_update`
  - `calculate` → id `admin_calculate`
  - `invalidate` → id `admin_invalidate`

```php
public function update(string $name, Request $request): RedirectResponse
{
    if (!$this->isCsrfTokenValid('admin_update', $request->request->get('_token'))) {
        throw $this->createAccessDeniedException('Invalid CSRF token.');
    }
    return $this->execute($name, 'update', 'POST');
}
```

### Template (`templates/Admin/_macros.html.twig`)

Le macro `actionButton` remplace `<a href>` par `<form method="post">` avec un champ caché `_token`. Le `csrfId` est dérivé de `actionPrefix` (`update`, `calculate`, `invalidate`) pour obtenir `admin_update`, etc.

```twig
{% set csrfId = 'admin_' ~ actionPrefix %}
<form method="post" action="{{ path('app_adminaction_' ~ actionPrefix, {'name': item}) }}">
  <input type="hidden" name="_token" value="{{ csrf_token(csrfId) }}">
  <button type="submit" class="btn btn-outline-primary admin-item-cta {{ currentState == 'pending' ? 'disabled' : '' }}">
    {{ ('admin.actions.' ~ action ~ '.' ~ item ~ '.cta')|trans }}
  </button>
</form>
```

### Tests unitaires (`tests/src/Unit/Controller/AdminActionControllerTest.php`)

- Les tests existants sont mis à jour pour passer un `Request` avec token valide et un `CsrfTokenManager` mocké
- Un test supplémentaire par méthode vérifie qu'un token invalide lève `AccessDeniedException`

### Tests d'intégration (`tests/src/Integration/Controller/Admin/AdminPageTest.php`)

- Les assertions `assertCountFilter` sur `.admin-item-cta` restent valides (`<button>` répond aux mêmes sélecteurs CSS)
- Aucun nouveau test de flux action n'est requis dans le cadre de cette correction

## Fichiers modifiés

| Fichier | Changement |
|---|---|
| `src/Controller/AdminActionController.php` | Routes GET → POST, validation CSRF |
| `templates/Admin/_macros.html.twig` | `<a href>` → `<form><button>` + token |
| `tests/src/Unit/Controller/AdminActionControllerTest.php` | Mocks CSRF, tests token invalide |
