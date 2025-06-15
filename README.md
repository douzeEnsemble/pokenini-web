# Pokémon Living/Alternate/Gender Extended Dex!

## To Begin

### Prerequistes

#### NVD API KEY

In order to update OWAP records, you need an NVD API Key. You can request one on https://nvd.nist.gov/developers/request-an-api-key.
Then define it to your env with

```
export NVD_API_KEY=374dc342-3ca3-47a7-8133-794cb256e581
```

### TL;DR

```
make stop build start quality tests
```
or
```
make quality tests
```

### Install

```
make start
```

### Restart

```
make stop start
```

## Tips

### Fake authentication

Id dev environnement only, you can use a fake authenticator to avoid using Oauth2.  
Go to 
* http://localhost/fr/connect/f/c?t=admin to set an *admin* session
* http://localhost/fr/connect/f/c?t=collector to set a *collector* session
* http://localhost/fr/connect/f/c?t=trainer to set a *trainer* session

### Open bash into php  container

```
make sh
```

`exit` in it to quit.

### Composer

To install a package

```
make composer c="require gedmo/doctrine-extensions"
```

### Debug easily

To save html into a file that you can open with your browser

```php
file_put_contents('tests/last.html', $client->getCrawler()->html());
```

### PHPStan baseline

To update the `phpstan-baseline.neon` file

```
make sh
tools/phpstan/vendor/bin/phpstan --generate-baseline --memory-limit=-1
```

### Docker Image build

```shell
docker login --username RenaudDouze --password ghp_token ghcr.io
```

```shell
docker build --target php_prod -f ./.docker/php/Dockerfile -t ghcr.io/douzeensemble/pokenini:latest .
docker push ghcr.io/douzeensemble/pokenini:latest
```
or

```shell
make img-build
```

### Debug 

### Check if json are valid or not

Dans le container (`make sh`)

``` bash
find tests/resources/moco -type f -name "*.json" -exec tools/jsonlint/vendor/bin/jsonlint {} \;
```

## Update moco mock from Pokénin-Api

```
curl -u web:douze "https://localhost:4431/catch_states" --insecure --output tests/resources/moco/Api/catch_states.json --header 'Accept: application/json'
curl -u web:douze "https://localhost:4431/types" --insecure --output tests/resources/moco/Api/types.json --header 'Accept: application/json'
curl -u web:douze "https://localhost:4431/forms/category" --insecure --output tests/resources/moco/Api/category_forms.json --header 'Accept: application/json'
curl -u web:douze "https://localhost:4431/forms/regional" --insecure --output tests/resources/moco/Api/regional_forms.json --header 'Accept: application/json'
curl -u web:douze "https://localhost:4431/forms/special" --insecure --output tests/resources/moco/Api/special_forms.json --header 'Accept: application/json'
curl -u web:douze "https://localhost:4431/forms/variant" --insecure --output tests/resources/moco/Api/variant_forms.json --header 'Accept: application/json'
curl -u web:douze "https://localhost:4431/game_bundles" --insecure --output tests/resources/moco/Api/game_bundles.json --header 'Accept: application/json'


curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/redgreenblueyellow" --insecure --output tests/resources/moco/Api/album/default/redgreenblueyellow.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/goldsilvercrystal" --insecure --output tests/resources/moco/Api/album/default/goldsilvercrystal.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/rubysapphireemerald" --insecure --output tests/resources/moco/Api/album/default/rubysapphireemerald.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/fireredleafgreen" --insecure --output tests/resources/moco/Api/album/default/fireredleafgreen.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/diamondpearlplatinium" --insecure --output tests/resources/moco/Api/album/default/diamondpearlplatinium.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/heartgoldsoulsilver" --insecure --output tests/resources/moco/Api/album/default/heartgoldsoulsilver.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/blackwhite" --insecure --output tests/resources/moco/Api/album/default/blackwhite.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/black2white2" --insecure --output tests/resources/moco/Api/album/default/black2white2.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/xy" --insecure --output tests/resources/moco/Api/album/default/xy.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/omegarubyalphasapphire" --insecure --output tests/resources/moco/Api/album/default/omegarubyalphasapphire.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/sunmoon" --insecure --output tests/resources/moco/Api/album/default/sunmoon.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/ultrasunultramoon" --insecure --output tests/resources/moco/Api/album/default/ultrasunultramoon.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/letsgopikachuletsgoeevee" --insecure --output tests/resources/moco/Api/album/default/letsgopikachuletsgoeevee.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/swordshield" --insecure --output tests/resources/moco/Api/album/default/swordshield.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/brilliantdiamondshiningpearl" --insecure --output tests/resources/moco/Api/album/default/brilliantdiamondshiningpearl.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/legendarceus" --insecure --output tests/resources/moco/Api/album/default/legendarceus.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/home" --insecure --output tests/resources/moco/Api/album/default/home.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/homeshiny" --insecure --output tests/resources/moco/Api/album/default/homeshiny.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/homepokemongo" --insecure --output tests/resources/moco/Api/album/default/homepokemongo.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/alpha" --insecure --output tests/resources/moco/Api/album/default/alpha.json
```
