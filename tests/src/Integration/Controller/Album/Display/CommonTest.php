<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Album\Display;

use App\Controller\AlbumIndexController;
use App\Service\GetTrainerPokedexService;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(AlbumIndexController::class)]
#[CoversClass(GetTrainerPokedexService::class)]
#[Group('api-mocked-testing')]
final class CommonTest extends WebTestCase
{
    use TestNavTrait;

    #[Test]
    public function listRead(): void
    {
        $client = self::createClient();

        $client->request('GET', '/fr/album/demolite?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertAlbum($client);
        $this->assertReadMode($client);
        $this->assertRegular($client);
        $this->assertStatistics($client);
        $this->assertNavigationBar($client);
        $this->assertNoConnectedNavBar($client->getCrawler());
    }

    #[Test]
    public function listEdit(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('12', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $client->request('GET', '/fr/album/demolite?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertAlbum($client);
        $this->assertWriteMode($client);
        $this->assertRegular($client);
        $this->assertStatistics($client);
        $this->assertTrainerAlbumNavBar($client->getCrawler());
    }

    #[Test]
    public function listShiny(): void
    {
        $client = self::createClient();

        $client->request('GET', '/fr/album/demoliteshiny?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertShiny($client);
    }

    /**
     * Brand new dex has an issue with a division by zero.
     */
    #[Test]
    public function listVirgin(): void
    {
        $client = self::createClient();

        $client->request('GET', '/fr/album/virgin?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertAlbum($client);
        $this->assertViriginStatistics($client);
    }

    /**
     * Testing with caches cleared.
     */
    #[Test]
    public function listCachesCleared(): void
    {
        self::bootKernel();
        self::getContainer()->get('cache.app')->clear();
        self::ensureKernelShutdown();

        $this->listVirgin();
    }

    #[Test]
    public function imageCreditBadgeIsShownWhenCreditExists(): void
    {
        $client = self::createClient();
        $client->request('GET', '/fr/album/demolite?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $crawler = $client->getCrawler();

        // The Pokedex grid never renders the credit badge - too much visual
        // noise at grid scale - only the modal detail view does.
        $this->assertCount(0, $crawler->filter('#bulbasaur .pokemon-image-credit'));

        $badge = $crawler->filter('#modal-bulbasaur a.album-modal-icon-regular .pokemon-image-credit');
        $this->assertCount(1, $badge);
        $this->assertStringContainsString('PokéSprite', $badge->filter('.visually-hidden')->text());
        // The credit link lives inside the tooltip content, not on the badge's own click:
        // touch devices have no hover, so a tap must always reveal the attribution first.
        $tooltipTitle = (string) $badge->first()->attr('data-bs-title');
        $this->assertStringContainsString('<a href="', $tooltipTitle);
        $this->assertStringContainsString('github.com', $tooltipTitle);
        $this->assertStringContainsString('msikma', $tooltipTitle);
        $this->assertStringContainsString('pokesprite', $tooltipTitle);
        $this->assertSame('event.stopPropagation();', (string) $badge->first()->attr('onclick'));
    }

    private function assertAlbum(KernelBrowser $client): void
    {
        $this->assertResponseIsSuccessful();

        $crawler = $client->getCrawler();

        $expectedPokemonCount = 41;

        $this->assertCountFilter($crawler, $expectedPokemonCount, '.album-case');

        $this->assertEquals(
            'https://icon.pokenini.fr/pokemon/small/regular/bulbasaur.png',
            $crawler->filter('#bulbasaur .album-case-image img')->attr('src')
        );

        $this->assertEquals(
            html_entity_decode('&nbsp;'),
            $crawler->filter('#bulbasaur .album-case-forms')->text()
        );
        $this->assertEquals(
            '♀️',
            $crawler->filter('#venusaur-f .album-case-forms')->text()
        );

        $this->assertCountFilter($crawler, 1, '#bulbasaur.album-case.catch-state-no');
        $this->assertCountFilter($crawler, 1, '#ivysaur.album-case.catch-state-no');
        $this->assertCountFilter($crawler, 1, '#venusaur.album-case.catch-state-toevolve');
        $this->assertCountFilter($crawler, 1, '#venusaur-f.album-case.catch-state-tobreed');
        $this->assertCountFilter($crawler, 1, '#venusaur-mega.album-case.catch-state-totransfer');
        $this->assertCountFilter($crawler, 1, '#venusaur-gmax.album-case.catch-state-totrade');
        $this->assertCountFilter($crawler, 1, '#charmander.album-case.catch-state-yes');
    }

    private function assertReadMode(KernelBrowser $client): void
    {
        $crawler = $client->getCrawler();

        $this->assertCountFilter($crawler, 0, '.album-case select');
        $this->assertCountFilter($crawler, 41, '.album-case .album-case-catch-state');
        $this->assertCountFilter($crawler, 0, '.album-case .album-case-catch-state a.album-case-catch-state-label');
        $this->assertCountFilter($crawler, 41, '.album-case .album-case-catch-state span.album-case-catch-state-label');

        $this->assertCountFilter($crawler, 2, '.toast');

        $this->assertCountFilter($crawler, 1, 'script[src="/js/album.js"]');
        $this->assertCountFilter($crawler, 0, 'script[src="/js/album-edit.js"]');

        $this->assertStringNotContainsString('const catchStates = JSON.parse', $crawler->outerHtml());
        $this->assertStringNotContainsString('watchCatchStates();', $crawler->outerHtml());
    }

    private function assertWriteMode(KernelBrowser $client): void
    {
        $crawler = $client->getCrawler();

        $this->assertCountFilter($crawler, 41, '.album-case select');

        $this->assertCountFilter($crawler, 6, '#bulbasaur select option');

        $this->assertCountFilter($crawler, 41, '.album-case .album-case-catch-state');
        $this->assertCountFilter($crawler, 41, '.album-case .album-case-catch-state a.album-case-catch-state-label');
        $this->assertCountFilter($crawler, 0, '.album-case .album-case-catch-state span.album-case-catch-state-label');

        $this->assertCountFilter($crawler, 88, '.toast');
        $this->assertCountFilter($crawler, 43, '.toast.text-bg-success');
        $this->assertCountFilter($crawler, 1, '.toast.text-bg-light');
        $this->assertCountFilter($crawler, 44, '.toast.text-bg-danger');

        $this->assertCountFilter($crawler, 1, 'script[src="/js/album.js"]');

        $this->assertStringContainsString('const catchStates = JSON.parse', $crawler->outerHtml());
        $this->assertStringContainsString('watchCatchStates();', $crawler->outerHtml());
    }

    private function assertStatistics(KernelBrowser $client): void
    {
        $crawler = $client->getCrawler();

        $this->assertCountFilter($crawler, 1, 'h2#stats');

        $this->assertCountFilter($crawler, 1, '.progress');
        $this->assertCountFilter($crawler, 6, '.progress-bar');

        $this->assertEquals(
            '45.95%',
            $crawler->filter('.progress-bar.catch-state-no')->text()
        );
        $this->assertEmpty(
            $crawler->filter('.progress-bar.catch-state-toevolve')->text()
        );
        $this->assertEmpty(
            $crawler->filter('.progress-bar.catch-state-tobreed')->text()
        );
        $this->assertEmpty(
            $crawler->filter('.progress-bar.catch-state-totransfer')->text()
        );
        $this->assertEquals(
            '54.05%',
            $crawler->filter('.progress-bar.catch-state-yes')->text()
        );

        $this->assertSame('popover', $crawler->filter('.progress')->attr('data-bs-toggle'));
        $this->assertSame('Stats', $crawler->filter('.progress')->attr('data-bs-title'));

        $popoverContent = (string) $crawler->filter('.progress')->attr('data-bs-content');
        $this->assertStringContainsString('catch-state-dot catch-state-no', $popoverContent);
        $this->assertStringContainsString('catch-state-dot catch-state-yes', $popoverContent);
        $this->assertStringContainsString('catch-state-dot catch-state-toevolve', $popoverContent);
        $this->assertStringContainsString('catch-state-dot catch-state-tobreed', $popoverContent);
        $this->assertStringContainsString('catch-state-dot catch-state-totransfer', $popoverContent);
        $this->assertStringContainsString('catch-state-dot catch-state-totrade', $popoverContent);
        $this->assertStringContainsString('Non : 45.95% (17)', $popoverContent);
        $this->assertStringContainsString('Oui : 54.05% (20)', $popoverContent);
        $this->assertStringContainsString('Af. évoluer', $popoverContent);
        $this->assertStringContainsString('Af. reproduire', $popoverContent);
        $this->assertStringContainsString('À transférer', $popoverContent);
        $this->assertStringContainsString('À échanger', $popoverContent);

        $this->assertCountFilter($crawler, 1, 'table#report');
        $this->assertCountFilter($crawler, 7, 'table#report tr');

        $this->assertCountFilter($crawler, 1, 'table#report tr.catch-state-no');
        $this->assertCountFilter($crawler, 1, 'table#report tr.catch-state-toevolve');
        $this->assertCountFilter($crawler, 1, 'table#report tr.catch-state-tobreed');
        $this->assertCountFilter($crawler, 1, 'table#report tr.catch-state-totransfer');
        $this->assertCountFilter($crawler, 1, 'table#report tr.catch-state-yes');
        $this->assertCountFilter($crawler, 1, 'table#report tr.catch-state-total');

        $this->assertEquals('', $crawler->filter('table#report tr.catch-state-no td')->eq(0)->text());
        $this->assertEquals('17', $crawler->filter('table#report tr.catch-state-no td')->eq(1)->text());

        $this->assertEquals('', $crawler->filter('table#report tr.catch-state-toevolve td')->eq(0)->text());
        $this->assertEquals('1', $crawler->filter('table#report tr.catch-state-toevolve td')->eq(1)->text());

        $this->assertEquals('', $crawler->filter('table#report tr.catch-state-tobreed td')->eq(0)->text());
        $this->assertEquals('1', $crawler->filter('table#report tr.catch-state-tobreed td')->eq(1)->text());

        $this->assertEquals('', $crawler->filter('table#report tr.catch-state-totransfer td')->eq(0)->text());
        $this->assertEquals('1', $crawler->filter('table#report tr.catch-state-totransfer td')->eq(1)->text());

        $this->assertEquals('', $crawler->filter('table#report tr.catch-state-yes td')->eq(0)->text());
        $this->assertEquals('20', $crawler->filter('table#report tr.catch-state-yes td')->eq(1)->text());

        $this->assertEquals('', $crawler->filter('table#report tr.catch-state-total th')->eq(1)->text());
        $this->assertEquals('37', $crawler->filter('table#report tr.catch-state-total th')->eq(2)->text());

        $this->assertStringContainsString(
            '/album/demolite?cs=no&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            (string) $crawler->filter('table#report tr.catch-state-no a')->attr('href')
        );
        $this->assertStringContainsString(
            '/album/demolite?cs=toevolve&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            (string) $crawler->filter('table#report tr.catch-state-toevolve a')->attr('href')
        );
        $this->assertStringContainsString(
            '/album/demolite?cs=tobreed&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            (string) $crawler->filter('table#report tr.catch-state-tobreed a')->attr('href')
        );
        $this->assertStringContainsString(
            '/album/demolite?cs=totransfer&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            (string) $crawler->filter('table#report tr.catch-state-totransfer a')->attr('href')
        );
        $this->assertStringContainsString(
            '/album/demolite?cs=yes&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            (string) $crawler->filter('table#report tr.catch-state-yes a')->attr('href')
        );
        $this->assertStringContainsString(
            '/album/demolite',
            (string) $crawler->filter('table#report tr.catch-state-total a')->attr('href')
        );
    }

    private function assertViriginStatistics(KernelBrowser $client): void
    {
        $crawler = $client->getCrawler();

        $this->assertCountFilter($crawler, 1, 'h2#stats');

        $this->assertCountFilter($crawler, 1, '.progress');
        $this->assertCountFilter($crawler, 6, '.progress-bar');

        $this->assertEquals(
            '0%',
            $crawler->filter('.progress-bar.catch-state-no')->text()
        );
        $this->assertEmpty(
            $crawler->filter('.progress-bar.catch-state-toevolve')->text()
        );
        $this->assertEmpty(
            $crawler->filter('.progress-bar.catch-state-tobreed')->text()
        );
        $this->assertEmpty(
            $crawler->filter('.progress-bar.catch-state-totransfer')->text()
        );
        $this->assertEquals(
            '0%',
            $crawler->filter('.progress-bar.catch-state-yes')->text()
        );

        $popoverContent = (string) $crawler->filter('.progress')->attr('data-bs-content');
        $this->assertStringContainsString('catch-state-dot catch-state-no', $popoverContent);
        $this->assertStringContainsString('catch-state-dot catch-state-yes', $popoverContent);
        $this->assertStringContainsString('Non : 0% (0)', $popoverContent);
        $this->assertStringContainsString('Oui : 0% (0)', $popoverContent);

        $this->assertCountFilter($crawler, 1, 'table#report');
        $this->assertCountFilter($crawler, 7, 'table#report tr');

        $this->assertCountFilter($crawler, 1, 'table#report tr.catch-state-no');
        $this->assertCountFilter($crawler, 1, 'table#report tr.catch-state-toevolve');
        $this->assertCountFilter($crawler, 1, 'table#report tr.catch-state-tobreed');
        $this->assertCountFilter($crawler, 1, 'table#report tr.catch-state-totransfer');
        $this->assertCountFilter($crawler, 1, 'table#report tr.catch-state-yes');
        $this->assertCountFilter($crawler, 1, 'table#report tr.catch-state-total');

        $this->assertEquals('', $crawler->filter('table#report tr.catch-state-no td')->eq(0)->text());
        $this->assertEquals('0', $crawler->filter('table#report tr.catch-state-no td')->eq(1)->text());

        $this->assertEquals('', $crawler->filter('table#report tr.catch-state-toevolve td')->eq(0)->text());
        $this->assertEquals('0', $crawler->filter('table#report tr.catch-state-toevolve td')->eq(1)->text());

        $this->assertEquals('', $crawler->filter('table#report tr.catch-state-tobreed td')->eq(0)->text());
        $this->assertEquals('0', $crawler->filter('table#report tr.catch-state-tobreed td')->eq(1)->text());

        $this->assertEquals('', $crawler->filter('table#report tr.catch-state-totransfer td')->eq(0)->text());
        $this->assertEquals('0', $crawler->filter('table#report tr.catch-state-totransfer td')->eq(1)->text());

        $this->assertEquals('', $crawler->filter('table#report tr.catch-state-yes td')->eq(0)->text());
        $this->assertEquals('0', $crawler->filter('table#report tr.catch-state-yes td')->eq(1)->text());

        $this->assertEquals('', $crawler->filter('table#report tr.catch-state-total th')->eq(1)->text());
        $this->assertEquals('0', $crawler->filter('table#report tr.catch-state-total th')->eq(2)->text());

        $this->assertStringContainsString(
            '/album/virgin?cs=no&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            (string) $crawler->filter('table#report tr.catch-state-no a')->attr('href')
        );
        $this->assertStringContainsString(
            '/album/virgin?cs=toevolve&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            (string) $crawler->filter('table#report tr.catch-state-toevolve a')->attr('href')
        );
        $this->assertStringContainsString(
            '/album/virgin?cs=tobreed&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            (string) $crawler->filter('table#report tr.catch-state-tobreed a')->attr('href')
        );
        $this->assertStringContainsString(
            '/album/virgin?cs=totransfer&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            (string) $crawler->filter('table#report tr.catch-state-totransfer a')->attr('href')
        );
        $this->assertStringContainsString(
            '/album/virgin?cs=yes&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            (string) $crawler->filter('table#report tr.catch-state-yes a')->attr('href')
        );
        $this->assertStringContainsString(
            '/album/virgin',
            (string) $crawler->filter('table#report tr.catch-state-total a')->attr('href')
        );
    }

    private function assertNavigationBar(KernelBrowser $client): void
    {
        $crawler = $client->getCrawler();

        $this->assertNoConnectedNavBar($crawler);
    }

    private function assertRegular(KernelBrowser $client): void
    {
        $crawler = $client->getCrawler();

        $this->assertStringContainsString(
            '/regular/',
            $crawler->filter('.pokemon-icon')->first()->attr('src') ?? ''
        );
    }

    private function assertShiny(KernelBrowser $client): void
    {
        $crawler = $client->getCrawler();

        $this->assertStringContainsString(
            '/shiny/',
            $crawler->filter('.pokemon-icon')->first()->attr('src') ?? ''
        );
    }
}
