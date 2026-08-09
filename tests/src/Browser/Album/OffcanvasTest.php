<?php

declare(strict_types=1);

namespace App\Tests\Browser\Album;

use App\Tests\Browser\AbstractBrowserTestCase;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
#[CoversNothing]
#[Group('api-mocked-testing')]
final class OffcanvasTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

    #[Test]
    public function offcanvas(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('109903422692691643666', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/album/demolite');

        $this->assertSelectorIsNotVisible('#offcanvas');

        $client->executeScript("document.querySelector('.open-offcanvas').click()");

        $this->assertSelectorWillBeVisible('#offcanvas');

        $client->executeScript("document.querySelector('#offcanvas .btn-close').click()");

        $this->assertSelectorWillNotBeVisible('#offcanvas');
    }
}
