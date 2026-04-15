<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject\Election;

use App\ResponseObject\Election\TopPokemon;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(TopPokemon::class)]
class TopPokemonTest extends KernelTestCase
{
    public function testDeserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = static::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "pokemon_slug": "venusaur-mega",
                "pokemon_name": "Mega Venusaur",
                "pokemon_national_dex_number": 3,
                "pokemon_simplified_name": "Venusaur",
                "pokemon_forms_label": "Mega",
                "pokemon_french_name": "Mega Florizarre",
                "pokemon_simplified_french_name": "Florizarre",
                "pokemon_forms_french_label": "Mega",
                "pokemon_icon": "venusaur-mega",
                "pokemon_family_order": 4,
                "family_lead_slug": "bulbasaur",
                "category_form_slug": "plant",
                "category_form_name": "Plant",
                "regional_form_slug": "kanto",
                "regional_form_name": "Kanto",
                "special_form_slug": "mega",
                "special_form_name": "Mega",
                "variant_form_slug": "starter",
                "variant_form_name": "Starter",
                "catch_state_slug": "yes",
                "catch_state_name": "Yes",
                "catch_state_french_name": "Oui",
                "pokemon_regional_dex_number": 15545,
                "elo": 1000,
                "significance": false
            }
            JSON;

        /** @var TopPokemon $object */
        $object = $serializer->deserialize($json, TopPokemon::class, 'json');

        $this->assertSame('venusaur-mega', $object->getPokemonSlug());
        $this->assertSame('Mega Venusaur', $object->getPokemonName());
        $this->assertSame(3, $object->getPokemonNationalDexNumber());
        $this->assertSame('Venusaur', $object->getPokemonSimplifiedName());
        $this->assertSame('Mega', $object->getPokemonFormsLabel());
        $this->assertSame('Mega Florizarre', $object->getPokemonFrenchName());
        $this->assertSame('Florizarre', $object->getPokemonSimplifiedFrenchName());
        $this->assertSame('Mega', $object->getPokemonFormsFrenchLabel());
        $this->assertSame('venusaur-mega', $object->getPokemonIcon());
        $this->assertSame(4, $object->getPokemonFamilyOrder());
        $this->assertSame('bulbasaur', $object->getFamilyLeadSlug());
        $this->assertSame('plant', $object->getCategoryFormSlug());
        $this->assertSame('Plant', $object->getCategoryFormName());
        $this->assertSame('kanto', $object->getRegionalFormSlug());
        $this->assertSame('Kanto', $object->getRegionalFormName());
        $this->assertSame('mega', $object->getSpecialFormSlug());
        $this->assertSame('Mega', $object->getSpecialFormName());
        $this->assertSame('starter', $object->getVariantFormSlug());
        $this->assertSame('Starter', $object->getVariantFormName());
        $this->assertSame('yes', $object->getCatchStateSlug());
        $this->assertSame('Yes', $object->getCatchStateName());
        $this->assertSame('Oui', $object->getCatchStateFrenchName());
        $this->assertSame(15545, $object->getPokemonRegionalDexNumber());
        $this->assertSame(1000, $object->getElo());
        $this->assertFalse($object->isSignificance());
    }

    public function testDeserializeWithNull(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = static::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "pokemon_slug": "venusaur-mega",
                "pokemon_name": "Mega Venusaur",
                "pokemon_national_dex_number": 3,
                "pokemon_simplified_name": "Venusaur",
                "pokemon_forms_label": "Mega",
                "pokemon_french_name": "Mega Florizarre",
                "pokemon_simplified_french_name": "Florizarre",
                "pokemon_forms_french_label": "Mega",
                "pokemon_icon": "venusaur-mega",
                "pokemon_family_order": 4,
                "family_lead_slug": null,
                "regional_form_slug": null,
                "regional_form_name": null,
                "special_form_slug": null,
                "special_form_name": null,
                "variant_form_slug": null,
                "variant_form_name": null,
                "catch_state_slug": null,
                "catch_state_name": null,
                "catch_state_french_name": null,
                "pokemon_regional_dex_number": 15545,
                "elo": 1000,
                "significance": false
            }
            JSON;

        /** @var TopPokemon $object */
        $object = $serializer->deserialize($json, TopPokemon::class, 'json');

        $this->assertSame('venusaur-mega', $object->getPokemonSlug());
        $this->assertSame('Mega Venusaur', $object->getPokemonName());
        $this->assertSame(3, $object->getPokemonNationalDexNumber());
        $this->assertSame('Venusaur', $object->getPokemonSimplifiedName());
        $this->assertSame('Mega', $object->getPokemonFormsLabel());
        $this->assertSame('Mega Florizarre', $object->getPokemonFrenchName());
        $this->assertSame('Florizarre', $object->getPokemonSimplifiedFrenchName());
        $this->assertSame('Mega', $object->getPokemonFormsFrenchLabel());
        $this->assertSame('venusaur-mega', $object->getPokemonIcon());
        $this->assertSame(4, $object->getPokemonFamilyOrder());
        $this->assertNull($object->getFamilyLeadSlug());
        $this->assertNull($object->getRegionalFormSlug());
        $this->assertNull($object->getSpecialFormSlug());
        $this->assertNull($object->getVariantFormSlug());
        $this->assertNull($object->getVariantFormName());
        $this->assertNull($object->getCatchStateSlug());
        $this->assertNull($object->getCatchStateName());
        $this->assertNull($object->getCatchStateFrenchName());
        $this->assertSame(15545, $object->getPokemonRegionalDexNumber());
        $this->assertSame(1000, $object->getElo());
        $this->assertFalse($object->isSignificance());
    }

    public function testDeserializeArray(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = static::getContainer()->get(SerializerInterface::class);

        $json = (string) file_get_contents('/app/tests/resources/moco/Back/responses/election/election_mega__top_5.json');

        /** @var TopPokemon[] $objects */
        $objects = $serializer->deserialize($json, TopPokemon::class.'[]', 'json');

        $this->assertCount(5, $objects);
        $this->assertContainsOnlyInstancesOf(TopPokemon::class, $objects);
    }

    public function testDeserializeEmptyArray(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = static::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            []
            JSON;

        /** @var TopPokemon[] $objects */
        $objects = $serializer->deserialize($json, TopPokemon::class.'[]', 'json');

        $this->assertCount(0, $objects);
    }
}
