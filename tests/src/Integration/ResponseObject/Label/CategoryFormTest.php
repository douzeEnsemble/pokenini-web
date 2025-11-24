<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject\Label;

use App\ResponseObject\Label\CategoryForm;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(CategoryForm::class)]
class CategoryFormTest extends KernelTestCase
{
    public function testDeserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = static::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "name": "Starter",
                "french_name": "de D\u00e9part",
                "slug": "starter"
            }
            JSON;

        /** @var CategoryForm $object */
        $object = $serializer->deserialize($json, CategoryForm::class, 'json');

        $this->assertInstanceOf(CategoryForm::class, $object);
        $this->assertSame('Starter', $object->getName());
        $this->assertSame('de Départ', $object->getFrenchName());
        $this->assertSame('starter', $object->getSlug());
    }
}
