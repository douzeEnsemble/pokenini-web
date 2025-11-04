<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject;

use App\ResponseObject\GameBundle;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(GameBundle::class)]
class GameBundleTest extends WebTestCase
{
    public function testDeserialize(): void
    {
        $serializer = static::getContainer()->get(SerializerInterface::class);

        $json = <<<JSON
        {
            "name": "Red, Green, Blue, Yellow",
            "frenchName": "Rouge, Vert, Bleu, Jaune",
            "slug": "redgreenblueyellow",
            "generationSlug": "1"
        }
        JSON;

        /** @var GameBundle $object */
        $object = $serializer->deserialize($json, GameBundle::class, 'json');

        $this->assertInstanceOf(GameBundle::class, $object);
        $this->assertSame('Red, Green, Blue, Yellow', $object->getName());
        $this->assertSame('Rouge, Vert, Bleu, Jaune', $object->getFrenchName());
        $this->assertSame('redgreenblueyellow', $object->getSlug());
        $this->assertSame('1', $object->getGenerationSlug());
    }
}
