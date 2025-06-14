<?php

declare(strict_types=1);

namespace App\Tests\Functional\StaticPage;

use App\Controller\StaticPageController;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(StaticPageController::class)]
class StaticPageTest extends WebTestCase
{
    public function testFrenchLegals(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/legals');

        $this->assertResponseStatusCodeSame(200);

        $this->assertStringContainsString('Mentions Légales', $crawler->filter('#main-container')->text());
    }

    public function testEnglishLegals(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/en/legals');

        $this->assertResponseStatusCodeSame(200);

        $this->assertStringContainsString('Legal Notice', $crawler->filter('#main-container')->text());
    }

    public function testFrenchPolicy(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/policy');

        $this->assertResponseStatusCodeSame(200);

        $this->assertStringContainsString('Politique de confidentialité', $crawler->filter('#main-container')->text());
    }

    public function testEnglishPolicy(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/en/policy');

        $this->assertResponseStatusCodeSame(200);

        $this->assertStringContainsString('Privacy Policy', $crawler->filter('#main-container')->text());
    }

    public function testFrenchCookies(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/cookies');

        $this->assertResponseStatusCodeSame(200);

        $this->assertStringContainsString('Cookies', $crawler->filter('#main-container')->text());
    }

    public function testEnglishCookies(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/en/cookies');

        $this->assertResponseStatusCodeSame(200);

        $this->assertStringContainsString('Cookies', $crawler->filter('#main-container')->text());
    }
}
