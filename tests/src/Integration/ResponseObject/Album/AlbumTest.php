<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject\Album;

use App\ResponseObject\Album\Album;
use App\ResponseObject\Album\Dex;
use App\ResponseObject\Album\Pokemon;
use App\ResponseObject\Album\Report;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(Album::class)]
class AlbumTest extends KernelTestCase
{
    public function testDeserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = static::getContainer()->get(SerializerInterface::class);

        $json = (string) file_get_contents('/var/www/html/tests/resources/unit/service/back/pokedex_lite.json');

        /** @var Album $object */
        $object = $serializer->deserialize($json, Album::class, 'json');

        $this->assertInstanceOf(Album::class, $object);
        $this->assertInstanceOf(Dex::class, $object->getDex());
        $this->assertCount(41, $object->getPokemons());
        $this->assertContainsOnlyInstancesOf(Pokemon::class, $object->getPokemons());
        $this->assertInstanceOf(Report::class, $object->getReport());
        $this->assertInstanceOf(Report::class, $object->getFilteredReport());
        $this->assertNotSame($object->getReport(), $object->getFilteredReport());
    }
}
