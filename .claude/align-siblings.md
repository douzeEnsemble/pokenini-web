# Alignement avec pokenini-api / pokenini-back

Différences identifiées par comparaison des CI, Dockerfiles, docker-compose et Makefile des trois projets.

## Bugs

- [ ] **`tools-composer/action.yml:12`** — Cache path incorrect : `path: vendor` → `path: tools/${{ inputs.dir }}/vendor` (le vendor du tool n'est jamais mis en cache)
- [ ] **`Makefile:181`** — `restart-mocks` ne redémarre que `moco.back`, manque `moco.matomo.gbl`

## CI manquante

- [ ] **`ci_infraquality.yml`** — Ajouter job `moco-refs` (présent dans api et back, absent dans web) :
  ```yaml
  moco-refs:
    name: Moco References
    runs-on: ubuntu-latest
    steps:
      - name: Check out code
        uses: actions/checkout@v6
      - name: Check moco file references
        run: |
          tools/check-moco-refs/check_moco_refs.sh tests/resources/moco/Back/moco.json tests/resources/moco/Back
          tools/check-moco-refs/check_moco_refs.sh tests/resources/moco/Matomo/moco.json tests/resources/moco/Matomo
  ```

## Versions incorrectes

- [ ] **`ci_codequality.yml:99`** — Job `w3c` : `actions/checkout@v5` → `@v6`
- [ ] **`security.yml:14`** — Job `composer-audit` : `actions/checkout@v5` → `@v6`

## Images Docker obsolètes (`docker-compose.yaml`)

- [ ] **nginx** : `nginx:1.28-alpine` → `nginx:1.29.8-alpine3.23`
- [ ] **redis** : `redis:8.0-alpine` → `redis:8.6.2-alpine3.23`

## Nettoyage

- [ ] **`.github/actions/local-tools-composer/`** — Action orpheline, non référencée dans aucun workflow, à supprimer
- [ ] **`tools/opcache-reset/`** — Répertoire orphelin (pas dans Makefile/CI/dependabot), à supprimer
- [ ] **`tools/owasp-check/`** — Répertoire orphelin (pas dans Makefile/CI/dependabot), à supprimer
- [ ] **`tools/php-security-checker/`** — Répertoire orphelin (pas dans Makefile/CI/dependabot), à supprimer

## Cohérence mineure

- [ ] **`.github/actions/local-composer/action.yml`** — Ajouter `--no-progress` : `composer install --no-interaction --prefer-dist` → `composer install --prefer-dist --no-progress --no-interaction`
- [ ] **`security.yml`** — Remplacer la boucle shell par une matrice + job séparé `tools-composer-audit-with-app` pour phpstan/psalm (alignement avec api/back)
- [ ] **`docker-compose.yaml`** — PHP service : ajouter `args: UID/GID` + aligner le Dockerfile avec `ARG UID/GID` (pattern api/back)
