<?php

declare(strict_types=1);

namespace App\Tests\Browser\Admin;

use App\Tests\Browser\AbstractBrowserTestCase;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Panther\Client;

/**
 * The server side is replaced by a fetch() stub (a POST marks the action as done, a GET answers
 * with the current page where the finished actions are marked), so that the chaining logic of the
 * page can be checked without any real action running.
 *
 * @internal
 */
#[CoversNothing]
final class RunAllTest extends AbstractBrowserTestCase
{
    private const string FETCH_STUB = <<<'JS'
        window.__posted = [];
        window.__done = {};
        let tick = Math.floor(Date.now() / 1000) + 1000;
        window.fetch = async (url, options) => {
            if ('POST' === ((options && options.method) || 'GET')) {
                const match = String(url).match(/action\/(update|calculate)\/(\w+)$/);
                window.__posted.push(match[1] + '_' + match[2]);
                window.__done[match[1] + '_' + match[2]] = {at: ++tick, state: window.__failing === match[1] + '_' + match[2] ? 'ko' : 'done'};
            }
            const doc = new DOMParser().parseFromString(document.documentElement.outerHTML, 'text/html');
            doc.querySelectorAll('.admin-item[data-current-state="pending"]').forEach(card => {
                window.__done[card.id] = {at: ++tick, state: 'done'};
            });
            Object.entries(window.__done).forEach(([id, result]) => {
                const card = doc.getElementById(id);
                card.dataset.currentState = result.state;
                card.dataset.currentCreatedAt = String(result.at);
            });
            return {ok: true, status: 200, text: async () => doc.documentElement.outerHTML};
        };
        JS;

    #[Test]
    public function runAllStartsEachStepInOrderOnceThePreviousOneIsDone(): void
    {
        $client = $this->openPage('109903422692691644444');

        $client->executeScript(self::FETCH_STUB);

        $expected = $this->script($client, 'return Array.from(document.querySelectorAll(".admin-item:not([data-current-state=pending])")).map(c => c.id);');

        $this->runAll($client);

        $this->waitUntil(
            $client,
            fn () => str_contains($this->pipelineStatus($client), 'terminées'),
            'the steps to be finished',
        );

        $this->assertSame($expected, $this->script($client, 'return window.__posted;'));
        $this->assertFalse((bool) $client->executeScript('return document.getElementById("run-all-button").disabled;'));

        // Once the chain is over, the link to the next page (which starts its own chain) shows up.
        $this->assertFalse((bool) $client->executeScript('return document.getElementById("run-all-next").hidden;'));

        /** @psalm-suppress MixedAssignment */
        $nextHref = $client->executeScript('return document.getElementById("run-all-next").href;');
        $this->assertIsString($nextHref);
        $this->assertStringContainsString('/istration/update_availabilities?run_all=1', $nextHref);
    }

    #[Test]
    public function aFailingStepStopsThePipeline(): void
    {
        $client = $this->openPage('109903422692691644555');

        $client->executeScript(self::FETCH_STUB);
        $client->executeScript('window.__failing = "update_pokemons";');

        $this->runAll($client);

        $this->waitUntil(
            $client,
            fn () => str_contains($this->pipelineStatus($client), 'Arrêt'),
            'the chain to stop',
        );

        $posted = $this->script($client, 'return window.__posted;');
        $this->assertContains('update_pokemons', $posted);
        $this->assertNotContains('update_regional_dex_numbers', $posted);
        $this->assertStringContainsString('Mise à jour des pokémons', $this->pipelineStatus($client));
    }

    /**
     * @return list<string>
     */
    private function script(Client $client, string $script): array
    {
        /** @psalm-suppress MixedAssignment */
        $result = $client->executeScript($script);

        $list = [];
        if (\is_array($result)) {
            /** @psalm-suppress MixedAssignment */
            foreach ($result as $item) {
                $list[] = \is_string($item) ? $item : '';
            }
        }

        return $list;
    }

    private function pipelineStatus(Client $client): string
    {
        /** @psalm-suppress MixedAssignment */
        $text = $client->executeScript('return document.getElementById("run-all-status").textContent;');

        return \is_string($text) ? $text : '';
    }

    private function openPage(string $userId): Client
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken($userId, 'TestProvider');
        $user->addAdminRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/istration/update_data');

        return $client;
    }

    private function runAll(Client $client): void
    {
        $client->executeScript('document.getElementById("run-all-button").scrollIntoView({block: "center"}); document.getElementById("run-all-button").click();');

        $message = $this->answerConfirmModal($client, '.confirm-modal-ok');
        $this->assertStringContainsString('4 étapes', $message);
    }
}
