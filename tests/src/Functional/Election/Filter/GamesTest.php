<?php

declare(strict_types=1);

namespace App\Tests\Functional\Election\Filter;

use App\Controller\ElectionIndexController;
use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(ElectionIndexController::class)]
class GamesTest extends WebTestCase
{
    use TestNavTrait;

    public function testFilterSwordShieldOriginalGame(): void
    {
        $client = static::createClient();

        $user = new User('789465465489', 'TestProvider');
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

    public function testFilterSwordShieldAndXYOriginalGame(): void
    {
        $client = static::createClient();

        $user = new User('789465465489', 'TestProvider');
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

    public function testFilterOriginalGameUnknown(): void
    {
        $client = static::createClient();

        $user = new User('789465465489', 'TestProvider');
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

    public function testFilterSwordShieldGameBundle(): void
    {
        $client = static::createClient();

        $user = new User('789465465489', 'TestProvider');
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

    public function testFilterSwordShieldGameBundleShiny(): void
    {
        $client = static::createClient();

        $user = new User('789465465489', 'TestProvider');
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

    public function testFilterNotSwordShieldGameBundle(): void
    {
        $client = static::createClient();

        $user = new User('789465465489', 'TestProvider');
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

    public function testFilterNotSwordShieldGameBundleShiny(): void
    {
        $client = static::createClient();

        $user = new User('789465465489', 'TestProvider');
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
