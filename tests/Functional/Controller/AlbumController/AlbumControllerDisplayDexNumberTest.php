<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\AlbumController;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlbumControllerDisplayDexNumberTest extends WebTestCase
{
    use TestNavTrait;

    public function testDisplayDexNumber(): void
    {
        $client = static::createClient();

        $user = new User('109903422692691643666');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/r/goldsilvercrystal');

        $this->assertCount(278, $crawler->filter('.album-case'));

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
