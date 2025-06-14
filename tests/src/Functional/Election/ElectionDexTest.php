<?php

declare(strict_types=1);

namespace App\Tests\Functional\Election;

use App\Controller\ElectionDexController;
use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(ElectionDexController::class)]
class ElectionDexTest extends WebTestCase
{
    use TestNavTrait;

    public function testAsTrainer(): void
    {
        $client = static::createClient();

        $user = new User('789465465489', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/election/dex');

        $this->assertResponseIsSuccessful();

        $this->assertSame('Choisir le dex pour lequel tu veux voter', $crawler->filter('h1')->text());
        $this->assertSame("Selon le dex, il y'a plus ou moins de pokémons, plus ou moins de formes. C'est à toi de voir", $crawler->filter('h2')->text());

        $this->assertCountFilter($crawler, 4, '.election-dex-item');
        $this->assertCountFilter($crawler, 4, '.election-dex-item .card-title');
        $this->assertCountFilter($crawler, 4, '.election-dex-item .card-title a');
        $this->assertCountFilter($crawler, 6, '.election-dex-item .badge');
        $this->assertCountFilter($crawler, 4, '.election-dex-item p.small');

        $this->assertSame('61 Pokémons', $crawler->filter('.election-dex-item .badge')->eq(0)->text());
        $this->assertSame('', $crawler->filter('.election-dex-item .badge')->eq(2)->text());

        $this->assertSame('/fr/election/home', $crawler->filter('.election-dex-item .card-title a')->eq(0)->attr('href'));
        $this->assertSame('/fr/election/homepogo', $crawler->filter('.election-dex-item .card-title a')->eq(2)->attr('href'));
    }

    public function testAsCollector(): void
    {
        $client = static::createClient();

        $user = new User('789465465489', 'TestProvider');
        $user->addTrainerRole();
        $user->addCollectorRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/election/dex');

        $this->assertResponseIsSuccessful();

        $this->assertSame('Choisir le dex pour lequel tu veux voter', $crawler->filter('h1')->text());
        $this->assertSame("Selon le dex, il y'a plus ou moins de pokémons, plus ou moins de formes. C'est à toi de voir", $crawler->filter('h2')->text());

        $this->assertCountFilter($crawler, 19, '.election-dex-item');
        $this->assertCountFilter($crawler, 19, '.election-dex-item .card-title');
        $this->assertCountFilter($crawler, 19, '.election-dex-item .card-title a');
        $this->assertCountFilter($crawler, 20, '.election-dex-item .badge');
        $this->assertCountFilter($crawler, 19, '.election-dex-item p.small');

        $this->assertSame('71 Pokémons', $crawler->filter('.election-dex-item .badge')->eq(0)->text());
        $this->assertSame('1 515 Pokémons', $crawler->filter('.election-dex-item .badge')->eq(2)->text());

        $this->assertSame('/fr/election/redgreenblueyellow', $crawler->filter('.election-dex-item .card-title a')->eq(0)->attr('href'));
        $this->assertSame('/fr/election/rubysapphireemerald', $crawler->filter('.election-dex-item .card-title a')->eq(2)->attr('href'));
    }

    public function testAsAdmin(): void
    {
        $client = static::createClient();

        $user = new User('789465465489', 'TestProvider');
        $user->addTrainerRole();
        $user->addCollectorRole();
        $user->addAdminRole();
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
        $this->assertSame('1 515 Pokémons', $crawler->filter('.election-dex-item .badge')->eq(2)->text());

        $this->assertSame('/fr/election/redgreenblueyellow', $crawler->filter('.election-dex-item .card-title a')->eq(0)->attr('href'));
        $this->assertSame('/fr/election/rubysapphireemerald', $crawler->filter('.election-dex-item .card-title a')->eq(2)->attr('href'));
    }
}
