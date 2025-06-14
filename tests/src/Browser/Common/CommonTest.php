<?php

declare(strict_types=1);

namespace App\Tests\Browser\Common;

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
class CommonTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

    public function testOpenCookieManager(): void
    {
        $client = $this->getNewClient();

        $user = new User('789465465489', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr');

        $this->assertSelectorIsNotVisible('#tarteaucitron');

        $client->executeScript("document.querySelector('#navbarNav .cookie-manager button').click();");

        $this->assertSelectorWillBeVisible('#tarteaucitron');
    }
}
