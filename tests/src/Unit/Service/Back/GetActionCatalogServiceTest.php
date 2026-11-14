<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Security\UserTokenServiceInterface;
use App\Service\Back\AbstractBackService;
use App\Service\Back\GetActionCatalogService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
#[CoversClass(GetActionCatalogService::class)]
final class GetActionCatalogServiceTest extends AbstractTestBackService
{
    public const ENDPOINT = 'istration/actions';
    public const RESPONSE_CONTENT = '/app/tests/resources/unit/service/back/action_catalog.json';

    #[Test]
    public function get(): void
    {
        /** @var GetActionCatalogService $service */
        $service = $this->getServiceWithLoggedUser(
            'GET',
            (new Filesystem())->readFile(self::RESPONSE_CONTENT),
            self::ENDPOINT,
        );

        $catalog = $service->get();

        $this->assertCount(11, $catalog);
        $this->assertSame(['type' => 'update', 'name' => 'labels', 'depends_on' => []], $catalog[0]);
    }

    #[Test]
    public function hasKnownAction(): void
    {
        /** @var GetActionCatalogService $service */
        $service = $this->getServiceWithLoggedUser(
            'GET',
            (new Filesystem())->readFile(self::RESPONSE_CONTENT),
            self::ENDPOINT,
        );

        $this->assertTrue($service->has('calculate', 'dex_availabilities'));
    }

    #[Test]
    public function hasUnknownAction(): void
    {
        /** @var GetActionCatalogService $service */
        $service = $this->getServiceWithLoggedUser(
            'GET',
            (new Filesystem())->readFile(self::RESPONSE_CONTENT),
            self::ENDPOINT,
        );

        $this->assertFalse($service->has('update', 'dex_availabilities'));
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
        return new GetActionCatalogService(
            $logger,
            $client,
            $url,
            $cafilePath,
            $userTokenService,
            $serializer,
        );
    }
}
