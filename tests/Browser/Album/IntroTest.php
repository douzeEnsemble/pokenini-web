<?php

declare(strict_types=1);

namespace App\Tests\Browser\Album;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Browser\AbstractBrowserTestCase;
use Symfony\Component\Panther\DomCrawler\Field\ChoiceFormField;

/**
 * @group browser-testing
 */
class IntroTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

    public function testIntroToggleDetails(): void
    {
        $client = $this->getClient();

        $user = new User('109903422692691643666');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $crawler = $client->request('GET', '/fr/album/demolite');

        $this->assertCountFilter($crawler, 9, '#intro .list-group .list-group-item');
        $this->assertCountFilter($crawler, 4, '#intro .list-group .list-group-item[hidden]');
        $this->assertSelectorIsNotVisible('.album-intro-details');

        $crawler = $client->click(
            $client
            ->getCrawler()
            ->filter('#toggle-intro-details')
            ->link()
        );

        $this->assertSelectorWillBeVisible('.album-intro-details');

        $crawler = $client->click(
            $client
            ->getCrawler()
            ->filter('#toggle-intro-details')
            ->link()
        );

        $this->assertSelectorWillNotBeVisible('.album-intro-details');
    }
}
