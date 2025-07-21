<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Service\Back\GetTypesService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(GetTypesService::class)]
class GetTypesServiceTest extends TestCase
{
    use BackServiceTrait;

    public const ENDPOINT = 'types';
    public const RESPONSE_CONTENT = '/var/www/html/tests/resources/unit/service/back/types.json';

    public function testGet(): void
    {
        /** @var GetTypesService $service */
        $service = $this->getServiceWithLoggedUser(
            GetTypesService::class,
            'GET',
            (string) file_get_contents(self::RESPONSE_CONTENT),
            self::ENDPOINT,
        );

        $items = $service->get();

        $this->assertCount(18, $items);
    }

    public function testGetWithoutLoggedUser(): void
    {
        /** @var GetTypesService $service */
        $service = $this->getServiceWithoutLoggedUser(
            GetTypesService::class,
            'GET',
            (string) file_get_contents(self::RESPONSE_CONTENT),
            self::ENDPOINT,
        );

        $items = $service->get();

        $this->assertCount(18, $items);
    }
}
