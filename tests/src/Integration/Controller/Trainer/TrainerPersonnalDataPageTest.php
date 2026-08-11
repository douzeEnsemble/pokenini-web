<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Trainer;

use App\Controller\TrainerPersonnalDataController;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(TrainerPersonnalDataController::class)]
#[Group('api-mocked-testing')]
final class TrainerPersonnalDataPageTest extends WebTestCase
{
    #[Test]
    public function personnalDataPage(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/trainer/personnal_data');

        $this->assertResponseStatusCodeSame(200);

        $this->assertSame(
            'Pokénini Ton espace de dresseur',
            $crawler->filter('title')->text()
        );

        $this->assertSame(
            'Ton espace de dresseur',
            $crawler->filter('h1')->text()
        );

        $this->assertCount(1, $crawler->filter('h1'));
        $this->assertCount(2, $crawler->filter('table thead th'));
        $this->assertCount(2, $crawler->filter('table tbody tr'));
        $this->assertEquals('Identifiant 789465465489', $crawler->filter('table tbody tr')->eq(0)->text());
        $this->assertEquals("Service d'identification TestProvider", $crawler->filter('table tbody tr')->eq(1)->text());

        $this->assertCount(3, $crawler->filter('#trainer-section-tab .nav-link'));
        $this->assertSame(
            '/fr/trainer/personnal_data',
            $crawler->filter('#trainer-section-tab .nav-link.active')->attr('href')
        );
    }

    #[Test]
    public function personnalDataPageNotAllowed(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $client->loginUser($user, 'web');

        $client->request('GET', '/fr/trainer/personnal_data');

        $this->assertResponseStatusCodeSame(403);
    }
}
