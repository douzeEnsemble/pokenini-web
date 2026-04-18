<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Election;

use App\Controller\ElectionDexListController;
use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use League\OAuth2\Client\Token\AccessToken;
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

        $user = new User(
            '789465465489',
            'TestProvider',
            new AccessToken(['access_token' => sha1('789465465489')])
        );
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/election/dex');

        $this->assertResponseIsSuccessful();

        $this->assertSame('Choisir le dex pour lequel tu veux voter', $crawler->filter('h1')->text());
        $this->assertSame("Selon le dex, il y'a plus ou moins de pokémons, plus ou moins de formes. C'est à toi de voir", $crawler->filter('h2')->text());

        $this->assertCountFilter($crawler, 21, '.election-dex-item');
        $this->assertCountFilter($crawler, 21, '.election-dex-item .card-title');
        $this->assertCountFilter($crawler, 21, '.election-dex-item .card-title a');
        $this->assertCountFilter($crawler, 24, '.election-dex-item .badge');
        $this->assertCountFilter($crawler, 21, '.election-dex-item p.small');

        $this->assertSame('71 Pokémons', $crawler->filter('.election-dex-item .badge')->eq(0)->text());
        $this->assertSame('1 Pokémons', $crawler->filter('.election-dex-item .badge')->eq(1)->text());

        $this->assertSame('/fr/election/redgreenblueyellow', $crawler->filter('.election-dex-item .card-title a')->eq(0)->attr('href'));
        $this->assertSame('/fr/election/rubysapphireemerald', $crawler->filter('.election-dex-item .card-title a')->eq(2)->attr('href'));
    }
}
