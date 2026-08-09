<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject\Election;

use App\ResponseObject\Common\Pokemon;
use App\ResponseObject\Election\ElectionList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(ElectionList::class)]
final class ElectionListTest extends KernelTestCase
{
    #[Test]
    public function deserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = (new Filesystem())->readFile('/app/tests/resources/unit/service/back/pokemons_all_b_12.json');

        $object = $serializer->deserialize($json, ElectionList::class, 'json');

        $this->assertSame('vote', $object->getType());
        $this->assertCount(12, $object->getItems());
        $this->assertContainsOnlyInstancesOf(Pokemon::class, $object->getItems());
    }

    #[Test]
    public function deserializeWithNull(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "type": "pick",
                "items": []
            }
            JSON;

        $object = $serializer->deserialize($json, ElectionList::class, 'json');

        $this->assertSame('pick', $object->getType());
        $this->assertCount(0, $object->getItems());
    }
}
