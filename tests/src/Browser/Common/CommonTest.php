<?php

declare(strict_types=1);

namespace App\Tests\Browser\Common;

use App\Tests\Browser\AbstractBrowserTestCase;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
#[CoversNothing]
final class CommonTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

    #[Test]
    public function openCookieManager(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr');

        // tarteaucitron injects its panel asynchronously, after the page is loaded
        $client->waitFor('#tarteaucitron');

        $this->assertSelectorIsNotVisible('#tarteaucitron');

        $client->executeScript("document.querySelector('#navbarNav .cookie-manager button').click();");

        $this->assertSelectorWillBeVisible('#tarteaucitron');
    }
}
