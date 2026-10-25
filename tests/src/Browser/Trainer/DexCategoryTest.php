<?php

declare(strict_types=1);

namespace App\Tests\Browser\Trainer;

use App\Tests\Browser\AbstractBrowserTestCase;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
#[CoversNothing]
final class DexCategoryTest extends AbstractBrowserTestCase
{
    #[Test]
    public function headingFoldsAndUnfoldsTheGroup(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/trainer');

        $this->assertSelectorIsVisible('#dex-category-home-list');

        $client->executeScript("document.querySelector('#dex-category-home .dex-category-toggle').click();");

        $this->assertSelectorWillNotBeVisible('#dex-category-home-list');
        $this->assertSelectorIsVisible('#dex-category-main-list');

        $client->executeScript("document.querySelector('#dex-category-home .dex-category-toggle').click();");

        $this->assertSelectorWillBeVisible('#dex-category-home-list');
    }

    #[Test]
    public function summaryLinkUnfoldsTheGroup(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/trainer');

        $client->executeScript("document.querySelector('#dex-category-special .dex-category-toggle').click();");

        $this->assertSelectorWillNotBeVisible('#dex-category-special-list');

        $client->executeScript("document.querySelector('.dex-category-link[href=\"#dex-category-special\"]').click();");

        $this->assertSelectorWillBeVisible('#dex-category-special-list');
    }
}
