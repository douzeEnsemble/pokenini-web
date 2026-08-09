<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject\Album;

use App\ResponseObject\Album\Dex;
use App\ResponseObject\Album\DexFlags;
use App\ResponseObject\Album\DexRegion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(Dex::class)]
final class DexTest extends KernelTestCase
{
    #[Test]
    public function deserialize(): void
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
                "flags": {
                    "is_shiny": false,
                    "is_private": false,
                    "is_on_home": false,
                    "is_display_form": true,
                    "is_released": true,
                    "is_premium": false,
                    "is_custom": false
                },
                "display_template": "box",
                "region": { "name": "Kanto", "french_name": "Kan-to" },
                "selection_rule": "",
                "description": "A small pokedex",
                "french_description": "Un petit pokédex",
                "version": "0.0"
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
        $this->assertSame('0.0', $object->getVersion());
        $this->assertTrue($object->isReleased());
        $this->assertFalse($object->isPremium());
        $this->assertFalse($object->isCustom());
        $this->assertFalse($object->isOnHome());
        $this->assertSame('', $object->getSelectionRule());
        // @phpstan-ignore method.alreadyNarrowedType
        $this->assertInstanceOf(DexFlags::class, $object->getFlags());
        $this->assertInstanceOf(DexRegion::class, $object->getRegion());
    }

    #[Test]
    public function deserializeWithNull(): void
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
                "flags": {
                    "is_shiny": false,
                    "is_private": false,
                    "is_on_home": false,
                    "is_display_form": true,
                    "is_released": true,
                    "is_premium": false,
                    "is_custom": false
                },
                "display_template": null,
                "region": null,
                "selection_rule": "",
                "description": "A small pokedex",
                "french_description": "Un petit pokédex",
                "version": "0"
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
        $this->assertNull($object->getRegionName());
        $this->assertNull($object->getRegionFrenchName());
        $this->assertSame('A small pokedex', $object->getDescription());
        $this->assertSame('Un petit pokédex', $object->getFrenchDescription());
        $this->assertSame('0', $object->getVersion());
        $this->assertTrue($object->isReleased());
        $this->assertFalse($object->isPremium());
        $this->assertFalse($object->isCustom());
        $this->assertFalse($object->isOnHome());
        $this->assertSame('', $object->getSelectionRule());
        // @phpstan-ignore method.alreadyNarrowedType
        $this->assertInstanceOf(DexFlags::class, $object->getFlags());
        $this->assertNull($object->getRegion());
    }
}
