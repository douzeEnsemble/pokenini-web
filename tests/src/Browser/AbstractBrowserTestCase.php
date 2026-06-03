<?php

declare(strict_types=1);

namespace App\Tests\Browser;

use App\Security\User;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\PantherTestCase;

abstract class AbstractBrowserTestCase extends PantherTestCase
{
    protected static function getNewClient(): Client
    {
        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability('acceptInsecureCerts', true);

        return static::createPantherClient(
            ['browser' => static::SELENIUM],
            [],
            [
                'host' => 'http://chrome:4444/wd/hub',
                'capabilities' => $capabilities,
            ],
        );
    }

    protected function loginUser(Client $client, User $user): void
    {
        $client->request('GET', '/fr/connect/f/c?t='.$user->getProfile());

        $cookieJar = $client->getCookieJar();
        $trackerCookie = new Cookie('tarteaucitron', '!matomocloud=true', null, null, '', false, false);
        $cookieJar->set($trackerCookie);
    }
}
