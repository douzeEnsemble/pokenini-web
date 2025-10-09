<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Service\Back\GetCollectionsService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(GetCollectionsService::class)]
class GetCollectionsServiceTest extends TestCase
{
    use BackServiceTrait;

    public const ENDPOINT = 'labels/collections';
    public const RESPONSE_CONTENT = '/var/www/html/tests/resources/unit/service/back/collections.json';

    public function testGet(): void
    {
        /** @var GetCollectionsService $service */
        $service = $this->getServiceWithLoggedUser(
            GetCollectionsService::class,
            'GET',
            (string) file_get_contents(self::RESPONSE_CONTENT),
            self::ENDPOINT,
        );

        $items = $service->get();

        $this->assertCount(8, $items);
    }

    public function testWithoutLoggedUser(): void
    {
        /** @var GetCollectionsService $service */
        $service = $this->getServiceWithoutLoggedUser(
            GetCollectionsService::class,
            'GET',
            (string) file_get_contents(self::RESPONSE_CONTENT),
            self::ENDPOINT,
        );

        $items = $service->get();

        $this->assertCount(8, $items);
    }
}
