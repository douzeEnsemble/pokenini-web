# Dépendances et mises à jour — Pokénini Web

Pour vérifier les mises à jour disponibles, exécuter dans le conteneur PHP :
```bash
docker compose exec php composer outdated
docker compose exec php composer outdated --working-dir=tools/phpstan
# etc. pour chaque répertoire tools/
```

---

## Composer — dépendances principales (`composer.json`)

| Dépendance | Version installée | Contrainte | Action recommandée |
|---|---|---|---|
| `php` | 8.4 (runtime) | `>=8.4` | OK |
| `symfony/*` | 8.0.8–8.0.9 | `8.0.*` | Vérifier avec `composer outdated` |
| `guzzlehttp/guzzle` | 7.10.0 | `^7.10.0` | Vérifier (`^7` → 7.x latest) |
| `knpuniversity/oauth2-client-bundle` | v2.20.2 | `^2.20.2` | Vérifier |
| `league/oauth2-google` | 5.0.0 | `^5.0.0` | Vérifier |
| `league/oauth2-client` | 2.9.0 | (transitive) | Vérifier |
| `wohali/oauth2-discord-new` | 1.2.1 | `^1.2.1` | Vérifier |
| `twig/twig` | v3.24.0 | `^3.24.0` | Vérifier |
| `twig/extra-bundle` | v3.24.0 | `^3.24.0` | Vérifier |
| `twig/intl-extra` | v3.24.0 | `^3.24.0` | Vérifier |
| `symfony/panther` | ^2.4 (dev) | `^2.4` | Vérifier |
| `phpunit/phpunit` | ^13.1.8 (dev) | `^13.1.8` | Vérifier |
| `monolog/monolog` | 3.10.0 | (transitive) | Vérifier |

Commande de mise à jour principale :
```bash
make updates  # met à jour main + tous les tools/ en une commande
```

---

## Composer — outils qualité (`tools/*/composer.json`)

| Outil | Répertoire | Version requise | Action |
|---|---|---|---|
| PHPStan | `tools/phpstan/` | `^2.1.54` + plugins | `composer outdated --working-dir=tools/phpstan` |
| Psalm | `tools/psalm/` | `6.16.1` (fixe) | `composer outdated --working-dir=tools/psalm` |
| PHP CS Fixer | `tools/php-cs-fixer/` | (voir composer.json) | `composer outdated --working-dir=tools/php-cs-fixer` |
| PHPMD | `tools/phpmd/` | (voir composer.json) | `composer outdated --working-dir=tools/phpmd` |
| Deptrac | `tools/deptrac/` | (voir composer.json) | `composer outdated --working-dir=tools/deptrac` |
| Infection | `tools/infection/` | (voir composer.json) | `composer outdated --working-dir=tools/infection` |
| PHPInsights | `tools/phpinsights/` | (voir composer.json) | `composer outdated --working-dir=tools/phpinsights` |
| jsonlint | `tools/jsonlint/` | (voir composer.json) | `composer outdated --working-dir=tools/jsonlint` |

Note : PHPStan exige `php >= 8.5.4` dans `tools/phpstan/composer.json` — s'assurer que le runtime PHP est compatible.

---

## Docker — images de base

| Service | Image actuelle | Action |
|---|---|---|
| `web` | `nginx:1.28-alpine` | Vérifier `nginx:latest-alpine` ou `nginx:1.28-alpine` = dernière 1.28 |
| `redis` | `redis:8.0-alpine` | Vérifier `redis:8.0-alpine` = dernière 8.0 |
| `vnu` | `ghcr.io/validator/validator:latest` | Épingler à une version pour la reproductibilité |
| `php` | Build local (Dockerfile) | Vérifier l'image de base dans `.docker/php/Dockerfile` |
| `moco` | Build local (Moco 1.5.0) | Vérifier si Moco 1.6+ existe |

Commande de mise à jour des images :
```bash
make rebuild  # docker compose build --no-cache --pull
```

---

## Docker — linters externes (appelés via `make`)

| Linter | Version actuellement utilisée | Action |
|---|---|---|
| `zavoloklom/dclint` | `3.1.0-alpine` (Makefile:13) | Vérifier la dernière version sur Docker Hub |
| `dotenvlinter/dotenv-linter` | `4.0.0` (Makefile:14) | Vérifier la dernière version |
| `mstruebing/editorconfig-checker` | `v3.6.0` (Makefile:15) | Vérifier la dernière version |
| `hadolint/hadolint` | `2.12.0-alpine` (Makefile:184) | Vérifier la dernière version |

---

## Outils non-Composer

| Outil | Version | Source | Action |
|---|---|---|---|
| `cachetool.phar` | 9.2.1 | GitHub release | Vérifier sur gordalina/cachetool |
| `local-php-security-checker` | 2.1.3 | GitHub release | Vérifier sur fabpot/local-php-security-checker |
| Moco (mock server) | 1.5.0 | `.docker/moco/Dockerfile` ARG | Vérifier sur GitHub moco-project/moco |

---

## CI GitHub Actions

Vérifier les actions dans `.github/actions/` et `.github/workflows/` pour les versions d'actions épinglées (ex. `actions/checkout@v4`). Mettre à jour avec Dependabot ou manuellement.

---

## Résumé des commandes de vérification

```bash
# Dans le conteneur PHP
docker compose exec php composer outdated
docker compose exec php composer outdated --working-dir=tools/phpstan
docker compose exec php composer outdated --working-dir=tools/psalm
docker compose exec php composer outdated --working-dir=tools/php-cs-fixer
docker compose exec php composer outdated --working-dir=tools/phpmd
docker compose exec php composer outdated --working-dir=tools/deptrac
docker compose exec php composer outdated --working-dir=tools/infection

# Sécurité
make security  # composer audit + local-php-security-checker
make owasp-check  # OWASP Dependency Check (nécessite NVD_API_KEY)
```
