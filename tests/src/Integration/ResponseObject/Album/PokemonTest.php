<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject\Album;

use App\ResponseObject\Album\Pokemon;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(Pokemon::class)]
class PokemonTest extends KernelTestCase
{
    public function testDeserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = static::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "pokemon_slug": "charizard-mega-y",
                "pokemon_name": "Mega Charizard Y",
                "pokemon_national_dex_number": 6,
                "pokemon_simplified_name": "Charizard",
                "pokemon_forms_label": "Mega Y",
                "pokemon_french_name": "Méga Dracaufeu Y",
                "pokemon_simplified_french_name": "Dracaufeu",
                "pokemon_forms_french_label": "Méga Y",
                "pokemon_icon": "charizard-mega-y",
                "pokemon_family_order": 4,
                "family_lead_slug": "charmander",
                "category_form_slug": "starter",
                "category_form_name": "Starter",
                "regional_form_slug": "kantonian",
                "regional_form_name": "Kantonian",
                "special_form_slug": "mega",
                "special_form_name": "Mega",
                "variant_form_slug": "battle",
                "variant_form_name": "Battle",
                "catch_state_slug": "yes",
                "catch_state_name": "Yes",
                "catch_state_french_name": "Oui",
                "pokemon_regional_dex_number": 9854,
                "primary_type_slug": "fire",
                "primary_type_name": "Fire",
                "primary_type_french_name": "Feu",
                "secondary_type_slug": "flying",
                "secondary_type_name": "Flying",
                "secondary_type_french_name": "Vol",
                "pokemon_order_number": "9999-0006-004"
                }
            JSON;

        /** @var Pokemon $object */
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
        $serializer = static::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "pokemon_slug": "charizard-mega-y",
                "pokemon_name": "Mega Charizard Y",
                "pokemon_national_dex_number": 6,
                "pokemon_simplified_name": "Charizard",
                "pokemon_forms_label": "Mega Y",
                "pokemon_french_name": "Méga Dracaufeu Y",
                "pokemon_simplified_french_name": "Dracaufeu",
                "pokemon_forms_french_label": "Méga Y",
                "pokemon_icon": "charizard-mega-y",
                "pokemon_family_order": 4,
                "family_lead_slug": "charmander",
                "category_form_slug": null,
                "category_form_name": null,
                "regional_form_slug": null,
                "regional_form_name": null,
                "special_form_slug": null,
                "special_form_name": null,
                "variant_form_slug": null,
                "variant_form_name": null,
                "catch_state_slug": null,
                "catch_state_name": null,
                "catch_state_french_name": null,
                "pokemon_regional_dex_number": null,
                "primary_type_slug": "fire",
                "primary_type_name": "Fire",
                "primary_type_french_name": "Feu",
                "secondary_type_slug": null,
                "secondary_type_name": null,
                "secondary_type_french_name": null,
                "pokemon_order_number": "9999-0006-004"
                }
            JSON;

        /** @var Pokemon $object */
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
        $this->assertNull($object->getSpecialFormSlug());
        $this->assertNull($object->getSpecialFormName());
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
}
