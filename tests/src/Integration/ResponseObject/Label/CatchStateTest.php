<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject\Label;

use App\ResponseObject\Label\CatchState;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(CatchState::class)]
final class CatchStateTest extends KernelTestCase
{
    #[Test]
    public function deserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "name": "No",
                "french_name": "Non",
                "slug": "no",
                "color": "#e57373",
                "text_color": "#2a0e0e"
            }
            JSON;

        $object = $serializer->deserialize($json, CatchState::class, 'json');

        $this->assertInstanceOf(CatchState::class, $object);
        $this->assertSame('No', $object->getName());
        $this->assertSame('Non', $object->getFrenchName());
        $this->assertSame('no', $object->getSlug());
        $this->assertSame('#e57373', $object->getColor());
        $this->assertSame('#2a0e0e', $object->getTextColor());
    }

    #[Test]
    public function deserializeWithoutATextColor(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = '{"name": "No", "french_name": "Non", "slug": "no", "color": "#e57373"}';

        $object = $serializer->deserialize($json, CatchState::class, 'json');

        $this->assertInstanceOf(CatchState::class, $object);
        $this->assertNull($object->getTextColor());
    }
}
