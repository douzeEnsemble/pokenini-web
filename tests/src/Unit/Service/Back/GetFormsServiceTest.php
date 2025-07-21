<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Service\Back\GetFormsService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(GetFormsService::class)]
class GetFormsServiceTest extends TestCase
{
    use BackServiceTrait;

    public function testGetFormsCategory(): void
    {
        $expectedResult = [
            [
                'name' => 'Starter',
                'frenchName' => 'de Départ',
                'slug' => 'starter',
            ],
            [
                'name' => 'Legendary',
                'frenchName' => 'Légendaire',
                'slug' => 'legendary',
            ],
        ];

        $this->assertEquals(
            $expectedResult,
            $this->getService('category')->getFormsCategory(),
        );
    }

    public function testGetFormsRegional(): void
    {
        $expectedResult = [
            [
                'name' => 'Alolan',
                'frenchName' => "d'Alola",
                'slug' => 'alolan',
            ],
            [
                'name' => 'Galarian',
                'frenchName' => 'de Galar',
                'slug' => 'galarian',
            ],
        ];

        $this->assertEquals(
            $expectedResult,
            $this->getService('regional')->getFormsRegional(),
        );
    }

    public function testGetFormsSpecial(): void
    {
        $expectedResult = [
            [
                'name' => 'Mega',
                'frenchName' => 'Mega',
                'slug' => 'mega',
            ],
            [
                'name' => 'Primal',
                'frenchName' => 'Originelle',
                'slug' => 'primal',
            ],
        ];

        $this->assertEquals(
            $expectedResult,
            $this->getService('special')->getFormsSpecial(),
        );
    }

    public function testGetFormsVariant(): void
    {
        $expectedResult = [
            [
                'name' => 'Gender',
                'frenchName' => 'Genre',
                'slug' => 'gender',
            ],
            [
                'name' => 'Alternate',
                'frenchName' => 'Alternatif',
                'slug' => 'alternate',
            ],
            [
                'name' => 'Therian',
                'frenchName' => 'Totémique',
                'slug' => 'therian',
            ],
        ];

        $this->assertEquals(
            $expectedResult,
            $this->getService('variant')->getFormsVariant(),
        );
    }

    public function testGetFormsCategoryWithoutLoggedUser(): void
    {
        $expectedResult = [
            [
                'name' => 'Starter',
                'frenchName' => 'de Départ',
                'slug' => 'starter',
            ],
            [
                'name' => 'Legendary',
                'frenchName' => 'Légendaire',
                'slug' => 'legendary',
            ],
        ];

        /** @var GetFormsService $service */
        $service = $this->getServiceWithoutLoggedUser(
            GetFormsService::class,
            'GET',
            (string) file_get_contents('/var/www/html/tests/resources/unit/service/back/category_forms.json'),
            'forms/category',
        );

        $this->assertEquals(
            $expectedResult,
            $service->getFormsCategory(),
        );
    }

    private function getService(string $type): GetFormsService
    {
        /** @var GetFormsService */
        return $this->getServiceWithLoggedUser(
            GetFormsService::class,
            'GET',
            (string) file_get_contents("/var/www/html/tests/resources/unit/service/back/{$type}_forms.json"),
            "forms/{$type}",
        );
    }
}
