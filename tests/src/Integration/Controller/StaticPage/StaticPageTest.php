<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\StaticPage;

use App\Controller\StaticPageController;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(StaticPageController::class)]
final class StaticPageTest extends WebTestCase
{
    public function testFrenchLegals(): void
    {
        $client = self::createClient();

        $crawler = $client->request('GET', '/fr/legals');

        $this->assertResponseStatusCodeSame(200);

        $this->assertStringContainsString('Mentions Légales', $crawler->filter('#main-container')->text());
    }

    public function testEnglishLegals(): void
    {
        $client = self::createClient();

        $crawler = $client->request('GET', '/en/legals');

        $this->assertResponseStatusCodeSame(200);

        $this->assertStringContainsString('Legal Notice', $crawler->filter('#main-container')->text());
    }

    public function testFrenchPolicy(): void
    {
        $client = self::createClient();

        $crawler = $client->request('GET', '/fr/policy');

        $this->assertResponseStatusCodeSame(200);

        $this->assertStringContainsString('Politique de confidentialité', $crawler->filter('#main-container')->text());
    }

    public function testEnglishPolicy(): void
    {
        $client = self::createClient();

        $crawler = $client->request('GET', '/en/policy');

        $this->assertResponseStatusCodeSame(200);

        $this->assertStringContainsString('Privacy Policy', $crawler->filter('#main-container')->text());
    }

    public function testFrenchCookies(): void
    {
        $client = self::createClient();

        $crawler = $client->request('GET', '/fr/cookies');

        $this->assertResponseStatusCodeSame(200);

        $this->assertStringContainsString('Cookies', $crawler->filter('#main-container')->text());
    }

    public function testEnglishCookies(): void
    {
        $client = self::createClient();

        $crawler = $client->request('GET', '/en/cookies');

        $this->assertResponseStatusCodeSame(200);

        $this->assertStringContainsString('Cookies', $crawler->filter('#main-container')->text());
    }
}
