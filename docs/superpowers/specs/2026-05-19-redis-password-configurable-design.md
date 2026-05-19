# Design — Mot de passe Redis configurable (Point #11)

## Problème

Le mot de passe Redis `douze` est hardcodé dans `docker-compose.yaml`. Par ailleurs, le `REDIS_DSN` Symfony ne contient pas de mot de passe, ce qui signifie que le cache `cache.labels` (Redis tag-aware) ne peut pas s'authentifier auprès de Redis — la connexion échoue silencieusement.

## Solution retenue

Source unique : `REDIS_PASSWORD` défini dans `.env` (base), lu à la fois par Symfony et Docker Compose.

## Fichiers à modifier

| Fichier | Changement |
|---|---|
| `.env` | Ajouter `REDIS_PASSWORD=douze` (valeur de dev, commentée comme telle) |
| `.env.dev` | `REDIS_DSN=redis://:${REDIS_PASSWORD}@redis:6379/0` |
| `.env.test` | `REDIS_DSN=redis://:${REDIS_PASSWORD}@redis:6379/0` |
| `.env.ci` | `REDIS_DSN=redis://:${REDIS_PASSWORD}@redis:6379/0` |
| `.env.int` | `REDIS_DSN=redis://:${REDIS_PASSWORD}@redis:6379/0` |
| `.env.prod` | `REDIS_DSN=redis://:${REDIS_PASSWORD}@redis:6379/0` |
| `docker-compose.yaml` | `redis-server --requirepass ${REDIS_PASSWORD:-douze}` |

## Comportement par environnement

- **Dev/test/ci/int** : `REDIS_PASSWORD=douze` provient du `.env` de base → aucune action manuelle requise
- **Prod** : surcharger `REDIS_PASSWORD` dans l'environnement shell ou `.env.prod.local` (non versionné) → Docker Compose et Symfony utilisent automatiquement la bonne valeur
- Le fallback `:-douze` dans `docker-compose.yaml` protège si la variable n'est pas encore définie dans l'environnement

## Périmètre

Aucun changement de code PHP. Configuration uniquement.

## Validation

Les tests d'intégration et browser (groupe `api-mocked-testing`) exercent le cache labels via Moco et valideront que l'authentification Redis fonctionne correctement après la correction du DSN.
