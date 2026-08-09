<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Trainer;

use App\Controller\TrainerIndexController;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(TrainerIndexController::class)]
#[Group('api-mocked-testing')]
final class TrainerPageFiltersTest extends WebTestCase
{
    use TestNavTrait;

    #[Test]
    public function privacyFilterOn(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
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

    #[Test]
    public function privacyFilterOff(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
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

    #[Test]
    public function homepagedFilterOn(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
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

    #[Test]
    public function homepagedFilterOff(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
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

    #[Test]
    public function releasedFilterOn(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
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

    #[Test]
    public function releasedFilterOff(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
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

    #[Test]
    public function shinyFilterOn(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
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

    #[Test]
    public function shinyFilterOff(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
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

    #[Test]
    public function premiumFilterOn(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
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

    #[Test]
    public function premiumFilterOff(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
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

    #[Test]
    public function allFilterOff(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
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

    #[Test]
    public function allFilterOn(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
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
