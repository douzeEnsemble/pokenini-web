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
    public function answerWithKeys(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/game/color/11111111-1111-4111-8111-111111111111');

        $this->assertSelectorTextContains('.round-position', 'Manche 2 sur 3');

        $client->getKeyboard()->sendKeys('2');
        $client->waitFor('.answer-result');

        $this->assertSelectorExists('.color-answers');
        $this->assertSelectorNotExists('.color-choices');

        $client->getKeyboard()->sendKeys(WebDriverKeys::ENTER);
        $client->waitFor('.color-choices');

        $this->assertSelectorNotExists('.answer-result');
    }
}
