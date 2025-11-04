<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject;

use App\ResponseObject\SpecialForm;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(SpecialForm::class)]
class SpecialFormTest extends WebTestCase
{
    public function testDeserialize(): void
    {
        $serializer = static::getContainer()->get(SerializerInterface::class);

        $json = <<<JSON
        {
            "name": "Primal",
            "frenchName": "Originelle",
            "slug": "primal"
        }
        JSON;

        /** @var SpecialForm $object */
        $object = $serializer->deserialize($json, SpecialForm::class, 'json');

        $this->assertInstanceOf(SpecialForm::class, $object);
        $this->assertSame('Primal', $object->getName());
        $this->assertSame('Originelle', $object->getFrenchName());
        $this->assertSame('primal', $object->getSlug());
    }
}
