<?php

declare(strict_types=1);

namespace App\Tests\Browser\Album;

use App\Tests\Browser\AbstractBrowserTestCase;
use App\Tests\Common\Traits\TestNavTrait;
use App\Security\User;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

/**
 * @internal
 */
#[CoversNothing]
#[Group('browser-testing')]
class ScreenshotModeTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

    public function testScreenshotMode(): void
    {
        $client = $this->getNewClient();

        $user = new User('109903422692691643666', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/album/demolite');

        $this->assertSelectorIsVisible('.screenshot-mode-on');
        $this->assertSelectorIsNotVisible('.screenshot-mode-off');
        $this->assertSelectorIsVisible('.album-case-catch-state');

        $client->click(
            $client
                ->getCrawler()
                ->filter('.screenshot-mode-on')
                ->link()
        );

        $this->assertSelectorWillBeVisible('.screenshot-mode-off');
        $this->assertSelectorWillNotBeVisible('.screenshot-mode-on');
        $this->assertSelectorWillNotBeVisible('.album-case-catch-state');

        $client->click(
            $client
                ->getCrawler()
                ->filter('.screenshot-mode-off')
                ->link()
        );

        $this->assertSelectorWillBeVisible('.screenshot-mode-on');
        $this->assertSelectorWillNotBeVisible('.screenshot-mode-off');
        $this->assertSelectorWillBeVisible('.album-case-catch-state');
    }
}
