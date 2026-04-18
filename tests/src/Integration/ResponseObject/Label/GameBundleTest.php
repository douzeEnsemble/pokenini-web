<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject\Label;

use App\ResponseObject\Label\GameBundle;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(GameBundle::class)]
final class GameBundleTest extends KernelTestCase
{
    public function testDeserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "name": "Red, Green, Blue, Yellow",
                "french_name": "Rouge, Vert, Bleu, Jaune",
                "slug": "redgreenblueyellow",
                "generation_slug": "1"
            }
            JSON;

        $object = $serializer->deserialize($json, GameBundle::class, 'json');

        $this->assertInstanceOf(GameBundle::class, $object);
        $this->assertSame('Red, Green, Blue, Yellow', $object->getName());
        $this->assertSame('Rouge, Vert, Bleu, Jaune', $object->getFrenchName());
        $this->assertSame('redgreenblueyellow', $object->getSlug());
        $this->assertSame('1', $object->getGenerationSlug());
    }
}
