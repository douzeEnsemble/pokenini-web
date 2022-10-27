<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\AlbumController;

use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlbumControllerDisplayFormTest extends WebTestCase
{
    use TestNavTrait;

    public function testDisplayForm(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr/album/r/home');

        $mainCrawler = $client->getCrawler();

        $this->assertEquals('Printemps', $mainCrawler->filter('#deerling-spring .album-case-forms')->text());
    }

    public function testNonDisplayForm(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr/album/r/homepokemongo');

        $mainCrawler = $client->getCrawler();

        $this->assertEquals(' ', $mainCrawler->filter('#deerling-spring .album-case-forms')->text());
    }
}
