<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Connect;

use App\Security\E2eAuthenticator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[Group('api-mocked-testing')]
#[CoversClass(E2eAuthenticator::class)]
final class E2eConnectTest extends WebTestCase
{
    #[Test]
    public function connect(): void
    {
        $client = self::createClient();

        $client->request('POST', '/fr/connect/e2e', [], [], ['HTTP_X-E2E-Login' => 'the-e2e-secret']);

        $this->assertResponseRedirects('/fr');

        $client->request('GET', '/fr/trainer');

        $this->assertResponseIsSuccessful();
    }

    #[Test]
    public function connectWithoutSecret(): void
    {
        $client = self::createClient();

        $client->request('POST', '/fr/connect/e2e');

        $this->assertResponseStatusCodeSame(403);
        $this->assertSame('Invalid credentials.', $client->getResponse()->getContent());

        $client->request('GET', '/fr/trainer');

        $this->assertResponseRedirects();
    }

    #[Test]
    public function connectWithGet(): void
    {
        $client = self::createClient();

        $client->request('GET', '/fr/connect/e2e', [], [], ['HTTP_X-E2E-Login' => 'the-e2e-secret']);

        $this->assertResponseStatusCodeSame(405);
    }
}
