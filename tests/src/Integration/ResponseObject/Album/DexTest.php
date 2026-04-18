<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject\Album;

use App\ResponseObject\Album\Dex;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(Dex::class)]
final class DexTest extends KernelTestCase
{
    public function testDeserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "slug": "lite",
                "original_slug": "litelite",
                "name": "Demo light",
                "french_name": "Démo, extrait",
                "is_shiny": false,
                "is_private": false,
                "is_display_form": true,
                "display_template": "box",
                "region_name": "Kanto",
                "region_french_name": "Kan-to",
                "description": "A small pokedex",
                "french_description": "Un petit pokédex",
                "version": 0,
                "is_released": true,
                "is_premium": false,
                "is_custom": false
            }
            JSON;

        $object = $serializer->deserialize($json, Dex::class, 'json');

        $this->assertInstanceOf(Dex::class, $object);
        $this->assertSame('lite', $object->getSlug());
        $this->assertSame('litelite', $object->getOriginalSlug());
        $this->assertSame('Demo light', $object->getName());
        $this->assertSame('Démo, extrait', $object->getFrenchName());
        $this->assertFalse($object->isShiny());
        $this->assertFalse($object->isPrivate());
        $this->assertTrue($object->isDisplayForm());
        $this->assertSame('box', $object->getDisplayTemplate());
        $this->assertSame('Kanto', $object->getRegionName());
        $this->assertSame('Kan-to', $object->getRegionFrenchName());
        $this->assertSame('A small pokedex', $object->getDescription());
        $this->assertSame('Un petit pokédex', $object->getFrenchDescription());
        $this->assertSame(0.0, $object->getVersion());
        $this->assertTrue($object->isReleased());
        $this->assertFalse($object->isPremium());
        $this->assertFalse($object->isCustom());
    }

    public function testDeserializeWithNull(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "slug": "lite",
                "original_slug": "litelite",
                "name": "Demo light",
                "french_name": "Démo, extrait",
                "is_shiny": false,
                "is_private": false,
                "is_display_form": true,
                "display_template": null,
                "region_name": "Kanto",
                "region_french_name": "Kan-to",
                "description": "A small pokedex",
                "french_description": "Un petit pokédex",
                "version": 0,
                "is_released": true,
                "is_premium": false,
                "is_custom": false
            }
            JSON;

        $object = $serializer->deserialize($json, Dex::class, 'json');

        $this->assertInstanceOf(Dex::class, $object);
        $this->assertSame('lite', $object->getSlug());
        $this->assertSame('litelite', $object->getOriginalSlug());
        $this->assertSame('Demo light', $object->getName());
        $this->assertSame('Démo, extrait', $object->getFrenchName());
        $this->assertFalse($object->isShiny());
        $this->assertFalse($object->isPrivate());
        $this->assertTrue($object->isDisplayForm());
        $this->assertNull($object->getDisplayTemplate());
        $this->assertSame('Kanto', $object->getRegionName());
        $this->assertSame('Kan-to', $object->getRegionFrenchName());
        $this->assertSame('A small pokedex', $object->getDescription());
        $this->assertSame('Un petit pokédex', $object->getFrenchDescription());
        $this->assertSame(0.0, $object->getVersion());
        $this->assertTrue($object->isReleased());
        $this->assertFalse($object->isPremium());
        $this->assertFalse($object->isCustom());
    }
}
