<?php

declare(strict_types=1);

namespace App\Tests\Browser;

use App\Security\User;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\PantherTestCase;

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

    protected function loginUser(Client $client, User $user): void
    {
        $client->request('GET', '/fr/connect/f/c?t='.$user->getProfile());

        // JS with explicit path=/ : Panther's addCookie omits it, Firefox then defaults to the current URL path (/fr/connect/f/c) which doesn't match /fr/*
        // Consent is denied (not accepted): accepting it would make tarteaucitron load the real
        // matomo.pokenini.fr script (hardcoded in _tracker.html.twig, unlike the mocked matomoUrl),
        // causing net::ERR_NAME_NOT_RESOLVED in CI when that external host isn't reachable.
        $client->executeScript("document.cookie = 'tarteaucitron=!matomocloud=false; path=/';");
    }
}
