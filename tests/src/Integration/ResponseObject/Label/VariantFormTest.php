<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject\Label;

use App\ResponseObject\Label\VariantForm;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(VariantForm::class)]
class VariantFormTest extends KernelTestCase
{
    public function testDeserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = static::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "name": "Gender",
                "french_name": "Genre",
                "slug": "gender"
            }
            JSON;

        /** @var VariantForm $object */
        $object = $serializer->deserialize($json, VariantForm::class, 'json');

        $this->assertInstanceOf(VariantForm::class, $object);
        $this->assertSame('Gender', $object->getName());
        $this->assertSame('Genre', $object->getFrenchName());
        $this->assertSame('gender', $object->getSlug());
    }
}
