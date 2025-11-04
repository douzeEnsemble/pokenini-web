<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject;

use App\ResponseObject\Type;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(Type::class)]
class TypeTest extends WebTestCase
{
    public function testDeserialize(): void
    {
        $serializer = static::getContainer()->get(SerializerInterface::class);

        $json = <<<JSON
        {
            "name": "Fighting",
            "frenchName": "Combat",
            "slug": "fighting",
            "color": "#cf4069"
        }
        JSON;

        /** @var Type $object */
        $object = $serializer->deserialize($json, Type::class, 'json');

        $this->assertInstanceOf(Type::class, $object);
        $this->assertSame('Fighting', $object->getName());
        $this->assertSame('Combat', $object->getFrenchName());
        $this->assertSame('fighting', $object->getSlug());
        $this->assertSame('#cf4069', $object->getColor());
    }
}
