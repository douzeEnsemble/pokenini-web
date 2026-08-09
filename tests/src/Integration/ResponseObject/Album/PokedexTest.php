<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject\Album;

use App\ResponseObject\Album\Dex;
use App\ResponseObject\Album\Pokedex;
use App\ResponseObject\Common\Pokemon;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(Pokedex::class)]
final class PokedexTest extends KernelTestCase
{
    #[Test]
    public function deserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);
        $filesystem = new Filesystem();

        $json = $filesystem->readFile('/app/tests/resources/unit/service/back/pokedex_lite.json');

        $object = $serializer->deserialize($json, Pokedex::class, 'json');

        $this->assertInstanceOf(Pokedex::class, $object);
        $this->assertInstanceOf(Dex::class, $object->getDex());
        $this->assertCount(41, $object->getPokemons());
        $this->assertContainsOnlyInstancesOf(Pokemon::class, $object->getPokemons());
        $this->assertNotSame($object->getReport(), $object->getFilteredReport());
    }

    #[Test]
    public function deserializeWithNull(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "dex": null,
                "pokemons": [],
                "report": {},
                "filtered_report": {}
            }
            JSON;

        $object = $serializer->deserialize($json, Pokedex::class, 'json');

        $this->assertInstanceOf(Pokedex::class, $object);
        $this->assertNull($object->getDex());
        $this->assertCount(0, $object->getPokemons());
    }
}
