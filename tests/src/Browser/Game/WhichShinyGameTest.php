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
final class WhichShinyGameTest extends AbstractBrowserTestCase
{
    #[Test]
    public function answerWithKeysAndClicks(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/game/which-shiny/11111111-1111-4111-8111-111111111111');

        $this->assertSelectorTextContains('.round-position', 'Manche 2 sur 3');

        $client->getKeyboard()->sendKeys('2');

        $this->assertSelectorAttributeContains('.which-shiny-choice[data-key="2"]', 'aria-pressed', 'true');
        $this->assertSelectorExists('.which-shiny-choices');

        $client->getKeyboard()->sendKeys('1');

        $this->assertSelectorAttributeContains('.which-shiny-choice[data-key="1"]', 'aria-pressed', 'true');
        $this->assertSelectorAttributeContains('.which-shiny-choice[data-key="2"]', 'aria-pressed', 'false');

        $client->getKeyboard()->sendKeys('2');
        $client->getKeyboard()->sendKeys(WebDriverKeys::ENTER);
        $client->waitFor('.answer-result');

        $this->assertSelectorExists('.which-shiny-answers');
        $this->assertSelectorNotExists('.which-shiny-choices');

        $client->getKeyboard()->sendKeys(WebDriverKeys::ENTER);
        $client->waitFor('.which-shiny-choices');

        $client->getCrawler()->filter('.which-shiny-choice')->eq(0)->click();

        $this->assertSelectorAttributeContains('.which-shiny-choice[data-key="1"]', 'aria-pressed', 'true');
        $this->assertSelectorExists('.which-shiny-choices');

        $client->getCrawler()->filter('.which-shiny-choice')->eq(0)->click();
        $client->waitFor('.answer-result');

        $this->assertSelectorExists('.which-shiny-answers');
    }
}
