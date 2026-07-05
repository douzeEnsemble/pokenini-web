<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject\Election;

use App\ResponseObject\Election\TopPokemon;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(TopPokemon::class)]
final class TopPokemonTest extends KernelTestCase
{
    public function testDeserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "pokemon": {
                    "slug": "venusaur-mega",
                    "labels": {
                        "name": "Mega Venusaur",
                        "french_name": "Mega Florizarre",
                        "simplified_name": "Venusaur",
                        "simplified_french_name": "Florizarre",
                        "forms_label": "Mega",
                        "forms_french_label": "Mega"
                    },
                    "national_dex_number": 3,
                    "regional_dex_number": null,
                    "icon": "venusaur-mega",
                    "family_order": 4,
                    "family_lead": { "slug": "bulbasaur" },
                    "original_game_bundle": { "slug": "redgreenblueyellow" },
                    "order_number": "9999-0003-004",
                    "game_bundles": {
                        "normal": [{ "slug": "redgreenblueyellow" }],
                        "shiny": []
                    }
                },
                "forms": {
                    "category": null,
                    "regional": null,
                    "special": { "slug": "mega", "name": "Mega", "french_name": "Mega" },
                    "variant": null
                },
                "types": {
                    "primary": { "slug": "grass", "name": "Grass", "french_name": "Plante", "color": "#78C850" },
                    "secondary": { "slug": "poison", "name": "Poison", "french_name": "Poison", "color": "#A040A0" }
                },
                "score": {
                    "elo": 1000,
                    "significance": false
                }
            }
            JSON;

        $object = $serializer->deserialize($json, TopPokemon::class, 'json');

        $this->assertSame('venusaur-mega', $object->getPokemon()->getSlug());
        $this->assertSame('Mega Venusaur', $object->getPokemon()->getLabels()->getName());
        $this->assertSame('Mega Florizarre', $object->getPokemon()->getLabels()->getFrenchName());
        $this->assertSame('Venusaur', $object->getPokemon()->getLabels()->getSimplifiedName());
        $this->assertSame('Florizarre', $object->getPokemon()->getLabels()->getSimplifiedFrenchName());
        $this->assertSame('Mega', $object->getPokemon()->getLabels()->getFormsLabel());
        $this->assertSame('Mega', $object->getPokemon()->getLabels()->getFormsFrenchLabel());
        $this->assertSame(3, $object->getPokemon()->getNationalDexNumber());
        $this->assertNull($object->getPokemon()->getRegionalDexNumber());
        $this->assertSame('venusaur-mega', $object->getPokemon()->getIcon());
        $this->assertSame(4, $object->getPokemon()->getFamilyOrder());
        $this->assertNotNull($object->getPokemon()->getFamilyLead());
        $this->assertSame('bulbasaur', $object->getPokemon()->getFamilyLead()->getSlug());
        $this->assertNotNull($object->getPokemon()->getOriginalGameBundle());
        $this->assertSame('redgreenblueyellow', $object->getPokemon()->getOriginalGameBundle()->getSlug());
        $this->assertSame('9999-0003-004', $object->getPokemon()->getOrderNumber());
        $this->assertCount(1, $object->getPokemon()->getGameBundles()->getNormal());
        $this->assertCount(0, $object->getPokemon()->getGameBundles()->getShiny());
        $this->assertNotNull($object->getForms());
        $this->assertNull($object->getForms()->getCategory());
        $this->assertNotNull($object->getForms()->getSpecial());
        $this->assertSame('mega', $object->getForms()->getSpecial()->getSlug());
        $this->assertNotNull($object->getTypes()->getPrimary());
        $this->assertSame('grass', $object->getTypes()->getPrimary()->getSlug());
        $this->assertNotNull($object->getTypes()->getSecondary());
        $this->assertSame('poison', $object->getTypes()->getSecondary()->getSlug());
        $this->assertSame(1000.0, $object->getScore()->getElo());
        $this->assertFalse($object->getScore()->isSignificance());
    }

    public function testDeserializeSignificant(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "pokemon": {
                    "slug": "blastoise-mega",
                    "labels": {
                        "name": "Mega Blastoise",
                        "french_name": "Mega Tortank",
                        "simplified_name": "Blastoise",
                        "simplified_french_name": "Tortank",
                        "forms_label": "Mega",
                        "forms_french_label": "Mega"
                    },
                    "national_dex_number": 9,
                    "regional_dex_number": null,
                    "icon": "blastoise-mega",
                    "family_order": 3,
                    "family_lead": { "slug": "squirtle" },
                    "original_game_bundle": null,
                    "order_number": null,
                    "game_bundles": { "normal": [], "shiny": [] }
                },
                "forms": {
                    "category": null,
                    "regional": null,
                    "special": { "slug": "mega", "name": "Mega", "french_name": "Mega" },
                    "variant": null
                },
                "types": { "primary": null, "secondary": null },
                "score": { "elo": 1016.5, "significance": true }
            }
            JSON;

        $object = $serializer->deserialize($json, TopPokemon::class, 'json');

        $this->assertNull($object->getPokemon()->getOriginalGameBundle());
        $this->assertNull($object->getPokemon()->getOrderNumber());
        $this->assertNull($object->getTypes()->getPrimary());
        $this->assertNull($object->getTypes()->getSecondary());
        $this->assertSame(1016.5, $object->getScore()->getElo());
        $this->assertTrue($object->getScore()->isSignificance());
    }

    public function testDeserializeNullForms(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "pokemon": {
                    "slug": "pikachu",
                    "labels": {
                        "name": "Pikachu",
                        "french_name": "Pikachu",
                        "simplified_name": "Pikachu",
                        "simplified_french_name": "Pikachu",
                        "forms_label": null,
                        "forms_french_label": null
                    },
                    "national_dex_number": 25,
                    "regional_dex_number": null,
                    "icon": "pikachu",
                    "family_order": 1,
                    "family_lead": null,
                    "original_game_bundle": null,
                    "order_number": null,
                    "game_bundles": { "normal": [], "shiny": [] }
                },
                "forms": null,
                "types": { "primary": { "slug": "electric", "name": "Electric", "french_name": "Electrik", "color": "#FFCC33" }, "secondary": null },
                "score": { "elo": 1000.0, "significance": false }
            }
            JSON;

        $object = $serializer->deserialize($json, TopPokemon::class, 'json');

        $this->assertNull($object->getForms());
        $this->assertNull($object->getPokemon()->getFamilyLead());
        $this->assertNull($object->getPokemon()->getLabels()->getFormsLabel());
        $this->assertNull($object->getPokemon()->getLabels()->getFormsFrenchLabel());
    }

    public function testDeserializeArray(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = (new Filesystem())->readFile('/app/tests/resources/integration/back/election_mega_top_5.json');

        /** @var TopPokemon[] $objects */
        $objects = $serializer->deserialize($json, TopPokemon::class.'[]', 'json');

        $this->assertCount(5, $objects);
        $this->assertContainsOnlyInstancesOf(TopPokemon::class, $objects);

        $this->assertNotNull($objects[0]->getPokemon()->getFamilyLead());
        $this->assertSame('bulbasaur', $objects[0]->getPokemon()->getFamilyLead()->getSlug());
        $this->assertNotEmpty($objects[0]->getPokemon()->getGameBundles()->getNormal());
        $this->assertNotNull($objects[0]->getTypes()->getPrimary());
        $this->assertNotNull($objects[0]->getForms());
    }

    public function testDeserializeEmptyArray(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            []
            JSON;

        /** @var TopPokemon[] $objects */
        $objects = $serializer->deserialize($json, TopPokemon::class.'[]', 'json');

        $this->assertCount(0, $objects);
    }
}
