<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject\Album;

use App\ResponseObject\Album\Album;
use App\ResponseObject\Album\Dex;
use App\ResponseObject\Common\Pokemon;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(Album::class)]
final class AlbumTest extends KernelTestCase
{
    #[Test]
    public function deserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = (new Filesystem())->readFile('/app/tests/resources/unit/service/back/album_lite.json');

        $object = $serializer->deserialize($json, Album::class, 'json');

        $this->assertInstanceOf(Album::class, $object);

        $this->assertInstanceOf(Dex::class, $object->getPokedex()->getDex());
        $this->assertCount(41, $object->getPokedex()->getPokemons());
        $this->assertContainsOnlyInstancesOf(Pokemon::class, $object->getPokedex()->getPokemons());
        $this->assertNotSame($object->getPokedex()->getReport(), $object->getPokedex()->getFilteredReport());

        $this->assertSame(
            [
                't1' => [
                    'fire',
                    'water',
                ],
            ],
            $object->getFilters(),
        );
    }

    #[Test]
    public function deserializeWithNull(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "pokedex": {
                    "dex": null,
                    "pokemons": [],
                    "report": {},
                    "filtered_report": {}
                },
                "filters": []
            }
            JSON;

        $object = $serializer->deserialize($json, Album::class, 'json');

        $this->assertInstanceOf(Album::class, $object);

        $this->assertNull($object->getPokedex()->getDex());
        $this->assertCount(0, $object->getPokedex()->getPokemons());

        $this->assertEmpty($object->getFilters());
    }
}
