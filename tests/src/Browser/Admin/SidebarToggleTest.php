<?php

declare(strict_types=1);

namespace App\Tests\Browser\Admin;

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

        $user = GetUserToken::getFakeUserToken('109903422692691643666', 'TestProvider');
        $user->addAdminRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/istration/versions');
        $client->executeScript("localStorage.removeItem('admin-sidebar-collapsed');");
        $client->request('GET', '/fr/istration/versions');

        $this->assertSelectorIsVisible('#admin-actions-tab');
        $this->assertSelectorAttributeContains('#admin-sidebar-toggle', 'aria-expanded', 'true');

        $expandedWidth = $client->executeScript("return document.getElementById('admin-main').offsetWidth;");
        $this->assertIsInt($expandedWidth);

        $client->executeScript("document.getElementById('admin-sidebar-toggle').click()");

        $this->assertSelectorWillNotBeVisible('#admin-actions-tab');
        $this->assertSelectorAttributeContains('#admin-sidebar-toggle', 'aria-expanded', 'false');
        $this->assertSelectorAttributeContains('#admin-sidebar-toggle', 'aria-label', 'Afficher le menu');

        $collapsedWidth = $client->executeScript("return document.getElementById('admin-main').offsetWidth;");
        $this->assertIsInt($collapsedWidth);
        $this->assertGreaterThan($expandedWidth, $collapsedWidth);

        // The choice is remembered across admin pages
        $client->request('GET', '/fr/istration/reports');

        $this->assertSelectorIsNotVisible('#admin-actions-tab');

        $client->executeScript("document.getElementById('admin-sidebar-toggle').click()");

        $this->assertSelectorWillBeVisible('#admin-actions-tab');
        $this->assertSelectorAttributeContains('#admin-sidebar-toggle', 'aria-expanded', 'true');

        $client->executeScript("localStorage.removeItem('admin-sidebar-collapsed');");
    }
}
