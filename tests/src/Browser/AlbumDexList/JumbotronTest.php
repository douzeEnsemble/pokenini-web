<?php

declare(strict_types=1);

namespace App\Tests\Browser\AlbumDexList;

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
final class JumbotronTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

    #[Test]
    public function jumbotronClosing(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('109903422692691643666', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/album/dex');

        $this->assertSelectorIsVisible('#jumbotron');

        $client->executeScript('document.querySelector(\'#jumbotron .btn-close\').click()');

        $this->assertSelectorWillNotBeVisible('#jumbotron');
    }

    #[Test]
    public function jumbotronHiddenSaved(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('109903422692691643666', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/album/dex');

        $client->executeScript('localStorage.setItem(\'app/album-dex-list/jumbotron/hidden\', \'true\')');

        $client->request('GET', '/fr/album/dex');

        $this->assertSelectorIsNotVisible('#jumbotron');
    }
}
