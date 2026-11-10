<?php

declare(strict_types=1);

namespace App\Tests\Browser;

use App\Security\User;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\PantherTestCase;

/**
 * Every browser test extends it, so its number of children grows with the browser tests.
 *
 * @SuppressWarnings("PHPMD.NumberOfChildren")
 */
abstract class AbstractBrowserTestCase extends PantherTestCase
{
    protected static function getNewClient(): Client
    {
        $rawHost = getenv('PANTHER_SELENIUM_HOST');
        $host = false !== $rawHost ? $rawHost : 'http://chrome:4444/wd/hub';
        $rawBrowserName = getenv('PANTHER_BROWSER_NAME');
        $browserName = false !== $rawBrowserName ? $rawBrowserName : 'chrome';

        $capabilities = 'firefox' === $browserName
            ? DesiredCapabilities::firefox()
            : DesiredCapabilities::chrome();
        $capabilities->setCapability('acceptInsecureCerts', true);

        return static::createPantherClient(
            ['browser' => static::SELENIUM],
            [],
            [
                'host' => $host,
                'capabilities' => $capabilities,
            ],
        );
    }

    /**
     * Waits for the condition like `$client->wait()->until()`, but a timeout tells where the browser was:
     * its URL, its title and the beginning of the text it shows, which the bare timeout message does not.
     *
     * @param callable(): mixed $condition
     */
    protected function waitUntil(Client $client, callable $condition, string $description): void
    {
        try {
            $client->wait()->until($condition);
        } catch (TimeoutException $e) {
            throw new \RuntimeException(
                sprintf(
                    "Timeout while waiting for %s.\nURL: %s\nCookies now: %s\nTitle: %s\nText: %s",
                    $description,
                    $client->getCurrentURL(),
                    $this->describeCookies($client),
                    $client->getTitle(),
                    mb_substr(trim($client->findElement(WebDriverBy::cssSelector('body'))->getText()), 0, 1500),
                ),
                0,
                $e,
            );
        }
    }

    /**
     * The cookies of the browser as "name=short hash of the value", to compare the session at two moments
     * without writing it in the logs.
     */
    protected function describeCookies(Client $client): string
    {
        $cookies = [];
        foreach ($client->getCookieJar()->all() as $cookie) {
            $cookies[] = sprintf('%s=%s', $cookie->getName(), substr(sha1($cookie->getValue()), 0, 8));
        }

        return implode(', ', $cookies);
    }

    /**
     * Clicks the first element matching the selector through WebDriver (works for buttons, unlike Crawler::link()).
     */
    protected function clickElement(Client $client, string $cssSelector): void
    {
        $client->getWebDriver()->findElement(WebDriverBy::cssSelector($cssSelector))->click();
    }

    /**
     * Waits for the Bootstrap confirmation modal built by public/js/admin.js (showConfirmModal)
     * to be fully shown, then returns its text and clicks the requested button.
     *
     * @param '.confirm-modal-cancel'|'.confirm-modal-ok' $buttonSelector
     */
    protected function answerConfirmModal(Client $client, string $buttonSelector): string
    {
        $client->waitForVisibility('.modal.show '.$buttonSelector);
        // Bootstrap ignores hide() while the fade transition runs: wait until the dialog has settled.
        $client->wait()->until(static fn () => (bool) $client->executeScript(<<<'JS'
            const dialog = document.querySelector('.modal.show .modal-dialog');
            return null !== dialog && ['none', 'matrix(1, 0, 0, 1, 0, 0)'].includes(getComputedStyle(dialog).transform);
            JS));
        $message = $client->findElement(WebDriverBy::cssSelector('.modal.show .confirm-modal-message'))->getText();
        $client->findElement(WebDriverBy::cssSelector('.modal.show '.$buttonSelector))->click();

        return $message;
    }

    protected function loginUser(Client $client, User $user): void
    {
        $client->request('GET', '/fr/connect/f/c?t='.$user->getProfile());

        // JS with explicit path=/ : Panther's addCookie omits it, Firefox then defaults to the current URL path (/fr/connect/f/c) which doesn't match /fr/*
        // Consent is denied (not accepted): accepting it would make tarteaucitron load the real
        // matomo.pokenini.fr script (hardcoded in _tracker.html.twig, unlike the mocked matomoUrl),
        // causing net::ERR_NAME_NOT_RESOLVED in CI when that external host isn't reachable.
        $client->executeScript("document.cookie = 'tarteaucitron=!matomocloud=false; path=/';");
    }

    /**
     * Keeps the body of every request the page sends with fetch, to check what the page saved.
     *
     * The wrapper is injected as a page script and the bodies are kept in the DOM: a WebDriver
     * script may run apart from the page (Firefox), and could neither wrap its fetch nor read its variables.
     */
    protected function recordFetchBodies(Client $client): void
    {
        $client->executeScript(<<<'JS'
            const script = document.createElement('script');
            script.textContent = `
                document.body.dataset.fetchBodies = '[]';
                const originalFetch = window.fetch;
                window.fetch = function (resource, options) {
                    if (resource instanceof Request) {
                        resource.clone().text().then(function (body) {
                            const bodies = JSON.parse(document.body.dataset.fetchBodies);
                            bodies.push(body);
                            document.body.dataset.fetchBodies = JSON.stringify(bodies);
                        });
                    }

                    return originalFetch(resource, options);
                };
            `;
            document.head.appendChild(script);
            JS);
    }

    /**
     * @return string[]
     */
    protected function getFetchBodies(Client $client): array
    {
        /** @var string $bodies */
        $bodies = $client->executeScript('return document.body.dataset.fetchBodies;');

        /** @var string[] */
        return json_decode($bodies, true, 512, JSON_THROW_ON_ERROR);
    }
}
