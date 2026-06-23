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
                        "simplified_french_name": "Florizarre"
                    },
                    "national_dex_number": 3,
                    "pokemon_icon": "venusaur-mega"
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
        $this->assertSame(3, $object->getPokemon()->getNationalDexNumber());
        $this->assertSame('Venusaur', $object->getPokemon()->getLabels()->getSimplifiedName());
        $this->assertSame('Mega Florizarre', $object->getPokemon()->getLabels()->getFrenchName());
        $this->assertSame('Florizarre', $object->getPokemon()->getLabels()->getSimplifiedFrenchName());
        $this->assertSame('venusaur-mega', $object->getPokemon()->getIcon());
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
                    "slug": "venusaur-mega",
                    "labels": {
                        "name": "Mega Venusaur",
                        "french_name": "Mega Florizarre",
                        "simplified_name": "Venusaur",
                        "simplified_french_name": "Florizarre"
                    },
                    "national_dex_number": 3,
                    "pokemon_icon": "venusaur-mega"
                },
                "score": {
                    "elo": 1016.5,
                    "significance": true
                }
            }
            JSON;

        $object = $serializer->deserialize($json, TopPokemon::class, 'json');

        $this->assertSame(1016.5, $object->getScore()->getElo());
        $this->assertTrue($object->getScore()->isSignificance());
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
