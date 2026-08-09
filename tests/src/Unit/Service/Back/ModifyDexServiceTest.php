<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Security\UserTokenServiceInterface;
use App\Service\Back\AbstractBackService;
use App\Service\Back\ModifyDexService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
#[CoversClass(ModifyDexService::class)]
final class ModifyDexServiceTest extends AbstractTestBackService
{
    #[Test]
    public function modify(): void
    {
        $this
            ->getService(
                'trainer/dex/home',
                'data-whatever',
            )
            ->modify(
                'home',
                'data-whatever',
            )
        ;
    }

    #[Test]
    public function modifyWithoutLoggedUser(): void
    {
        /** @var ModifyDexService $service */
        $service = $this->getServiceWithoutLoggedUser(
            'PUT',
            '',
            'trainer/dex/home',
            [
                'body' => 'data-whatever',
            ],
        );

        $service->modify('home', 'data-whatever');
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
        return new ModifyDexService(
            $logger,
            $client,
            $url,
            $cafilePath,
            $userTokenService,
            $serializer,
        );
    }

    private function getService(
        string $suffix,
        string $body
    ): ModifyDexService {
        /** @var ModifyDexService */
        return $this->getServiceWithLoggedUser(
            'PUT',
            '',
            $suffix,
            [
                'body' => $body,
            ],
        );
    }
}
