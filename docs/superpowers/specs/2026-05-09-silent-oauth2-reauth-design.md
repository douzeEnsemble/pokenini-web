# Design : Reconnexion OAuth2 silencieuse (issue #287)

## Contexte

Les utilisateurs sont déconnectés après 30-60 minutes d'activité. Cause : le token OAuth2 Google expire après 1 heure. Si aucun refresh token n'est disponible (ex. autorisation déjà accordée précédemment), `UserRefresher::refresh()` lève `AuthenticationExpiredException`, Symfony efface le security token et redirige vers la home — sans prévenir l'utilisateur.

Objectif : quand le token expire et ne peut pas être rafraîchi silencieusement, relancer le flux OAuth2 du provider d'origine et ramener l'utilisateur sur la page qu'il consultait. Transparent pour l'utilisateur tant que le refresh token est valide.

Scope : Google et Discord.

## Architecture

### Flux normal (refresh token disponible)

```
Requête → UserProvider::refreshUser() → UserRefresher::refresh()
    → token expiré + refresh token présent → nouveau token → continue normalement
```

Aucun changement ici. Déjà fonctionnel.

### Flux de reconnexion silencieuse (pas de refresh token)

```
Requête → UserProvider::refreshUser() → UserRefresher::refresh()
    → token expiré + pas de refresh token → AuthenticationExpiredException
    → Symfony efface security token
    → AuthenticationEntryPoint::start()
        → lit _security_provider depuis la session
        → sauvegarde l'URL courante dans _security_target_path (session)
        → redirige vers app_connect_{provider}_goto
    → flux OAuth2 complet
    → AuthenticatorTrait::onAuthenticationSuccess()
        → lit _security_target_path depuis la session
        → redirige vers l'URL sauvegardée
        → supprime _security_target_path
```

### Pourquoi stocker le provider en session

Symfony efface le security token (`tokenStorage->setToken(null)`) **avant** d'appeler `AuthenticationEntryPoint::start()`. Le `User` (qui contient `providerName`) n'est donc plus accessible via `Security::getUser()` dans `start()`. La clé `_security_provider` est écrite en session au moment du login (quand le `User` est encore disponible) et survit à l'expiration du token OAuth2.

## Composants

### `AuthenticatorTrait` (modifié)

`onAuthenticationSuccess()` reçoit déjà `$request` en paramètre — `$request->getSession()` est disponible sans injection supplémentaire.

1. Stocker `_security_provider = $user->getProviderName()` en session (à chaque login réussi)
2. Lire `_security_target_path` en session :
   - Si présent → rediriger vers cette URL + supprimer la clé
   - Sinon → comportement actuel (outerroom ou home selon le rôle)

### `AuthenticationEntryPoint` (modifié)

Injecter `RequestStack` en plus du `RouterInterface` existant.

`start()` :
1. Lire `_security_provider` depuis la session
2. Si provider reconnu (dans `PROVIDER_ROUTES`) :
   - Écrire `_security_target_path = $request->getUri()` en session
   - Rediriger vers `app_connect_{provider}_goto`
3. Sinon → comportement actuel (redirect `app_home_index`)

```php
private const array PROVIDER_ROUTES = [
    'google'  => 'app_connect_google_goto',
    'discord' => 'app_connect_discord_goto',
];
```

### `UserRefresher` (inchangé)

Le comportement existant est correct : lever `AuthenticationExpiredException` quand le token est expiré sans refresh token.

## Tests

### `Unit/Security/AuthenticatorTraitTest`

- `onAuthenticationSuccess` avec `_security_target_path` en session → redirige vers cette URL et supprime la clé
- `onAuthenticationSuccess` sans `_security_target_path` + trainer → redirige vers home
- `onAuthenticationSuccess` sans `_security_target_path` + non-trainer → redirige vers outerroom
- `_security_provider` est toujours écrit en session peu importe le cas

### `Unit/Security/AuthenticationEntryPointTest`

- Provider `google` en session → redirect `app_connect_google_goto` + `_security_target_path` sauvegardé
- Provider `discord` en session → redirect `app_connect_discord_goto` + `_security_target_path` sauvegardé
- Pas de provider en session → redirect `app_home_index` (comportement actuel)
- Provider inconnu en session → redirect `app_home_index` (fallback sûr)

## Fichiers à modifier

| Fichier | Nature |
|---|---|
| `src/Security/AuthenticatorTrait.php` | Ajout stockage provider + lecture target path |
| `src/Security/AuthenticationEntryPoint.php` | Ajout RequestStack + logique reconnexion silencieuse |

Aucune modification nécessaire dans les classes concrètes utilisant le trait (`DiscordAuthenticator`, `GoogleAuthenticator`, `FakeAuthenticator`) — le trait utilise `$request->getSession()` déjà disponible via le paramètre existant de `onAuthenticationSuccess`.
