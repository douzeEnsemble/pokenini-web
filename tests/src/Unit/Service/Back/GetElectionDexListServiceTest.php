<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\ResponseObject\Album\DexFlags;
use App\ResponseObject\Election\ElectionDexListItem;
use App\Security\UserTokenServiceInterface;
use App\Service\Back\AbstractBackService;
use App\Service\Back\GetElectionDexListService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
#[CoversClass(GetElectionDexListService::class)]
final class GetElectionDexListServiceTest extends AbstractTestBackService
{
    #[Test]
    public function get(): void
    {
        $json = '{"doesnt": "matter"}';

        $items = $this->makeItems(['homeshiny']);

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('deserialize')
            ->with($json, ElectionDexListItem::class.'[]', 'json')
            ->willReturn($items)
        ;

        /** @var GetElectionDexListService $service */
        $service = $this->getServiceWithLoggedUser('GET', $json, 'election/dex', [], $serializer);

        $this->assertSame(
            ['homeshiny'],
            self::extractSlugs($service->get()),
        );
    }

    #[Test]
    public function withoutLoggedUser(): void
    {
        $json = '{"doesnt": "matter"}';

        $items = $this->makeItems(['homeshiny']);

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('deserialize')
            ->with($json, ElectionDexListItem::class.'[]', 'json')
            ->willReturn($items)
        ;

        /** @var GetElectionDexListService $service */
        $service = $this->getServiceWithoutLoggedUser('GET', $json, 'election/dex', [], $serializer);

        $this->assertSame(
            ['homeshiny'],
            self::extractSlugs($service->get()),
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
     * @param string[] $slugs
     *
     * @return ElectionDexListItem[]
     */
    private function makeItems(array $slugs): array
    {
        return array_map(fn (string $slug) => new ElectionDexListItem(
            slug: $slug,
            originalSlug: $slug,
            name: $slug,
            frenchName: $slug,
            flags: new DexFlags(
                isShiny: false,
                isPrivate: false,
                isOnHome: true,
                isDisplayForm: true,
                isReleased: true,
                isPremium: false,
                isCustom: false,
            ),
            displayTemplate: 'box',
            description: null,
            frenchDescription: null,
            dexTotalCount: null,
        ), $slugs);
    }

    /**
     * @param ElectionDexListItem[] $items
     *
     * @return string[]
     */
    private static function extractSlugs(array $items): array
    {
        return array_map(fn (ElectionDexListItem $item) => $item->getSlug(), $items);
    }
}
