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
final class OffcanvasLinksStickyControlsTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

    #[Test]
    public function directionControlsStickToTopOfOffcanvas(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('109903422692691643666', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/album/demolite');

        $client->executeScript("document.querySelector('.open-offcanvas').click()");
        $this->assertSelectorWillBeVisible('#offcanvas');
        $client->waitFor('#offcanvas.show:not(.showing)');

        $client->waitFor('.dex-pick-card.linked');

        $position = $client->executeScript("return getComputedStyle(document.querySelector('.dex-link-controls')).position;");

        $this->assertSame('sticky', $position);

        // Behavioral proof: the stylesheet rule alone would still "pass" with a
        // wrong scrolling ancestor, an ancestor clipping with overflow: hidden,
        // or a containing block too short to allow pinning. Scroll the actual
        // offcanvas body 400px past the links section and check the controls
        // stayed pinned to the top of that scrolling container.
        $client->executeScript("
            var body = document.querySelector('.offcanvas-body');
            var section = document.getElementById('album-links-section');
            body.scrollTop += section.getBoundingClientRect().top - body.getBoundingClientRect().top + 400;
        ");

        // A sticky element pins to the scrollport's content edge, so the
        // container's own padding-top has to be discounted here. Without
        // pinning the controls would have scrolled far above that edge, and
        // the reported offset shows by how much.
        $this->assertSame('pinned', $client->executeScript("
            var body = document.querySelector('.offcanvas-body');
            var offset = Math.round(
                document.querySelector('.dex-link-controls').getBoundingClientRect().top
                - body.getBoundingClientRect().top
                - parseFloat(getComputedStyle(body).paddingTop)
            );

            return Math.abs(offset) <= 2 ? 'pinned' : 'offset:' + offset;
        "));
    }
}
