# Design: remplacer `.env.prod` par `.env.dev` comme source du build Docker

## Contexte

`.env.prod` n'est pas de la configuration de production réelle : c'est un
fichier de valeurs factices renommé en `.env` juste avant que
`composer run-script post-install-cmd` (via `auto-scripts` → `cache:clear`)
compile le container Symfony pendant le build de l'image `php_prod`. Il est
détruit avant la fin du build (`rm .env && touch .env`) : l'image finale
n'embarque qu'un `.env` vide. Les vraies valeurs de prod sont injectées au
runtime par l'orchestration externe (`pi-projects/pokenini-prod-web/`), pas
par ce fichier.

Un remplacement analogue a déjà été tenté sans remplaçant dans
`pokenini-back` (commit `06e0c95`) et a cassé le build, forçant sa
réintroduction 13 jours plus tard (`601118f`). Une suppression sèche n'est
donc pas une option — il faut un remplaçant qui fonctionne.

## Décision

Réutiliser `.env.dev` — déjà tracké, déjà maintenu (les devs en dépendent
pour `make start`), déjà composé de valeurs non-secrètes — comme source du
`.env` factice de build, et supprimer `.env.prod` du dépôt.

Vérifié : les clés de `.env.prod` et `.env.dev` sont strictement identiques
dans `pokenini-web`. Aucune perte de couverture des `%env(...)%` référencés
dans `config/`.

## Changement

Dans `.docker/php/Dockerfile`, stage `php_prod` :

```diff
-RUN mv .env.prod .env && chown www-data:www-data .env
+RUN mv .env.dev .env && chown www-data:www-data .env
```

Rien d'autre ne change dans le Dockerfile : le fichier reste détruit après le
build (`rm .env && touch .env`), l'image finale garde un `.env` vide.

Le `ENV APP_ENV=prod` fixé par Docker avant le `COPY . ./` continue de
prévaloir sur la ligne `APP_ENV=dev` du fichier copié : Symfony Dotenv ne
réécrit jamais une variable d'environnement déjà présente dans le process.
C'est le même mécanisme qui permet déjà à l'orchestration de prod de
surcharger les valeurs bidon aujourd'hui — comportement inchangé, juste
vérifié pour de vrai cette fois (`.env.prod` contenait `APP_ENV=prod`, donc
ce chemin n'était jamais exercé).

Supprimer `.env.prod` du dépôt (`git rm`).


Compagnon obligatoire : `.dockerignore` exclut tous les `.env.*` sauf une
exception nommée (`!.env.prod` auparavant). Sans mettre à jour cette
exception en `!.env.dev`, `.env.dev` resterait exclu du contexte de build et
l'étape `mv .env.dev .env` échouerait ("No such file or directory") — la
panne que ce changement doit justement éviter de reproduire.

`.env.dev` devient donc load-bearing pour le build de release (déclenché
uniquement sur `release`, pas sur chaque push) : supprimer une clé de
`.env.dev` peut casser le prochain build de release, avec un délai de
détection potentiellement long. Un commentaire dans `.dockerignore` et dans
le `Dockerfile` documente cette dépendance pour que personne ne la retire
par inadvertance.

## Documentation à corriger

- `doc/improvement.md:135,147` — mentionne `.env.prod` parmi les fichiers
  contenant des credentials factices ; à adapter puisque le fichier n'existe
  plus.

## Vérification

- `docker build --target php_prod .` et confirmer dans les logs que
  `cache:clear` s'exécute avec l'environnement "prod" (Symfony affiche
  explicitement l'environnement dans son message), pas "dev".
- Confirmer que l'image finale contient toujours un `.env` vide
  (`docker run --rm <image> cat .env`).

## Hors périmètre

- `pokenini-api` et `pokenini-back` ont leur propre spec équivalente,
  appliquée indépendamment (chaque repo a sa propre histoire git).
- Le fichier orphelin `.env.test.local` et les résidus
  `ELECTION_CANDIDATE_COUNT`/`ELECTION_TOP_COUNT` restent hors périmètre, à
  traiter séparément si besoin.
