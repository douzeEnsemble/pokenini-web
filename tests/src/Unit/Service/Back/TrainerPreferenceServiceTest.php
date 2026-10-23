<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\DTO\HomePage;
use App\Security\UserTokenServiceInterface;
use App\Service\Back\AbstractBackService;
use App\Service\Back\TrainerPreferenceService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
#[CoversClass(TrainerPreferenceService::class)]
final class TrainerPreferenceServiceTest extends AbstractTestBackService
{
    #[Test]
    #[DataProvider('providerResponses')]
    public function getHomePage(string $json, string $expected): void
    {
        /** @var TrainerPreferenceService $service */
        $service = $this->getServiceWithLoggedUser('GET', $json, 'trainer/preference');

        $this->assertSame($expected, $service->getHomePage()->value);
    }

    /**
     * @return string[][]
     */
    public static function providerResponses(): array
    {
        return [
            'chosen' => ['{"home_page":"game"}', 'game'],
            'hub' => ['{"home_page":"hub"}', 'hub'],
            'unknown' => ['{"home_page":"admin"}', 'hub'],
            'not a string' => ['{"home_page":12}', 'hub'],
            'missing' => ['{}', 'hub'],
            'not json' => ['nope', 'hub'],
        ];
    }

    #[Test]
    public function modifyHomePage(): void
    {
        /** @var TrainerPreferenceService $service */
        $service = $this->getServiceWithLoggedUser(
            'PUT',
            '',
            'trainer/preference',
            ['body' => '{"home_page":"election"}'],
        );

        $service->modifyHomePage(new HomePage('election'));
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
        return new TrainerPreferenceService(
            $logger,
            $client,
            $url,
            $cafilePath,
            $userTokenService,
            $serializer,
        );
    }
}
