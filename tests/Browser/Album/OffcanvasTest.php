<?php

declare(strict_types=1);

namespace App\Tests\Browser\Album;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Browser\AbstractBrowserTestCase;

/**
 * @group browser-testing
 */
class OffcanvasTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

    public function testOffcanvas(): void
    {
        $client = $this->getNewClient();

        $user = new User('109903422692691643666');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/album/demolite');

        $this->assertSelectorIsNotVisible('#offcanvas');

        $client->executeScript('document.querySelector(\'.open-offcanvas\').click()');

        $this->assertSelectorWillBeVisible('#offcanvas');

        $client->executeScript('document.querySelector(\'#offcanvas .btn-close\').click()');

        $this->assertSelectorWillNotBeVisible('#offcanvas');
    }
}
