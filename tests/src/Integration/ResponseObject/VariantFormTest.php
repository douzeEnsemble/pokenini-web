<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject;

use App\ResponseObject\VariantForm;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(VariantForm::class)]
class VariantFormTest extends WebTestCase
{
    public function testDeserialize(): void
    {
        $serializer = static::getContainer()->get(SerializerInterface::class);

        $json = <<<JSON
        {
            "name": "Gender",
            "frenchName": "Genre",
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
