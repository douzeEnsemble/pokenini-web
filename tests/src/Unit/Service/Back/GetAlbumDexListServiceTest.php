<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Security\UserTokenServiceInterface;
use App\Service\Back\AbstractBackService;
use App\Service\Back\GetAlbumDexListService;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
#[CoversClass(GetAlbumDexListService::class)]
final class GetAlbumDexListServiceTest extends AbstractTestBackService
{
    public function testGet(): void
    {
        $service = $this->getMockService(
            '/app/tests/resources/unit/service/back/dex.json',
            'album/dex',
        );

        $expectedSlugs = [
            'homepokemongo',
            'alpha',
            'mega',
        ];

        $this->assertEquals(
            $expectedSlugs,
            self::extractSlugs($service->get()),
        );
    }

    public function testGetWithEmptyTrainerId(): void
    {
        /** @var GetAlbumDexListService $service */
        $service = $this->getServiceWithoutLoggedUser(
            'GET',
            (string) file_get_contents('/app/tests/resources/unit/service/back/dex.json'),
            'album/dex',
        );

        $expectedSlugs = [
            'homepokemongo',
            'alpha',
            'mega',
        ];

        $this->assertEquals(
            $expectedSlugs,
            self::extractSlugs($service->get('')),
        );
    }

    public function testGetWithTrainerId(): void
    {
        /** @var GetAlbumDexListService $service */
        $service = $this->getServiceWithoutLoggedUser(
            'GET',
            (string) file_get_contents('/app/tests/resources/unit/service/back/dex_123.json'),
            'album/dex',
            ['query' => ['trainer_id' => '123']],
        );

        $expectedSlugs = [
            'homepokemongo',
            'alpha',
        ];

        $this->assertEquals(
            $expectedSlugs,
            self::extractSlugs($service->get('123')),
        );
    }

    #[\Override]
    protected function instanciateService(
        LoggerInterface $logger,
        HttpClientInterface $client,
        string $url,
        string $cafilePath,
        UserTokenServiceInterface $userTokenService,
        SerializerInterface $serializer,
    ): AbstractBackService {
        return new GetAlbumDexListService(
            $logger,
            $client,
            $url,
            $cafilePath,
            $userTokenService,
            $serializer,
        );
    }

    private function getMockService(
        string $filename,
        string $endpoint,
    ): GetAlbumDexListService {
        /** @var GetAlbumDexListService */
        return $this->getServiceWithLoggedUser(
            'GET',
            (string) file_get_contents($filename),
            $endpoint,
        );
    }

    /**
     * @param string[][] $items
     *
     * @return string[]
     */
    private static function extractSlugs(array $items): array
    {
        $slugs = [];

        foreach ($items as $item) {
            $slugs[] = $item['slug'];
        }

        return $slugs;
    }
}
