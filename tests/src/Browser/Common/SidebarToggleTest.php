<?php

declare(strict_types=1);

namespace App\Tests\Browser\Common;

use App\Tests\Browser\AbstractBrowserTestCase;
use App\Tests\Utils\GetUserToken;
use Facebook\WebDriver\WebDriverDimension;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
#[CoversNothing]
final class SidebarToggleTest extends AbstractBrowserTestCase
{
    #[DataProvider('providerSidebars')]
    #[Test]
    public function toggleSidebar(
        string $name,
        string $navId,
        string $identifier,
        string $role,
        string $firstPage,
        string $secondPage,
    ): void {
        $client = $this->getNewClient();
        $client->manage()->window()->setSize(new WebDriverDimension(1280, 1024));

        $user = GetUserToken::getFakeUserToken($identifier, 'TestProvider');
        match ($role) {
            'admin' => $user->addAdminRole(),
            default => $user->addTrainerRole(),
        };
        $this->loginUser($client, $user);

        $storageKey = "{$name}-sidebar-collapsed";

        $client->request('GET', $firstPage);
        $client->executeScript("localStorage.removeItem('{$storageKey}');");
        $client->request('GET', $firstPage);

        $this->assertSelectorIsVisible("#{$navId}");
        $this->assertSelectorAttributeContains("#{$name}-sidebar-toggle", 'aria-expanded', 'true');

        $expandedWidth = $client->executeScript("return document.getElementById('{$name}-main').offsetWidth;");
        $this->assertIsInt($expandedWidth);

        $client->executeScript("document.getElementById('{$name}-sidebar-toggle').click()");

        $this->assertSelectorWillNotBeVisible("#{$navId}");
        $this->assertSelectorAttributeContains("#{$name}-sidebar-toggle", 'aria-expanded', 'false');
        $this->assertSelectorAttributeContains("#{$name}-sidebar-toggle", 'aria-label', 'Afficher le menu');

        $collapsedWidth = $client->executeScript("return document.getElementById('{$name}-main').offsetWidth;");
        $this->assertIsInt($collapsedWidth);
        $this->assertGreaterThan($expandedWidth, $collapsedWidth);

        // The choice is remembered across the pages sharing the sidebar
        $client->request('GET', $secondPage);

        $this->assertSelectorIsNotVisible("#{$navId}");

        $client->executeScript("document.getElementById('{$name}-sidebar-toggle').click()");

        $this->assertSelectorWillBeVisible("#{$navId}");
        $this->assertSelectorAttributeContains("#{$name}-sidebar-toggle", 'aria-expanded', 'true');

        $client->executeScript("localStorage.removeItem('{$storageKey}');");
    }

    /**
     * @return array<string, array{name: string, navId: string, identifier: string, role: string, firstPage: string, secondPage: string}>
     */
    public static function providerSidebars(): array
    {
        return [
            'admin' => [
                'name' => 'admin',
                'navId' => 'admin-actions-tab',
                'identifier' => '109903422692691643666',
                'role' => 'admin',
                'firstPage' => '/fr/istration/versions',
                'secondPage' => '/fr/istration/reports',
            ],
            'trainer' => [
                'name' => 'trainer',
                'navId' => 'trainer-section-tab',
                'identifier' => '789465465489',
                'role' => 'trainer',
                'firstPage' => '/fr/trainer',
                'secondPage' => '/fr/trainer/links',
            ],
        ];
    }
}
