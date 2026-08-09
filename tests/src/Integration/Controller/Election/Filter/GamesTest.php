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
final class GamesTest extends WebTestCase
{
    use TestNavTrait;

    #[Test]
    public function filterSwordShieldOriginalGame(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request(
            'GET',
            '/fr/election/demolite?ogb[]=swordshield&t=7b52009b64fd0a2a49e6d8a939753077792b0554'
        );

        $this->assertSelectedOptions($crawler, 'select#any_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#primary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#secondary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#category_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#regional_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#special_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#variant_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#original_game_bundle', ['swordshield']);
        $this->assertSelectedOptions($crawler, 'select#game_bundle_availability', ['']);
        $this->assertCount(0, $crawler->filter('select#game_bundle_shiny_availability'));
        $this->assertSelectedOptions($crawler, 'select#collection_availability', ['']);
    }

    #[Test]
    public function filterSwordShieldAndXYOriginalGame(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request(
            'GET',
            '/fr/election/demolite?ogb[]=swordshield&ogb[]=xy&t=7b52009b64fd0a2a49e6d8a939753077792b0554'
        );

        $this->assertSelectedOptions($crawler, 'select#any_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#primary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#secondary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#category_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#regional_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#special_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#variant_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#original_game_bundle', ['swordshield', 'xy']);
        $this->assertSelectedOptions($crawler, 'select#game_bundle_availability', ['']);
        $this->assertCount(0, $crawler->filter('select#game_bundle_shiny_availability'));
        $this->assertSelectedOptions($crawler, 'select#collection_availability', ['']);
    }

    #[Test]
    public function filterOriginalGameUnknown(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/election/demolite?ogb[]=unknown&t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertSelectedOptions($crawler, 'select#any_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#primary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#secondary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#category_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#regional_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#special_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#variant_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#original_game_bundle', []);
        $this->assertSelectedOptions($crawler, 'select#game_bundle_availability', ['']);
        $this->assertCount(0, $crawler->filter('select#game_bundle_shiny_availability'));
        $this->assertSelectedOptions($crawler, 'select#collection_availability', ['']);
    }

    #[Test]
    public function filterSwordShieldGameBundle(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request(
            'GET',
            '/fr/election/demolite?gba[]=swordshield&t=7b52009b64fd0a2a49e6d8a939753077792b0554'
        );

        $this->assertSelectedOptions($crawler, 'select#any_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#primary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#secondary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#category_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#regional_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#special_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#variant_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#original_game_bundle', ['']);
        $this->assertSelectedOptions($crawler, 'select#game_bundle_availability', ['swordshield']);
        $this->assertCount(0, $crawler->filter('select#game_bundle_shiny_availability'));
        $this->assertSelectedOptions($crawler, 'select#collection_availability', ['']);
    }

    #[Test]
    public function filterSwordShieldGameBundleShiny(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request(
            'GET',
            '/fr/election/demoliteshiny?gbsa[]=swordshield&t=7b52009b64fd0a2a49e6d8a939753077792b0554'
        );

        $this->assertSelectedOptions($crawler, 'select#any_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#primary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#secondary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#category_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#regional_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#special_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#variant_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#original_game_bundle', ['']);
        $this->assertCount(0, $crawler->filter('select#game_bundle_availability'));
        $this->assertSelectedOptions($crawler, 'select#game_bundle_shiny_availability', ['swordshield']);
        $this->assertSelectedOptions($crawler, 'select#collection_availability', ['']);
    }

    #[Test]
    public function filterNotSwordShieldGameBundle(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request(
            'GET',
            '/fr/election/demolite?gba[]=!swordshield&t=7b52009b64fd0a2a49e6d8a939753077792b0554'
        );

        $this->assertSelectedOptions($crawler, 'select#any_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#primary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#secondary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#category_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#regional_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#special_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#variant_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#original_game_bundle', ['']);
        $this->assertSelectedOptions($crawler, 'select#game_bundle_availability', ['!swordshield']);
        $this->assertCount(0, $crawler->filter('select#game_bundle_shiny_availability'));
        $this->assertSelectedOptions($crawler, 'select#collection_availability', ['']);
    }

    #[Test]
    public function filterNotSwordShieldGameBundleShiny(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request(
            'GET',
            '/fr/election/demoliteshiny?gbsa[]=!swordshield&t=7b52009b64fd0a2a49e6d8a939753077792b0554'
        );

        $this->assertSelectedOptions($crawler, 'select#any_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#primary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#secondary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#category_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#regional_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#special_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#variant_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#original_game_bundle', ['']);
        $this->assertCount(0, $crawler->filter('select#game_bundle_availability'));
        $this->assertSelectedOptions($crawler, 'select#game_bundle_shiny_availability', ['!swordshield']);
        $this->assertSelectedOptions($crawler, 'select#collection_availability', ['']);
    }
}
