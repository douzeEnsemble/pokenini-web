<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Service\Back\GetCatchStatesService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(GetCatchStatesService::class)]
class GetCatchStatesServiceTest extends TestCase
{
    use BackServiceTrait;

    public const ENDPOINT = 'catch_states';
    public const RESPONSE_CONTENT = '/var/www/html/tests/resources/unit/service/back/catch-states.json';

    public function testGet(): void
    {
        $expectedResult = [
            [
                'name' => 'No',
                'frenchName' => 'Non',
                'slug' => 'no',
                'color' => '#e57373',
            ],
            [
                'name' => 'To evolve',
                'frenchName' => 'af. évoluer',
                'slug' => 'toevolve',
                'color' => '#9575cd',
            ],
            [
                'name' => 'To breed',
                'frenchName' => 'af. reproduire',
                'slug' => 'tobreed',
                'color' => '#4fc3f7',
            ],
            [
                'name' => 'To transfer',
                'frenchName' => 'à transférer',
                'slug' => 'totransfer',
                'color' => '#ffd54f',
            ],
            [
                'name' => 'To trade',
                'frenchName' => 'À échanger',
                'slug' => 'totrade',
                'color' => '#ff9100',
            ],
            [
                'name' => 'Yes',
                'frenchName' => 'Oui',
                'slug' => 'yes',
                'color' => '#66bb6a',
            ],
        ];

        /** @var GetCatchStatesService $service */
        $service = $this->getServiceWithLoggedUser(
            GetCatchStatesService::class,
            'GET',
            (string) file_get_contents(self::RESPONSE_CONTENT),
            self::ENDPOINT,
        );

        $this->assertEquals(
            $expectedResult,
            $service->get(),
        );
    }

    public function testWithoutLoggedUser(): void
    {
        /** @var GetCatchStatesService $service */
        $service = $this->getServiceWithoutLoggedUser(
            GetCatchStatesService::class,
            'GET',
            (string) file_get_contents(self::RESPONSE_CONTENT),
            self::ENDPOINT,
        );

        $service->get();
    }
}
