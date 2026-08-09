<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject;

use App\ResponseObject\BrickVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(BrickVersion::class)]
final class BrickVersionTest extends KernelTestCase
{
    #[Test]
    public function deserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "version": "1.2.12",
                "updated_at": "2026-08-05T09:12:00+00:00"
            }
            JSON;

        $object = $serializer->deserialize($json, BrickVersion::class, 'json');

        $this->assertInstanceOf(BrickVersion::class, $object);
        $this->assertSame('1.2.12', $object->version);
        $this->assertSame('2026-08-05T09:12:00+00:00', $object->updatedAt?->format(\DateTimeInterface::ATOM));
    }

    #[Test]
    public function deserializeWithNullValues(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "version": null,
                "updated_at": null
            }
            JSON;

        $object = $serializer->deserialize($json, BrickVersion::class, 'json');

        $this->assertInstanceOf(BrickVersion::class, $object);
        $this->assertNull($object->version);
        $this->assertNull($object->updatedAt);
    }
}
