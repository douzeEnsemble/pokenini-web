<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject\Election;

use App\ResponseObject\Common\Pokemon;
use App\ResponseObject\Election\ElectionList;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(ElectionList::class)]
class ElectionListTest extends KernelTestCase
{
    public function testDeserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = static::getContainer()->get(SerializerInterface::class);

        $json = (string) file_get_contents('/var/www/html/tests/resources/unit/service/back/pokemons_all_b_12.json');

        /** @var ElectionList $object */
        $object = $serializer->deserialize($json, ElectionList::class, 'json');

        $this->assertSame('vote', $object->getType());
        $this->assertCount(12, $object->getItems());
        $this->assertContainsOnlyInstancesOf(Pokemon::class, $object->getItems());
    }

    public function testDeserializeWithNull(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = static::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "type": "pick",
                "items": []
            }
            JSON;

        /** @var ElectionList $object */
        $object = $serializer->deserialize($json, ElectionList::class, 'json');

        $this->assertSame('pick', $object->getType());
        $this->assertCount(0, $object->getItems());
    }
}
