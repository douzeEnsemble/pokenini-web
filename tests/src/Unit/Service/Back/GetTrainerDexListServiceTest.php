<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\ResponseObject\Album\DexFlags;
use App\ResponseObject\Album\DexListItem;
use App\ResponseObject\Album\DexListItemRef;
use App\ResponseObject\Album\DexListItemSettings;
use App\Security\UserTokenServiceInterface;
use App\Service\Back\AbstractBackService;
use App\Service\Back\GetTrainerDexListService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
#[CoversClass(GetTrainerDexListService::class)]
final class GetTrainerDexListServiceTest extends AbstractTestBackService
{
    #[Test]
    public function get(): void
    {
        $json = '{"doesnt": "matter"}';

        $items = $this->makeItems(['homepokemongo', 'alpha', 'mega']);

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('deserialize')
            ->with($json, DexListItem::class.'[]', 'json')
            ->willReturn($items)
        ;

        /** @var GetTrainerDexListService $service */
        $service = $this->getServiceWithLoggedUser('GET', $json, 'trainer/dex', [], $serializer);

        $this->assertSame(
            ['homepokemongo', 'alpha', 'mega'],
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
        return new GetTrainerDexListService(
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
     * @return DexListItem[]
     */
    private function makeItems(array $slugs): array
    {
        return array_map(fn (string $slug) => new DexListItem(
            dex: new DexListItemRef(slug: $slug),
            settings: new DexListItemSettings(
                name: $slug,
                frenchName: $slug,
                slug: $slug,
                displayTemplate: 'box',
            ),
            flags: new DexFlags(
                isShiny: false,
                isPrivate: false,
                isOnHome: false,
                isDisplayForm: false,
                isReleased: true,
                isPremium: false,
                isCustom: false,
            ),
        ), $slugs);
    }

    /**
     * @param DexListItem[] $items
     *
     * @return string[]
     */
    private static function extractSlugs(array $items): array
    {
        return array_map(fn (DexListItem $item) => $item->getDex()->getSlug(), $items);
    }
}
