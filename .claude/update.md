# Inventaire des dépendances — pokenini-web

## Composer — dépendances runtime

| Dépendance | Version actuelle | Action recommandée |
|-----------|-----------------|-------------------|
| php | >=8.4 | OK — PHP 8.4 LTS actif |
| guzzlehttp/guzzle | ^7.10.0 | Vérifier si 7.10.x est la dernière minor — surveiller 8.x |
| knpuniversity/oauth2-client-bundle | ^2.20.2 | Stable, vérifier changelog pour Symfony 8 compat |
| league/oauth2-google | ^5.0.0 | OK |
| phpdocumentor/reflection-docblock | ^6.0.3 | OK |
| phpstan/phpdoc-parser | ^2.3.2 | OK — dépendance transitive PHPStan |
| symfony/* | 8.0.* | Vérifier disponibilité 8.1+ — 8.0 LTS jusqu'à 2028 |
| symfony/flex | ^2.10.0 | OK |
| symfony/monolog-bundle | ^4.0.2 | OK |
| twig/extra-bundle | ^3.24.0 | OK |
| twig/intl-extra | ^3.24.0 | OK |
| twig/twig | ^3.24.0 | OK |
| wohali/oauth2-discord-new | ^1.2.1 | Vérifier activité du mainteneur (fork non officiel) |

## Composer — dépendances dev

| Dépendance | Version actuelle | Action recommandée |
|-----------|-----------------|-------------------|
| phpunit/phpunit | ^13.1.8 | OK — version récente |
| symfony/browser-kit | 8.0.* | OK |
| symfony/css-selector | 8.0.* | OK |
| symfony/debug-bundle | 8.0.* | OK |
| symfony/panther | ^2.4 | Vérifier compatibilité PHP 8.4 + Chrome headless |
| symfony/phpunit-bridge | 8.0.* | OK |
| symfony/stopwatch | 8.0.* | OK |
| symfony/var-dumper | 8.0.* | OK |
| symfony/web-profiler-bundle | 8.0.* | OK |

## Outils qualité (`tools/*/composer.json`)

| Outil | Dépendance | Version actuelle | Action recommandée |
|-------|-----------|-----------------|-------------------|
| phpstan | phpstan/phpstan | ^2.1.54 | OK — PHPStan 2.x actif |
| phpstan | phpstan/phpstan-symfony | ^2.0.15 | OK |
| phpstan | phpstan/phpstan-phpunit | ^2.0.16 | OK |
| psalm | vimeo/psalm | 6.16.1 | Version fixée — vérifier si 6.17+ disponible |
| psalm | psalm/plugin-symfony | ^5.3.0 | OK |
| psalm | psalm/plugin-phpunit | ^0.19.7 | Vérifier activité — dernier tag ancien |
| deptrac | deptrac/deptrac | ^4.6.0 | OK |
| infection | infection/infection | ^0.32.7 | OK |
| phpmd | phpmd/phpmd | ^2.15 | OK |
| php-cs-fixer | friendsofphp/php-cs-fixer | ^3.95.1 | OK — vérifier changelog 3.96+ |
| jsonlint | seld/jsonlint | ^1.11 | OK |

## Images Docker

| Image | Version actuelle | Action recommandée |
|-------|-----------------|-------------------|
| nginx | 1.28-alpine | OK — version récente |
| redis | 8.0-alpine | OK — Redis 8 LTS |
| ghcr.io/validator/validator (vnu) | latest | **Risque** : tag `latest` non reproductible — épingler une version fixe |
| hadolint/hadolint (CI) | 2.12.0-alpine | Vérifier si 2.13+ disponible |
| zavoloklom/dclint (CI) | 3.1.0-alpine | OK |
| dotenvlinter/dotenv-linter (CI) | 4.0.0 | OK |
| mstruebing/editorconfig-checker (CI) | v3.6.0 | OK |
| Moco | 1.5.0 | Vérifier si 1.6+ disponible |

## Outils non-Composer

| Outil | Version actuelle | Action recommandée |
|-------|-----------------|-------------------|
| local-php-security-checker | v2.1.3 | Vérifier si v2.2+ disponible sur GitHub |
| cachetool | 9.2.1 | Vérifier v10.x |

## Observations critiques

1. **`wohali/oauth2-discord-new`** : fork communautaire non maintenu par Discord. Si Discord modifie son API OAuth2, ce package pourrait ne pas être mis à jour rapidement. Envisager une alternative ou un fork interne.

2. **`vnu` avec `latest`** : l'image du validateur W3C utilise le tag `latest`, ce qui brise la reproductibilité des builds CI. Épingler la version (`ghcr.io/validator/validator:24.x.x`).

3. **Exigence PHP dans les `tools/`** : certains outils requièrent `php >= 8.5.4` alors que l'image PHP du projet utilise PHP 8.4. Harmoniser avec `>=8.4`.

## Commandes de vérification

```bash
# Dépendances Composer
composer outdated

# Dépendances des outils qualité
for tool in tools/*/; do echo "=== $tool ===" && composer outdated -d "$tool"; done

# Audit de sécurité
composer audit
symfony security:check
```
