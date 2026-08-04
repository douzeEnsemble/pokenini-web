<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\ResponseObject\Album\TrainerDexLink;
use App\Security\UserTokenServiceInterface;
use App\Service\Back\AbstractBackService;
use App\Service\Back\TrainerDexLinkService;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
#[CoversClass(TrainerDexLinkService::class)]
final class TrainerDexLinkServiceTest extends AbstractTestBackService
{
    public function testList(): void
    {
        $json = '[{"id":"link-1"}]';
        $links = [new TrainerDexLink('link-1', 'to', 'shiny', 'Shiny Living', 'Vivarium Chromatique')];

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects($this->once())
            ->method('deserialize')
            ->with($json, TrainerDexLink::class.'[]', 'json')
            ->willReturn($links)
        ;

        /** @var TrainerDexLinkService $service */
        $service = $this->getServiceWithLoggedUser('GET', $json, 'album_link/national', [], $serializer);

        $this->assertSame($links, $service->list('national'));
    }

    public function testCreate(): void
    {
        /** @var TrainerDexLinkService $service */
        $service = $this->getServiceWithLoggedUser(
            'POST',
            '',
            'album_link/national',
            ['body' => '{"targetDexSlug":"shiny","bidirectional":true}'],
        );

        $service->create('national', '{"targetDexSlug":"shiny","bidirectional":true}');
    }

    public function testDelete(): void
    {
        /** @var TrainerDexLinkService $service */
        $service = $this->getServiceWithLoggedUser('DELETE', '', 'album_link/link-1');

        $service->delete('link-1');
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
        return new TrainerDexLinkService(
            $logger,
            $client,
            $url,
            $cafilePath,
            $userTokenService,
            $serializer,
        );
    }
}
