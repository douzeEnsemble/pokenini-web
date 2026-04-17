<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Twig\AppRequestExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 */
#[CoversClass(AppRequestExtension::class)]
class AppRequestExtensionTest extends TestCase
{
    public function testGetArrayFromRequestWithoutRequest(): void
    {
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getCurrentRequest')->willReturn(null);

        $extension = new AppRequestExtension($requestStack);

        $this->assertEquals([], $extension->getArrayFromRequest('foo'));
    }

    /**
     * @param array<string, array<int, string>|string> $query
     * @param array<int, string>                       $expected
     */
    #[DataProvider('providerGetArrayFromRequestWithRequest')]
    public function testGetArrayFromRequestWithRequest(array $query, string $name, array $expected): void
    {
        $requestStack = $this->createMock(RequestStack::class);
        $request = new Request($query);
        $requestStack->method('getCurrentRequest')->willReturn($request);

        $extension = new AppRequestExtension($requestStack);

        $this->assertEquals($expected, $extension->getArrayFromRequest($name));
    }

    /**
     * @return array<string, array{
     *  query: array<string, array<int, string>|string>,
     *  name: string,
     *  expected: array<int, string>,
     * }>
     */
    public static function providerGetArrayFromRequestWithRequest(): array
    {
        return [
            'missing parameter' => [
                'query' => ['bar' => ['baz']],
                'name' => 'foo',
                'expected' => [],
            ],
            'existing parameter' => [
                'query' => ['foo' => ['val1', 'val2']],
                'name' => 'foo',
                'expected' => ['val1', 'val2'],
            ],
        ];
    }
}
