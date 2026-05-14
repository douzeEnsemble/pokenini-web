<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Security\UserTokenService;
use App\Service\Back\AbstractBackService;
use App\Service\Back\GetElectionDexListService;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
#[CoversClass(GetElectionDexListService::class)]
final class GetElectionDexListServiceTest extends AbstractTestBackService
{
    public const ENDPOINT = 'election/dex';
    public const RESPONSE_CONTENT = '/app/tests/resources/unit/service/back/election_dex_list.json';

    public function testGet(): void
    {
        /** @var GetElectionDexListService $service */
        $service = $this->getServiceWithLoggedUser(
            'GET',
            (string) file_get_contents(self::RESPONSE_CONTENT),
            self::ENDPOINT,
        );

        $this->assertEquals(
            [
                'homeshiny',
            ],
            self::extractSlugs($service->get()),
        );
    }

    public function testWithoutLoggedUser(): void
    {
        /** @var GetElectionDexListService $service */
        $service = $this->getServiceWithoutLoggedUser(
            'GET',
            (string) file_get_contents(self::RESPONSE_CONTENT),
            self::ENDPOINT,
        );

        $this->assertEquals(
            [
                'homeshiny',
            ],
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
        return new GetElectionDexListService(
            $logger,
            $client,
            $url,
            $cafilePath,
            $userTokenService,
            $serializer,
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
