<?php

declare(strict_types=1);

namespace App\Tests\Browser\Trainer;

use App\Tests\Browser\AbstractBrowserTestCase;
use App\Tests\Utils\GetUserToken;
use Facebook\WebDriver\WebDriverDimension;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
#[CoversNothing]
final class SidebarToggleTest extends AbstractBrowserTestCase
{
    #[Test]
    public function toggleSidebar(): void
    {
        $client = $this->getNewClient();
        $client->manage()->window()->setSize(new WebDriverDimension(1280, 1024));

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/trainer');
        $client->executeScript("localStorage.removeItem('trainer-sidebar-collapsed');");
        $client->request('GET', '/fr/trainer');

        $this->assertSelectorIsVisible('#trainer-section-tab');
        $this->assertSelectorAttributeContains('#trainer-sidebar-toggle', 'aria-expanded', 'true');

        $expandedWidth = $client->executeScript("return document.getElementById('trainer-main').offsetWidth;");
        $this->assertIsInt($expandedWidth);

        $client->executeScript("document.getElementById('trainer-sidebar-toggle').click()");

        $this->assertSelectorWillNotBeVisible('#trainer-section-tab');
        $this->assertSelectorAttributeContains('#trainer-sidebar-toggle', 'aria-expanded', 'false');
        $this->assertSelectorAttributeContains('#trainer-sidebar-toggle', 'aria-label', 'Afficher le menu');

        $collapsedWidth = $client->executeScript("return document.getElementById('trainer-main').offsetWidth;");
        $this->assertIsInt($collapsedWidth);
        $this->assertGreaterThan($expandedWidth, $collapsedWidth);

        // The choice is remembered across trainer pages
        $client->request('GET', '/fr/trainer/links');

        $this->assertSelectorIsNotVisible('#trainer-section-tab');

        $client->executeScript("document.getElementById('trainer-sidebar-toggle').click()");

        $this->assertSelectorWillBeVisible('#trainer-section-tab');
        $this->assertSelectorAttributeContains('#trainer-sidebar-toggle', 'aria-expanded', 'true');

        $client->executeScript("localStorage.removeItem('trainer-sidebar-collapsed');");
    }
}
