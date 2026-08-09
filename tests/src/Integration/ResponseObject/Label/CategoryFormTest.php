<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject\Label;

use App\ResponseObject\Label\CategoryForm;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(CategoryForm::class)]
final class CategoryFormTest extends KernelTestCase
{
    #[Test]
    public function deserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "name": "Starter",
                "french_name": "de D\u00e9part",
                "slug": "starter"
            }
            JSON;

        $object = $serializer->deserialize($json, CategoryForm::class, 'json');

        $this->assertInstanceOf(CategoryForm::class, $object);
        $this->assertSame('Starter', $object->getName());
        $this->assertSame('de Départ', $object->getFrenchName());
        $this->assertSame('starter', $object->getSlug());
    }
}
