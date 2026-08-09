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
final class ProgressBarPopoverTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

    #[Test]
    public function popoverOpensOnClick(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('109903422692691643666', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/album/demolite');

        $this->assertSelectorNotExists('.popover');

        $client->executeScript('document.querySelector(\'.report-container .progress\').click()');

        $this->assertSelectorWillExist('.popover');
        $this->assertSelectorIsVisible('.popover');
    }

    /**
     * Regression guard: Bootstrap's popover content is HTML-sanitized and
     * strips inline style="" attributes, which used to leave the legend's
     * per-state color dots invisible (0x0, transparent bg). The dot's size
     * and color must come from CSS classes, not inline style, to survive
     * that sanitization and actually render.
     */
    #[Test]
    public function popoverColorDotSurvivesSanitization(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('109903422692691643666', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/album/demolite');

        $client->executeScript('document.querySelector(\'.report-container .progress\').click()');
        $this->assertSelectorWillBeVisible('.popover');

        $this->assertSelectorExists('.popover-body .catch-state-dot.catch-state-no');

        $dotWidth = $client->executeScript(<<<'JS'
                const dot = document.querySelector('.popover-body .catch-state-dot.catch-state-no');
                return getComputedStyle(dot).width;
            JS);
        $dotBackgroundColor = $client->executeScript(<<<'JS'
                const dot = document.querySelector('.popover-body .catch-state-dot.catch-state-no');
                return getComputedStyle(dot).backgroundColor;
            JS);

        $this->assertIsString($dotWidth);
        $this->assertIsString($dotBackgroundColor);
        $this->assertNotSame('0px', $dotWidth);
        $this->assertNotSame('rgba(0, 0, 0, 0)', $dotBackgroundColor);
    }

    #[Test]
    public function popoverTogglesClosedOnSecondClick(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('109903422692691643666', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/album/demolite');

        $client->executeScript('document.querySelector(\'.report-container .progress\').click()');
        $this->assertSelectorWillBeVisible('.popover');

        $client->executeScript('document.querySelector(\'.report-container .progress\').click()');
        $this->assertSelectorWillNotExist('.popover');
    }
}
