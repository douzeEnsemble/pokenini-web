<?php

declare(strict_types=1);

namespace App\Tests\Integration\Twig;

use App\Tests\Common\Traits\TestNavTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversNothing]
final class ErrorPageTest extends WebTestCase
{
    use TestNavTrait;

    public function testError404(): void
    {
        $crawler = $this->renderErrorTemplate(404, 'Not Found');

        $this->assertEquals("Pokénini La page n'a pas été trouvée", $crawler->filter('title')->text());
        $this->assertCountFilter($crawler, 1, '#main-container h1');
        $this->assertCountFilter($crawler, 1, '#main-container p');
        $this->assertCountFilter($crawler, 1, '#main-container a');
    }

    public function testError500(): void
    {
        $crawler = $this->renderErrorTemplate(500, 'Internal Server Error');

        $this->assertEquals("Pokénini Y'a eu une merde. C'est pas toi, c'est nous", $crawler->filter('title')->text());
        $this->assertCountFilter($crawler, 1, '#main-container h1');
        $this->assertCountFilter($crawler, 1, '#main-container p');
        $this->assertCountFilter($crawler, 1, '#main-container a');
    }

    public function testError512(): void
    {
        $crawler = $this->renderErrorTemplate(512, 'Custom Error');

        $this->assertEquals("Pokénini Y'a eu une erreur", $crawler->filter('title')->text());
        $this->assertCountFilter($crawler, 1, '#main-container h1');
        $this->assertCountFilter($crawler, 1, '#main-container p');
        $this->assertCountFilter($crawler, 1, '#main-container a');
    }

    private function renderErrorTemplate(int $statusCode, string $statusText): Crawler
    {
        self::bootKernel();
        $container = self::getContainer();

        $request = Request::create('/fr/_error/'.$statusCode, 'GET');
        $request->setLocale('fr');

        $requestStack = $container->get('request_stack');

        $requestStack->push($request);

        $twig = $container->get('twig');

        $template = sprintf('bundles/TwigBundle/Exception/error%d.html.twig', $statusCode);
        $fallback = 'bundles/TwigBundle/Exception/error.html.twig';
        $templateName = $twig->getLoader()->exists($template) ? $template : $fallback;

        $html = $twig->render($templateName, [
            'status_code' => $statusCode,
            'status_text' => $statusText,
        ]);

        $requestStack->pop();

        return new Crawler($html);
    }
}
