<?php

declare(strict_types=1);

namespace App\Tests\Browser\Election;

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
final class ElectionTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

    #[Test]
    public function checked(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/election/demolite');

        $this->assertSelectorTextContains('#election-counter-bottom', '0');

        $client->executeScript("
            var checkbox = document.querySelector('#vote-bulbasaur');
            checkbox.checked = true;
            var event = new Event('change', { bubbles: true });
            checkbox.dispatchEvent(event);
        ");

        $this->assertSelectorTextContains('#election-counter-bottom', '1');

        $client->executeScript("
            var checkbox = document.querySelector('#vote-ivysaur');
            checkbox.checked = true;
            var event = new Event('change', { bubbles: true });
            checkbox.dispatchEvent(event);
        ");

        $this->assertSelectorTextContains('#election-counter-bottom', '2');

        $client->executeScript("
            var checkbox = document.querySelector('#vote-ivysaur');
            checkbox.checked = false;
            var event = new Event('change', { bubbles: true });
            checkbox.dispatchEvent(event);
        ");

        $this->assertSelectorTextContains('#election-counter-bottom', '1');
    }

    #[Test]
    public function welcomeModalVisible(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/election/swordshield');

        $this->assertSelectorIsVisible('#election-modal-welcome');
        $this->assertSelectorIsNotVisible('#election-modal-filters');
        $this->assertSelectorIsNotVisible('#election-modal-filters-advanced');
    }

    #[Test]
    public function welcomeModalHidden(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/election/swordshield?at[]=ground&at[]=rock');

        $this->assertSelectorIsNotVisible('#election-modal-welcome');
        $this->assertSelectorIsNotVisible('#election-modal-filters');
        $this->assertSelectorIsNotVisible('#election-modal-filters-advanced');
    }

    #[Test]
    public function welcomeModalHiddenBis(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/election/swordshield/favorite');

        $this->assertSelectorIsNotVisible('#election-modal-welcome');
        $this->assertSelectorIsNotVisible('#election-modal-filters');
        $this->assertSelectorIsNotVisible('#election-modal-filters-advanced');
    }
}
