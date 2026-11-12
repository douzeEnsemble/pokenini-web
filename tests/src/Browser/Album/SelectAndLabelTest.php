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
final class SelectAndLabelTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

    #[Test]
    public function actionCatchStateGoldSilverCrystal(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('12', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $crawler = $client->request('GET', '/fr/album/goldsilvercrystal');

        $this->assertCountFilter($crawler, 3, '.album-case');
        $this->assertCountFilter($crawler, 3, '.album-case-action');
        $this->assertCountFilter($crawler, 3, '.album-case-action[hidden]');
        $this->assertCountFilter($crawler, 3, '.album-case-catch-state');
        $this->assertCountFilter($crawler, 0, '.album-case-catch-state[hidden]');
        $this->assertCountFilter($crawler, 3, '.album-case-catch-state a.album-case-catch-state-label');
        $this->assertCountFilter($crawler, 0, '.album-case-catch-state span.album-case-catch-state-label');
        $this->assertCountFilter(
            $crawler,
            3,
            '.album-case-catch-state .album-case-catch-state-edit-action'
        );

        $this->assertCountFilter($crawler, 1, '#chikorita .album-case-catch-state-edit-action');
    }

    #[Test]
    public function actionCatchStateDemo(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('12', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $crawler = $client->request('GET', '/fr/album/demo');

        $this->assertCountFilter($crawler, 25, '.album-case');
        $this->assertCountFilter($crawler, 25, '.album-case-action');
        $this->assertCountFilter($crawler, 25, '.album-case-action[hidden]');
        $this->assertCountFilter($crawler, 25, '.album-case-catch-state');
        $this->assertCountFilter($crawler, 0, '.album-case-catch-state[hidden]');
        $this->assertCountFilter($crawler, 25, '.album-case-catch-state a.album-case-catch-state-label');
        $this->assertCountFilter($crawler, 0, '.album-case-catch-state span.album-case-catch-state-label');
        $this->assertCountFilter(
            $crawler,
            25,
            '.album-case-catch-state .album-case-catch-state-edit-action'
        );

        $this->assertCountFilter($crawler, 1, '#bulbasaur .album-case-catch-state-edit-action');
    }

    #[Test]
    public function actionCatchStateDemoList3(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('12', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $crawler = $client->request('GET', '/fr/album/demolist3');

        $this->assertCountFilter($crawler, 41, '.album-case');
        $this->assertCountFilter($crawler, 41, '.album-case-action');
        $this->assertCountFilter($crawler, 41, '.album-case-action[hidden]');
        $this->assertCountFilter($crawler, 41, '.album-case-catch-state');
        $this->assertCountFilter($crawler, 0, '.album-case-catch-state[hidden]');
        $this->assertCountFilter($crawler, 41, '.album-case-catch-state a.album-case-catch-state-label');
        $this->assertCountFilter($crawler, 0, '.album-case-catch-state span.album-case-catch-state-label');
        $this->assertCountFilter(
            $crawler,
            41,
            '.album-case-catch-state .album-case-catch-state-edit-action'
        );

        $this->assertCountFilter($crawler, 1, '#bulbasaur .album-case-catch-state-edit-action');
    }

    #[Test]
    public function actionCatchStateOpensTheSheet(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('12', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/album/demo');

        $this->assertSelectorIsVisible('#bulbasaur .album-case-catch-state');
        $this->assertSelectorIsNotVisible('#bulbasaur .album-case-action');
        $this->assertSelectorIsNotVisible('#catch-state-sheet');

        $client->executeScript("document.getElementById('bulbasaur').scrollIntoView();");
        $this->clickElement($client, '#bulbasaur-catch-state-edit-action');

        $this->assertSelectorWillBeVisible('#catch-state-sheet');
        // The select stays hidden: the sheet replaces it
        $this->assertSelectorIsVisible('#bulbasaur .album-case-catch-state');
        $this->assertSelectorIsNotVisible('#bulbasaur .album-case-action');
        $this->assertSelectorTextContains('#catch-state-sheet-title', 'Bulbizarre');
        $this->assertSelectorAttributeContains('#catch-state-sheet [data-catch-state="no"]', 'aria-current', 'true');
    }

    #[Test]
    public function actionCatchStateOpensTheSheetWithLabel(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('12', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/album/demo');

        $client->executeScript("document.getElementById('bulbasaur').scrollIntoView();");
        $this->clickElement($client, '#bulbasaur .album-case-catch-state .album-case-catch-state-label');

        $this->assertSelectorWillBeVisible('#catch-state-sheet');
        $this->assertSelectorIsVisible('#bulbasaur .album-case-catch-state');
    }

    #[Test]
    public function sheetChangesTheStateAndCanBeUndone(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('12', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/album/demo');

        $this->assertSelectorAttributeContains('#bulbasaur', 'class', 'catch-state-no');

        $client->executeScript("document.getElementById('bulbasaur').scrollIntoView();");
        $this->clickElement($client, '#bulbasaur .album-case-catch-state .album-case-catch-state-label');
        $this->assertSelectorWillExist('#catch-state-sheet.show:not(.showing)');
        // The slide is over when the sheet no longer goes below the screen
        $client->getWebDriver()->wait(5)->until(
            static fn (): mixed => $client->executeScript(
                'return document.getElementById("catch-state-sheet").getBoundingClientRect().bottom <= window.innerHeight + 1;'
            )
        );
        $this->clickElement($client, '#catch-state-sheet [data-catch-state="totrade"]');

        $this->assertSelectorWillNotBeVisible('#catch-state-sheet');
        $this->assertSelectorWillBeVisible('#undoToast');
        $this->assertSelectorTextContains('#undoToast-text', 'Bulbizarre');
        $this->assertSelectorAttributeContains('#bulbasaur', 'class', 'catch-state-totrade');
        $this->assertSelectorAttributeNotContains('#bulbasaur', 'class', 'catch-state-no');

        $this->clickElement($client, '#undoToast-action');

        $this->assertSelectorWillNotBeVisible('#undoToast');
        $this->assertSelectorAttributeContains('#bulbasaur', 'class', 'catch-state-no');
        $this->assertSelectorAttributeNotContains('#bulbasaur', 'class', 'catch-state-totrade');
    }

    #[Test]
    public function seriesModeMarksSeveralCases(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('12', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/album/demo');

        $this->assertSelectorIsNotVisible('#series-bar');
        $this->clickElement($client, '.album-series-action');
        $this->assertSelectorWillBeVisible('#series-bar');
        $this->assertSelectorAttributeContains('.album-series-action', 'aria-pressed', 'true');

        // A tap before a state is chosen changes nothing and opens nothing
        $client->executeScript("document.getElementById('bulbasaur').scrollIntoView();");
        $this->clickElement($client, '#bulbasaur .album-case-image button');
        $this->assertSelectorIsNotVisible('#modal-bulbasaur');
        $this->assertSelectorAttributeContains('#bulbasaur', 'class', 'catch-state-no');

        $this->clickElement($client, '#series-bar [data-catch-state="totrade"]');
        $this->assertSelectorAttributeContains('#series-bar [data-catch-state="totrade"]', 'aria-pressed', 'true');

        $this->clickElement($client, '#bulbasaur .album-case-image button');
        $this->assertSelectorWillBeVisible('#undoToast');
        $this->assertSelectorAttributeContains('#bulbasaur', 'class', 'catch-state-totrade');

        $client->executeScript("document.getElementById('ivysaur').scrollIntoView();");
        $this->clickElement($client, '#ivysaur .album-case-image button');
        $this->assertSelectorWillBeVisible('#undoToast');
        $this->assertSelectorAttributeContains('#ivysaur', 'class', 'catch-state-totrade');
        $this->assertSelectorIsNotVisible('#modal-ivysaur');

        $this->clickElement($client, '#series-bar .series-bar-done');
        $this->assertSelectorWillNotBeVisible('#series-bar');
        $this->assertSelectorAttributeContains('.album-series-action', 'aria-pressed', 'false');

        // Out of the mode, the image opens the modal again
        $this->clickElement($client, '#ivysaur .album-case-image button');
        $this->assertSelectorWillBeVisible('#modal-ivysaur');
    }

    #[Test]
    public function actionCatchStateToggleAllEdit(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('12', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $crawler = $client->request('GET', '/fr/album/demo');

        $this->assertCountFilter($crawler, 25, '.album-case-action[hidden]');
        $this->assertCountFilter($crawler, 0, '.album-case-catch-state[hidden]');
        $this->assertCountFilter($crawler, 0, '.album-all-catch-state-edit-action[hidden]');
        $this->assertCountFilter($crawler, 1, '.album-all-catch-state-read-action[hidden]');

        $this->clickElement($client, '.album-all-catch-state-edit-action');

        $this->assertCountFilter($crawler, 0, '.album-case-action[hidden]');
        $this->assertCountFilter($crawler, 25, '.album-case-catch-state[hidden]');
        $this->assertCountFilter($crawler, 1, '.album-all-catch-state-edit-action[hidden]');
        $this->assertCountFilter($crawler, 0, '.album-all-catch-state-read-action[hidden]');
    }

    #[Test]
    public function actionCatchStateToggleAllEditThenRead(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('12', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $crawler = $client->request('GET', '/fr/album/demo');

        $this->assertCountFilter($crawler, 25, '.album-case-action[hidden]');
        $this->assertCountFilter($crawler, 0, '.album-case-catch-state[hidden]');
        $this->assertCountFilter($crawler, 0, '.album-all-catch-state-edit-action[hidden]');
        $this->assertCountFilter($crawler, 1, '.album-all-catch-state-read-action[hidden]');

        $this->clickElement($client, '.album-all-catch-state-edit-action');

        $this->assertCountFilter($crawler, 0, '.album-case-action[hidden]');
        $this->assertCountFilter($crawler, 25, '.album-case-catch-state[hidden]');
        $this->assertCountFilter($crawler, 1, '.album-all-catch-state-edit-action[hidden]');
        $this->assertCountFilter($crawler, 0, '.album-all-catch-state-read-action[hidden]');

        $this->clickElement($client, '.album-all-catch-state-read-action');

        $this->assertCountFilter($crawler, 25, '.album-case-action[hidden]');
        $this->assertCountFilter($crawler, 0, '.album-case-catch-state[hidden]');
        $this->assertCountFilter($crawler, 0, '.album-all-catch-state-edit-action[hidden]');
        $this->assertCountFilter($crawler, 1, '.album-all-catch-state-read-action[hidden]');
    }

    #[Test]
    public function actionCatchStateChangeSuccess(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('12', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/album/demo');

        $this->assertSelectorIsNotVisible('#successToast-bulbasaur');
        $this->assertSelectorIsNotVisible('#errorToast-bulbasaur');
        $this->assertSelectorAttributeContains('#bulbasaur', 'class', 'catch-state-no');
        $this->assertSelectorAttributeNotContains('#bulbasaur', 'class', 'catch-state-totrade');

        $client->executeScript("document.getElementById('bulbasaur').scrollIntoView();");

        $this->clickElement($client, '.album-all-catch-state-edit-action');

        $form = $client->getCrawler()->filter('#album-form')->form();

        /** @var ChoiceFormField $field */
        $field = $form->get('catch-state[bulbasaur]');
        $field->setValue('totrade');

        $this->assertSelectorWillBeVisible('#successToast-bulbasaur');
        $this->assertSelectorWillNotBeVisible('#successToast-bulbasaur');
        $this->assertSelectorWillNotBeVisible('#errorToast-bulbasaur');

        $this->assertSelectorAttributeContains('#bulbasaur', 'class', 'catch-state-totrade');
        $this->assertSelectorAttributeNotContains('#bulbasaur', 'class', 'catch-state-no');
    }

    #[Test]
    public function actionCatchStateChangeError(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('12', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/album/demo');

        $this->assertSelectorIsNotVisible('#errorToast-squirtle');
        $this->assertSelectorIsNotVisible('#successToast-squirtle');
        $this->assertSelectorAttributeContains('#squirtle', 'class', 'catch-state-no');
        $this->assertSelectorAttributeNotContains('#squirtle', 'class', 'catch-state-tobreed');

        $client->executeScript("document.getElementById('squirtle').scrollIntoView();");

        $this->clickElement($client, '.album-all-catch-state-edit-action');

        $form = $client->getCrawler()->filter('#album-form')->form();

        /** @var ChoiceFormField $field */
        $field = $form->get('catch-state[squirtle]');
        $field->setValue('tobreed');

        $this->assertSelectorWillBeVisible('#errorToast-squirtle');
        $this->assertSelectorWillNotBeVisible('#errorToast-squirtle');
        $this->assertSelectorWillNotBeVisible('#successToast-squirtle');

        // On a failed save, the case must revert to its last confirmed catch-state
        // instead of silently keeping the unsaved value the user picked.
        $this->assertSelectorAttributeContains('#squirtle', 'class', 'catch-state-no');
        $this->assertSelectorAttributeNotContains('#squirtle', 'class', 'catch-state-tobreed');
    }

    #[Test]
    public function actionCatchStatePendingChangeBlocksUnload(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('12', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/album/demo');

        $client->executeScript("document.getElementById('ivysaur').scrollIntoView();");

        $this->clickElement($client, '.album-all-catch-state-edit-action');

        $form = $client->getCrawler()->filter('#album-form')->form();

        /** @var ChoiceFormField $field */
        $field = $form->get('catch-state[ivysaur]');
        $field->setValue('toevolve');

        $blocksUnload = $client->executeScript(<<<'JS'
                const event = new Event('beforeunload', {cancelable: true});
                window.dispatchEvent(event);
                return event.defaultPrevented;
            JS);

        $this->assertTrue($blocksUnload);
    }

    #[Test]
    public function actionCatchStateResolvedChangeAllowsUnload(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('12', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/album/demo');

        $client->executeScript("document.getElementById('ivysaur').scrollIntoView();");

        $this->clickElement($client, '.album-all-catch-state-edit-action');

        $form = $client->getCrawler()->filter('#album-form')->form();

        /** @var ChoiceFormField $field */
        $field = $form->get('catch-state[ivysaur]');
        $field->setValue('toevolve');

        $this->assertSelectorWillBeVisible('#successToast-ivysaur');

        $blocksUnload = $client->executeScript(<<<'JS'
                const event = new Event('beforeunload', {cancelable: true});
                window.dispatchEvent(event);
                return event.defaultPrevented;
            JS);

        $this->assertFalse($blocksUnload);
    }

    #[Test]
    public function actionCatchStateFailedChangeAllowsUnload(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('12', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/album/demo');

        $client->executeScript("document.getElementById('squirtle').scrollIntoView();");

        $this->clickElement($client, '.album-all-catch-state-edit-action');

        $form = $client->getCrawler()->filter('#album-form')->form();

        /** @var ChoiceFormField $field */
        $field = $form->get('catch-state[squirtle]');
        $field->setValue('tobreed');

        $this->assertSelectorWillBeVisible('#errorToast-squirtle');

        $blocksUnload = $client->executeScript(<<<'JS'
                const event = new Event('beforeunload', {cancelable: true});
                window.dispatchEvent(event);
                return event.defaultPrevented;
            JS);

        $this->assertFalse($blocksUnload);
    }
}
