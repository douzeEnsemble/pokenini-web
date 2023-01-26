<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Security\User;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\PantherTestCase;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

abstract class AbstractBrowserTestCase extends PantherTestCase
{
    protected static function getClient()
    {
        return static::createPantherClient(
            ['browser' => static::CHROME],
            [],
            [
                'capabilities' => [
                    'acceptInsecureCerts' => true
                ],
            ]
        );
    }

    protected function loginUser(Client $client, User $user): void
    {
        $session = $this->getContainer()->get('session.factory')->createSession();

        $firewallName = 'main';
        $firewallContext = 'main';

        $token = new PostAuthenticationToken($user, $firewallName, $user->getRoles());
        $session->set('_security_'.$firewallContext, serialize($token));
        $session->save();

        $client->request('GET', '/');

        $cookie = new Cookie($session->getName(), $session->getId(), null, null, '127.0.0.1');

        $client->getCookieJar()->set($cookie);
    }
}
