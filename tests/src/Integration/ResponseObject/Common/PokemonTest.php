<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject\Common;

use App\ResponseObject\Common\Pokemon;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(Pokemon::class)]
final class PokemonTest extends KernelTestCase
{
    public function testDeserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "pokemon": {
                    "slug": "charizard-mega-y",
                    "name": "Mega Charizard Y",
                    "french_name": "Méga Dracaufeu Y",
                    "national_dex_number": 6,
                    "regional_dex_number": 9854,
                    "simplified_name": "Charizard",
                    "forms_label": "Mega Y",
                    "simplified_french_name": "Dracaufeu",
                    "forms_french_label": "Méga Y",
                    "icon": "charizard-mega-y",
                    "family_order": 4,
                    "family_lead": { "slug": "charmander" },
                    "original_game_bundle": { "slug": "xy" },
                    "order_number": "9999-0006-004",
                    "game_bundles": [{ "slug": "xy" }, { "slug": "omegarubyalphasapphire" }],
                    "game_bundles_shiny": [{ "slug": "xy" }]
                },
                "catch_state": {
                    "slug": "yes",
                    "name": "Yes",
                    "french_name": "Oui",
                    "color": "#66bb6a"
                },
                "forms": {
                    "category": { "slug": "starter", "name": "Starter", "french_name": "de Départ" },
                    "regional": { "slug": "kantonian", "name": "Kantonian", "french_name": "Kantien" },
                    "special": { "slug": "mega", "name": "Mega", "french_name": "Mega" },
                    "variant": { "slug": "battle", "name": "Battle", "french_name": "Combat" }
                },
                "types": {
                    "primary": { "slug": "fire", "name": "Fire", "french_name": "Feu", "color": "#FF9D55" },
                    "secondary": { "slug": "flying", "name": "Flying", "french_name": "Vol", "color": "#8EA9DF" }
                }
            }
            JSON;

        $object = $serializer->deserialize($json, Pokemon::class, 'json');

        $this->assertInstanceOf(Pokemon::class, $object);
        $this->assertSame('charizard-mega-y', $object->getPokemonSlug());
        $this->assertSame('Mega Charizard Y', $object->getPokemonName());
        $this->assertSame(6, $object->getPokemonNationalDexNumber());
        $this->assertSame('Charizard', $object->getPokemonSimplifiedName());
        $this->assertSame('Mega Y', $object->getPokemonFormsLabel());
        $this->assertSame('Méga Dracaufeu Y', $object->getPokemonFrenchName());
        $this->assertSame('Dracaufeu', $object->getPokemonSimplifiedFrenchName());
        $this->assertSame('Méga Y', $object->getPokemonFormsFrenchLabel());
        $this->assertSame('charizard-mega-y', $object->getPokemonIcon());
        $this->assertSame(4, $object->getPokemonFamilyOrder());
        $this->assertSame('charmander', $object->getFamilyLeadSlug());
        $this->assertSame('starter', $object->getCategoryFormSlug());
        $this->assertSame('Starter', $object->getCategoryFormName());
        $this->assertSame('kantonian', $object->getRegionalFormSlug());
        $this->assertSame('Kantonian', $object->getRegionalFormName());
        $this->assertSame('mega', $object->getSpecialFormSlug());
        $this->assertSame('Mega', $object->getSpecialFormName());
        $this->assertSame('battle', $object->getVariantFormSlug());
        $this->assertSame('Battle', $object->getVariantFormName());
        $this->assertSame('yes', $object->getCatchStateSlug());
        $this->assertSame('Yes', $object->getCatchStateName());
        $this->assertSame('Oui', $object->getCatchStateFrenchName());
        $this->assertSame(9854, $object->getPokemonRegionalDexNumber());
        $this->assertSame('fire', $object->getPrimaryTypeSlug());
        $this->assertSame('Fire', $object->getPrimaryTypeName());
        $this->assertSame('Feu', $object->getPrimaryTypeFrenchName());
        $this->assertSame('flying', $object->getSecondaryTypeSlug());
        $this->assertSame('Flying', $object->getSecondaryTypeName());
        $this->assertSame('Vol', $object->getSecondaryTypeFrenchName());
        $this->assertSame('9999-0006-004', $object->getPokemonOrderNumber());
    }

    public function testDeserializeWithNullValues(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "pokemon": {
                    "slug": "charizard-mega-y",
                    "name": "Mega Charizard Y",
                    "french_name": "Méga Dracaufeu Y",
                    "national_dex_number": 6,
                    "regional_dex_number": null,
                    "simplified_name": "Charizard",
                    "forms_label": "Mega Y",
                    "simplified_french_name": "Dracaufeu",
                    "forms_french_label": "Méga Y",
                    "icon": "charizard-mega-y",
                    "family_order": 4,
                    "family_lead": { "slug": "charmander" },
                    "original_game_bundle": { "slug": "xy" },
                    "order_number": "9999-0006-004",
                    "game_bundles": [{ "slug": "xy" }],
                    "game_bundles_shiny": []
                },
                "catch_state": null,
                "forms": {
                    "category": null,
                    "regional": null,
                    "special": { "slug": "mega", "name": "Mega", "french_name": "Mega" },
                    "variant": null
                },
                "types": {
                    "primary": { "slug": "fire", "name": "Fire", "french_name": "Feu", "color": "#FF9D55" },
                    "secondary": null
                }
            }
            JSON;

        $object = $serializer->deserialize($json, Pokemon::class, 'json');

        $this->assertInstanceOf(Pokemon::class, $object);
        $this->assertSame('charizard-mega-y', $object->getPokemonSlug());
        $this->assertSame('Mega Charizard Y', $object->getPokemonName());
        $this->assertSame(6, $object->getPokemonNationalDexNumber());
        $this->assertSame('Charizard', $object->getPokemonSimplifiedName());
        $this->assertSame('Mega Y', $object->getPokemonFormsLabel());
        $this->assertSame('Méga Dracaufeu Y', $object->getPokemonFrenchName());
        $this->assertSame('Dracaufeu', $object->getPokemonSimplifiedFrenchName());
        $this->assertSame('Méga Y', $object->getPokemonFormsFrenchLabel());
        $this->assertSame('charizard-mega-y', $object->getPokemonIcon());
        $this->assertSame(4, $object->getPokemonFamilyOrder());
        $this->assertSame('charmander', $object->getFamilyLeadSlug());
        $this->assertNull($object->getCategoryFormSlug());
        $this->assertNull($object->getCategoryFormName());
        $this->assertNull($object->getRegionalFormSlug());
        $this->assertNull($object->getRegionalFormName());
        $this->assertSame('mega', $object->getSpecialFormSlug());
        $this->assertSame('Mega', $object->getSpecialFormName());
        $this->assertNull($object->getVariantFormSlug());
        $this->assertNull($object->getVariantFormName());
        $this->assertNull($object->getCatchStateSlug());
        $this->assertNull($object->getCatchStateName());
        $this->assertNull($object->getCatchStateFrenchName());
        $this->assertNull($object->getPokemonRegionalDexNumber());
        $this->assertSame('fire', $object->getPrimaryTypeSlug());
        $this->assertSame('Fire', $object->getPrimaryTypeName());
        $this->assertSame('Feu', $object->getPrimaryTypeFrenchName());
        $this->assertNull($object->getSecondaryTypeSlug());
        $this->assertNull($object->getSecondaryTypeName());
        $this->assertNull($object->getSecondaryTypeFrenchName());
        $this->assertSame('9999-0006-004', $object->getPokemonOrderNumber());
    }

    public function testDeserializeWithNullForms(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "pokemon": {
                    "slug": "bulbasaur",
                    "name": "Bulbasaur",
                    "french_name": "Bulbizarre",
                    "national_dex_number": 1,
                    "regional_dex_number": null,
                    "simplified_name": "Bulbasaur",
                    "forms_label": "",
                    "simplified_french_name": "Bulbizarre",
                    "forms_french_label": "",
                    "icon": "bulbasaur",
                    "family_order": 0,
                    "family_lead": null,
                    "original_game_bundle": null,
                    "order_number": "0001-0001-000",
                    "game_bundles": [],
                    "game_bundles_shiny": []
                },
                "catch_state": null,
                "forms": null,
                "types": {
                    "primary": { "slug": "grass", "name": "Grass", "french_name": "Plante", "color": "#61bb59" },
                    "secondary": null
                }
            }
            JSON;

        $object = $serializer->deserialize($json, Pokemon::class, 'json');

        $this->assertInstanceOf(Pokemon::class, $object);
        $this->assertNull($object->getCategoryFormSlug());
        $this->assertNull($object->getCategoryFormName());
        $this->assertNull($object->getRegionalFormSlug());
        $this->assertNull($object->getRegionalFormName());
        $this->assertNull($object->getSpecialFormSlug());
        $this->assertNull($object->getSpecialFormName());
        $this->assertNull($object->getVariantFormSlug());
        $this->assertNull($object->getVariantFormName());
    }
}
