<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Service\Back\ModifyDexService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ModifyDexService::class)]
class ModifyDexServiceTest extends TestCase
{
    use BackServiceTrait;

    public function testModify(): void
    {
        $this
            ->getService(
                'dex/home',
                'data-whatever',
            )
            ->modify(
                'home',
                'data-whatever',
            )
        ;
    }

    public function testModifyWithoutLoggedUser(): void
    {
        /** @var ModifyDexService $service */
        $service = $this->getServiceWithoutLoggedUser(
            ModifyDexService::class,
            'PUT',
            '',
            'dex/home',
            [
                'body' => 'data-whatever',
            ],
        );

        $service->modify('home', 'data-whatever');
    }

    private function getService(
        string $suffix,
        string $body
    ): ModifyDexService {
        /** @var ModifyDexService */
        return $this->getServiceWithLoggedUser(
            ModifyDexService::class,
            'PUT',
            '',
            $suffix,
            [
                'body' => $body,
            ],
        );
    }
}
