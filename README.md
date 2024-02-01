# Pokénini

## To Begin

### TL;DR

```
make stop start quality tests
```

or

```
make quality tests
```

### Install

```
make build start
```

### Restart

```
make stop start
```

## Tips

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
file_put_contents('tests/last.html', $crawler->html());
```

## Update moco mock from Pokénin-Api

```
curl -u web:douze "https://localhost:4431/catch_states" --insecure --output tests/resources/moco/catch_states.json --header 'Accept: application/json'
curl -u web:douze "https://localhost:4431/types" --insecure --output tests/resources/moco/types.json --header 'Accept: application/json'
curl -u web:douze "https://localhost:4431/forms/category" --insecure --output tests/resources/moco/category_forms.json --header 'Accept: application/json'
curl -u web:douze "https://localhost:4431/forms/regional" --insecure --output tests/resources/moco/regional_forms.json --header 'Accept: application/json'
curl -u web:douze "https://localhost:4431/forms/special" --insecure --output tests/resources/moco/special_forms.json --header 'Accept: application/json'
curl -u web:douze "https://localhost:4431/forms/variant" --insecure --output tests/resources/moco/variant_forms.json --header 'Accept: application/json'


curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/redgreenblueyellow" --insecure --output tests/resources/moco/album/default/redgreenblueyellow.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/goldsilvercrystal" --insecure --output tests/resources/moco/album/default/goldsilvercrystal.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/rubysapphireemerald" --insecure --output tests/resources/moco/album/default/rubysapphireemerald.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/fireredleafgreen" --insecure --output tests/resources/moco/album/default/fireredleafgreen.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/diamondpearlplatinium" --insecure --output tests/resources/moco/album/default/diamondpearlplatinium.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/heartgoldsoulsilver" --insecure --output tests/resources/moco/album/default/heartgoldsoulsilver.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/blackwhite" --insecure --output tests/resources/moco/album/default/blackwhite.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/black2white2" --insecure --output tests/resources/moco/album/default/black2white2.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/xy" --insecure --output tests/resources/moco/album/default/xy.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/omegarubyalphasapphire" --insecure --output tests/resources/moco/album/default/omegarubyalphasapphire.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/sunmoon" --insecure --output tests/resources/moco/album/default/sunmoon.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/ultrasunultramoon" --insecure --output tests/resources/moco/album/default/ultrasunultramoon.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/letsgopikachuletsgoeevee" --insecure --output tests/resources/moco/album/default/letsgopikachuletsgoeevee.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/swordshield" --insecure --output tests/resources/moco/album/default/swordshield.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/brilliantdiamondshiningpearl" --insecure --output tests/resources/moco/album/default/brilliantdiamondshiningpearl.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/legendarceus" --insecure --output tests/resources/moco/album/default/legendarceus.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/home" --insecure --output tests/resources/moco/album/default/home.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/homeshiny" --insecure --output tests/resources/moco/album/default/homeshiny.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/homepokemongo" --insecure --output tests/resources/moco/album/default/homepokemongo.json
curl -u web:douze "https://localhost:4431/album/7b52009b64fd0a2a49e6d8a939753077792b0554/alpha" --insecure --output tests/resources/moco/album/default/alpha.json
```

### Check if json are valid or not

Dans le container (`make sh`)

``` bash
find tests/resources/moco -type f -name "*.json" -exec vendor/bin/jsonlint {} \;
    ```