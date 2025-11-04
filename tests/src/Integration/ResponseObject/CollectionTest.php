<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject;

use App\ResponseObject\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(Collection::class)]
class CollectionTest extends WebTestCase
{
    public function testDeserialize(): void
    {
        $serializer = static::getContainer()->get(SerializerInterface::class);

        $json = <<<JSON
        {
            "name": "Sword, Shield - Dynamax Adventures bosses",
            "frenchName": "Sword, Shield - Boss des exp\u00e9ditions Dynamax",
            "slug": "swshdynamaxadventuresbosses",
            "orderNumber": 11
        }
        JSON;

        /** @var Collection $object */
        $object = $serializer->deserialize($json, Collection::class, 'json');

        $this->assertInstanceOf(Collection::class, $object);
        $this->assertSame('Sword, Shield - Dynamax Adventures bosses', $object->getName());
        $this->assertSame('Sword, Shield - Boss des expéditions Dynamax', $object->getFrenchName());
        $this->assertSame('swshdynamaxadventuresbosses', $object->getSlug());
        $this->assertSame(11, $object->getOrderNumber());
    }
}
