<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\ResponseObject\Common\PokemonCredit;
use App\Security\UserTokenServiceInterface;
use App\Service\Back\AbstractBackService;
use App\Service\Back\GetCreditsService;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
#[CoversClass(GetCreditsService::class)]
final class GetCreditsServiceTest extends AbstractTestBackService
{
    public const ENDPOINT = 'credits';
    public const RESPONSE_CONTENT = '/app/tests/resources/unit/service/back/credits.json';

    public function testGet(): void
    {
        $json = (new Filesystem())->readFile(self::RESPONSE_CONTENT);

        $credits = [
            new PokemonCredit(name: 'PokéSprite', url: 'https://github.com/msikma/pokesprite'),
            new PokemonCredit(name: 'PokemonDB', url: 'https://pokemondb.net'),
        ];

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('deserialize')
            ->with(
                $json,
                PokemonCredit::class.'[]',
                'json',
            )
            ->willReturn($credits)
        ;

        /** @var GetCreditsService $service */
        $service = $this->getServiceWithLoggedUser(
            'GET',
            $json,
            self::ENDPOINT,
            [],
            $serializer,
        );

        $object = $service->get();

        $this->assertCount(2, $object);
        $this->assertSame('PokéSprite', $object[0]->getName());
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
        return new GetCreditsService(
            $logger,
            $client,
            $url,
            $cafilePath,
            $userTokenService,
            $serializer,
        );
    }
}
