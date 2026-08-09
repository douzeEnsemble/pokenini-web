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
final class ShareLinkTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

    #[Test]
    public function clickingShareLinkWithWorkingClipboardShowsSuccessToastAndDoesNotNavigate(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('12', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $crawler = $client->request('GET', '/fr/album/demoliteshiny');

        $client->executeScript(
            "Object.defineProperty(navigator, 'clipboard', { configurable: true, value: { writeText: () => Promise.resolve() } });"
        );

        $this->assertSelectorIsNotVisible('#shareToastSuccess');
        $this->assertSelectorIsNotVisible('#shareToastError');

        $urlBeforeClick = $client->getCurrentURL();

        $client->click($crawler->filter('#intro .share')->link());

        $this->assertSame($urlBeforeClick, $client->getCurrentURL());

        $this->assertSelectorWillBeVisible('#shareToastSuccess');
        $this->assertSelectorWillNotBeVisible('#shareToastSuccess');
        $this->assertSelectorWillNotBeVisible('#shareToastError');
    }

    #[Test]
    public function clickingShareLinkWithFailingClipboardShowsErrorToastAndDoesNotNavigate(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('12', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $crawler = $client->request('GET', '/fr/album/demoliteshiny');

        $client->executeScript(
            "Object.defineProperty(navigator, 'clipboard', { configurable: true, value: { writeText: () => Promise.reject() } });"
        );

        $this->assertSelectorIsNotVisible('#shareToastSuccess');
        $this->assertSelectorIsNotVisible('#shareToastError');

        $urlBeforeClick = $client->getCurrentURL();

        $client->click($crawler->filter('#intro .share')->link());

        $this->assertSame($urlBeforeClick, $client->getCurrentURL());

        $this->assertSelectorWillBeVisible('#shareToastError');
        $this->assertSelectorWillNotBeVisible('#shareToastError');
        $this->assertSelectorWillNotBeVisible('#shareToastSuccess');
    }
}
