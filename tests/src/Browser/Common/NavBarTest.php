<?php

declare(strict_types=1);

namespace App\Tests\Browser\Common;

use App\Tests\Browser\AbstractBrowserTestCase;
use App\Tests\Utils\GetUserToken;
use Facebook\WebDriver\WebDriverDimension;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
#[CoversNothing]
final class NavBarTest extends AbstractBrowserTestCase
{
    #[Test]
    public function morePanelOpensAboveTheTabBarAndCloses(): void
    {
        $client = $this->getNewClient();
        $client->manage()->window()->setSize(new WebDriverDimension(390, 800));

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr');

        // The tab bar is there, the rest of the items wait in the panel
        $this->assertSelectorIsVisible('.nav-main .album-link');
        $this->assertSelectorIsNotVisible('#navbarNav');
        $this->assertSelectorAttributeContains('.nav-more button', 'aria-expanded', 'false');

        $client->executeScript("document.querySelector('.nav-more button').click();");
        // "show" comes when the opening transition ends: only then does it count as open
        $this->assertSelectorWillExist('#navbarNav.show');
        $this->assertSelectorWillBeVisible('#navbarNav');
        $this->assertSelectorAttributeContains('.nav-more button', 'aria-expanded', 'true');

        // The panel is above the tab bar, which stays visible
        $tabBarTop = $client->executeScript("return document.querySelector('.nav-main').getBoundingClientRect().top;");
        $panelBottom = $client->executeScript("return document.getElementById('navbarNav').getBoundingClientRect().bottom;");
        $this->assertIsNumeric($tabBarTop);
        $this->assertIsNumeric($panelBottom);
        $this->assertLessThanOrEqual((float) $tabBarTop, (float) $panelBottom);
        $this->assertSelectorIsVisible('.nav-main .album-link');

        // Escape puts it away
        $client->executeScript("document.dispatchEvent(new KeyboardEvent('keydown', {key: 'Escape'}));");
        $this->assertSelectorWillNotBeVisible('#navbarNav');
        $this->assertSelectorAttributeContains('.nav-more button', 'aria-expanded', 'false');
    }

    #[Test]
    public function morePanelClosesOnATapOutside(): void
    {
        $client = $this->getNewClient();
        $client->manage()->window()->setSize(new WebDriverDimension(390, 800));

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr');

        $client->executeScript("document.querySelector('.nav-more button').click();");
        $this->assertSelectorWillExist('#navbarNav.show');

        $client->executeScript('document.body.click();');
        $this->assertSelectorWillNotBeVisible('#navbarNav');
    }

    #[Test]
    public function wideScreenShowsOneRow(): void
    {
        $client = $this->getNewClient();
        $client->manage()->window()->setSize(new WebDriverDimension(1280, 800));

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr');

        // No "More" tab, everything is in sight
        $this->assertSelectorIsNotVisible('.nav-more');
        $this->assertSelectorIsVisible('#navbarNav');
        $this->assertSelectorIsVisible('.cookie-manager');
        $this->assertSelectorIsVisible('.lang-switch');
    }
}
