<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject\Label;

use App\ResponseObject\Label\SpecialForm;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(SpecialForm::class)]
class SpecialFormTest extends KernelTestCase
{
    public function testDeserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = static::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "name": "Primal",
                "french_name": "Originelle",
                "slug": "primal"
            }
            JSON;

        $object = $serializer->deserialize($json, SpecialForm::class, 'json');

        $this->assertInstanceOf(SpecialForm::class, $object);
        $this->assertSame('Primal', $object->getName());
        $this->assertSame('Originelle', $object->getFrenchName());
        $this->assertSame('primal', $object->getSlug());
    }
}
