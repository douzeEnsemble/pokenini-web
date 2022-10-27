<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\AlbumController;

use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlbumControllerFilterCatchStateTest extends WebTestCase
{
    use TestNavTrait;

    public function testFilterCatchStateNo(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/r/demo/no');

        $this->assertCount(
            1732,
            $crawler->filter('.album-case')
        );

        $this->assertCount(0, $crawler->filter('h2.box'));
        $this->assertCount(1, $crawler->filter('#bulbasaur'));
        $this->assertCount(0, $crawler->filter('#venusaur-f'));
        $this->assertCount(0, $crawler->filter('#charmander'));

        $this->assertCount(
            0,
            $crawler
                ->filter('.toast')
        );
    }

    public function testFilterCatchStateYes(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/r/demo/yes');

        $this->assertCount(
            2,
            $crawler->filter('.album-case')
        );

        $this->assertCount(0, $crawler->filter('h2.box'));
        $this->assertCount(0, $crawler->filter('#bulbasaur'));
        $this->assertCount(0, $crawler->filter('#venusaur-f'));
        $this->assertCount(1, $crawler->filter('#charmander'));

        $this->assertCount(
            0,
            $crawler
                ->filter('.toast')
        );
    }

    public function testEditFilterCatchStateYes(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/w/demo/yes', [], [], [
            'PHP_AUTH_USER' => 'renaud',
            'PHP_AUTH_PW'   => 'douze',
        ]);

        $this->assertCount(
            2,
            $crawler->filter('.album-case')
        );

        $this->assertCount(0, $crawler->filter('h2.box'));
        $this->assertCount(0, $crawler->filter('#bulbasaur'));
        $this->assertCount(0, $crawler->filter('#venusaur-f'));
        $this->assertCount(1, $crawler->filter('#charmander'));

        $this->assertCount(
            4,
            $crawler
                ->filter('.toast')
        );
        $this->assertCount(
            2,
            $crawler
                ->filter('.toast.text-bg-success')
        );
        $this->assertCount(
            2,
            $crawler
                ->filter('.toast.text-bg-danger')
        );
    }

    public function testFilterCatchStateUnknown(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/r/demo/unknown');

        $this->assertCount(
            0,
            $crawler->filter('.album-case')
        );

        $this->assertCount(0, $crawler->filter('h2.box'));
    }
}
