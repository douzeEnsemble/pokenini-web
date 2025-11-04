<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject;

use App\ResponseObject\RegionalForm;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(RegionalForm::class)]
class RegionalFormTest extends WebTestCase
{
    public function testDeserialize(): void
    {
        $serializer = static::getContainer()->get(SerializerInterface::class);

        $json = <<<JSON
        {
            "name": "Alolan",
            "frenchName": "d\u0027Alola",
            "slug": "alolan"
        }
        JSON;

        /** @var RegionalForm $object */
        $object = $serializer->deserialize($json, RegionalForm::class, 'json');

        $this->assertInstanceOf(RegionalForm::class, $object);
        $this->assertSame('Alolan', $object->getName());
        $this->assertSame('d\'Alola', $object->getFrenchName());
        $this->assertSame('alolan', $object->getSlug());
    }
}
