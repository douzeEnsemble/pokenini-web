<?php

declare(strict_types=1);

namespace App\Tests\Browser\Trainer;

use App\Tests\Browser\AbstractBrowserTestCase;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Panther\DomCrawler\Field\ChoiceFormField;

/**
 * @internal
 */
#[CoversNothing]
final class CustomAlbumTrainerTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

    #[Test]
    public function successTick(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $crawler = $client->request('GET', '/fr/trainer');

        $this->assertSelectorIsNotVisible('#successToast-goldsilvercrystal');

        $client->executeScript("document.getElementById('trainer-dex-goldsilvercrystal').scrollIntoView();");

        $form = $crawler->filter('form[data-dex="goldsilvercrystal"]')->form();

        /** @var ChoiceFormField $field */
        $field = $form->get('goldsilvercrystal-is_on_home');
        $field->tick();

        $this->assertSelectorWillBeVisible('#successToast-goldsilvercrystal');
        $this->assertSelectorWillNotBeVisible('#successToast-goldsilvercrystal');
        $this->assertSelectorWillNotBeVisible('#errorToast-goldsilvercrystal');
    }

    #[Test]
    public function successUntick(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $crawler = $client->request('GET', '/fr/trainer');

        $this->assertSelectorIsNotVisible('#successToast-goldsilvercrystal');

        $client->executeScript("document.getElementById('trainer-dex-goldsilvercrystal').scrollIntoView();");

        $form = $crawler->filter('form[data-dex="goldsilvercrystal"]')->form();

        /** @var ChoiceFormField $field */
        $field = $form->get('goldsilvercrystal-is_private');
        $field->untick();

        $this->assertSelectorWillBeVisible('#successToast-goldsilvercrystal');
        $this->assertSelectorWillNotBeVisible('#successToast-goldsilvercrystal');
        $this->assertSelectorWillNotBeVisible('#errorToast-goldsilvercrystal');
    }

    #[Test]
    public function errorTick(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $crawler = $client->request('GET', '/fr/trainer');

        $this->assertSelectorIsNotVisible('#errorToast-redgreenblueyellow');

        $client->executeScript("document.getElementById('trainer-dex-redgreenblueyellow').scrollIntoView();");

        $form = $crawler->filter('form[data-dex="redgreenblueyellow"]')->form();

        /** @var ChoiceFormField $field */
        $field = $form->get('redgreenblueyellow-is_on_home');
        $field->tick();

        $this->assertSelectorWillBeVisible('#errorToast-redgreenblueyellow');
        $this->assertSelectorWillNotBeVisible('#errorToast-redgreenblueyellow');
        $this->assertSelectorWillNotBeVisible('#successToast-redgreenblueyellow');
    }

    #[Test]
    public function errorUntick(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $crawler = $client->request('GET', '/fr/trainer');

        $this->assertSelectorIsNotVisible('#errorToast-redgreenblueyellow');

        $client->executeScript("document.getElementById('trainer-dex-redgreenblueyellow').scrollIntoView();");

        $form = $crawler->filter('form[data-dex="redgreenblueyellow"]')->form();

        /** @var ChoiceFormField $field */
        $field = $form->get('redgreenblueyellow-is_private');
        $field->untick();

        $this->assertSelectorWillBeVisible('#errorToast-redgreenblueyellow');
        $this->assertSelectorWillNotBeVisible('#errorToast-redgreenblueyellow');
        $this->assertSelectorWillNotBeVisible('#successToast-redgreenblueyellow');
    }
}
