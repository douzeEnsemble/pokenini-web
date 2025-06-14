<?php

declare(strict_types=1);

namespace App\Tests\Functional\Album\Display;

use App\Controller\AlbumIndexController;
use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(AlbumIndexController::class)]
class DexNumberTest extends WebTestCase
{
    use TestNavTrait;

    public function testDisplayDexNumber(): void
    {
        $client = static::createClient();

        $user = new User('109903422692691643666', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/goldsilvercrystal');

        $this->assertCountFilter($crawler, 278, '.album-case');

        $this->assertEquals(
            'Germignon',
            $crawler->filter('.album-case')->first()->filter('.album-case-name')->text()
        );
        $this->assertEquals(
            '#1',
            $crawler->filter('.album-case')->first()->filter('.album-case-dex-number')->text()
        );

        $this->assertEquals(
            'Bulbizarre',
            $crawler->filter('.album-case')->eq(252)->filter('.album-case-name')->text()
        );
        $this->assertEquals(
            '#231',
            $crawler->filter('.album-case')->eq(252)->filter('.album-case-dex-number')->text()
        );
    }
}
