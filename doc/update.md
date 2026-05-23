# Inventaire des dépendances — Pokénini Web

## Composer — dépendances runtime

| Dépendance                           | Version actuelle | Action recommandée                                                                             |
| ------------------------------------ | ---------------- | ---------------------------------------------------------------------------------------------- |
| `php`                                | `>=8.4`          | OK — Docker utilise 8.5.5                                                                      |
| `guzzlehttp/guzzle`                  | `^7.10.0`        | Surveiller (7.x actif) ; vérifier si 8.x est compatible                                        |
| `knpuniversity/oauth2-client-bundle` | `^2.20.2`        | Stable ; vérifier changelog pour Symfony 8 compat                                              |
| `league/oauth2-google`               | `^5.0.0`         | OK                                                                                             |
| `wohali/oauth2-discord-new`          | `^1.2.1`         | **Surveiller** — fork communautaire non officiel ; vérifier activité GitHub et support PHP 8.4 |
| `phpdocumentor/reflection-docblock`  | `^6.0.3`         | OK                                                                                             |
| `phpstan/phpdoc-parser`              | `^2.3.2`         | OK                                                                                             |
| `symfony/*`                          | `8.0.*`          | OK — 8.0 LTS jusqu'à 2028 ; surveiller 8.1+                                                    |
| `symfony/flex`                       | `^2.10.0`        | OK                                                                                             |
| `symfony/monolog-bundle`             | `^4.0.2`         | OK                                                                                             |
| `twig/extra-bundle`                  | `^3.24.0`        | Surveiller (Twig 3.x)                                                                          |
| `twig/intl-extra`                    | `^3.24.0`        | Surveiller                                                                                     |
| `twig/twig`                          | `^3.24.0`        | Surveiller                                                                                     |

## Composer — dépendances dev

| Dépendance                    | Version actuelle | Action recommandée                                            |
| ----------------------------- | ---------------- | ------------------------------------------------------------- |
| `phpunit/phpunit`             | `^13.1.8`        | OK — version récente                                          |
| `symfony/browser-kit`         | `8.0.*`          | OK                                                            |
| `symfony/css-selector`        | `8.0.*`          | OK                                                            |
| `symfony/debug-bundle`        | `8.0.*`          | OK                                                            |
| `symfony/panther`             | `^2.4`           | Surveiller ; vérifier compatibilité PHP 8.4 + Chrome headless |
| `symfony/phpunit-bridge`      | `8.0.*`          | OK                                                            |
| `symfony/stopwatch`           | `8.0.*`          | OK                                                            |
| `symfony/var-dumper`          | `8.0.*`          | OK                                                            |
| `symfony/web-profiler-bundle` | `8.0.*`          | OK                                                            |

## Outils qualité (`tools/*/composer.json`)

| Outil        | Dépendance                  | Version actuelle   | Action recommandée                                                                                  |
| ------------ | --------------------------- | ------------------ | --------------------------------------------------------------------------------------------------- |
| php-cs-fixer | `friendsofphp/php-cs-fixer` | `^3.95.1`          | OK — vérifier changelog 3.96+                                                                       |
| phpstan      | `phpstan/phpstan`           | `^2.1.54`          | OK — PHPStan 2.x actif                                                                              |
| phpstan      | `phpstan/phpstan-symfony`   | `^2.0.15`          | OK                                                                                                  |
| phpstan      | `phpstan/phpstan-phpunit`   | `^2.0.16`          | OK                                                                                                  |
| psalm        | `vimeo/psalm`               | `6.16.1` (fixée !) | **Surveiller** — version épinglée sans `^` ; vérifier si 6.17+ existe et mettre à jour manuellement |
| psalm        | `psalm/plugin-symfony`      | `^5.3.0`           | OK                                                                                                  |
| psalm        | `psalm/plugin-phpunit`      | `^0.19.7`          | Surveiller — dernier tag peut être ancien                                                           |
| phpmd        | `phpmd/phpmd`               | `^2.15`            | OK                                                                                                  |
| infection    | `infection/infection`       | `^0.32.7`          | OK — récent                                                                                         |
| deptrac      | `deptrac/deptrac`           | `^4.6.0`           | OK                                                                                                  |
| jsonlint     | `seld/jsonlint`             | `^1.11`            | OK                                                                                                  |

**Note Psalm** : la version `6.16.1` est épinglée sans `^`. Si Psalm 6.17+ est sorti, mettre à jour manuellement. Envisager de dépingler vers `^6.16` pour bénéficier des correctifs de patch automatiquement.

## Docker / Infrastructure

| Service | Image | Version actuelle | Action recommandée |
|---------|-------|-----------------|-------------------|
| `php` | `php:*-fpm-alpine3.23` | `8.5.6` | OK — récent ; mettre à jour lors des patch releases PHP |
| `web` | `nginx:*-alpine` | `1.28-alpine` | OK — récent |
| `redis` | `redis:*-alpine` | `8.0-alpine` | OK — Redis 8 LTS |
| `moco.back` | `.docker/moco/Dockerfile` | Moco `1.5.0` | Surveiller — vérifier si 1.6+ existe |
| `moco.matomo.gbl` | idem | `1.5.0` | Surveiller |
| W3C validator (w3c_validate.sh) | `ghcr.io/validator/validator:latest` | `latest` | **Risque** — tag `latest` non reproductible ; épingler une version explicite (ex. `24.x.x`). Lancé via `docker run` à la volée (plus de service Compose) |
| symfony-cli (dans php dev) | `ghcr.io/symfony-cli/symfony-cli:5.17.1` | `5.17.1` | Surveiller |

## Outils CI (images)

| Image                             | Version actuelle | Action recommandée           |
| --------------------------------- | ---------------- | ---------------------------- |
| `hadolint/hadolint`               | `2.12.0-alpine`  | Vérifier si 2.13+ disponible |
| `zavoloklom/dclint`               | `3.1.0-alpine`   | OK                           |
| `dotenvlinter/dotenv-linter`      | `4.0.0`          | OK                           |
| `mstruebing/editorconfig-checker` | `v3.6.0`         | OK                           |

## Outils non-Composer

| Outil                        | Version actuelle | Action recommandée                      |
| ---------------------------- | ---------------- | --------------------------------------- |
| `local-php-security-checker` | `v2.1.3`         | Vérifier si v2.2+ disponible sur GitHub |
| `cachetool`                  | `9.2.1`          | Vérifier v10.x                          |

## CI / GitHub Actions

| Action             | Version actuelle | Action recommandée                                                                                                                                                                                |
| ------------------ | ---------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `actions/checkout` | `v6`             | **Vérifier** — `v6` n'existe pas encore en stable (stable = `v4` en mai 2026) ; possiblement une version preview ou une erreur de configuration. Revenir à `@v4` pour garantir la stabilité du CI |

## Variables d'environnement

| Variable                      | Valeur par défaut (dev)               | Commentaire sécurité/prod                                   |
| ----------------------------- | ------------------------------------- | ----------------------------------------------------------- |
| `APP_SECRET`                  | `'$ecretf0rt3st'`                     | Obligatoire en prod : valeur aléatoire forte (32+ chars)    |
| `OAUTH_DISCORD_CLIENT_SECRET` | valeur de dev versionnée              | Ne jamais committer la valeur prod ; utiliser secrets CI/CD |
| `OAUTH_GOOGLE_CLIENT_SECRET`  | valeur de dev versionnée              | Idem                                                        |
| `BACK_CAFILE_PATH`            | `./resources/certificates/cacert.pem` | Vérifier que le certificat est à jour en prod               |
| `BACK_URL`                    | `http://moco.back` (dev)              | URL réelle de pokenini-api en prod                          |
| `LIST_ADMIN`                  | identifiants dev                      | Vérifier en prod que la liste est à jour                    |
| `LIST_COLLECTOR`              | identifiants dev                      | Idem                                                        |
| `LIST_TRAINER`                | identifiants dev                      | Idem                                                        |
| `REQUIRE_INVITATION`          | `false`                               | Activer en prod si invitations requises                     |
| `DEMO_USER_ID`                | hash SHA1                             | Vérifier qu'il correspond à un compte valide en prod        |

## Observations critiques

1. **`wohali/oauth2-discord-new`** : fork communautaire non maintenu par Discord. Si Discord modifie son API OAuth2, ce package pourrait ne pas être mis à jour rapidement. Envisager une alternative ou un fork interne.

2. **`vnu` avec `latest`** : l'image du validateur W3C utilise le tag `latest`, ce qui brise la reproductibilité des builds CI. Épingler la version (`ghcr.io/validator/validator:24.x.x`).

3. **Exigence PHP dans les `tools/`** : certains outils requièrent `php >= 8.5.4` alors que l'image PHP du projet utilise PHP 8.4. Harmoniser avec `>=8.4`.

4. **`actions/checkout@v6`** dans les workflows CI : la version stable recommandée est `v4`. Corriger vers `@v4` si c'est une erreur de frappe.

5. **Dépingler Psalm** `6.16.1` → `^6.16` pour bénéficier des correctifs de patch sans mise à jour manuelle.

## Recommandations prioritaires

1. Fixer le tag `vnu` dans `docker-compose.yaml:30` : remplacer `latest` par une version explicite.
2. Vérifier et corriger `actions/checkout@v6` dans les 5 workflows CI vers `@v4`.
3. Dépingler Psalm `6.16.1` → `^6.16`.
4. Auditer `wohali/oauth2-discord-new` : activité de maintenance, support PHP 8.4, issues ouvertes.
5. Déplacer les credentials OAuth de `.env`/`.env.dev` vers `.env.dev.local` (non versionné).

## Commandes de vérification

```bash
# Dépendances Composer runtime et dev
docker compose exec php composer outdated

# Audit de sécurité
docker compose exec php composer audit

# Outils qualité (sous tools/)
for tool in tools/*/; do
    echo "=== $tool ==="
    docker compose exec php composer outdated -d "$tool"
done
```
