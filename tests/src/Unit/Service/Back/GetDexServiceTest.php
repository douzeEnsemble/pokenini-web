<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Security\UserTokenService;
use App\Service\Back\BackServiceInterface;
use App\Service\Back\GetDexService;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
#[CoversClass(GetDexService::class)]
final class GetDexServiceTest extends AbstractTestBackService
{
    public function testGet(): void
    {
        $service = $this->getMockService(
            '/app/tests/resources/unit/service/back/dex.json',
            'dex/list',
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
        /** @var GetDexService $service */
        $service = $this->getServiceWithoutLoggedUser(
            'GET',
            (string) file_get_contents('/app/tests/resources/unit/service/back/dex.json'),
            'dex/list',
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
        /** @var GetDexService $service */
        $service = $this->getServiceWithoutLoggedUser(
            'GET',
            (string) file_get_contents('/app/tests/resources/unit/service/back/dex_123.json'),
            'dex/list?trainer_id=123',
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
        UserTokenService $userTokenService,
        SerializerInterface $serializer,
    ): BackServiceInterface {
        return new GetDexService(
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
    ): GetDexService {
        /** @var GetDexService */
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
