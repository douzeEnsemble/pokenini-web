<?php

declare(strict_types=1);

namespace App\Tests\Browser\Album;

use App\Tests\Browser\AbstractBrowserTestCase;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Panther\DomCrawler\Field\ChoiceFormField;

/**
 * @internal
 */
#[CoversNothing]
#[Group('api-mocked-testing')]
final class PrivacyHomeToggleTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

    #[Test]
    public function successTickIsOnHome(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $crawler = $client->request('GET', '/fr/album/goldsilvercrystal');

        $this->assertSelectorIsNotVisible('#successToast-goldsilvercrystal');

        $client->executeScript("document.querySelector('.open-offcanvas').click()");
        $this->assertSelectorWillBeVisible('#offcanvas');
        $client->waitFor('#offcanvas.show:not(.showing)');

        $client->executeScript("document.getElementById('offcanvas-goldsilvercrystal-is_on_home').scrollIntoView({block: 'center'});");

        $form = $crawler->filter('#offcanvas form[data-dex="goldsilvercrystal"]')->form();

        /** @var ChoiceFormField $field */
        $field = $form->get('goldsilvercrystal-is_on_home');
        $field->tick();

        $this->assertSelectorWillBeVisible('#successToast-goldsilvercrystal');
        $this->assertSelectorWillNotBeVisible('#successToast-goldsilvercrystal');
        $this->assertSelectorWillNotBeVisible('#errorToast-goldsilvercrystal');
    }

    #[Test]
    public function successUntickIsPrivate(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $crawler = $client->request('GET', '/fr/album/goldsilvercrystal');

        $this->assertSelectorIsNotVisible('#successToast-goldsilvercrystal');

        $client->executeScript("document.querySelector('.open-offcanvas').click()");
        $this->assertSelectorWillBeVisible('#offcanvas');
        $client->waitFor('#offcanvas.show:not(.showing)');

        $form = $crawler->filter('#offcanvas form[data-dex="goldsilvercrystal"]')->form();

        /** @var ChoiceFormField $field */
        $field = $form->get('goldsilvercrystal-is_private');
        $field->untick();

        $this->assertSelectorWillBeVisible('#successToast-goldsilvercrystal');
        $this->assertSelectorWillNotBeVisible('#successToast-goldsilvercrystal');
        $this->assertSelectorWillNotBeVisible('#errorToast-goldsilvercrystal');
    }

    #[Test]
    public function errorTickIsOnHome(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $user->addAdminRole();
        $this->loginUser($client, $user);

        $crawler = $client->request('GET', '/fr/album/redgreenblueyellow');

        $this->assertSelectorIsNotVisible('#errorToast-redgreenblueyellow');

        $client->executeScript("document.querySelector('.open-offcanvas').click()");
        $this->assertSelectorWillBeVisible('#offcanvas');
        $client->waitFor('#offcanvas.show:not(.showing)');

        $form = $crawler->filter('#offcanvas form[data-dex="redgreenblueyellow"]')->form();

        /** @var ChoiceFormField $field */
        $field = $form->get('redgreenblueyellow-is_on_home');
        $field->tick();

        $this->assertSelectorWillBeVisible('#errorToast-redgreenblueyellow');
        $this->assertSelectorWillNotBeVisible('#errorToast-redgreenblueyellow');
        $this->assertSelectorWillNotBeVisible('#successToast-redgreenblueyellow');
    }

    #[Test]
    public function errorUntickIsPrivate(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $user->addAdminRole();
        $this->loginUser($client, $user);

        $crawler = $client->request('GET', '/fr/album/redgreenblueyellow');

        $this->assertSelectorIsNotVisible('#errorToast-redgreenblueyellow');

        $client->executeScript("document.querySelector('.open-offcanvas').click()");
        $this->assertSelectorWillBeVisible('#offcanvas');
        $client->waitFor('#offcanvas.show:not(.showing)');

        $form = $crawler->filter('#offcanvas form[data-dex="redgreenblueyellow"]')->form();

        /** @var ChoiceFormField $field */
        $field = $form->get('redgreenblueyellow-is_private');
        $field->untick();

        $this->assertSelectorWillBeVisible('#errorToast-redgreenblueyellow');
        $this->assertSelectorWillNotBeVisible('#errorToast-redgreenblueyellow');
        $this->assertSelectorWillNotBeVisible('#successToast-redgreenblueyellow');
    }
}
