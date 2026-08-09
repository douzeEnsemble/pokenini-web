<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Election\Filter;

use App\Controller\ElectionIndexController;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(ElectionIndexController::class)]
#[Group('api-mocked-testing')]
final class TypesTest extends WebTestCase
{
    use TestNavTrait;

    #[Test]
    public function filterPrimaryTypeFire(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/election/demolite?t1[]=fire&t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertSelectedOptions($crawler, 'select#any_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#primary_type', ['fire']);
        $this->assertSelectedOptions($crawler, 'select#secondary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#category_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#regional_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#special_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#variant_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#original_game_bundle', ['']);
        $this->assertSelectedOptions($crawler, 'select#game_bundle_availability', ['']);
        $this->assertCount(0, $crawler->filter('select#game_bundle_shiny_availability'));
        $this->assertSelectedOptions($crawler, 'select#collection_availability', ['']);
    }

    #[Test]
    public function filterSecondaryTypePoisonOrFlying(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request(
            'GET',
            '/fr/election/demolite?t2[]=poison&t2[]=flying&t=7b52009b64fd0a2a49e6d8a939753077792b0554'
        );

        $this->assertSelectedOptions($crawler, 'select#any_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#primary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#secondary_type', ['poison', 'flying']);
        $this->assertSelectedOptions($crawler, 'select#category_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#regional_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#special_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#variant_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#original_game_bundle', ['']);
        $this->assertSelectedOptions($crawler, 'select#game_bundle_availability', ['']);
        $this->assertCount(0, $crawler->filter('select#game_bundle_shiny_availability'));
        $this->assertSelectedOptions($crawler, 'select#collection_availability', ['']);
    }

    #[Test]
    public function filterPrimaryTypeFightingAndSecondaryTypeFireOrWater(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request(
            'GET',
            '/fr/election/demolite?t=7b52009b64fd0a2a49e6d8a939753077792b0554&t1[]=fighting&t2[]=fire&t2[]=water',
        );

        $this->assertSelectedOptions($crawler, 'select#any_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#primary_type', ['fighting']);
        $this->assertSelectedOptions($crawler, 'select#secondary_type', ['fire', 'water']);
        $this->assertSelectedOptions($crawler, 'select#category_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#regional_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#special_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#variant_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#original_game_bundle', ['']);
        $this->assertSelectedOptions($crawler, 'select#game_bundle_availability', ['']);
        $this->assertCount(0, $crawler->filter('select#game_bundle_shiny_availability'));
        $this->assertSelectedOptions($crawler, 'select#collection_availability', ['']);
    }

    #[Test]
    public function filterPrimaryTypeFightingAndSecondaryTypeNullFireOrWater(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request(
            'GET',
            '/fr/election/demolite?t=7b52009b64fd0a2a49e6d8a939753077792b0554&t1[]=fighting&t2[]=null&t2[]=fire&t2[]=water',
        );

        $this->assertSelectedOptions($crawler, 'select#any_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#primary_type', ['fighting']);
        $this->assertSelectedOptions($crawler, 'select#secondary_type', ['null', 'fire', 'water']);
        $this->assertSelectedOptions($crawler, 'select#category_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#regional_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#special_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#variant_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#original_game_bundle', ['']);
        $this->assertSelectedOptions($crawler, 'select#game_bundle_availability', ['']);
        $this->assertCount(0, $crawler->filter('select#game_bundle_shiny_availability'));
        $this->assertSelectedOptions($crawler, 'select#collection_availability', ['']);
    }

    #[Test]
    public function filterAnyTypeFire(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/election/demolite?at[]=fire&t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertSelectedOptions($crawler, 'select#any_type', ['fire']);
        $this->assertSelectedOptions($crawler, 'select#primary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#secondary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#category_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#regional_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#special_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#variant_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#original_game_bundle', ['']);
        $this->assertSelectedOptions($crawler, 'select#game_bundle_availability', ['']);
        $this->assertCount(0, $crawler->filter('select#game_bundle_shiny_availability'));
        $this->assertSelectedOptions($crawler, 'select#collection_availability', ['']);
    }

    #[Test]
    public function filterTypeUnknown(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/election/demolite?t1[]=unknown&t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertSelectedOptions($crawler, 'select#any_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#primary_type', []);
        $this->assertSelectedOptions($crawler, 'select#secondary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#category_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#regional_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#special_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#variant_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#original_game_bundle', ['']);
        $this->assertSelectedOptions($crawler, 'select#game_bundle_availability', ['']);
        $this->assertCount(0, $crawler->filter('select#game_bundle_shiny_availability'));
        $this->assertSelectedOptions($crawler, 'select#collection_availability', ['']);
    }
}
