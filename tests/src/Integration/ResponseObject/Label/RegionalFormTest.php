<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject\Label;

use App\ResponseObject\Label\RegionalForm;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(RegionalForm::class)]
class RegionalFormTest extends KernelTestCase
{
    public function testDeserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = static::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "name": "Alolan",
                "french_name": "d\u0027Alola",
                "slug": "alolan"
            }
            JSON;

        $object = $serializer->deserialize($json, RegionalForm::class, 'json');

        $this->assertInstanceOf(RegionalForm::class, $object);
        $this->assertSame('Alolan', $object->getName());
        $this->assertSame('d\'Alola', $object->getFrenchName());
        $this->assertSame('alolan', $object->getSlug());
    }
}
