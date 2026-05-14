<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Security\UserTokenService;
use App\Service\Back\AbstractBackService;
use App\Service\Back\GetTrainerDexListService;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
#[CoversClass(GetTrainerDexListService::class)]
final class GetTrainerDexListServiceTest extends AbstractTestBackService
{
    public function testGet(): void
    {
        $service = $this->getMockService(
            '/app/tests/resources/unit/service/back/dex.json',
            'trainer/dex',
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

    #[\Override]
    protected function instanciateService(
        LoggerInterface $logger,
        HttpClientInterface $client,
        string $url,
        string $cafilePath,
        UserTokenService $userTokenService,
        SerializerInterface $serializer,
    ): AbstractBackService {
        return new GetTrainerDexListService(
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
    ): GetTrainerDexListService {
        /** @var GetTrainerDexListService */
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
