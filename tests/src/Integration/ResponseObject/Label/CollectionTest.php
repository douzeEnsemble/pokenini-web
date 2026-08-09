<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject\Label;

use App\ResponseObject\Label\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(Collection::class)]
final class CollectionTest extends KernelTestCase
{
    #[Test]
    public function deserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "name": "Sword, Shield - Dynamax Adventures bosses",
                "french_name": "Sword, Shield - Boss des exp\u00e9ditions Dynamax",
                "slug": "swshdynamaxadventuresbosses",
                "order_number": 11
            }
            JSON;

        $object = $serializer->deserialize($json, Collection::class, 'json');

        $this->assertInstanceOf(Collection::class, $object);
        $this->assertSame('Sword, Shield - Dynamax Adventures bosses', $object->getName());
        $this->assertSame('Sword, Shield - Boss des expéditions Dynamax', $object->getFrenchName());
        $this->assertSame('swshdynamaxadventuresbosses', $object->getSlug());
        $this->assertSame(11, $object->getOrderNumber());
    }
}
