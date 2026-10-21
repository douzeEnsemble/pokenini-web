<?php

declare(strict_types=1);

namespace App\Tests\Browser\Game;

use App\Tests\Browser\AbstractBrowserTestCase;
use App\Tests\Utils\GetUserToken;
use Facebook\WebDriver\WebDriverKeys;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
#[CoversNothing]
#[Group('api-mocked-testing')]
final class ColorGameTest extends AbstractBrowserTestCase
{
    #[Test]
    public function answerWithKeysAndClicks(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/game/color/11111111-1111-4111-8111-111111111111');

        $this->assertSelectorTextContains('.round-position', 'Manche 2 sur 3');

        $client->getKeyboard()->sendKeys('2');

        $this->assertSelectorAttributeContains('.color-choice[data-key="2"]', 'aria-pressed', 'true');
        $this->assertSelectorExists('.color-choices');

        $client->getKeyboard()->sendKeys('1');

        $this->assertSelectorAttributeContains('.color-choice[data-key="1"]', 'aria-pressed', 'true');
        $this->assertSelectorAttributeContains('.color-choice[data-key="2"]', 'aria-pressed', 'false');

        $client->getKeyboard()->sendKeys('2');
        $client->getKeyboard()->sendKeys(WebDriverKeys::ENTER);
        $client->waitFor('.answer-result');

        $this->assertSelectorExists('.color-answers');
        $this->assertSelectorNotExists('.color-choices');

        $client->getKeyboard()->sendKeys(WebDriverKeys::ENTER);
        $client->waitFor('.color-choices');

        $client->getCrawler()->filter('.color-choice')->eq(0)->click();

        $this->assertSelectorAttributeContains('.color-choice[data-key="1"]', 'aria-pressed', 'true');
        $this->assertSelectorExists('.color-choices');

        $client->getCrawler()->filter('.color-choice')->eq(0)->click();
        $client->waitFor('.answer-result');

        $this->assertSelectorExists('.color-answers');
    }
}
