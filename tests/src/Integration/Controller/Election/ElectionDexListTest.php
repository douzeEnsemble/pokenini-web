<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Election;

use App\Controller\ElectionDexListController;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(ElectionDexListController::class)]
#[Group('api-mocked-testing')]
final class ElectionDexListTest extends WebTestCase
{
    use TestNavTrait;

    public function testIndex(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('789465465489', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/election/dex');

        $this->assertResponseIsSuccessful();

        $this->assertSame(
            'Pokénini Choisir le dex pour lequel tu veux voter',
            $crawler->filter('title')->text()
        );

        $this->assertSame(
            'Choisir le dex pour lequel tu veux voter',
            $crawler->filter('h1')->text()
        );

        $this->assertSame('Choisir le dex pour lequel tu veux voter', $crawler->filter('h1')->text());
        $this->assertSame("Selon le dex, il y'a plus ou moins de pokémons, plus ou moins de formes. C'est à toi de voir", $crawler->filter('h2')->text());

        $this->assertCountFilter($crawler, 21, '.dex-item');
        $this->assertCountFilter($crawler, 21, '.dex-item .card-title');
        $this->assertCountFilter($crawler, 21, '.dex-item .card-title a');
        $this->assertCountFilter($crawler, 24, '.dex-item .badge');
        $this->assertCountFilter($crawler, 21, '.dex-item .progress');
        $this->assertCountFilter($crawler, 21, '.dex-item .progress-bar');
        $this->assertCountFilter($crawler, 21, '.dex-item p.small');

        $this->assertSame('71 Pokémons', $crawler->filter('.dex-item .badge')->eq(0)->text());

        $firstDex = $crawler->filter('.dex-item')->eq(0);
        $this->assertStringContainsString('progress-bar-striped', (string) $firstDex->filter('.progress-bar')->attr('class'));
        $this->assertStringContainsString('bg-info', (string) $firstDex->filter('.progress-bar')->attr('class'));
        $this->assertStringContainsString('width: 31%', (string) $firstDex->filter('.progress-bar')->attr('style'));
        $this->assertSame('31%', trim($firstDex->filter('.progress-bar')->text()));
        $this->assertSame(
            'Tu as fait <strong>4</strong> tours sur <strong>13</strong>*.',
            (string) $firstDex->filter('.progress-bar')->attr('data-bs-title')
        );

        $successDex = $crawler->filter('.dex-item')->eq(1);
        $this->assertStringContainsString('bg-success', (string) $successDex->filter('.progress-bar')->attr('class'));
        $this->assertStringContainsString('width: 100%', (string) $successDex->filter('.progress-bar')->attr('style'));

        $dangerDex = $crawler->filter('.dex-item')->eq(19);
        $this->assertStringContainsString('bg-danger', (string) $dangerDex->filter('.progress-bar')->attr('class'));
        $this->assertStringContainsString('width: 100%', (string) $dangerDex->filter('.progress-bar')->attr('style'));

        $this->assertSame('/fr/election/redgreenblueyellow', $crawler->filter('.dex-item .card-title a')->eq(0)->attr('href'));
        $this->assertSame('/fr/election/rubysapphireemerald', $crawler->filter('.dex-item .card-title a')->eq(2)->attr('href'));
    }
}
