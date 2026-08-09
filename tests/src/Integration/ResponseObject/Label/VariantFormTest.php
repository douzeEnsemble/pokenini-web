<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject\Label;

use App\ResponseObject\Label\VariantForm;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(VariantForm::class)]
final class VariantFormTest extends KernelTestCase
{
    #[Test]
    public function deserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "name": "Gender",
                "french_name": "Genre",
                "slug": "gender"
            }
            JSON;

        $object = $serializer->deserialize($json, VariantForm::class, 'json');

        $this->assertInstanceOf(VariantForm::class, $object);
        $this->assertSame('Gender', $object->getName());
        $this->assertSame('Genre', $object->getFrenchName());
        $this->assertSame('gender', $object->getSlug());
    }
}
