# Selenium Container Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [x]`) syntax for tracking.

**Goal:** Remplacer l'installation de Chrome/chromedriver dans le container PHP par un service Docker dédié `selenium/standalone-chromium`, et connecter Panther à ce service via WebDriver distant.

**Architecture:** Un container `chrome` (image `selenium/standalone-chromium:4`) expose le WebDriver sur le port 4444. Le container PHP ne contient plus aucun binaire Chrome. `AbstractBrowserTestCase` utilise `createSeleniumClient()` à la place de `createPantherClient()`, avec `http://web:8080` comme base URI de l'appli (Nginx existant).

**Tech Stack:** Docker Compose, Symfony Panther, selenium/standalone-chromium:4

---

### Task 1 : Ajouter le service `chrome` dans docker-compose.yaml

**Files:**
- Modify: `docker-compose.yaml`

- [x] **Step 1 : Ajouter le service**

Dans `docker-compose.yaml`, ajouter le service `chrome` après le service `redis` :

```yaml
  chrome:
    image: selenium/standalone-chromium:4
    shm_size: '2g'
```

Le `shm_size: '2g'` est requis pour que Chrome ne crashe pas avec `/dev/shm too small`.

Le fichier complet après modification :

```yaml
name: Pokénini Web

services:
  moco.back:
    build:
      context: ./.docker/moco
      args:
        MOCO_VERSION: ${MOCO_VERSION:-1.5.0}
    volumes:
      - ./tests/resources/moco/Back/:/var/moco:ro
    command: "/var/moco/moco.json"

  moco.matomo.gbl:
    build:
      context: ./.docker/moco
      args:
        MOCO_VERSION: ${MOCO_VERSION:-1.5.0}
    volumes:
      - ./tests/resources/moco/Matomo/:/var/moco:ro
    ports:
      - '127.0.0.1:8888:80'
    command: "/var/moco/moco.json"

  php:
    build:
      context: .
      dockerfile: ./.docker/php/Dockerfile
      target: php_dev
    volumes:
      - .:/app
    environment:
      - PHP_CS_FIXER_IGNORE_ENV=1
    tty: true

  redis:
    image: redis:8.0-alpine
    volumes:
      - redis_data:/data
    command: redis-server --requirepass ${REDIS_PASSWORD:-douze}
    restart: always

  chrome:
    image: selenium/standalone-chromium:4
    shm_size: '2g'

  web:
    image: nginx:1.28-alpine
    volumes:
      - .:/app
      - ./.docker/nginx/nginx.conf:/etc/nginx/nginx.conf
      - ./.docker/nginx/conf.d:/etc/nginx/conf.d
    ports:
      - '127.0.0.1:80:8080'
      - '127.0.0.1:443:8080'

volumes:
  redis_data:
```

- [x] **Step 2 : Vérifier que le service démarre**

```bash
docker compose up chrome -d
docker compose logs chrome | tail -20
```

Expected : `Started Selenium Standalone` dans les logs, pas d'erreur.

---

### Task 2 : Supprimer Chrome du Dockerfile PHP

**Files:**
- Modify: `.docker/php/Dockerfile:70-96`

- [x] **Step 1 : Retirer le bloc Chrome et adapter les ENV Panther**

Supprimer les lignes 70-96 (bloc `## < Install chromedriver` … `## > Install chromedriver`) et remplacer les ENV Panther Chrome-spécifiques par la configuration Remote WebDriver.

Le bloc actuel à supprimer :

```dockerfile
## < Install chromedriver
ENV PANTHER_NO_SANDBOX=1
ENV PANTHER_NO_REDUCED_MOTION=1
ENV PANTHER_ERROR_SCREENSHOT_DIR=/app/var/screenshots
ENV PANTHER_DEVTOOLS=disabled
ENV PANTHER_CHROME_ARGUMENTS='--disable-dev-shm-usage'
ENV PANTHER_CHROME_DRIVER_BINARY=/usr/local/bin/chromedriver
ENV PANTHER_CHROME_BINARY=/usr/local/bin/chrome
ENV GNUPGHOME=/usr/local/share/gnupg

# hadolint ignore=DL3018
RUN apk add --no-cache \
  gnupg \
  unzip \
  wget \
  chromium \
  chromium-chromedriver \
  fontconfig \
  freetype \
  libx11 \
  libxext \
  libxrender \
  libxtst

RUN ln -sf /usr/bin/chromium-browser /usr/local/bin/chrome \
  && ln -sf /usr/bin/chromedriver /usr/local/bin/chromedriver
## > Install chromedriver
```

Le remplacer par :

```dockerfile
## < Panther / Selenium
ENV PANTHER_NO_REDUCED_MOTION=1
ENV PANTHER_ERROR_SCREENSHOT_DIR=/app/var/screenshots
ENV PANTHER_EXTERNAL_BASE_URI=http://web:8080
## > Panther / Selenium
```

- `PANTHER_EXTERNAL_BASE_URI=http://web:8080` indique à Panther d'utiliser le Nginx existant (port 8080 interne) plutôt que de démarrer son propre serveur PHP.
- `PANTHER_NO_SANDBOX` et `PANTHER_CHROME_ARGUMENTS` ne sont plus pertinents : le sandbox et le shm sont configurés côté container Selenium.
- `PANTHER_DEVTOOLS=disabled` était Chrome-spécifique, inutile avec Selenium distant.

- [x] **Step 2 : Rebuild le container PHP**

```bash
docker compose build php
```

Expected : build sans erreur, image plus légère (~150-200 Mo de moins).

- [x] **Step 3 : Vérifier que Chrome n'est plus dans l'image**

```bash
docker compose run --rm php which chromium-browser 2>&1
```

Expected : `which: no chromium-browser in (...)` — le binaire n'existe plus.

---

### Task 3 : Adapter AbstractBrowserTestCase pour Selenium distant

**Files:**
- Modify: `tests/src/Browser/AbstractBrowserTestCase.php`

- [x] **Step 1 : Remplacer createPantherClient par createSeleniumClient**

`createPantherClient` démarre un chromedriver local. `createSeleniumClient` se connecte à un WebDriver distant.

Remplacer le contenu de `getNewClient()` :

```php
protected static function getNewClient(): Client
{
    return static::createSeleniumClient(
        'http://chrome:4444',
        ['acceptInsecureCerts' => true],
    );
}
```

- Le premier argument est l'URL du container Selenium (nom de service Docker `chrome`, port 4444).
- `acceptInsecureCerts` remplace l'ancien `--ignore-certificate-errors` du chromedriver local.
- `PANTHER_EXTERNAL_BASE_URI` (défini dans le Dockerfile) est lue automatiquement par Panther pour construire les URLs.

- [x] **Step 2 : Vérifier que le fichier compile (pas d'import mort)**

L'import `Symfony\Component\Panther\PantherTestCase` reste valide — `createSeleniumClient` est une méthode statique de `PantherTestCase`. Aucun import à ajouter ni supprimer.

```bash
docker compose exec php php -l tests/src/Browser/AbstractBrowserTestCase.php
```

Expected : `No syntax errors detected`.

---

### Task 4 : Reconstruire et lancer les tests browser

**Files:** aucun fichier modifié dans cette tâche — validation uniquement.

- [x] **Step 1 : Redémarrer la stack complète**

```bash
docker compose down && docker compose up -d
```

- [x] **Step 2 : Vérifier que le container chrome est healthy**

```bash
docker compose ps chrome
```

Expected : état `running` (ou `healthy` si healthcheck configuré).

- [x] **Step 3 : Lancer les tests browser**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Browser
```

Expected : tous les tests passent, sans erreur `chromedriver not found` ni `connection refused`.

En cas d'échec : récupérer les screenshots dans `var/screenshots/` pour diagnostiquer.

- [x] **Step 4 : Lancer la suite complète**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/
```

Expected : tests unit + integration + browser tous verts.

---

### Task 5 : Mettre à jour la documentation

**Files:**
- Modify: `CLAUDE.md`

- [x] **Step 1 : Mettre à jour la section Infrastructure de CLAUDE.md**

Dans le tableau Infrastructure, remplacer la ligne PHP FPM et ajouter le service chrome :

```markdown
| `chrome`      | Selenium Standalone Chromium (WebDriver pour Panther) |
```

Le tableau complet devient :

```markdown
| Service       | Purpose                              |
| ------------- | ------------------------------------ |
| `php`         | PHP 8.5 FPM (dev image)              |
| `web`         | Nginx                                |
| `redis`       | Cache backend (Symfony Cache + tags) |
| `chrome`      | Selenium Standalone Chromium (WebDriver pour Panther) |
| `moco.back`   | Moco mock pour `pokenini-back`       |
| `moco.matomo.gbl` | Moco mock pour Matomo           |
```

*(Adapter selon le tableau existant dans CLAUDE.md — ne pas réécrire les sections non concernées.)*
