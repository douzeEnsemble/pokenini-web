<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Service\Back\GetGameBundlesService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(GetGameBundlesService::class)]
class GetGameBundlesServiceTest extends TestCase
{
    use BackServiceTrait;

    public const ENDPOINT = 'game_bundles';
    public const RESPONSE_CONTENT = '/var/www/html/tests/resources/unit/service/back/game_bundles.json';

    public function testGet(): void
    {
        /** @var GetGameBundlesService $service */
        $service = $this->getServiceWithLoggedUser(
            GetGameBundlesService::class,
            'GET',
            (string) file_get_contents(self::RESPONSE_CONTENT),
            self::ENDPOINT,
        );

        $items = $service->get();

        $this->assertCount(20, $items);
    }

    public function testGetWithoutLoggedUser(): void
    {
        /** @var GetGameBundlesService $service */
        $service = $this->getServiceWithoutLoggedUser(
            GetGameBundlesService::class,
            'GET',
            (string) file_get_contents(self::RESPONSE_CONTENT),
            self::ENDPOINT,
        );

        $items = $service->get();

        $this->assertCount(20, $items);
    }
}
