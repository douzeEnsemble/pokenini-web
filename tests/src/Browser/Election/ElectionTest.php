<?php

declare(strict_types=1);

namespace App\Tests\Browser\Election;

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
class ElectionTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

    public function testChecked(): void
    {
        $client = $this->getNewClient();

        $user = new User('789465465489', 'TestProvider');
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

    public function testWelcomeModalVisible(): void
    {
        $client = $this->getNewClient();

        $user = new User('789465465489', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/election/swordshield');

        $this->assertSelectorIsVisible('#election-modal-welcome');
        $this->assertSelectorIsNotVisible('#election-modal-filters');
        $this->assertSelectorIsNotVisible('#election-modal-filters-advanced');
    }

    public function testWelcomeModalHidden(): void
    {
        $client = $this->getNewClient();

        $user = new User('789465465489', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/election/swordshield?at[]=ground&at[]=rock');

        $this->assertSelectorIsNotVisible('#election-modal-welcome');
        $this->assertSelectorIsNotVisible('#election-modal-filters');
        $this->assertSelectorIsNotVisible('#election-modal-filters-advanced');
    }

    public function testWelcomeModalHiddenBis(): void
    {
        $client = $this->getNewClient();

        $user = new User('789465465489', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/election/swordshield/favorite');

        $this->assertSelectorIsNotVisible('#election-modal-welcome');
        $this->assertSelectorIsNotVisible('#election-modal-filters');
        $this->assertSelectorIsNotVisible('#election-modal-filters-advanced');
    }
}
