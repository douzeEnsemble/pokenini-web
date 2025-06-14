<?php

declare(strict_types=1);

namespace App\Tests\Functional\Trainer;

use App\Controller\TrainerIndexController;
use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(TrainerIndexController::class)]
class TrainerPageFiltersTest extends WebTestCase
{
    use TestNavTrait;

    public function testPrivacyFilterOn(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider');
        $user->addTrainerRole();
        $user->addCollectorRole();
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/trainer?p=1');

        $this->assertResponseStatusCodeSame(200);

        $this->assertSelectedOptions($crawler, 'select#filter-privacy', ['1']);
        $this->assertSelectedOptions($crawler, 'select#filter-homepaged', ['']);
        $this->assertSelectedOptions($crawler, 'select#filter-released', ['']);
        $this->assertSelectedOptions($crawler, 'select#filter-shiny', ['']);
        $this->assertSelectedOptions($crawler, 'select#filter-premium', ['']);

        $this->assertCountFilter($crawler, 15, '.trainer-dex-item');
    }

    public function testPrivacyFilterOff(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider');
        $user->addTrainerRole();
        $user->addCollectorRole();
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/trainer?p=0');

        $this->assertResponseStatusCodeSame(200);

        $this->assertSelectedOptions($crawler, 'select#filter-privacy', ['0']);
        $this->assertSelectedOptions($crawler, 'select#filter-homepaged', ['']);
        $this->assertSelectedOptions($crawler, 'select#filter-released', ['']);
        $this->assertSelectedOptions($crawler, 'select#filter-shiny', ['']);
        $this->assertSelectedOptions($crawler, 'select#filter-premium', ['']);

        $this->assertCountFilter($crawler, 6, '.trainer-dex-item');
    }

    public function testHomepagedFilterOn(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider');
        $user->addTrainerRole();
        $user->addCollectorRole();
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/trainer?h=1');

        $this->assertResponseStatusCodeSame(200);

        $this->assertSelectedOptions($crawler, 'select#filter-privacy', ['']);
        $this->assertSelectedOptions($crawler, 'select#filter-homepaged', ['1']);
        $this->assertSelectedOptions($crawler, 'select#filter-released', ['']);
        $this->assertSelectedOptions($crawler, 'select#filter-shiny', ['']);
        $this->assertSelectedOptions($crawler, 'select#filter-premium', ['']);

        $this->assertCountFilter($crawler, 6, '.trainer-dex-item');
    }

    public function testHomepagedFilterOff(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider');
        $user->addTrainerRole();
        $user->addCollectorRole();
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/trainer?h=0');

        $this->assertResponseStatusCodeSame(200);

        $this->assertSelectedOptions($crawler, 'select#filter-privacy', ['']);
        $this->assertSelectedOptions($crawler, 'select#filter-homepaged', ['0']);
        $this->assertSelectedOptions($crawler, 'select#filter-released', ['']);
        $this->assertSelectedOptions($crawler, 'select#filter-shiny', ['']);
        $this->assertSelectedOptions($crawler, 'select#filter-premium', ['']);

        $this->assertCountFilter($crawler, 15, '.trainer-dex-item');
    }

    public function testReleasedFilterOn(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider');
        $user->addTrainerRole();
        $user->addCollectorRole();
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/trainer?r=1');

        $this->assertResponseStatusCodeSame(200);

        $this->assertSelectedOptions($crawler, 'select#filter-privacy', ['']);
        $this->assertSelectedOptions($crawler, 'select#filter-homepaged', ['']);
        $this->assertSelectedOptions($crawler, 'select#filter-released', ['1']);
        $this->assertSelectedOptions($crawler, 'select#filter-shiny', ['']);
        $this->assertSelectedOptions($crawler, 'select#filter-premium', ['']);

        $this->assertCountFilter($crawler, 19, '.trainer-dex-item');
    }

    public function testReleasedFilterOff(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider');
        $user->addTrainerRole();
        $user->addCollectorRole();
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/trainer?r=0');

        $this->assertResponseStatusCodeSame(200);

        $this->assertSelectedOptions($crawler, 'select#filter-privacy', ['']);
        $this->assertSelectedOptions($crawler, 'select#filter-homepaged', ['']);
        $this->assertSelectedOptions($crawler, 'select#filter-released', ['0']);
        $this->assertSelectedOptions($crawler, 'select#filter-shiny', ['']);
        $this->assertSelectedOptions($crawler, 'select#filter-premium', ['']);

        $this->assertCountFilter($crawler, 2, '.trainer-dex-item');
    }

    public function testShinyFilterOn(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider');
        $user->addTrainerRole();
        $user->addCollectorRole();
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/trainer?s=1');

        $this->assertResponseStatusCodeSame(200);

        $this->assertSelectedOptions($crawler, 'select#filter-privacy', ['']);
        $this->assertSelectedOptions($crawler, 'select#filter-homepaged', ['']);
        $this->assertSelectedOptions($crawler, 'select#filter-released', ['']);
        $this->assertSelectedOptions($crawler, 'select#filter-shiny', ['1']);
        $this->assertSelectedOptions($crawler, 'select#filter-premium', ['']);

        $this->assertCountFilter($crawler, 2, '.trainer-dex-item');
    }

    public function testShinyFilterOff(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider');
        $user->addTrainerRole();
        $user->addCollectorRole();
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/trainer?s=0');

        $this->assertResponseStatusCodeSame(200);

        $this->assertSelectedOptions($crawler, 'select#filter-privacy', ['']);
        $this->assertSelectedOptions($crawler, 'select#filter-homepaged', ['']);
        $this->assertSelectedOptions($crawler, 'select#filter-released', ['']);
        $this->assertSelectedOptions($crawler, 'select#filter-shiny', ['0']);
        $this->assertSelectedOptions($crawler, 'select#filter-premium', ['']);

        $this->assertCountFilter($crawler, 19, '.trainer-dex-item');
    }

    public function testPremiumFilterOn(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider');
        $user->addTrainerRole();
        $user->addCollectorRole();
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/trainer?m=1');

        $this->assertResponseStatusCodeSame(200);

        $this->assertSelectedOptions($crawler, 'select#filter-privacy', ['']);
        $this->assertSelectedOptions($crawler, 'select#filter-homepaged', ['']);
        $this->assertSelectedOptions($crawler, 'select#filter-released', ['']);
        $this->assertSelectedOptions($crawler, 'select#filter-shiny', ['']);
        $this->assertSelectedOptions($crawler, 'select#filter-premium', ['1']);

        $this->assertCountFilter($crawler, 3, '.trainer-dex-item');
    }

    public function testPremiumFilterOff(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider');
        $user->addTrainerRole();
        $user->addCollectorRole();
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/trainer?m=0');

        $this->assertResponseStatusCodeSame(200);

        $this->assertSelectedOptions($crawler, 'select#filter-privacy', ['']);
        $this->assertSelectedOptions($crawler, 'select#filter-homepaged', ['']);
        $this->assertSelectedOptions($crawler, 'select#filter-released', ['']);
        $this->assertSelectedOptions($crawler, 'select#filter-shiny', ['']);
        $this->assertSelectedOptions($crawler, 'select#filter-premium', ['0']);

        $this->assertCountFilter($crawler, 18, '.trainer-dex-item');
    }

    public function testAllFilterOff(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider');
        $user->addTrainerRole();
        $user->addCollectorRole();
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/trainer?p=0&h=0&r=0&s=0&m=0');

        $this->assertResponseStatusCodeSame(200);

        $this->assertSelectedOptions($crawler, 'select#filter-privacy', ['0']);
        $this->assertSelectedOptions($crawler, 'select#filter-homepaged', ['0']);
        $this->assertSelectedOptions($crawler, 'select#filter-released', ['0']);
        $this->assertSelectedOptions($crawler, 'select#filter-shiny', ['0']);
        $this->assertSelectedOptions($crawler, 'select#filter-premium', ['0']);

        $this->assertCountFilter($crawler, 1, '.trainer-dex-item');
    }

    public function testAllFilterOn(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider');
        $user->addTrainerRole();
        $user->addCollectorRole();
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/trainer?p=1&h=1&r=1&s=1&m=1');

        $this->assertResponseStatusCodeSame(200);

        $this->assertSelectedOptions($crawler, 'select#filter-privacy', ['1']);
        $this->assertSelectedOptions($crawler, 'select#filter-homepaged', ['1']);
        $this->assertSelectedOptions($crawler, 'select#filter-released', ['1']);
        $this->assertSelectedOptions($crawler, 'select#filter-shiny', ['1']);
        $this->assertSelectedOptions($crawler, 'select#filter-premium', ['1']);

        $this->assertCountFilter($crawler, 0, '.trainer-dex-item');
    }
}
