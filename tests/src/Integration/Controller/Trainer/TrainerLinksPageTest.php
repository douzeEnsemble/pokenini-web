<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Trainer;

use App\Controller\TrainerLinksController;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(TrainerLinksController::class)]
#[Group('api-mocked-testing')]
final class TrainerLinksPageTest extends WebTestCase
{
    #[Test]
    public function linksPage(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/trainer/links');

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

        $mainText = $crawler->filter('#main-container')->text();

        $this->assertStringContainsString(
            'Voici tous les liens que tu as créés entre tes dex.',
            $mainText
        );
        $this->assertStringContainsString(
            "Tu n'as pas encore créé de lien entre tes dex.",
            $mainText
        );
        $this->assertCount(0, $crawler->filter('.dex-links-tree'));
    }

    #[Test]
    public function linksPageNotAllowed(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $client->loginUser($user, 'web');

        $client->request('GET', '/fr/trainer/links');

        $this->assertResponseStatusCodeSame(403);
    }
}
