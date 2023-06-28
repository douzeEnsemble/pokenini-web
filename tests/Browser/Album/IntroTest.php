<?php

declare(strict_types=1);

namespace App\Tests\Browser\Album;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Browser\AbstractBrowserTestCase;

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

        $client->request('GET', '/fr/album/demolite');

        $this->assertSelectorIsNotVisible('.album-intro-details');

        $client->click(
            $client
            ->getCrawler()
            ->filter('#toggle-intro-details')
            ->link()
        );

        $this->assertSelectorWillBeVisible('.album-intro-details');

        $client->click(
            $client
            ->getCrawler()
            ->filter('#toggle-intro-details')
            ->link()
        );

        $this->assertSelectorWillNotBeVisible('.album-intro-details');
    }
}
